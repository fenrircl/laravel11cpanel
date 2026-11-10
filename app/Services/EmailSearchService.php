<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class EmailSearchService
{
    public function __construct(private readonly AssistantDataService $data)
    {
    }

    /** Search the configured cPanel IMAP mailbox through the private n8n webhook. */
    public function search(array $arguments): array
    {
        $url = trim((string) config('services.n8n.email_search_webhook_url', ''));
        $bearer = trim((string) config('services.n8n.email_search_bearer', ''));

        if ($url === '' || $bearer === '' || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return [
                'ok' => false,
                'error' => 'La búsqueda en correos no está configurada.',
            ];
        }

        $from = $this->filterText($arguments['from'] ?? null, 180);
        $to = $this->filterText($arguments['to'] ?? null, 180);
        $contactName = $this->filterText($arguments['contact_name'] ?? null, 120);

        // Older tool calls sometimes treated a display name as an IMAP From value.
        // Resolve those names against CRM contacts instead of sending them as an address filter.
        if ($contactName === '' && $from !== '' && !str_contains($from, '@')) {
            $contactName = $from;
            $from = '';
        } elseif ($contactName === '' && $to !== '' && !str_contains($to, '@')) {
            $contactName = $to;
            $to = '';
        }

        $query = $this->filterText($arguments['query'] ?? null, 180);
        $contact = null;
        $resolution = null;
        if ($contactName !== '') {
            $resolution = $this->data->resolveEmailContact(
                $contactName,
                in_array($arguments['contact_type'] ?? null, ['client', 'provider'], true)
                    ? $arguments['contact_type']
                    : 'all'
            );

            if ($resolution['status'] === 'ambiguous') {
                return [
                    'ok' => false,
                    'error' => 'Hay más de un contacto con ese nombre y distintos correos. Indica si es cliente o proveedor.',
                    'matches' => array_map(fn (array $match) => [
                        'name' => $match['name'], 'type' => $match['type'], 'email' => $match['email'],
                    ], $resolution['matches']),
                ];
            }

            if ($resolution['status'] === 'matched') {
                $contact = $resolution['contact'];
            } elseif ($query === '') {
                // If there is no CRM match, IMAP TEXT searches headers and body for the name.
                $query = $contactName;
            }
        }

        $payload = [
            'direction' => in_array($arguments['direction'] ?? null, ['inbound', 'sent', 'both'], true)
                ? $arguments['direction']
                : 'both',
            'query' => $query,
            'contact_email' => $contact['email'] ?? '',
            'from' => $from,
            'to' => $to,
            'subject' => $this->filterText($arguments['subject'] ?? null, 180),
            'after' => $this->filterDate($arguments['after'] ?? null),
            'before' => $this->filterDate($arguments['before'] ?? null),
            'limit' => min(10, max(1, (int) ($arguments['limit'] ?? 5))),
        ];

        if (!array_filter($payload, fn ($value, $key) => !in_array($key, ['direction', 'limit'], true) && $value !== '', ARRAY_FILTER_USE_BOTH)) {
            return [
                'ok' => false,
                'error' => 'Indica al menos un dato para buscar, como nombre, asunto, número de factura o fecha.',
            ];
        }

        try {
            $request = Http::acceptJson()
                ->asJson()
                ->connectTimeout(5)
                ->timeout((int) config('services.n8n.email_search_timeout', 25));

            $authorization = str_starts_with(strtolower($bearer), 'bearer ')
                ? $request->withHeaders(['Authorization' => $bearer])
                : $request->withToken($bearer);

            $response = $authorization->post($url, $payload);
        } catch (Throwable $exception) {
            logger()->warning('Assistant email search webhook request failed', [
                'exception' => get_class($exception),
            ]);

            return [
                'ok' => false,
                'error' => 'No se pudo consultar el correo en este momento.',
            ];
        }

        $data = $response->json();
        if (!$response->successful() || !is_array($data) || ($data['ok'] ?? false) !== true) {
            logger()->warning('Assistant email search webhook returned an error', [
                'status' => $response->status(),
            ]);

            return [
                'ok' => false,
                'error' => 'No se pudo completar la búsqueda de correos.',
            ];
        }

        $rawResults = is_array($data['results'] ?? null) ? $data['results'] : [];
        $results = array_slice(array_values(array_filter(
            $rawResults,
            fn ($result) => is_array($result)
        )), 0, $payload['limit']);

        return [
            'ok' => true,
            'direction' => $payload['direction'],
            'count' => count($results),
            'truncated' => (bool) ($data['truncated'] ?? false),
            'matched_contact' => $contact ? [
                'name' => $contact['name'], 'type' => $contact['type'],
            ] : null,
            'results' => array_map(fn (array $result) => [
                'mailbox' => $this->filterText($result['mailbox'] ?? null, 80),
                'date' => $this->filterText($result['date'] ?? null, 40),
                'subject' => $this->filterText($result['subject'] ?? null, 180),
                'from' => $this->formatAddresses($result['from'] ?? []),
                'to' => $this->formatAddresses($result['to'] ?? []),
                'attachments' => array_slice(array_map(
                    fn ($attachment) => $this->filterText($attachment['name'] ?? null, 180),
                    array_filter(is_array($result['attachments'] ?? null) ? $result['attachments'] : [], 'is_array')
                ), 0, 10),
                'excerpt' => $this->filterText($result['excerpt'] ?? null, 700),
            ], $results),
        ];
    }

    private function filterText(mixed $value, int $limit): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return '';
        }

        return mb_substr(trim((string) $value), 0, $limit);
    }

    private function filterDate(mixed $value): string
    {
        $date = $this->filterText($value, 10);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) !== 1) {
            return '';
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $date : '';
    }

    private function formatAddresses(mixed $addresses): array
    {
        if (!is_array($addresses)) {
            return [];
        }

        return array_slice(array_map(function ($address): string {
            if (!is_array($address)) {
                return '';
            }

            $name = $this->filterText($address['name'] ?? null, 100);
            $email = $this->filterText($address['address'] ?? null, 180);

            return $name !== '' && $email !== '' ? $name . ' <' . $email . '>' : ($email ?: $name);
        }, array_filter($addresses, 'is_array')), 0, 5);
    }
}
