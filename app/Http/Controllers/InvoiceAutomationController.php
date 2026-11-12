<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\FilesRegistry;
use App\Models\InvoiceDocumentContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InvoiceAutomationController extends Controller
{
    public function pendingDocuments(Request $request): JsonResponse
    {
        $limit = min(max((int)$request->query('limit', 25), 1), 100);
        $fileId = $request->integer('file_id');
        $latestExtraction = DB::table('invoice_extractions')->selectRaw('file_registry_id, MAX(id) as extraction_id')
            ->whereNotNull('file_registry_id')->groupBy('file_registry_id');
        $latestIngestion = DB::table('document_ingestions')->selectRaw('external_id, MAX(id) as ingestion_id')
            ->where('source', 'registered_pdf')->groupBy('external_id');
        $files = DB::table('files_registry as f')
            ->join('invoices as i', 'i.id', '=', 'f.model_id')
            ->leftJoinSub($latestExtraction, 'latest_extraction', fn ($join) => $join->on('latest_extraction.file_registry_id', '=', 'f.id'))
            ->leftJoin('invoice_extractions as e', 'e.id', '=', 'latest_extraction.extraction_id')
            ->leftJoinSub($latestIngestion, 'latest_ingestion', fn ($join) => $join->on('latest_ingestion.external_id', '=', 'f.id'))
            ->leftJoin('document_ingestions as j', 'j.id', '=', 'latest_ingestion.ingestion_id')
            ->where('f.model_type', 'App\\Invoice')
            ->where(function ($q) { $q->where('f.mime_type', 'application/pdf')->orWhere('f.file_name', 'like', '%.pdf')->orWhere('f.path', 'like', '%.pdf'); })
            ->when($fileId, fn ($q) => $q->where('f.id', $fileId), fn ($q) => $q->where(fn ($pending) => $pending->whereNull('e.id')->orWhere('j.status', 'processing')))
            ->orderBy('f.id')->limit($limit)
            ->get(['f.id as file_id', 'f.path as r2_key', 'f.file_name', 'f.mime_type', 'f.size', 'f.created_at', 'i.id as invoice_id', 'i.invoice as invoice_number']);
        return response()->json(['count' => $files->count(), 'data' => $files]);
    }

    public function existingDocumentUrl(int $file): JsonResponse
    {
        $record = DB::table('files_registry')->where('id', $file)->first();
        abort_unless($record && DB::table('invoices')->where('id', $record->model_id)->exists(), 404, 'PDF asociado a factura no encontrado.');
        $mime = strtolower(trim(preg_replace('/[\\x00-\\x20\\x7F-\\x9F]/', '', (string) $record->mime_type) ?? ''));
        $fileName = preg_replace('/[\\x00-\\x1F\\x7F]/', '', (string) $record->file_name) ?? '';
        $path = preg_replace('/[\\x00-\\x1F\\x7F]/', '', (string) $record->path) ?? '';
        $isPdf = in_array($mime, ['application/pdf', 'application/x-pdf'], true)
            || preg_match('/\\.pdf(?:$|[^a-z0-9])/i', $fileName) === 1
            || preg_match('/\\.pdf(?:$|[^a-z0-9])/i', $path) === 1;
        abort_unless($isPdf, 422, 'El archivo no es PDF.');
        $key = app(R2Controller::class)->registeredR2Path($file);
        $url = Storage::disk('r2')->temporaryUrl($key, now()->addMinutes(15));
        return response()->json(['file_id' => $record->id, 'download_url' => $url, 'expires_in' => 900]);
    }

    public function uploadUrl(string $ingestion): JsonResponse
    {
        $row = DB::table('document_ingestions')->where('uuid', $ingestion)->first();
        abort_unless($row, 404);
        if ($row->r2_key) {
            $key = $row->r2_key;
        } else {
            $key = 'invoice-imports/' . $row->uuid . '/' . Str::slug(pathinfo($row->filename ?: 'document.pdf', PATHINFO_FILENAME)) . '.pdf';
            DB::table('document_ingestions')->where('id', $row->id)->update(['r2_key' => $key, 'updated_at' => now()]);
        }
        $signed = Storage::disk('r2')->temporaryUploadUrl($key, now()->addMinutes(15));
        return response()->json(['ingestion_id' => $row->uuid, 'r2_key' => $key, 'upload_url' => $signed['url'], 'headers' => $signed['headers'] ?? [], 'expires_in' => 900]);
    }

    public function reserveIngestion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source' => ['required', 'in:drive,registered_pdf,email_attachment'],
            'external_id' => ['required', 'string', 'max:255'],
            'external_version' => ['nullable', 'string', 'max:128'],
            'sha256' => ['nullable', 'regex:/^[a-f0-9]{64}$/i'],
            'filename' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:120'],
            'size_bytes' => ['nullable', 'integer', 'min:1'],
            'invoice_id' => ['nullable', 'integer'],
            'file_registry_id' => ['nullable', 'integer'],
        ]);
        $key = ['source' => $data['source'], 'external_id' => $data['external_id'], 'external_version' => $data['external_version'] ?? ''];
        $row = DB::table('document_ingestions')->where($key)->first();
        if ($row) {
            $existingExtraction = DB::table('invoice_extractions')
                ->where('ingestion_id', $row->id)
                ->orderByDesc('id')
                ->first(['payload', 'normalized_data']);
            if (in_array($row->status, ['completed', 'ignored'], true) || $existingExtraction) {
                $invoice = $row->invoice_id
                    ? Factura::with(['proveedor:id,name', 'cliente:id,name'])->find($row->invoice_id)
                    : null;
                $extracted = $existingExtraction
                    ? json_decode((string) ($existingExtraction->normalized_data ?: $existingExtraction->payload), true)
                    : [];
                if (is_string($extracted)) {
                    $extracted = json_decode($extracted, true);
                }
                $storedCounterparty = data_get($extracted, 'issuer')
                    ?: data_get($extracted, 'data.issuer')
                    ?: data_get($extracted, 'supplier_name')
                    ?: data_get($extracted, 'data.supplier_name')
                    ?: data_get($extracted, 'recipient')
                    ?: data_get($extracted, 'data.recipient');
                return response()->json([
                    'ingestion_id' => $row->uuid,
                    'status' => $row->status,
                    'action' => 'skip',
                    'invoice_id' => $row->invoice_id,
                    'file_id' => $row->file_registry_id,
                    'counterparty_name' => $invoice?->proveedor?->name ?? $invoice?->cliente?->name ?? $storedCounterparty,
                ]);
            }
            DB::table('document_ingestions')->where('id', $row->id)->update(['source_sha256' => $data['sha256'] ?? $row->source_sha256, 'updated_at' => now()]);
        } else {
            $id = DB::table('document_ingestions')->insertGetId($key + [
                'uuid' => (string) Str::uuid(), 'source_sha256' => $data['sha256'] ?? null,
                'filename' => $data['filename'] ?? null, 'mime_type' => $data['mime_type'] ?? 'application/pdf',
                'size_bytes' => $data['size_bytes'] ?? null, 'invoice_id' => $data['invoice_id'] ?? null,
                'file_registry_id' => $data['file_registry_id'] ?? null, 'status' => 'processing',
                'attempts' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $row = DB::table('document_ingestions')->find($id);
        }
        return response()->json(['ingestion_id' => $row->uuid, 'status' => $row->status, 'action' => 'process', 'r2_key' => $row->r2_key], 201);
    }

    public function storeExtraction(Request $request, string $ingestion): JsonResponse
    {
        $request->validate([
            'adapter_version' => ['required', 'string', 'max:64'],
            'request_id' => ['required_without:extraction', 'nullable', 'string', 'max:100'],
            'document_type' => ['required_without:extraction', 'nullable', 'string', 'max:48'],
            'data' => ['required_without:extraction', 'nullable', 'array'],
            'extraction' => ['sometimes', 'array'],
            'extraction.request_id' => ['nullable', 'string', 'max:100'],
            'extraction.document_type' => ['nullable', 'string', 'max:48'],
            'extraction.data' => ['nullable', 'array'],
        ]);
        // The PDF API returns the extraction fields at the top level. Accept the earlier
        // wrapped form too, so existing n8n drafts can be migrated without data loss.
        $data = $request->all();
        // The PDF service itself has an `extraction` metadata object at the top
        // level. Detect its envelope by the actual business fields, not by key name.
        $isApiEnvelope = isset($data['request_id'], $data['document_type'], $data['data']);
        $ex = $isApiEnvelope ? $data : (($data['extraction']['data'] ?? null) !== null ? $data['extraction'] : $data);
        if (!isset($ex['source']) && isset($data['source'])) $ex['source'] = $data['source'];
        if (!isset($ex['extraction']) && isset($data['extraction_metadata'])) $ex['extraction'] = $data['extraction_metadata'];
        Validator::make($ex, [
            'request_id' => ['required', 'string', 'max:100'], 'document_type' => ['required', 'string', 'max:48'],
            'data' => ['required', 'array'], 'source.sha256' => ['nullable', 'regex:/^[a-f0-9]{64}$/i'],
            'confidence' => ['nullable', 'numeric', 'between:0,1'], 'warnings' => ['nullable', 'array'],
            'raw_text' => ['nullable', 'string', 'max:500000'], 'schema_version' => ['nullable', 'string', 'max:32'],
        ])->validate();
        $ingest = DB::table('document_ingestions')->where('uuid', $ingestion)->first();
        abort_unless($ingest, 404, 'Importación no encontrada.');
        if ($ingest->status === 'completed') return response()->json(['ingestion_id' => $ingest->uuid, 'status' => 'completed', 'action' => 'unchanged']);
        if ($ex['document_type'] === 'quote') {
            DB::table('document_ingestions')->where('id', $ingest->id)->update(['status' => 'ignored', 'updated_at' => now()]);
            return response()->json(['ingestion_id' => $ingest->uuid, 'status' => 'ignored', 'action' => 'skip', 'reason' => 'intranet_quotations_are_already_structured']);
        }
        if (!in_array($ex['document_type'], ['invoice', 'receipt'], true)) {
            DB::table('document_ingestions')->where('id', $ingest->id)->update(['status' => 'needs_review', 'updated_at' => now()]);
            return response()->json(['ingestion_id' => $ingest->uuid, 'status' => 'needs_review', 'reason' => 'document_type_not_supported']);
        }
        $this->savePendingExtraction($ingest, $ex, $data['adapter_version']);
        $payload = $ex['data'];
        $folio = trim((string)($payload['invoice_number'] ?? ''));
        $supplierRut = preg_replace('/[^0-9kK]/', '', (string)($payload['supplier_rut'] ?? ''));
        $customerRut = preg_replace('/[^0-9kK]/', '', (string)($payload['customer_rut'] ?? ''));
        $ownRut = preg_replace('/[^0-9kK]/', '', (string) config('services.invoices.own_rut', ''));
        $lineItems = $payload['line_items'] ?? [];
        $confidence = $ex['confidence'] ?? null;
        $minimumConfidence = (float) config('services.invoices.minimum_extraction_confidence', 0.80);
        $warnings = $ex['warnings'] ?? [];
        $blockingWarnings = array_filter((array) $warnings, function ($warning) {
            $warning = mb_strtolower((string) $warning);
            return str_contains($warning, 'múltiples facturas')
                || str_contains($warning, 'multiples facturas')
                || str_contains($warning, 'facturas independientes')
                || str_contains($warning, 'no fue posible estructurar')
                || str_contains($warning, 'no se pudo estructurar')
                || str_contains($warning, 'documento ambiguo');
        });
        $reviewReasons = [];
        if ($folio === '') $reviewReasons[] = 'número de factura no identificado';
        if (!$ownRut) $reviewReasons[] = 'falta configurar INVOICE_OWN_RUT en Laravel';
        if (empty($payload['date'])) $reviewReasons[] = 'fecha no identificada';
        if (!is_numeric($payload['amount'] ?? null)) $reviewReasons[] = 'monto no identificado';
        if (!is_array($lineItems) || $lineItems === []) $reviewReasons[] = 'detalle de productos/servicios no identificado';
        if ($confidence === null || $confidence < $minimumConfidence) $reviewReasons[] = 'confianza menor al mínimo (' . number_format($minimumConfidence, 2) . ')';
        if ($blockingWarnings) $reviewReasons[] = 'el extractor reportó advertencias que impiden identificar una sola factura';
        if ($reviewReasons) {
            DB::table('document_ingestions')->where('id', $ingest->id)->update([
                'status' => 'needs_review',
                'last_error' => 'Pendiente de revisión: ' . implode('; ', $reviewReasons) . '.',
                'updated_at' => now(),
            ]);
            return response()->json(['ingestion_id' => $ingest->uuid, 'status' => 'needs_review', 'reason' => 'required_fields_confidence_or_warnings']);
        }
        $isPurchase = $customerRut === $ownRut;
        $counterpartyRut = $isPurchase ? $supplierRut : ($supplierRut === $ownRut ? $customerRut : '');
        if (!$counterpartyRut || ($supplierRut !== $ownRut && !$isPurchase)) {
            DB::table('document_ingestions')->where('id', $ingest->id)->update(['status' => 'needs_review', 'last_error' => 'No se pudo identificar con certeza si el documento es compra o venta.', 'updated_at' => now()]);
            return response()->json(['ingestion_id' => $ingest->uuid, 'status' => 'needs_review', 'reason' => 'party_identity_ambiguous']);
        }
        if (!$ingest->file_registry_id && (!$ingest->r2_key || !Storage::disk('r2')->exists($ingest->r2_key))) {
            DB::table('document_ingestions')->where('id', $ingest->id)->update(['status' => 'needs_review', 'last_error' => 'El PDF no está cargado en R2 ni asociado a un archivo existente.', 'updated_at' => now()]);
            return response()->json(['ingestion_id' => $ingest->uuid, 'status' => 'needs_review', 'reason' => 'pdf_not_uploaded_or_registered']);
        }
        $currency = $this->normalizeCurrencyCode($payload['currency'] ?? null);
        try {
        return DB::transaction(function () use ($request, $data, $ex, $payload, $lineItems, $ingest, $folio, $counterpartyRut, $isPurchase, $currency) {
        $partyId = $this->partyIdForRut($counterpartyRut, $isPurchase);
        $counterpartyTable = $isPurchase ? 'providers' : 'clients';
        $rutMatches = DB::table($counterpartyTable)->whereRaw("REPLACE(REPLACE(REPLACE(LOWER(rut), '.', ''), '-', ''), ' ', '') = ?", [strtolower($counterpartyRut)])->count();
        if (!$partyId && $rutMatches === 0 && $isPurchase && config('services.invoices.auto_create_providers', true)
            && $this->isValidChileanRut($supplierRut) && !empty($payload['issuer'])) {
            $provider = \App\Models\Proveedor::query()->create([
                'name' => trim((string)$payload['issuer']), 'rut' => trim((string)$payload['supplier_rut']),
                'email' => $payload['supplier_email'] ?? null, 'phone' => $payload['supplier_phone'] ?? null,
                'address' => $payload['supplier_address'] ?? null,
            ]);
            DB::table('audit_logs')->insert(['user_id' => null, 'action' => 'auto_create', 'module' => 'providers',
                'entity_id' => (string)$provider->id, 'description' => 'Proveedor creado automáticamente desde extracción de factura; RUT ' . $counterpartyRut,
                'created_at' => now(), 'updated_at' => now()]);
            $partyId = (int)$provider->id;
        }
        if (!$partyId) {
            DB::table('document_ingestions')->where('id', $ingest->id)->update(['status' => 'needs_review', 'last_error' => 'No existe contraparte con ese RUT y no se pudo crear automáticamente.', 'updated_at' => now()]);
            return response()->json(['ingestion_id' => $ingest->uuid, 'status' => 'needs_review', 'reason' => 'counterparty_not_found']);
        }
        $this->fillMissingPartyContact($partyId, $isPurchase, $payload);
            $invoice = $ingest->invoice_id ? Factura::find($ingest->invoice_id) : null;
            if (!$invoice) {
                $matches = Factura::query()->where('invoice', $folio)
                    ->where($isPurchase ? 'provider_id' : 'client_id', $partyId)->get();
                if ($matches->count() > 1) {
                    DB::table('document_ingestions')->where('id', $ingest->id)->update(['status' => 'needs_review', 'updated_at' => now()]);
                    return response()->json(['ingestion_id' => $ingest->uuid, 'status' => 'needs_review', 'reason' => 'multiple_invoice_matches']);
                }
                $invoice = $matches->first();
            }
            if (!$invoice) {
                $invoice = Factura::create(['invoice' => $folio, $isPurchase ? 'provider_id' : 'client_id' => $partyId,
                    'date' => $payload['date'], 'amount' => (int) round((float)$payload['amount']), 'status' => 0]);
                $snapshot = $invoice->fresh()->toArray();
                DB::table('audit_logs')->insert([
                    'user_id' => null,
                    'action' => 'auto_create',
                    'module' => 'facturas',
                    'entity_id' => (string) $invoice->id,
                    'description' => 'Creó factura #' . $invoice->invoice . ' automáticamente desde importación n8n ' . $ingest->uuid . '.',
                    'model' => Factura::class,
                    'data_before' => null,
                    'data_after' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                    'changes' => json_encode(\App\Services\AuditLogger::simpleDiff([], $snapshot), JSON_UNESCAPED_UNICODE),
                    'reversible' => false,
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                    'url' => mb_substr($request->fullUrl(), 0, 255),
                    'method' => $request->method(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $partyColumn = $isPurchase ? 'provider_id' : 'client_id';
                $otherPartyColumn = $isPurchase ? 'client_id' : 'provider_id';
                if (!$invoice->{$partyColumn} && !$invoice->{$otherPartyColumn}) {
                    $before = $invoice->toArray();
                    $invoice->forceFill([$partyColumn => $partyId])->save();
                    $after = $invoice->fresh()->toArray();
                    DB::table('audit_logs')->insert([
                        'user_id' => null,
                        'action' => 'auto_update',
                        'module' => 'facturas',
                        'entity_id' => (string) $invoice->id,
                        'description' => 'Asoció automáticamente la factura #' . $invoice->invoice . ' con la contraparte identificada por RUT desde importación n8n ' . $ingest->uuid . '.',
                        'model' => Factura::class,
                        'data_before' => json_encode($before, JSON_UNESCAPED_UNICODE),
                        'data_after' => json_encode($after, JSON_UNESCAPED_UNICODE),
                        'changes' => json_encode(\App\Services\AuditLogger::simpleDiff($before, $after), JSON_UNESCAPED_UNICODE),
                        'reversible' => false,
                        'ip_address' => $request->ip(),
                        'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                        'url' => mb_substr($request->fullUrl(), 0, 255),
                        'method' => $request->method(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $fileId = $ingest->file_registry_id;
            if (!$fileId && $ingest->r2_key && Storage::disk('r2')->exists($ingest->r2_key)) {
                $fileId = DB::table('files_registry')->insertGetId([
                    'model_type' => 'App\\Invoice', 'model_id' => $invoice->id, 'real_id' => $invoice->id,
                    'path' => $ingest->r2_key, 'file_name' => $ingest->filename ?: basename($ingest->r2_key),
                    'mime_type' => 'application/pdf', 'size' => $ingest->size_bytes, 'migrated' => 0, 'created_at' => now(),
                ]);
            }
            $extractionId = DB::table('invoice_extractions')->where('ingestion_id', $ingest->id)->value('id');
            DB::table('invoice_extractions')->where('id', $extractionId)->update([
                'invoice_id' => $invoice->id, 'file_registry_id' => $fileId,
                'validation_status' => 'validated', 'is_active' => true, 'processed_at' => now(), 'updated_at' => now(),
            ]);
            if ($fileId) {
                DB::table('invoice_extractions')->where('file_registry_id', $fileId)->where('id', '<>', $extractionId)->update(['is_active' => false]);
            }
            foreach ($lineItems as $index => $line) {
                if (!is_array($line) || empty($line['description'])) continue;
                DB::table('invoice_line_items')->insert(['invoice_id' => $invoice->id, 'extraction_id' => $extractionId,
                    'line_number' => $index + 1, 'description_original' => mb_substr((string)$line['description'], 0, 1000),
                    'description_normalized' => mb_strtolower(trim((string)$line['description'])),
                    'supplier_sku' => isset($line['code']) ? mb_substr((string)$line['code'], 0, 191) : null,
                    'quantity' => $line['quantity'] ?? null, 'unit_price' => $line['unit_price'] ?? null,
                    'total_amount' => $line['total_amount'] ?? null, 'currency' => $currency,
                    'source_line' => json_encode($line, JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('document_ingestions')->where('id', $ingest->id)->update(['invoice_id' => $invoice->id,
            'file_registry_id' => $fileId, 'status' => 'completed', 'updated_at' => now()]);
            return response()->json(['ingestion_id' => $ingest->uuid, 'status' => 'completed', 'action' => 'updated', 'invoice_id' => $invoice->id, 'extraction_id' => $extractionId]);
        });
        } catch (\Throwable $exception) {
            report($exception);
            DB::table('document_ingestions')->where('id', $ingest->id)->update([
                'status' => 'failed',
                'last_error' => 'Laravel no pudo guardar completamente la extracción. Revisa el registro de errores y reintenta.',
                'lease_until' => null,
                'updated_at' => now(),
            ]);

            throw $exception;
        }
    }

    private function normalizeCurrencyCode(mixed $currency): ?string
    {
        if (!is_string($currency) || trim($currency) === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($currency));
        $knownCurrencies = [
            'peso' => 'CLP', 'pesos' => 'CLP', 'peso chileno' => 'CLP', 'pesos chilenos' => 'CLP',
            'clp' => 'CLP', 'chilean peso' => 'CLP', 'chilean pesos' => 'CLP',
            'dolar' => 'USD', 'dólar' => 'USD', 'dolares' => 'USD', 'dólares' => 'USD', 'usd' => 'USD',
            'euro' => 'EUR', 'euros' => 'EUR', 'eur' => 'EUR',
        ];

        if (isset($knownCurrencies[$normalized])) {
            return $knownCurrencies[$normalized];
        }

        $code = strtoupper($normalized);
        return preg_match('/^[A-Z]{3}$/', $code) === 1 ? $code : null;
    }

    private function partyIdForRut(string $rut, bool $provider): ?int
    {
        $table = $provider ? 'providers' : 'clients';
        if (!\Illuminate\Support\Facades\Schema::hasTable($table)) return null;
        $id = DB::table($table)->whereRaw("REPLACE(REPLACE(REPLACE(LOWER(rut), '.', ''), '-', ''), ' ', '') = ?", [strtolower($rut)])->value('id');
        return $id ? (int)$id : null;
    }

    private function savePendingExtraction(object $ingest, array $ex, string $adapterVersion): void
    {
        $existing = DB::table('invoice_extractions')->where('ingestion_id', $ingest->id)->first();
        $values = [
            'file_registry_id' => $ingest->file_registry_id, 'request_id' => $ex['request_id'] ?? null,
            'schema_version' => $ex['schema_version'] ?? null, 'adapter_version' => $adapterVersion,
            'source_sha256' => $ex['source']['sha256'] ?? $ingest->source_sha256,
            'document_type' => $ex['document_type'], 'document_subtype' => $ex['document_subtype'] ?? null,
            'payload' => json_encode($ex, JSON_UNESCAPED_UNICODE), 'normalized_data' => json_encode($ex['data'] ?? [], JSON_UNESCAPED_UNICODE),
            'raw_text' => $ex['raw_text'] ?? null, 'confidence' => $ex['confidence'] ?? null,
            'warnings' => json_encode($ex['warnings'] ?? []), 'validation_status' => 'needs_review', 'is_active' => false,
            'updated_at' => now(),
        ];
        if ($existing) DB::table('invoice_extractions')->where('id', $existing->id)->update($values);
        else DB::table('invoice_extractions')->insert($values + ['ingestion_id' => $ingest->id, 'created_at' => now()]);
    }

    private function isValidChileanRut(string $rut): bool
    {
        $rut = strtoupper(preg_replace('/[^0-9K]/', '', $rut));
        if (strlen($rut) < 2) return false;
        $body = substr($rut, 0, -1); $dv = substr($rut, -1); $sum = 0; $factor = 2;
        for ($i = strlen($body) - 1; $i >= 0; $i--) { $sum += (int)$body[$i] * $factor; $factor = $factor === 7 ? 2 : $factor + 1; }
        $value = 11 - ($sum % 11); $expected = $value === 11 ? '0' : ($value === 10 ? 'K' : (string)$value);
        return hash_equals($expected, $dv);
    }

    private function fillMissingPartyContact(int $partyId, bool $provider, array $data): void
    {
        $table = $provider ? 'providers' : 'clients';
        $fields = $provider
            ? ['email' => 'supplier_email', 'phone' => 'supplier_phone', 'address' => 'supplier_address']
            : ['email' => 'customer_email', 'phone' => 'customer_phone', 'address' => 'customer_address'];
        $current = DB::table($table)->where('id', $partyId)->first();
        if (!$current) return;
        $updates = [];
        foreach ($fields as $column => $source) {
            $value = trim((string)($data[$source] ?? ''));
            if (empty($current->{$column}) && $value !== '') $updates[$column] = mb_substr($value, 0, $column === 'email' ? 255 : 255);
        }
        if ($updates) DB::table($table)->where('id', $partyId)->update($updates);
    }
    public function sourceFiles(Factura $factura): JsonResponse
    {
        $files = FilesRegistry::query()
            ->where('model_type', 'App\\Invoice')
            ->where('model_id', $factura->id)
            ->orderByDesc('id')
            ->get(['id', 'real_id', 'path', 'file_name', 'mime_type', 'size', 'created_at'])
            ->filter(fn (FilesRegistry $file) => strtolower((string) pathinfo($file->file_name ?: $file->path, PATHINFO_EXTENSION)) === 'pdf'
                || strtolower((string) $file->mime_type) === 'application/pdf')
            ->values();

        $processed = InvoiceDocumentContext::query()
            ->whereIn('file_registry_id', $files->pluck('id'))
            ->pluck('processed_at', 'file_registry_id');

        return response()->json([
            'invoice' => [
                'id' => $factura->id,
                'number' => $factura->invoice,
                'client_id' => $factura->client_id,
                'provider_id' => $factura->provider_id,
                'party_type' => $factura->client_id ? 'client' : 'provider',
                'party_id' => $factura->client_id ?: $factura->provider_id,
                'party_name' => $factura->client_id ? $factura->cliente?->name : $factura->proveedor?->name,
                'amount' => (int) $factura->amount,
                'date' => $factura->date?->toDateString(),
                'due_date' => $factura->expiry?->toDateString(),
                'status' => (int) $factura->status === 1 ? 'paid' : 'pending',
            ],
            'files' => $files->map(fn (FilesRegistry $file) => [
                'id' => $file->id,
                'r2_key' => $file->path,
                'file_name' => $file->file_name,
                'mime_type' => $file->mime_type,
                'size_bytes' => $file->size,
                'uploaded_at' => $file->created_at,
                'processed_at' => $processed[$file->id] ?? null,
            ])->all(),
        ]);
    }

    public function storeMarkdown(Request $request, Factura $factura, FilesRegistry $file): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'invoice_number' => ['required', 'string', 'max:80'],
            'markdown' => ['required', 'string', 'min:1', 'max:500000'],
            'source_sha256' => ['nullable', 'string', 'regex:/^[a-f0-9]{64}$/i'],
            'metadata' => ['nullable', 'array'],
        ])->validate();

        if ((int) $file->model_id !== (int) $factura->id
            || $file->model_type !== 'App\\Invoice'
            || (string) $factura->invoice !== (string) $validated['invoice_number']) {
            throw ValidationException::withMessages([
                'file' => 'El archivo, su tipo o el número no corresponde a esta factura.',
            ]);
        }

        $extension = strtolower((string) pathinfo($file->file_name ?: $file->path, PATHINFO_EXTENSION));
        if ($extension !== 'pdf' && strtolower((string) $file->mime_type) !== 'application/pdf') {
            throw ValidationException::withMessages(['file' => 'El archivo asociado no es un PDF.']);
        }

        $markdown = trim($validated['markdown']);
        $context = InvoiceDocumentContext::query()->updateOrCreate(
            ['file_registry_id' => $file->id],
            [
                'invoice_id' => $factura->id,
                'source_sha256' => $validated['source_sha256'] ?? hash('sha256', $markdown),
                'markdown' => $markdown,
                'metadata' => $validated['metadata'] ?? null,
                'processed_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'invoice_id' => $factura->id,
            'file_id' => $file->id,
            'context_id' => $context->id,
            'processed_at' => $context->processed_at,
        ], 200);
    }

    public function searchContext(Request $request, \App\Services\AssistantDataService $data): JsonResponse
    {
        $arguments = $request->validate([
            'term' => ['required', 'string', 'min:2', 'max:120'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:3'],
        ]);
        $arguments['limit'] = (int) ($arguments['limit'] ?? 3);

        return response()->json(['data' => $data->searchInvoiceDocuments($arguments)]);
    }
}
