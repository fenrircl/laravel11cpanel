<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DocumentIngestionsController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $type = $request->query('type');
        $types = DB::table('invoice_extractions')->whereNotNull('document_type')->where('document_type', '<>', '')
            ->distinct()->orderBy('document_type')->pluck('document_type');
        $jobs = DB::table('document_ingestions as j')->leftJoin('invoice_extractions as e', 'e.ingestion_id', '=', 'j.id')
            ->select('j.*', 'e.document_type', 'e.confidence', 'e.warnings', 'e.normalized_data', 'e.payload as extraction_payload', 'e.validation_status')
            ->when($status, fn ($q) => $q->where('j.status', $status))
            ->when(!$status, fn ($q) => $q->where('j.status', '<>', 'ignored'))
            ->when($type, fn ($q) => $type === 'unclassified'
                ? $q->where(fn ($query) => $query->whereNull('e.document_type')->orWhere('e.document_type', ''))
                : $q->where('e.document_type', $type))
            ->orderByRaw("CASE WHEN j.status='needs_review' THEN 0 WHEN j.status='failed' THEN 1 ELSE 2 END")
            ->orderByDesc('j.updated_at')->paginate(30)->withQueryString();
        $providers = DB::table('providers')->orderBy('name')->get(['id', 'name', 'rut']);
        $clients = DB::table('clients')->orderBy('name')->get(['id', 'name', 'rut']);
        $ownRut = $this->normalizeRut((string) config('services.invoices.own_rut', ''));
        $normalizeRut = fn (string $rut): string => $this->normalizeRut($rut);
        $normalizeName = static function (?string $name): string {
            $ascii = \Illuminate\Support\Str::ascii(mb_strtolower(trim((string) $name)));
            return (string) preg_replace('/[^a-z0-9]/', '', $ascii);
        };
        $findParty = function ($parties, string $rut, ?string $name) use ($normalizeRut, $normalizeName) {
            if ($rut !== '') {
                $match = $parties->first(fn ($party) => $normalizeRut((string) $party->rut) === $rut);
                if ($match) return ['party' => $match, 'method' => 'RUT'];
            }

            $nameKey = $normalizeName($name);
            if ($nameKey === '') return ['party' => null, 'method' => null];
            $matches = $parties->filter(fn ($party) => $normalizeName((string) $party->name) === $nameKey)->values();
            return $matches->count() === 1
                ? ['party' => $matches->first(), 'method' => 'nombre normalizado']
                : ['party' => null, 'method' => null];
        };

        $jobs->getCollection()->transform(function ($job) use ($providers, $clients, $ownRut, $normalizeName, $findParty) {
            $job->extracted_data = json_decode($job->normalized_data ?: '{}', true) ?: [];
            $folio = trim((string) data_get($job->extracted_data, 'invoice_number', ''));
            $invoiceColumns = ['id', 'invoice', 'client_id', 'provider_id', 'date', 'amount'];
            $job->invoice_matches_count = 0;
            $job->suggested_invoice = null;
            $job->suggested_provider_id = null;
            $job->suggested_client_id = null;
            $job->suggested_party_name = null;
            $job->suggested_party_rut = null;
            $job->suggested_party_type = null;
            $supplierRut = $this->normalizeRut((string) data_get($job->extracted_data, 'supplier_rut', ''));
            $customerRut = $this->normalizeRut((string) data_get($job->extracted_data, 'customer_rut', ''));
            $issuer = data_get($job->extracted_data, 'issuer') ?: data_get($job->extracted_data, 'supplier_name');
            $recipient = data_get($job->extracted_data, 'customer_name') ?: data_get($job->extracted_data, 'recipient');
            $providerMatch = $findParty($providers, $supplierRut, is_string($issuer) ? $issuer : null);
            $clientMatch = $findParty($clients, $customerRut, is_string($recipient) ? $recipient : null);
            $provider = $providerMatch['party'];
            $client = $clientMatch['party'];
            $isOwnName = fn ($name) => in_array($normalizeName(is_string($name) ? $name : null), [
                'sociedadaceroseralimitada', 'sociedadaceroseraltda', 'aceroseralimitada', 'aceroseraltda',
            ], true);

            if (($ownRut !== '' && $customerRut === $ownRut) || $isOwnName($recipient)) {
                $job->suggested_provider_id = $provider?->id;
                $job->suggested_party_name = $issuer;
                $job->suggested_party_rut = data_get($job->extracted_data, 'supplier_rut');
                $job->suggested_party_type = 'Proveedor';
            } elseif (($ownRut !== '' && $supplierRut === $ownRut) || $isOwnName($issuer)) {
                $job->suggested_client_id = $client?->id;
                $job->suggested_party_name = $recipient;
                $job->suggested_party_rut = data_get($job->extracted_data, 'customer_rut');
                $job->suggested_party_type = 'Cliente';
            } elseif ($provider && !$client) {
                $job->suggested_provider_id = $provider->id;
                $job->suggested_party_name = data_get($job->extracted_data, 'issuer');
                $job->suggested_party_rut = data_get($job->extracted_data, 'supplier_rut');
                $job->suggested_party_type = 'Proveedor';
            } elseif ($client && !$provider) {
                $job->suggested_client_id = $client->id;
                $job->suggested_party_name = data_get($job->extracted_data, 'recipient');
                $job->suggested_party_rut = data_get($job->extracted_data, 'customer_rut');
                $job->suggested_party_type = 'Cliente';
            }

            if ($folio !== '') {
                $folioCandidates = array_values(array_unique([$folio, preg_replace('/^N\s*[°º]?\s*(?=\d)/iu', '', $folio) ?? $folio]));
                $matchesQuery = Factura::query()->with(['cliente:id,name,rut', 'proveedor:id,name,rut'])->whereIn('invoice', $folioCandidates);
                if ($job->suggested_client_id) $matchesQuery->where('client_id', $job->suggested_client_id);
                elseif ($job->suggested_provider_id) $matchesQuery->where('provider_id', $job->suggested_provider_id);
                $matches = $matchesQuery->limit(2)->get($invoiceColumns);
                $job->invoice_matches_count = $matches->count();
                $job->suggested_invoice = $matches->count() === 1 ? $matches->first() : null;
            }
            $job->suggested_invoice_id = $job->suggested_invoice?->id;

            $job->counterparty_name_matches = [];
            $job->page_counterparties = [];
            $invoiceEntries = data_get($job->extracted_data, 'invoices', []);
            $job->page_invoice_suggestions = [];
            if (is_array($invoiceEntries)) {
                $folioCandidates = [];
                foreach ($invoiceEntries as $entry) {
                    if (!is_array($entry)) continue;
                    $folio = trim((string) data_get($entry, 'invoice_number', ''));
                    if ($folio === '') continue;
                    $folioCandidates[] = $folio;
                    $withoutPrefix = preg_replace('/^N\s*[°º]?\s*(?=\d)/iu', '', $folio) ?? $folio;
                    if ($withoutPrefix !== $folio) $folioCandidates[] = $withoutPrefix;
                }
                $folioRows = $folioCandidates
                    ? Factura::query()->whereIn('invoice', array_values(array_unique($folioCandidates)))->get(['id', 'invoice'])
                    : collect();
                $foliosByNumber = $folioRows->groupBy(fn ($invoice) => trim((string) $invoice->invoice));

                foreach ($invoiceEntries as $index => $entry) {
                    if (!is_array($entry)) continue;
                    $page = (int) (data_get($entry, 'page') ?: $index + 1);
                    $folio = trim((string) data_get($entry, 'invoice_number', ''));
                    $withoutPrefix = preg_replace('/^N\s*[°º]?\s*(?=\d)/iu', '', $folio) ?? $folio;
                    $folioMatches = $foliosByNumber->get($folio, collect())->concat($foliosByNumber->get($withoutPrefix, collect()))->unique('id')->values();
                    $job->page_invoice_suggestions[$page] = $folioMatches->count() === 1
                        ? ['id' => $folioMatches->first()->id, 'folio' => $folioMatches->first()->invoice]
                        : null;
                    $entryIssuer = data_get($entry, 'issuer') ?: data_get($entry, 'supplier_name');
                    $entryRecipient = data_get($entry, 'customer_name') ?: data_get($entry, 'recipient');
                    $entrySupplierRut = $this->normalizeRut((string) data_get($entry, 'supplier_rut', ''));
                    $entryCustomerRut = $this->normalizeRut((string) data_get($entry, 'customer_rut', ''));
                    $issuerIsOwn = ($ownRut !== '' && $entrySupplierRut === $ownRut) || $isOwnName($entryIssuer);
                    $recipientIsOwn = ($ownRut !== '' && $entryCustomerRut === $ownRut) || $isOwnName($entryRecipient);

                    $candidates = [];
                    if ($issuerIsOwn) {
                        $candidates[] = ['Cliente', $clients, $entryCustomerRut, $entryRecipient];
                    } elseif ($recipientIsOwn) {
                        $candidates[] = ['Proveedor', $providers, $entrySupplierRut, $entryIssuer];
                    } else {
                        $candidates[] = ['Proveedor', $providers, $entrySupplierRut, $entryIssuer];
                        $candidates[] = ['Cliente', $clients, $entryCustomerRut, $entryRecipient];
                    }

                    $primaryCandidate = $candidates[0];
                    $job->page_counterparties[$page] = [
                        'type' => $primaryCandidate[0],
                        'name' => is_string($primaryCandidate[3]) ? $primaryCandidate[3] : null,
                        'party_id' => null,
                    ];

                    foreach ($candidates as [$partyType, $partyList, $partyRut, $partyName]) {
                        $partyMatch = $findParty($partyList, $partyRut, is_string($partyName) ? $partyName : null);
                        if (!$partyMatch['party']) continue;
                        if ($partyType === $primaryCandidate[0]) {
                            $job->page_counterparties[$page]['name'] = $partyMatch['party']->name;
                            $job->page_counterparties[$page]['party_id'] = $partyMatch['party']->id;
                        }
                        $job->counterparty_name_matches[] = [
                            'page' => $page,
                            'invoice_number' => data_get($entry, 'invoice_number'),
                            'type' => $partyType,
                            'extracted_name' => $partyName,
                            'matched_name' => $partyMatch['party']->name,
                            'matched_rut' => $partyMatch['party']->rut,
                            'method' => $partyMatch['method'],
                        ];
                    }
                }
            }

            return $job;
        });
        return view('admin.document-ingestions.index', compact('jobs', 'status', 'type', 'types', 'providers', 'clients'));
    }

    public function approve(Request $request, int $ingestion): RedirectResponse
    {
        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:80'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'], 'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'invoice_date' => ['nullable', 'date'],
            'invoice_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'create_missing' => ['nullable', 'boolean'],
        ]);
        if (!empty($data['provider_id']) && !empty($data['client_id'])) {
            return back()->withErrors(['provider_id' => 'Selecciona proveedor o cliente, no ambos.'])->withInput();
        }
        DB::transaction(function () use ($data, $ingestion, $request) {
            $job = DB::table('document_ingestions')->where('id', $ingestion)->lockForUpdate()->firstOrFail();
            $extraction = DB::table('invoice_extractions')->where('ingestion_id', $job->id)->lockForUpdate()->firstOrFail();
            $normalized = json_decode($extraction->normalized_data ?: '{}', true) ?: [];
            $number = trim($data['invoice_number']);
            $number = preg_replace('/^N\s*[°º]?\s*(?=\d)/iu', '', $number) ?? $number;
            $folioCandidates = array_values(array_unique([$number, preg_replace('/^N\s*[°º]?\s*(?=\d)/iu', '', $number) ?? $number]));
            $partyType = !empty($data['client_id']) ? 'client' : (!empty($data['provider_id']) ? 'provider' : null);
            $partyId = (int) ($data['client_id'] ?? $data['provider_id'] ?? 0);
            $query = Factura::query()->whereIn('invoice', $folioCandidates);
            if ($partyId) $query->where($partyType === 'client' ? 'client_id' : 'provider_id', $partyId);
            $matches = $query->lockForUpdate()->limit(3)->get();
            if ($matches->isEmpty() && $partyId && Factura::query()->whereIn('invoice', $folioCandidates)->exists()) {
                throw ValidationException::withMessages(['invoice_number' => 'Ese folio ya existe asociado a otra contraparte. Revisa cliente/proveedor y folio antes de continuar.']);
            }
            if ($matches->count() > 1) throw ValidationException::withMessages(['invoice_number' => 'El folio coincide con más de una factura. Usa el folio exacto de la factura.']);

            $created = false;
            if ($matches->isEmpty()) {
                if (!filter_var($data['create_missing'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    throw ValidationException::withMessages(['invoice_number' => "No existe la factura {$number}. Marca “Crear factura si no existe” para crearla."]);
                }
                if (!$partyId || !$partyType) {
                    throw ValidationException::withMessages(['provider_id' => 'Selecciona el cliente o proveedor antes de crear la factura.']);
                }
                $createValues = [
                    'invoice' => mb_substr($number, 0, 80),
                    'date' => $data['invoice_date'] ?? data_get($normalized, 'date'),
                    'amount' => $data['invoice_amount'] ?? data_get($normalized, 'amount'),
                    'status' => 0,
                ];
                $createValues[$partyType === 'client' ? 'client_id' : 'provider_id'] = $partyId;
                $invoice = Factura::create($createValues);
                $created = true;
                $invoiceBefore = [];
            } else {
                $invoice = $matches->first();
                $invoiceBefore = $invoice->toArray();
                $changes = [];
                // Keep every populated field; extraction only fills missing values.
                if (empty($invoice->invoice)) $changes['invoice'] = mb_substr($number, 0, 80);
                $date = $data['invoice_date'] ?? data_get($normalized, 'date');
                $amount = $data['invoice_amount'] ?? data_get($normalized, 'amount');
                if (empty($invoice->date) && $date) $changes['date'] = $date;
                if ($invoice->amount === null && $amount !== null && $amount !== '') $changes['amount'] = $amount;
                if ($partyId && !$invoice->client_id && !$invoice->provider_id) {
                    $changes[$partyType === 'client' ? 'client_id' : 'provider_id'] = $partyId;
                }
                if ($changes) $invoice->forceFill($changes)->save();
            }
            if ($number !== '') $normalized['invoice_number'] = $number;
            if (!empty($data['invoice_date'])) $normalized['date'] = $data['invoice_date'];
            if (($data['invoice_amount'] ?? null) !== null) $normalized['amount'] = (float) $data['invoice_amount'];
            $items = $normalized['line_items'] ?? [];
            if (!is_array($items)) $items = [];
            if ($job->file_registry_id) {
                DB::table('files_registry')->where('id', $job->file_registry_id)->update(['model_type' => 'App\\Invoice', 'model_id' => $invoice->id, 'real_id' => $invoice->id]);
            }
            if ($job->file_registry_id) {
                DB::table('invoice_extractions')->where('file_registry_id', $job->file_registry_id)->where('id', '<>', $extraction->id)->update(['is_active' => false]);
            }
            DB::table('invoice_extractions')->where('id', $extraction->id)->update(['invoice_id' => $invoice->id, 'validation_status' => 'validated', 'is_active' => true, 'processed_at' => now(), 'updated_at' => now()]);
            DB::table('invoice_extractions')->where('id', $extraction->id)->update(['normalized_data' => json_encode($normalized, JSON_UNESCAPED_UNICODE)]);
            foreach ($items as $i => $line) {
                if (!is_array($line) || empty($line['description'])) continue;
                $this->addMissingInvoiceLineItem(['invoice_id' => $invoice->id, 'extraction_id' => $extraction->id,
                    'line_number' => $i + 1, 'description_original' => mb_substr((string)$line['description'], 0, 1000),
                    'description_normalized' => mb_substr(mb_strtolower(trim((string)$line['description'])), 0, 700),
                    'supplier_sku' => isset($line['code']) ? mb_substr((string)$line['code'], 0, 191) : null,
                    'quantity' => $line['quantity'] ?? null, 'unit_price' => $line['unit_price'] ?? null,
                    'total_amount' => $line['total_amount'] ?? null, 'currency' => $normalized['currency'] ?? null,
                    'source_line' => json_encode($line, JSON_UNESCAPED_UNICODE)]);
            }
            $invoiceAfter = $invoice->fresh()->toArray();
            $auditChanges = AuditLogger::simpleDiff($invoiceBefore, $invoiceAfter);
            $auditChanges['line_items'] = [
                'from' => 'Detalle anterior de la extracción',
                'to' => count(array_filter($items, fn ($line) => is_array($line) && !empty($line['description']))) . ' ítem(s) guardado(s) desde extracción #' . $extraction->id,
            ];
            AuditLogger::log($request, $created ? 'create_from_extraction' : 'approve_extraction', 'facturas', $invoice->id,
                ($created ? 'Creó' : 'Aprobó') . ' la extracción #' . $extraction->id . ' para la factura #' . $invoice->invoice . '.',
                [
                    'model' => Factura::class,
                    'data_before' => $invoiceBefore,
                    'data_after' => $invoiceAfter,
                    'changes' => $auditChanges,
                    'reversible' => false,
                ]);
            DB::table('document_ingestions')->where('id', $job->id)->update(['invoice_id' => $invoice->id, 'status' => 'completed', 'last_error' => null, 'updated_at' => now()]);
            DB::table('audit_logs')->insert(['user_id' => $request->user()->id, 'action' => 'approve', 'module' => 'document_ingestions',
                'entity_id' => (string)$job->id, 'description' => "Aprobó importación {$job->uuid}, la vinculó a factura {$invoice->invoice} y aplicó correcciones administrativas.",
                'ip_address' => $request->ip(), 'user_agent' => mb_substr((string)$request->userAgent(), 0, 255), 'url' => mb_substr($request->fullUrl(), 0, 255),
                'method' => 'POST', 'created_at' => now(), 'updated_at' => now()]);
        });
        return back()->with('success', 'Importación aprobada y asociada a la factura.');
    }

    public function approvePages(Request $request, int $ingestion): RedirectResponse
    {
        $data = $request->validate([
            'pages' => ['required', 'array', 'min:1'],
            'pages.*' => ['required', 'integer', 'distinct', 'min:1'],
            'invoice_numbers' => ['required', 'array'],
            'invoice_numbers.*' => ['required', 'string', 'max:80'],
            'invoice_dates' => ['nullable', 'array'],
            'invoice_dates.*' => ['nullable', 'date'],
            'invoice_amounts' => ['nullable', 'array'],
            'invoice_amounts.*' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'party_types' => ['nullable', 'array'],
            'party_types.*' => ['nullable', 'in:client,provider'],
            'party_ids' => ['nullable', 'array'],
            'party_ids.*' => ['nullable', 'integer', 'min:1'],
            'create_missing' => ['nullable', 'array'],
            'create_missing.*' => ['nullable', 'boolean'],
        ]);

        $result = DB::transaction(function () use ($data, $ingestion, $request) {
            $job = DB::table('document_ingestions')->where('id', $ingestion)->lockForUpdate()->firstOrFail();
            $extraction = DB::table('invoice_extractions')->where('ingestion_id', $job->id)->lockForUpdate()->firstOrFail();
            $normalized = json_decode($extraction->normalized_data ?: '{}', true) ?: [];
            $entries = data_get($normalized, 'invoices', []);
            if (!is_array($entries) || $entries === []) {
                return ['error' => 'Esta extracción no contiene facturas separadas por página.'];
            }

            $byPage = [];
            foreach ($entries as $index => $entry) {
                if (!is_array($entry)) continue;
                $page = (int) (data_get($entry, 'page') ?: $index + 1);
                $byPage[$page] = $entry;
            }
            $approvals = data_get($normalized, 'page_approvals', []);
            if (!is_array($approvals)) $approvals = [];
            $now = now();
            $approvedInvoices = [];

            foreach ($data['pages'] as $pageValue) {
                $page = (int) $pageValue;
                if (!isset($byPage[$page])) throw ValidationException::withMessages(['pages' => "No se encontró la factura de la página {$page} en la extracción."]);
                $entry = $byPage[$page];
                $number = trim((string) ($data['invoice_numbers'][$page] ?? data_get($entry, 'invoice_number', '')));
                $number = preg_replace('/^N\s*[°º]?\s*(?=\d)/iu', '', $number) ?? $number;
                if ($number === '') throw ValidationException::withMessages(['pages' => "Ingresa el número de factura de la página {$page}."]);
                $date = $data['invoice_dates'][$page] ?? data_get($entry, 'date');
                $amount = $data['invoice_amounts'][$page] ?? data_get($entry, 'amount');
                $partyType = $data['party_types'][$page] ?? null;
                $partyId = (int) ($data['party_ids'][$page] ?? 0);
                if ($partyType && $partyId) {
                    $partyTable = $partyType === 'client' ? 'clients' : 'providers';
                    if (!DB::table($partyTable)->where('id', $partyId)->exists()) {
                        throw ValidationException::withMessages(['pages' => "La contraparte de la página {$page} ya no existe; revisa los datos extraídos."]);
                    }
                } else {
                    $partyType = null;
                    $partyId = 0;
                }

                $folioCandidates = [$number, preg_replace('/^N\s*[°º]?\s*(?=\d)/iu', '', $number) ?? $number];
                $folioCandidates = array_values(array_unique(array_filter($folioCandidates)));
                $invoiceQuery = Factura::query()->whereIn('invoice', $folioCandidates);
                if ($partyId) {
                    $partyColumn = $partyType === 'client' ? 'client_id' : 'provider_id';
                    $invoiceQuery->where($partyColumn, $partyId);
                }
                $matches = $invoiceQuery->lockForUpdate()->limit(3)->get();
                if ($matches->isEmpty() && $partyId && Factura::query()->whereIn('invoice', $folioCandidates)->exists()) {
                    throw ValidationException::withMessages(['pages' => "El folio {$number} ya existe asociado a otra contraparte. Revisa la página antes de crear una factura duplicada."]);
                }
                if ($matches->count() > 1 && $date) {
                    $dateMatches = $matches->filter(fn ($candidate) => $candidate->date?->format('Y-m-d') === (string) $date)->values();
                    if ($dateMatches->count() === 1) $matches = $dateMatches;
                }
                if ($matches->count() > 1 && $amount !== null && $amount !== '') {
                    $amountMatches = $matches->filter(fn ($candidate) => (float) $candidate->amount === (float) $amount)->values();
                    if ($amountMatches->count() === 1) $matches = $amountMatches;
                }
                if ($matches->count() > 1) {
                    throw ValidationException::withMessages(['pages' => "El folio {$number} coincide con varias facturas. Usa “Últimas facturas” para confirmar el folio correcto."]);
                }

                $created = false;
                if ($matches->isEmpty()) {
                    $createMissing = filter_var($data['create_missing'][$page] ?? false, FILTER_VALIDATE_BOOLEAN);
                    if (!$createMissing) throw ValidationException::withMessages(['pages' => "No existe la factura {$number}. Marca “Crear factura si ese número todavía no existe” para crearla."]);
                    if (!$partyId || !$partyType) throw ValidationException::withMessages(['pages' => "No se puede crear la factura {$number} porque su cliente o proveedor no está identificado."]);
                    $partyColumn = $partyType === 'client' ? 'client_id' : 'provider_id';
                    $invoice = Factura::create([
                        'invoice' => mb_substr($number, 0, 80),
                        $partyColumn => $partyId,
                        'date' => $date ?: null,
                        'amount' => $amount !== null && $amount !== '' ? $amount : null,
                        'status' => 0,
                    ]);
                    $created = true;
                } else {
                    $invoice = $matches->first();
                }

                $changes = [];
                if (!$created) {
                    // Extraction is additive: existing values always win over missing or uncertain OCR data.
                    if (empty($invoice->invoice) && $number !== '') $changes['invoice'] = mb_substr($number, 0, 80);
                    if (empty($invoice->date) && $date) $changes['date'] = $date;
                    if ($invoice->amount === null && $amount !== null && $amount !== '') $changes['amount'] = $amount;
                    if ($partyId && !$invoice->client_id && !$invoice->provider_id) {
                        $changes[$partyType === 'client' ? 'client_id' : 'provider_id'] = $partyId;
                    }
                }
                $before = $invoice->toArray();
                if ($changes) $invoice->forceFill($changes)->save();
                $approvedInvoices[$page] = $invoice->id;
                $approvals[(string) $page] = ['invoice_id' => $invoice->id, 'approved_at' => $now->toISOString(), 'approved_by' => $request->user()->id];

                $after = $invoice->fresh()->toArray();
                AuditLogger::log($request, $created ? 'create_from_extraction_page' : 'approve_extraction_page', 'facturas', $invoice->id,
                    ($created ? 'Creó' : 'Aprobó') . " la factura {$number} desde la página {$page} de la extracción #{$extraction->id} (" . ($job->filename ?: $job->uuid) . ').',
                    ['model' => Factura::class, 'data_before' => $created ? [] : $before, 'data_after' => $after,
                        'changes' => AuditLogger::simpleDiff($created ? [] : $before, $after), 'reversible' => false]);
            }

            $normalized['page_approvals'] = $approvals;
            $pageCount = count($byPage);
            $approvedPageKeys = array_intersect(array_map('strval', array_keys($byPage)), array_keys($approvals));
            $approvedCount = count($approvedPageKeys);
            $isComplete = $approvedCount >= $pageCount;

            // Merge extracted lines without deleting previously saved information.
            $lineNumber = 0;
            foreach ($byPage as $page => $entry) {
                $approval = $approvals[(string) $page] ?? null;
                $invoiceId = is_array($approval) ? (int) ($approval['invoice_id'] ?? 0) : (int) $approval;
                if (!$invoiceId) continue;
                foreach ((array) data_get($entry, 'line_items', []) as $line) {
                    if (!is_array($line) || empty($line['description'])) continue;
                    $lineNumber++;
                    $description = mb_substr((string) $line['description'], 0, 1000);
                    $currency = strtoupper((string) data_get($entry, 'currency', 'CLP'));
                    if (!preg_match('/^[A-Z]{3}$/', $currency)) $currency = 'CLP';
                    $this->addMissingInvoiceLineItem([
                        'invoice_id' => $invoiceId, 'extraction_id' => $extraction->id, 'line_number' => $lineNumber,
                        'description_original' => $description,
                        'description_normalized' => mb_substr(mb_strtolower(trim($description)), 0, 700),
                        'supplier_sku' => isset($line['code']) ? mb_substr((string) $line['code'], 0, 191) : null,
                        'quantity' => $line['quantity'] ?? null, 'unit_original' => isset($line['unit']) ? mb_substr((string) $line['unit'], 0, 32) : null,
                        'unit_price' => $line['unit_price'] ?? null,
                        'total_amount' => $line['total_amount'] ?? $line['total_price'] ?? $line['amount'] ?? null,
                        'currency' => $currency, 'source_line' => json_encode($line + ['source_page' => $page], JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }

            $primaryInvoiceId = reset($approvedInvoices);
            DB::table('invoice_extractions')->where('id', $extraction->id)->update([
                'invoice_id' => $approvedCount === 1 ? $primaryInvoiceId : null,
                'validation_status' => $isComplete ? 'validated' : 'needs_review',
                'is_active' => $isComplete,
                'processed_at' => $isComplete ? $now : null,
                'normalized_data' => json_encode($normalized, JSON_UNESCAPED_UNICODE),
                'updated_at' => $now,
            ]);
            DB::table('document_ingestions')->where('id', $job->id)->update([
                'invoice_id' => $approvedCount === 1 ? $primaryInvoiceId : null,
                'status' => $isComplete ? 'completed' : 'needs_review',
                'last_error' => $isComplete ? null : 'Aprobadas ' . $approvedCount . ' de ' . $pageCount . ' facturas del PDF.',
                'updated_at' => $now,
            ]);

            if ($job->file_registry_id && $approvedCount === 1) {
                DB::table('files_registry')->where('id', $job->file_registry_id)->update([
                    'model_type' => 'App\\Invoice', 'model_id' => $primaryInvoiceId, 'real_id' => $primaryInvoiceId,
                ]);
            }

            return ['error' => null, 'approved' => $approvedCount, 'total' => $pageCount, 'complete' => $isComplete];
        });

        if ($result['error'] ?? null) return back()->withErrors(['pages' => $result['error']]);
        $message = $result['complete']
            ? 'Todas las facturas del PDF fueron aprobadas y sus ítems quedaron asociados.'
            : "Se aplicaron las páginas seleccionadas. Quedan {$result['approved']} de {$result['total']} facturas aprobadas; puedes continuar después.";

        return back()->with('success', $message);
    }

    public function retry(int $ingestion): RedirectResponse
    {
        DB::table('document_ingestions')->where('id', $ingestion)->whereIn('status', ['failed', 'needs_review'])->update(['status' => 'processing', 'attempts' => DB::raw('attempts + 1'), 'last_error' => null, 'updated_at' => now()]);
        return back()->with('success', 'Job reabierto para reintento desde n8n.');
    }

    public function downloadPdf(int $ingestion)
    {
        $job = DB::table('document_ingestions')->where('id', $ingestion)->first();
        abort_unless($job, 404, 'Importación no encontrada.');

        if ($job->file_registry_id) {
            return redirect()->route('files.registry.download', ['file' => $job->file_registry_id]);
        }

        $key = (string) ($job->r2_key ?? '');
        abort_if($key === '' || str_contains($key, "\0") || preg_match('/(^|\/)(\.\.?)(\/|$)|[\x00-\x1F\x7F]/', $key), 404, 'El PDF no está disponible en R2.');

        try {
            $disk = Storage::disk('r2');
            abort_unless($disk->exists($key), 404, 'El PDF no está disponible en R2.');
            $filename = (string) ($job->filename ?: basename($key));
            $filename = function_exists('mb_convert_encoding')
                ? mb_convert_encoding($filename, 'UTF-8', 'UTF-8')
                : (preg_match('//u', $filename) === 1 ? $filename : (iconv('UTF-8', 'UTF-8//IGNORE', $filename) ?: ''));
            $filename = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '', $filename) ?: 'documento.pdf';
            $asciiFilename = \Illuminate\Support\Str::ascii($filename);
            $asciiFilename = preg_replace('/[^A-Za-z0-9._ -]/', '', $asciiFilename) ?: 'documento.pdf';

            return $disk->response($key, $asciiFilename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . addcslashes($asciiFilename, '"\\') . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            \Log::error('No se pudo servir PDF de importación', [
                'ingestion_id' => $job->id,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            abort(503, 'No se pudo leer el PDF desde R2.');
        }
    }

    public function recentInvoices(Request $request, int $ingestion, int $page): JsonResponse
    {
        $data = $request->validate([
            'party_type' => ['required', 'in:client,provider'],
            'party_id' => ['required', 'integer', 'min:1'],
        ]);
        $job = DB::table('document_ingestions')->where('id', $ingestion)->first();
        abort_unless($job, 404, 'Importación no encontrada.');
        $extraction = DB::table('invoice_extractions')->where('ingestion_id', $job->id)->first(['normalized_data']);
        abort_unless($extraction, 404, 'Extracción no encontrada.');
        $normalized = json_decode($extraction->normalized_data ?: '{}', true) ?: [];
        $entries = data_get($normalized, 'invoices', []);
        $pageExists = false;
        if (is_array($entries) && $entries !== []) {
            foreach ($entries as $index => $entry) {
                if ((int) (data_get($entry, 'page') ?: $index + 1) === $page) {
                    $pageExists = true;
                    break;
                }
            }
        } else {
            $pageExists = $page === 1;
        }
        abort_unless($pageExists, 404, 'Página no encontrada.');

        $table = $data['party_type'] === 'client' ? 'clients' : 'providers';
        $invoicePartyColumn = $data['party_type'] === 'client' ? 'client_id' : 'provider_id';
        $party = DB::table($table)->where('id', $data['party_id'])->first(['id', 'name', 'rut']);
        abort_unless($party, 404, 'Cliente o proveedor no encontrado.');

        $invoices = DB::table('invoices')->where($invoicePartyColumn, $party->id)
            ->orderByDesc('date')->orderByDesc('id')->limit(20)
            ->get(['invoice', 'date', 'amount'])
            ->map(fn ($invoice) => [
                'invoice_number' => (string) $invoice->invoice,
                'date' => $invoice->date ? substr((string) $invoice->date, 0, 10) : null,
                'amount' => $invoice->amount,
            ]);

        return response()->json(['party' => $party->name, 'invoices' => $invoices]);
    }

    public function discard(Request $request, int $ingestion): RedirectResponse
    {
        $job = DB::table('document_ingestions')->where('id', $ingestion)->first();
        abort_unless($job, 404, 'Importación no encontrada.');

        if ($job->file_registry_id || $job->invoice_id) {
            return back()->withErrors(['discard' => 'No se puede descartar desde aquí un PDF ya vinculado a una factura o al registro de archivos.']);
        }
        if ($job->status === 'processing' && $job->lease_until && now()->lt($job->lease_until)) {
            return back()->withErrors(['discard' => 'El job sigue en procesamiento. Espera a que finalice o expire antes de descartarlo.']);
        }

        $key = (string) ($job->r2_key ?? '');
        if ($key !== '') {
            if (str_contains($key, "\0") || preg_match('/(^|\/)(\.\.?)(\/|$)|[\x00-\x1F\x7F]/', $key)) {
                return back()->withErrors(['discard' => 'La ruta del archivo no es válida; no se eliminó ningún objeto.']);
            }
            if (DB::table('files_registry')->where('path', $key)->exists()) {
                return back()->withErrors(['discard' => 'El objeto está referenciado por el registro de archivos y no se eliminará.']);
            }
            try {
                $disk = Storage::disk('r2');
                if ($disk->exists($key) && !$disk->delete($key)) {
                    return back()->withErrors(['discard' => 'R2 no confirmó la eliminación; el job se mantiene sin cambios.']);
                }
            } catch (\Throwable $exception) {
                report($exception);
                return back()->withErrors(['discard' => 'No se pudo eliminar el PDF de R2; el job se mantiene sin cambios.']);
            }
        }

        DB::table('document_ingestions')->where('id', $job->id)->update([
            'status' => 'ignored',
            'last_error' => 'Descartado manualmente desde administración; el objeto de R2 fue eliminado si estaba disponible.',
            'lease_until' => null,
            'updated_at' => now(),
        ]);
        AuditLogger::log($request, 'discard_pdf', 'document_ingestions', $job->id,
            'Descartó la importación ' . $job->uuid . ' y eliminó su objeto R2 cuando estaba disponible.');

        return back()->with('success', 'PDF descartado y retirado de la lista general. El registro queda disponible en el filtro “Omitidos”.');
    }

    private function normalizeRut(string $rut): string
    {
        return strtolower((string) preg_replace('/[^0-9k]/i', '', $rut));
    }

    /** Insert an extracted line or fill only its empty fields; never delete or clear existing values. */
    private function addMissingInvoiceLineItem(array $values): void
    {
        $existing = DB::table('invoice_line_items')
            ->where('extraction_id', $values['extraction_id'])
            ->where('line_number', $values['line_number'])
            ->first();

        if (!$existing) {
            DB::table('invoice_line_items')->insert($values + ['created_at' => now(), 'updated_at' => now()]);
            return;
        }

        $missingValues = [];
        foreach ($values as $column => $value) {
            if (in_array($column, ['extraction_id', 'line_number', 'invoice_id'], true)) continue;
            $current = $existing->{$column} ?? null;
            if (($current === null || $current === '') && $value !== null && $value !== '') {
                $missingValues[$column] = $value;
            }
        }

        if ($missingValues) {
            $missingValues['updated_at'] = now();
            DB::table('invoice_line_items')->where('id', $existing->id)->update($missingValues);
        }
    }
}
