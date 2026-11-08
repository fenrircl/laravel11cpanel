<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OpenAiAssistantService
{
    public function __construct(
        private readonly AssistantDataService $data,
        private readonly AssistantSettingsService $settings,
    )
    {
    }

    /** @param array<int, array{role:string,content:string}> $history */
    public function answer(array $history): array
    {
        $apiKey = (string) config('services.openai.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('El asistente no está configurado.');
        }

        $payload = [
            'model' => $this->settings->model(),
            'reasoning' => ['effort' => $this->settings->reasoningEffort()],
            'instructions' => $this->settings->prompt()."\n\nFormato visual: responde con Markdown. Usa como máximo 2 emojis funcionales por respuesta (por ejemplo 📅, 🧾, 💰, ✅ o ⚠️), solo en títulos o avisos; nunca repitas emojis en cada fila. Mantén tablas cuando faciliten comparar datos.",
            'input' => $history,
            'tools' => $this->tools(),
            'tool_choice' => 'auto',
            'max_output_tokens' => $this->settings->maxOutputTokens(),
            'store' => false,
        ];

        $input = $history;
        $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'total_tokens' => 0];
        $toolCalls = 0;
        $response = $this->request($apiKey, $payload);

        for ($round = 0; $round < 4; $round++) {
            $this->addUsage($usage, $response['usage'] ?? []);
            $calls = array_values(array_filter(
                $response['output'] ?? [],
                fn (array $item) => ($item['type'] ?? null) === 'function_call'
            ));

            if ($calls === []) {
                return [
                    'content' => $this->outputText($response), 'usage' => $usage,
                    'tool_calls' => $toolCalls, 'model' => $payload['model'],
                ];
            }

            if ($round === 3 || $toolCalls >= 6) {
                $input = [...$input, ...array_filter($response['output'] ?? [], fn (array $item) => ($item['type'] ?? null) !== 'function_call')];
                $response = $this->request($apiKey, [...$payload, 'input' => $input, 'tool_choice' => 'none']);
                $this->addUsage($usage, $response['usage'] ?? []);
                return [
                    'content' => $this->outputText($response), 'usage' => $usage,
                    'tool_calls' => $toolCalls, 'model' => $payload['model'],
                ];
            }

            $callsToProcess = array_slice($calls, 0, 6 - $toolCalls);
            $processedIds = array_column($callsToProcess, 'call_id');
            $input = [...$input, ...array_filter(
                $response['output'] ?? [],
                fn (array $item) => ($item['type'] ?? null) !== 'function_call'
                    || in_array($item['call_id'] ?? null, $processedIds, true)
            )];
            foreach ($callsToProcess as $call) {
                $arguments = json_decode($call['arguments'] ?? '{}', true);
                if (!is_array($arguments)) {
                    $arguments = [];
                }

                try {
                    $toolResult = match ($call['name'] ?? '') {
                        'search_records' => $this->data->searchRecords($arguments),
                        'summarize_invoices' => $this->data->summarizeInvoices($arguments),
                        'invoice_schedule' => $this->data->invoiceSchedule($arguments),
                        'provider_balances' => $this->data->providerBalances($arguments),
                        'search_invoice_documents' => $this->data->searchInvoiceDocuments($arguments),
                        'list_party_records' => $this->data->listPartyRecords($arguments),
                        'invoice_details' => $this->data->invoiceDetails($arguments),
                        'quotation_details' => $this->data->quotationDetails($arguments),
                        default => ['error' => 'Consulta no disponible.'],
                    };
                } catch (ValidationException) {
                    $toolResult = ['error' => 'Los filtros no son válidos. Revisa los datos e inténtalo nuevamente.'];
                }

                $input[] = [
                    'type' => 'function_call_output', 'call_id' => $call['call_id'],
                    'output' => json_encode($toolResult, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                ];
                $toolCalls++;
            }

            $response = $this->request($apiKey, [...$payload, 'input' => $input]);
        }

        throw new RuntimeException('No se pudo completar la consulta del asistente.');
    }

    private function addUsage(array &$total, array $current): void
    {
        foreach (['input_tokens', 'output_tokens', 'total_tokens'] as $key) {
            $total[$key] += (int) ($current[$key] ?? 0);
        }
    }

    private function request(string $apiKey, array $payload): array
    {
        $response = Http::acceptJson()
            ->withToken($apiKey)
            ->connectTimeout(8)
            ->timeout((int) config('services.openai.timeout', 35))
            ->post('https://api.openai.com/v1/responses', $payload);

        if (!$response->successful()) {
            logger()->warning('OpenAI Responses API request failed', ['status' => $response->status()]);
            throw new RuntimeException('No se pudo obtener una respuesta del asistente.');
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException('La respuesta del asistente no es válida.');
        }

        return $json;
    }

    private function outputText(array $response): string
    {
        foreach ($response['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                    return trim($content['text']);
                }
            }
        }

        $text = trim((string) ($response['output_text'] ?? ''));
        if ($text === '') {
            throw new RuntimeException('El asistente devolvió una respuesta vacía.');
        }

        return $text;
    }

    private function tools(): array
    {
        return [
            [
                'type' => 'function',
                'name' => 'search_records',
                'description' => 'Busca hasta cinco clientes, proveedores, facturas o cotizaciones. Busca por nombre, RUT, contacto, número de factura, id de cotización, trabajo o monto. Omite term solo para pedir los registros más recientes.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'entity' => ['type' => 'string', 'enum' => ['clients', 'providers', 'invoices', 'quotations']],
                        'term' => ['type' => ['string', 'null'], 'maxLength' => 120],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
                    ],
                    'required' => ['entity', 'term', 'limit'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'type' => 'function',
                'name' => 'list_party_records',
                'description' => 'Lista facturas y cotizaciones asociadas a un cliente, o facturas de un proveedor. Usa el party_id obtenido al buscar la entidad.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'party_type' => ['type' => 'string', 'enum' => ['client', 'provider']],
                        'party_id' => ['type' => 'integer', 'minimum' => 1],
                        'record_type' => ['type' => 'string', 'enum' => ['all', 'invoices', 'quotations']],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10],
                    ],
                    'required' => ['party_type', 'party_id', 'record_type', 'limit'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'type' => 'function',
                'name' => 'invoice_details',
                'description' => 'Devuelve los datos completos de una factura, sus datos de cliente/proveedor, fechas, monto, pago, adjuntos y estado de procesamiento del PDF. Usa invoice_id o invoice_number.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'invoice_id' => ['type' => ['integer', 'null'], 'minimum' => 1],
                        'invoice_number' => ['type' => ['string', 'null'], 'maxLength' => 80],
                    ],
                    'required' => ['invoice_id', 'invoice_number'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'type' => 'function',
                'name' => 'quotation_details',
                'description' => 'Devuelve una cotización por id con cliente, fecha, ejecutivo, trabajo, partidas, cantidades, precios, totales y archivos asociados.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'quotation_id' => ['type' => 'integer', 'minimum' => 1],
                    ],
                    'required' => ['quotation_id'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'type' => 'function',
                'name' => 'summarize_invoices',
                'description' => 'Calcula cantidad y monto de facturas. Elige date_basis=payment para pagos/gastos pagados, due para vencimientos o invoice para fecha de emisión. status también puede ser overdue.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'party_type' => ['type' => 'string', 'enum' => ['all', 'client', 'provider']],
                        'party_name' => ['type' => ['string', 'null'], 'maxLength' => 120],
                        'status' => ['type' => 'string', 'enum' => ['all', 'paid', 'pending', 'overdue']],
                        'start_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                        'end_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                        'date_basis' => ['type' => 'string', 'enum' => ['invoice', 'due', 'payment']],
                    ],
                    'required' => ['party_type', 'party_name', 'status', 'start_date', 'end_date', 'date_basis'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'type' => 'function',
                'name' => 'invoice_schedule',
                'description' => 'Busca facturas pendientes del año actual por defecto. Para otro año solicitado expresamente, pasa ese year. Encuentra vencimientos antes de fin de mes, atrasos o pendientes. Incluye fecha y días de atraso.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'mode' => ['type' => 'string', 'enum' => ['due_before_month_end', 'overdue', 'pending']],
                        'party_name' => ['type' => ['string', 'null'], 'maxLength' => 120],
                        'year' => ['type' => ['integer', 'null'], 'minimum' => 2000, 'maximum' => 2100],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
                    ],
                    'required' => ['mode', 'party_name', 'year', 'limit'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'type' => 'function',
                'name' => 'provider_balances',
                'description' => 'Resume cuánto se debe a cada proveedor en facturas pendientes e indica por separado montos/cantidades vencidos.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
                    ],
                    'required' => ['limit'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'type' => 'function',
                'name' => 'search_invoice_documents',
                'description' => 'Busca fragmentos cortos en Markdown extraído de PDFs de facturas. Úsala solo para preguntas sobre el contenido de documentos.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'term' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 120],
                        'invoice_number' => ['type' => ['string', 'null'], 'maxLength' => 80],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 3],
                    ],
                    'required' => ['term', 'invoice_number', 'limit'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
