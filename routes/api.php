<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\AssistantApiController;
use App\Http\Controllers\InvoiceAutomationController;

Route::get('/ping', fn () => response()->json(['message' => 'pong']));

Route::prefix('v1/assistant')->middleware(['assistant.enabled', 'auth.chat-api', 'assistant.api.audit', 'throttle:30,1'])->group(function () {
    Route::post('/search', [AssistantApiController::class, 'search'])->name('api.v1.assistant.search');
    Route::post('/invoices/summary', [AssistantApiController::class, 'invoiceSummary'])->name('api.v1.assistant.invoices.summary');
    Route::post('/invoices/search', [AssistantApiController::class, 'searchInvoices'])->name('api.v1.assistant.invoices.search');
    Route::post('/invoices/schedule', [AssistantApiController::class, 'invoiceSchedule'])->name('api.v1.assistant.invoices.schedule');
    Route::post('/providers/balances', [AssistantApiController::class, 'providerBalances'])->name('api.v1.assistant.providers.balances');
    Route::post('/party/records', [AssistantApiController::class, 'partyRecords'])->name('api.v1.assistant.party.records');
    Route::post('/invoices/details', [AssistantApiController::class, 'invoiceDetails'])->name('api.v1.assistant.invoices.details');
    Route::post('/quotations/details', [AssistantApiController::class, 'quotationDetails'])->name('api.v1.assistant.quotations.details');
    Route::post('/invoice-documents/search', [InvoiceAutomationController::class, 'searchContext'])->name('api.v1.assistant.invoice-documents.search');
});

Route::prefix('v1/automation')->middleware(['auth.n8n', 'throttle:60,1'])->group(function () {
    Route::get('/invoices/{factura}/documents', [InvoiceAutomationController::class, 'sourceFiles'])->name('api.v1.automation.invoice-documents.index');
    Route::put('/invoices/{factura}/documents/{file}/markdown', [InvoiceAutomationController::class, 'storeMarkdown'])->name('api.v1.automation.invoice-documents.store');
});

// Proxy simple hacia n8n usando N8N_WEBHOOK_URL del .env
Route::post('/mcp/webhook', function (Request $request) {
    $url = config('services.n8n.webhook_url');
    if (!$url) {
        return response()->json(['error' => 'Webhook no configurado'], 500);
    }

    if (strlen($request->getContent()) > 16000) {
        return response()->json(['error' => 'La solicitud excede el tamaño permitido.'], 413);
    }

    try {
        $payload = $request->all();
        $bearer = config('services.mcp.bearer');
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if ($bearer) {
            $headers['Authorization'] = 'Bearer ' . $bearer;
        }

        $res = Http::withHeaders($headers)->post($url, $payload);

        $contentType = $res->header('Content-Type', 'application/json');
        $status = $res->status();
        if (str_contains($contentType, 'application/json')) {
            return response()->json($res->json(), $status);
        }
        return response($res->body(), $status)->header('Content-Type', $contentType);
    } catch (\Throwable $e) {
        report($e);
        return response()->json(['error' => 'Error al contactar el servicio configurado.'], 502);
    }
})->middleware('auth.bearer');


