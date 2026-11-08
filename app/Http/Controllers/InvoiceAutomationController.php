<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\FilesRegistry;
use App\Models\InvoiceDocumentContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InvoiceAutomationController extends Controller
{
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
