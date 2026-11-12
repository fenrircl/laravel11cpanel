<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ExistingInvoicePdfsController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'pending');
        $latestExtraction = DB::table('invoice_extractions')
            ->selectRaw('file_registry_id, MAX(id) as extraction_id')
            ->whereNotNull('file_registry_id')->groupBy('file_registry_id');
        $latestIngestion = DB::table('document_ingestions')
            ->selectRaw('external_id, MAX(id) as ingestion_id')
            ->where('source', 'registered_pdf')->groupBy('external_id');
        $lineCounts = DB::table('invoice_line_items')->selectRaw('extraction_id, COUNT(*) as line_count')->groupBy('extraction_id');

        $query = DB::table('files_registry as f')
            ->join('invoices as i', 'i.id', '=', 'f.model_id')
            ->leftJoinSub($latestExtraction, 'le', fn ($join) => $join->on('le.file_registry_id', '=', 'f.id'))
            ->leftJoin('invoice_extractions as e', 'e.id', '=', 'le.extraction_id')
            ->leftJoinSub($latestIngestion, 'lj', fn ($join) => $join->on('lj.external_id', '=', 'f.id'))
            ->leftJoin('document_ingestions as j', 'j.id', '=', 'lj.ingestion_id')
            ->leftJoinSub($lineCounts, 'lc', fn ($join) => $join->on('lc.extraction_id', '=', 'e.id'))
            ->leftJoin('clients as c', 'c.id', '=', 'i.client_id')
            ->leftJoin('providers as p', 'p.id', '=', 'i.provider_id')
            ->where('f.model_type', 'App\\Invoice')
            ->where(function ($q) {
                $q->where('f.mime_type', 'application/pdf')
                    ->orWhere('f.file_name', 'like', '%.pdf')
                    ->orWhere('f.path', 'like', '%.pdf');
            })
            ->select([
                'f.id as file_id', 'f.path', 'f.file_name', 'f.mime_type', 'f.size', 'f.created_at as uploaded_at',
                'i.id as invoice_id', 'i.invoice as invoice_number', 'i.date as invoice_date', 'i.amount as invoice_amount',
                'c.name as client_name', 'p.name as provider_name',
                'e.id as extraction_id', 'e.document_type', 'e.confidence', 'e.validation_status', 'e.warnings',
                'j.status as ingestion_status', 'j.last_error', 'lc.line_count',
            ])
            ->selectRaw("CASE WHEN j.status = 'processing' THEN 'processing' WHEN j.status = 'failed' THEN 'failed' WHEN e.validation_status = 'validated' THEN 'processed' WHEN e.id IS NOT NULL OR j.status = 'needs_review' THEN 'needs_review' ELSE 'pending' END as processing_status");

        $query->when($status === 'pending', fn ($q) => $q->whereNull('e.id')->whereNull('j.id'))
            ->when($status === 'processing', fn ($q) => $q->where('j.status', 'processing'))
            ->when($status === 'failed', fn ($q) => $q->where('j.status', 'failed'))
            ->when($status === 'needs_review', fn ($q) => $q->where(fn ($sub) => $sub->whereNotNull('e.id')->where('e.validation_status', '<>', 'validated')->orWhere('j.status', 'needs_review')))
            ->when($status === 'processed', fn ($q) => $q->where('e.validation_status', 'validated'));

        $files = $query->orderByDesc('f.created_at')->paginate(30)->withQueryString();

        return view('admin.existing-invoice-pdfs.index', compact('files', 'status'));
    }

    public function process(Request $request, int $file): RedirectResponse
    {
        $record = DB::table('files_registry')->where('id', $file)->where('model_type', 'App\\Invoice')->first();
        abort_unless($record, 404, 'PDF asociado a una factura no encontrado.');
        $isPdf = strtolower((string) $record->mime_type) === 'application/pdf'
            || strtolower((string) pathinfo($record->file_name ?: $record->path, PATHINFO_EXTENSION)) === 'pdf';
        abort_unless($isPdf, 422, 'El archivo asociado no es un PDF.');

        $webhookUrl = trim((string) config('services.n8n.existing_pdf_process_webhook_url', ''));
        $bearer = trim((string) config('services.n8n.ingest_bearer', ''));
        if ($webhookUrl === '' || $bearer === '') {
            return back()->withErrors(['n8n' => 'Configura N8N_EXISTING_PDF_PROCESS_WEBHOOK_URL y N8N_INGEST_BEARER en el .env.']);
        }

        if (DB::table('invoice_extractions')->where('file_registry_id', $file)->where('validation_status', 'validated')->exists()) {
            return back()->with('success', 'Este PDF ya tiene una extracción validada y sus ítems están guardados.');
        }

        $externalId = (string) $record->id;
        $externalVersion = (string) ($record->created_at ?? '');
        $identity = ['source' => 'registered_pdf', 'external_id' => $externalId, 'external_version' => $externalVersion];
        $ingestion = DB::table('document_ingestions')->where($identity)->first();
        if ($ingestion && $ingestion->status === 'processing' && $ingestion->lease_until && now()->lt($ingestion->lease_until)) {
            return back()->with('success', 'Este PDF ya está en proceso.');
        }

        if ($ingestion) {
            DB::table('document_ingestions')->where('id', $ingestion->id)->update([
                'status' => 'processing', 'attempts' => DB::raw('attempts + 1'), 'last_error' => null,
                'lease_until' => now()->addMinutes(30), 'updated_at' => now(),
            ]);
        } else {
            DB::table('document_ingestions')->insert($identity + [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'filename' => $record->file_name ?: basename((string) $record->path),
                'mime_type' => $record->mime_type ?: 'application/pdf', 'size_bytes' => $record->size,
                'invoice_id' => $record->model_id, 'file_registry_id' => $record->id,
                'status' => 'processing', 'attempts' => 1, 'lease_until' => now()->addMinutes(30),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        try {
            $response = Http::acceptJson()->withToken($bearer)->connectTimeout(5)->timeout(15)
                ->post($webhookUrl, ['file_id' => (int) $record->id]);
            if (!$response->successful()) {
                DB::table('document_ingestions')->where($identity)->update([
                    'status' => 'failed', 'last_error' => 'n8n rechazó el inicio del procesamiento (HTTP ' . $response->status() . ').',
                    'lease_until' => null, 'updated_at' => now(),
                ]);
                return back()->withErrors(['n8n' => 'n8n rechazó la solicitud (HTTP ' . $response->status() . ').']);
            }
        } catch (\Throwable $exception) {
            report($exception);
            DB::table('document_ingestions')->where($identity)->update([
                'status' => 'failed', 'last_error' => 'No se pudo conectar con n8n para iniciar el procesamiento.',
                'lease_until' => null, 'updated_at' => now(),
            ]);

            return back()->withErrors(['n8n' => 'No se pudo conectar con n8n. Revisa la URL del webhook y el servicio.']);
        }

        AuditLogger::log($request, 'process_pdf', 'invoice_documents', $record->id,
            'Solicitó a n8n extraer ítems del PDF de la factura #' . $record->model_id . '.');

        return back()->with('success', 'PDF enviado a n8n. Los ítems se guardarán solo si la extracción supera las validaciones; de lo contrario quedará en revisión.');
    }
}
