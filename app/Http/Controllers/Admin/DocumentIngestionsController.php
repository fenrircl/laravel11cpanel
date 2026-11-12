<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->when($type, fn ($q) => $type === 'unclassified'
                ? $q->where(fn ($query) => $query->whereNull('e.document_type')->orWhere('e.document_type', ''))
                : $q->where('e.document_type', $type))
            ->orderByRaw("CASE WHEN j.status='needs_review' THEN 0 WHEN j.status='failed' THEN 1 ELSE 2 END")
            ->orderByDesc('j.updated_at')->paginate(30)->withQueryString();
        $providers = DB::table('providers')->orderBy('name')->get(['id', 'name', 'rut']);
        $clients = DB::table('clients')->orderBy('name')->get(['id', 'name', 'rut']);
        $ownRut = $this->normalizeRut((string) config('services.invoices.own_rut', ''));
        $jobs->getCollection()->transform(function ($job) use ($providers, $clients, $ownRut) {
            $job->extracted_data = json_decode($job->normalized_data ?: '{}', true) ?: [];
            $folio = trim((string) data_get($job->extracted_data, 'invoice_number', ''));
            $invoiceColumns = ['id', 'invoice', 'client_id', 'provider_id', 'date', 'amount'];
            $job->invoice_matches_count = 0;
            $job->suggested_invoice = null;
            $job->suggested_provider_id = null;
            $job->suggested_client_id = null;
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
            $provider = $supplierRut !== '' ? $providers->first(fn ($row) => $this->normalizeRut((string) $row->rut) === $supplierRut) : null;
            $client = $customerRut !== '' ? $clients->first(fn ($row) => $this->normalizeRut((string) $row->rut) === $customerRut) : null;
            if ($ownRut !== '' && $customerRut === $ownRut) {
                $job->suggested_provider_id = $provider?->id;
                $job->suggested_party_name = data_get($job->extracted_data, 'issuer');
                $job->suggested_party_rut = data_get($job->extracted_data, 'supplier_rut');
                $job->suggested_party_type = 'Proveedor';
            } elseif ($ownRut !== '' && $supplierRut === $ownRut) {
                $job->suggested_client_id = $client?->id;
                $job->suggested_party_name = data_get($job->extracted_data, 'recipient');
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
            DB::table('document_ingestions')->where('id', $job->id)->update(['invoice_id' => $invoice->id, 'status' => 'completed', 'last_error' => null, 'updated_at' => now()]);
            DB::table('audit_logs')->insert(['user_id' => $request->user()->id, 'action' => 'approve', 'module' => 'document_ingestions',
                'entity_id' => (string)$job->id, 'description' => "Aprobó importación {$job->uuid}, la vinculó a factura {$invoice->invoice} y aplicó correcciones administrativas.",
                'ip_address' => $request->ip(), 'user_agent' => mb_substr((string)$request->userAgent(), 0, 255), 'url' => mb_substr($request->fullUrl(), 0, 255),
                'method' => 'POST', 'created_at' => now(), 'updated_at' => now()]);
        });
        return back()->with('success', 'Importación aprobada y asociada a la factura.');
    }

    public function retry(int $ingestion): RedirectResponse
    {
        DB::table('document_ingestions')->where('id', $ingestion)->whereIn('status', ['failed', 'needs_review'])->update(['status' => 'processing', 'attempts' => DB::raw('attempts + 1'), 'last_error' => null, 'updated_at' => now()]);
        return back()->with('success', 'Job reabierto para reintento desde n8n.');
    }

    private function normalizeRut(string $rut): string
    {
        return strtolower((string) preg_replace('/[^0-9k]/i', '', $rut));
    }
}
