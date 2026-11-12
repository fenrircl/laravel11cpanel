<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            if ($job->invoice_id) {
                $job->suggested_invoice = Factura::query()->with(['cliente:id,name,rut', 'proveedor:id,name,rut'])
                    ->find($job->invoice_id, $invoiceColumns);
                $job->invoice_matches_count = $job->suggested_invoice ? 1 : 0;
            } elseif ($folio !== '') {
                $matches = Factura::query()->with(['cliente:id,name,rut', 'proveedor:id,name,rut'])
                    ->where('invoice', $folio)->limit(2)->get($invoiceColumns);
                $job->invoice_matches_count = $matches->count();
                $job->suggested_invoice = $matches->count() === 1 ? $matches->first() : null;
            }
            $job->suggested_invoice_id = $job->suggested_invoice?->id;

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

            $job->counterparty_name_matches = [];
            $invoiceEntries = data_get($job->extracted_data, 'invoices', []);
            $job->page_invoice_suggestions = [];
            if (is_array($invoiceEntries)) {
                $folioCandidates = [];
                foreach ($invoiceEntries as $entry) {
                    if (!is_array($entry)) continue;
                    $folio = trim((string) data_get($entry, 'invoice_number', ''));
                    if ($folio === '') continue;
                    $folioCandidates[] = $folio;
                    $withoutPrefix = preg_replace('/^N\s*[°º]?\s*/iu', '', $folio) ?? $folio;
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
                    $withoutPrefix = preg_replace('/^N\s*[°º]?\s*/iu', '', $folio) ?? $folio;
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

                    foreach ($candidates as [$partyType, $partyList, $partyRut, $partyName]) {
                        $partyMatch = $findParty($partyList, $partyRut, is_string($partyName) ? $partyName : null);
                        if (!$partyMatch['party']) continue;
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
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'], 'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'invoice_number' => ['nullable', 'string', 'max:80'], 'invoice_date' => ['nullable', 'date'],
            'invoice_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
        ]);
        if (!empty($data['provider_id']) && !empty($data['client_id'])) {
            return back()->withErrors(['provider_id' => 'Selecciona proveedor o cliente, no ambos.'])->withInput();
        }
        DB::transaction(function () use ($data, $ingestion, $request) {
            $job = DB::table('document_ingestions')->where('id', $ingestion)->lockForUpdate()->firstOrFail();
            $extraction = DB::table('invoice_extractions')->where('ingestion_id', $job->id)->lockForUpdate()->firstOrFail();
            $invoice = Factura::findOrFail($data['invoice_id']);
            $invoiceBefore = $invoice->fresh()->toArray();
            $changes = [];
            if (isset($data['provider_id'])) $changes += ['provider_id' => $data['provider_id'], 'client_id' => null];
            if (isset($data['client_id'])) $changes += ['client_id' => $data['client_id'], 'provider_id' => null];
            foreach (['invoice_number' => 'invoice', 'invoice_date' => 'date', 'invoice_amount' => 'amount'] as $input => $column) {
                if (array_key_exists($input, $data) && $data[$input] !== null && $data[$input] !== '') $changes[$column] = $data[$input];
            }
            if ($changes) $invoice->forceFill($changes)->save();
            $normalized = json_decode($extraction->normalized_data ?: '{}', true) ?: [];
            if (isset($data['invoice_number'])) $normalized['invoice_number'] = $data['invoice_number'];
            if (isset($data['invoice_date'])) $normalized['date'] = $data['invoice_date'];
            if (isset($data['invoice_amount'])) $normalized['amount'] = (float)$data['invoice_amount'];
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
            DB::table('invoice_line_items')->where('extraction_id', $extraction->id)->delete();
            foreach ($items as $i => $line) {
                if (!is_array($line) || empty($line['description'])) continue;
                DB::table('invoice_line_items')->insert(['invoice_id' => $invoice->id, 'extraction_id' => $extraction->id,
                    'line_number' => $i + 1, 'description_original' => mb_substr((string)$line['description'], 0, 1000),
                    'description_normalized' => mb_substr(mb_strtolower(trim((string)$line['description'])), 0, 700),
                    'supplier_sku' => isset($line['code']) ? mb_substr((string)$line['code'], 0, 191) : null,
                    'quantity' => $line['quantity'] ?? null, 'unit_price' => $line['unit_price'] ?? null,
                    'total_amount' => $line['total_amount'] ?? null, 'currency' => $normalized['currency'] ?? null,
                    'source_line' => json_encode($line, JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
            }
            $invoiceAfter = $invoice->fresh()->toArray();
            $auditChanges = AuditLogger::simpleDiff($invoiceBefore, $invoiceAfter);
            $auditChanges['line_items'] = [
                'from' => 'Detalle anterior de la extracción',
                'to' => count(array_filter($items, fn ($line) => is_array($line) && !empty($line['description']))) . ' ítem(s) guardado(s) desde extracción #' . $extraction->id,
            ];
            AuditLogger::log($request, 'approve_extraction', 'facturas', $invoice->id,
                'Aprobó la extracción #' . $extraction->id . ' y aplicó sus valores a la factura #' . $invoice->invoice . '.',
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
            'invoice_ids' => ['required', 'array'],
            'invoice_ids.*' => ['nullable', 'integer', 'exists:invoices,id'],
            'invoice_numbers' => ['nullable', 'array'],
            'invoice_numbers.*' => ['nullable', 'string', 'max:80'],
            'invoice_dates' => ['nullable', 'array'],
            'invoice_dates.*' => ['nullable', 'date'],
            'invoice_amounts' => ['nullable', 'array'],
            'invoice_amounts.*' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
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
                if (!isset($byPage[$page])) return ['error' => "No se encontró la factura de la página {$page} en la extracción."];
                $invoiceId = (int) ($data['invoice_ids'][$page] ?? 0);
                if (!$invoiceId) return ['error' => "Selecciona una factura existente para la página {$page}."];

                $invoice = Factura::query()->lockForUpdate()->findOrFail($invoiceId);
                $entry = $byPage[$page];
                $number = trim((string) ($data['invoice_numbers'][$page] ?? data_get($entry, 'invoice_number', '')));
                $date = $data['invoice_dates'][$page] ?? data_get($entry, 'date');
                $amount = $data['invoice_amounts'][$page] ?? data_get($entry, 'amount');
                $changes = [];
                if ($number !== '') $changes['invoice'] = mb_substr($number, 0, 80);
                if ($date) $changes['date'] = $date;
                if ($amount !== null && $amount !== '') $changes['amount'] = $amount;
                $before = $invoice->toArray();
                if ($changes) $invoice->forceFill($changes)->save();
                $approvedInvoices[$page] = $invoice->id;
                $approvals[(string) $page] = ['invoice_id' => $invoice->id, 'approved_at' => $now->toISOString(), 'approved_by' => $request->user()->id];

                AuditLogger::log($request, 'approve_extraction_page', 'facturas', $invoice->id,
                    "Aprobó la página {$page} de la extracción #{$extraction->id} (" . ($job->filename ?: $job->uuid) . ').',
                    ['model' => Factura::class, 'data_before' => $before, 'data_after' => $invoice->fresh()->toArray(),
                        'changes' => AuditLogger::simpleDiff($before, $invoice->fresh()->toArray()), 'reversible' => false]);
            }

            $normalized['page_approvals'] = $approvals;
            $pageCount = count($byPage);
            $approvedPageKeys = array_intersect(array_map('strval', array_keys($byPage)), array_keys($approvals));
            $approvedCount = count($approvedPageKeys);
            $isComplete = $approvedCount >= $pageCount;

            // Rebuild this extraction's lines from the approved pages so partial approvals
            // remain repeatable without duplicating or mixing line items between invoices.
            DB::table('invoice_line_items')->where('extraction_id', $extraction->id)->delete();
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
                    DB::table('invoice_line_items')->insert([
                        'invoice_id' => $invoiceId, 'extraction_id' => $extraction->id, 'line_number' => $lineNumber,
                        'description_original' => $description,
                        'description_normalized' => mb_substr(mb_strtolower(trim($description)), 0, 700),
                        'supplier_sku' => isset($line['code']) ? mb_substr((string) $line['code'], 0, 191) : null,
                        'quantity' => $line['quantity'] ?? null, 'unit_original' => isset($line['unit']) ? mb_substr((string) $line['unit'], 0, 32) : null,
                        'unit_price' => $line['unit_price'] ?? null,
                        'total_amount' => $line['total_amount'] ?? $line['total_price'] ?? $line['amount'] ?? null,
                        'currency' => $currency, 'source_line' => json_encode($line + ['source_page' => $page], JSON_UNESCAPED_UNICODE),
                        'created_at' => $now, 'updated_at' => $now,
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
            $filename = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '', (string) ($job->filename ?: basename($key))) ?: 'documento.pdf';

            return $disk->response($key, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . addcslashes($filename, '"\\') . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            abort(503, 'No se pudo leer el PDF desde R2.');
        }
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
}
