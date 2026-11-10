<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\AuditLog;
use App\Models\Factura;
use App\Models\Cotizacion;
use App\Models\InvoiceDocumentContext;
use App\Models\Proveedor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssistantDataService
{
    /** Resolve a CRM name to one unambiguous email address for mailbox searches. */
    public function resolveEmailContact(string $name, string $partyType = 'all'): array
    {
        $name = trim($name);
        if (mb_strlen($name) < 2 || !in_array($partyType, ['all', 'client', 'provider'], true)) {
            return ['status' => 'not_found', 'matches' => []];
        }

        $like = $this->likePattern($name);
        $matches = [];

        if (in_array($partyType, ['all', 'provider'], true)) {
            $matches = array_merge($matches, Proveedor::query()
                ->select(['id', 'name', 'email'])
                ->where('name', 'like', $like)
                ->whereNotNull('email')
                ->where('email', '<>', '')
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->map(fn (Proveedor $provider) => [
                    'id' => (int) $provider->id,
                    'name' => (string) $provider->name,
                    'email' => trim((string) $provider->email),
                    'type' => 'provider',
                ])->all());
        }

        if (in_array($partyType, ['all', 'client'], true)) {
            $matches = array_merge($matches, Cliente::query()
                ->select(['id', 'name', 'email'])
                ->where('name', 'like', $like)
                ->whereNotNull('email')
                ->where('email', '<>', '')
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->map(fn (Cliente $client) => [
                    'id' => (int) $client->id,
                    'name' => (string) $client->name,
                    'email' => trim((string) $client->email),
                    'type' => 'client',
                ])->all());
        }

        $exactMatches = array_values(array_filter(
            $matches,
            fn (array $match) => mb_strtolower($match['name']) === mb_strtolower($name)
        ));
        if ($exactMatches !== []) {
            $matches = $exactMatches;
        }

        $unique = [];
        foreach ($matches as $match) {
            $unique[mb_strtolower($match['email'])] = $match;
        }
        $matches = array_values($unique);

        if (count($matches) === 1) {
            return ['status' => 'matched', 'contact' => $matches[0], 'matches' => $matches];
        }

        return [
            'status' => $matches === [] ? 'not_found' : 'ambiguous',
            'matches' => array_slice($matches, 0, 5),
        ];
    }

    /**
     * Return a small, allow-listed result set for the assistant.
     * The model never receives a database connection or arbitrary SQL access.
     */
    public function searchRecords(array $arguments): array
    {
        $validated = Validator::make($arguments, [
            'entity' => ['required', Rule::in(['clients', 'providers', 'invoices', 'quotations'])],
            'term' => ['nullable', 'string', 'max:120'],
            'limit' => ['required', 'integer', 'min:1', 'max:5'],
        ])->validate();

        $term = trim((string) ($validated['term'] ?? ''));
        $like = $this->likePattern($term);
        $limit = (int) $validated['limit'];

        $records = match ($validated['entity']) {
            'clients' => Cliente::query()
                ->select(['id', 'name', 'rut', 'email', 'phone', 'address', 'box'])
                ->when($term !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('rut', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)))
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(fn (Cliente $client) => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'rut' => $client->rut,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'address' => $client->address,
                    'box' => $client->box,
                ])->all(),

            'providers' => Proveedor::query()
                ->select(['id', 'name', 'rut', 'email', 'phone', 'address', 'box'])
                ->when($term !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('rut', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)))
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(fn (Proveedor $provider) => [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'rut' => $provider->rut,
                    'email' => $provider->email,
                    'phone' => $provider->phone,
                    'address' => $provider->address,
                    'box' => $provider->box,
                ])->all(),

            'invoices' => $this->searchInvoices($term, $like, $limit),
            'quotations' => $this->searchQuotations($term, $like, $limit),
        };

        return [
            'entity' => $validated['entity'],
            'count' => count($records),
            'records' => $records,
        ];
    }

    /** List a client's invoices and/or quotations using the id returned by client search. */
    public function listPartyRecords(array $arguments): array
    {
        $validated = Validator::make($arguments, [
            'party_type' => ['required', Rule::in(['client', 'provider'])],
            'party_id' => ['required', 'integer', 'min:1'],
            'record_type' => ['required', Rule::in(['all', 'invoices', 'quotations'])],
            'limit' => ['required', 'integer', 'min:1', 'max:10'],
        ])->validate();

        $type = $validated['party_type'];
        $recordType = $validated['record_type'];
        if ($type === 'provider' && $recordType === 'quotations') {
            return ['party_type' => $type, 'party_id' => (int) $validated['party_id'], 'count' => 0, 'records' => []];
        }

        $limit = (int) $validated['limit'];
        $records = [];
        if (in_array($recordType, ['all', 'invoices'], true)) {
            $foreignKey = $type === 'client' ? 'client_id' : 'provider_id';
            $records = Factura::query()->where($foreignKey, $validated['party_id'])
                ->orderByDesc('date')->limit($limit)->get(['id', 'invoice', 'date', 'expiry', 'amount', 'status'])
                ->map(fn (Factura $invoice) => [
                    'type' => 'invoice', 'id' => $invoice->id, 'number' => $invoice->invoice,
                    'date' => $invoice->date?->toDateString(), 'amount' => (int) $invoice->amount,
                    'currency' => 'CLP', 'status' => $this->invoiceStatus($invoice),
                ])->all();
        }

        if ($type === 'client' && in_array($recordType, ['all', 'quotations'], true)) {
            $quotes = Cotizacion::query()->where('client_id', $validated['party_id'])
                ->orderByDesc('date')->limit($limit)->get(['id', 'date', 'total', 'work']);
            $records = [...$records, ...$quotes->map(fn (Cotizacion $quote) => [
                'type' => 'quotation', 'id' => $quote->id, 'number' => (string) $quote->id,
                'date' => $quote->date?->toDateString(), 'amount' => (int) $quote->total,
                'currency' => 'CLP', 'work' => $quote->work,
            ])->all()];
        }

        usort($records, fn (array $a, array $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));

        return [
            'party_type' => $type,
            'party_id' => (int) $validated['party_id'],
            'count' => count($records),
            'records' => array_slice($records, 0, $limit),
        ];
    }

    /** Return complete allow-listed invoice data; an invoice number may match more than one record. */
    public function invoiceDetails(array $arguments): array
    {
        $validated = Validator::make($arguments, [
            'invoice_id' => ['nullable', 'integer', 'min:1'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
        ])->validate();
        if (empty($validated['invoice_id']) && empty($validated['invoice_number'])) {
            throw ValidationException::withMessages(['invoice_id' => 'Indica el id o número de factura.']);
        }

        $query = Factura::query()->with([
            'cliente:id,name,rut,email,phone,address', 'proveedor:id,name,rut,email,phone,address',
            'metodoPago:id,name', 'archivos:id,model_id,file_name,path,mime_type,size',
            'documentContexts:id,invoice_id,file_registry_id,processed_at',
        ]);
        if (!empty($validated['invoice_id'])) {
            $query->whereKey($validated['invoice_id']);
        } else {
            $query->where('invoice', trim($validated['invoice_number']));
        }

        $invoices = $query->orderByDesc('date')->limit(5)->get();
        return [
            'count' => $invoices->count(),
            'records' => $invoices->map(fn (Factura $invoice) => $this->formatInvoiceDetails($invoice))->all(),
        ];
    }

    /** Return complete quotation data including its line items. */
    public function quotationDetails(array $arguments): array
    {
        $validated = Validator::make($arguments, [
            'quotation_id' => ['required', 'integer', 'min:1'],
        ])->validate();

        $quotation = Cotizacion::query()->with([
            'cliente:id,name,rut,email,phone,address',
            'items:id,quotation_id,description,amount,price,total',
        ])->find($validated['quotation_id']);

        if (!$quotation) {
            return ['found' => false, 'quotation' => null];
        }

        $subtotal = (int) $quotation->items->sum('total');
        $files = \App\Models\FilesRegistry::query()
            ->where('model_type', 'App\\Quotation')
            ->where('model_id', $quotation->id)
            ->get(['id', 'file_name', 'mime_type', 'size'])
            ->map(fn ($file) => [
                'file_id' => $file->id, 'name' => $file->file_name,
                'mime_type' => $file->mime_type, 'size_bytes' => (int) $file->size,
            ])->all();

        return ['found' => true, 'quotation' => [
            'id' => $quotation->id, 'number' => (string) $quotation->id,
            'date' => $quotation->date?->toDateString(), 'agent' => $quotation->agent,
            'work' => $quotation->work, 'email' => $quotation->email,
            'client' => $quotation->cliente ? [
                'id' => $quotation->cliente->id, 'name' => $quotation->cliente->name,
                'rut' => $quotation->cliente->rut, 'email' => $quotation->cliente->email,
                'phone' => $quotation->cliente->phone, 'address' => $quotation->cliente->address,
            ] : null,
            'items' => $quotation->items->map(fn ($item) => [
                'description' => $item->description, 'quantity' => (int) $item->amount,
                'unit_price' => (int) $item->price, 'total' => (int) $item->total,
            ])->all(),
            'subtotal' => $subtotal, 'tax_included_total' => (int) $quotation->total,
            'tax_amount' => max(0, (int) $quotation->total - $subtotal),
            'currency' => 'CLP', 'files' => $files,
        ]];
    }

    /** Return count and amount totals without sending invoice rows to the model. */
    public function summarizeInvoices(array $arguments): array
    {
        $validated = Validator::make($arguments, [
            'party_type' => ['required', Rule::in(['all', 'client', 'provider'])],
            'party_name' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['all', 'paid', 'pending', 'overdue'])],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'date_basis' => ['sometimes', Rule::in(['invoice', 'due', 'payment'])],
        ])->validate();

        $query = Factura::query();

        if ($validated['party_type'] === 'client') {
            $query->whereNotNull('client_id');
        } elseif ($validated['party_type'] === 'provider') {
            $query->whereNotNull('provider_id');
        }

        $partyName = trim((string) ($validated['party_name'] ?? ''));
        if ($partyName !== '') {
            $like = $this->likePattern($partyName);
            $query->where(function ($query) use ($validated, $like) {
                if ($validated['party_type'] !== 'provider') {
                    $query->whereHas('cliente', fn ($party) => $party->where('name', 'like', $like));
                }

                if ($validated['party_type'] === 'provider') {
                    $query->whereHas('proveedor', fn ($party) => $party->where('name', 'like', $like));
                } elseif ($validated['party_type'] === 'all') {
                    $query->orWhereHas('proveedor', fn ($party) => $party->where('name', 'like', $like));
                }
            });
        }

        if ($validated['status'] === 'paid') {
            $query->where('status', 1);
        } elseif ($validated['status'] === 'pending') {
            $query->where('status', 0);
        } elseif ($validated['status'] === 'overdue') {
            $query->where('status', 0)->whereNotNull('expiry')->whereDate('expiry', '<', now()->toDateString());
        }

        $dateColumn = match ($validated['date_basis'] ?? 'invoice') {
            'due' => 'expiry', 'payment' => 'pay_date', default => 'date',
        };
        if (!empty($validated['start_date'])) {
            $query->whereDate($dateColumn, '>=', $validated['start_date']);
        }
        if (!empty($validated['end_date'])) {
            $query->whereDate($dateColumn, '<=', $validated['end_date']);
        }

        $summary = $query->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as amount_total')->first();

        return [
            'count' => (int) $summary->count,
            'amount_total' => (int) $summary->amount_total,
            'currency' => 'CLP',
            'filters' => [
                'party_type' => $validated['party_type'],
                'party_name' => $partyName !== '' ? $partyName : null,
                'status' => $validated['status'],
                'date_basis' => $dateColumn,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
            ],
        ];
    }

    /** Search invoices by any combination of number, party, amount, date, and status. */
    public function searchInvoicesByFilters(array $arguments): array
    {
        $arguments['date_from'] = $this->normalizeInvoiceDate($arguments['date_from'] ?? null, 'date_from');
        $arguments['date_to'] = $this->normalizeInvoiceDate($arguments['date_to'] ?? null, 'date_to');
        $arguments['amount'] = $this->normalizeInvoiceAmount($arguments['amount'] ?? null, 'amount');
        $arguments['amount_min'] = $this->normalizeInvoiceAmount($arguments['amount_min'] ?? null, 'amount_min');
        $arguments['amount_max'] = $this->normalizeInvoiceAmount($arguments['amount_max'] ?? null, 'amount_max');

        $validator = Validator::make($arguments, [
            'party_type' => ['sometimes', Rule::in(['all', 'client', 'provider'])],
            'party_name' => ['nullable', 'string', 'max:120'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'amount_min' => ['nullable', 'integer', 'min:0'],
            'amount_max' => ['nullable', 'integer', 'min:0'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'date_basis' => ['sometimes', Rule::in(['issued', 'due', 'payment', 'created', 'modified'])],
            'status' => ['sometimes', Rule::in(['all', 'paid', 'pending', 'overdue'])],
            'limit' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        $validator->after(function ($validator) use ($arguments): void {
            $hasAmount = ($arguments['amount'] ?? null) !== null;
            $hasAmountRange = ($arguments['amount_min'] ?? null) !== null || ($arguments['amount_max'] ?? null) !== null;
            if ($hasAmount && $hasAmountRange) {
                $validator->errors()->add('amount', 'Usa un monto exacto o un rango, no ambos.');
            }
            if (($arguments['amount_min'] ?? null) !== null
                && ($arguments['amount_max'] ?? null) !== null
                && (int) $arguments['amount_min'] > (int) $arguments['amount_max']) {
                $validator->errors()->add('amount_max', 'El monto máximo debe ser igual o mayor al mínimo.');
            }
            if (($arguments['date_from'] ?? null) !== null
                && ($arguments['date_to'] ?? null) !== null
                && $arguments['date_to'] < $arguments['date_from']) {
                $validator->errors()->add('date_to', 'La fecha final debe ser igual o posterior a la inicial.');
            }

            $hasCriteria = trim((string) ($arguments['party_name'] ?? '')) !== ''
                || trim((string) ($arguments['invoice_number'] ?? '')) !== ''
                || ($arguments['amount'] ?? null) !== null
                || ($arguments['amount_min'] ?? null) !== null
                || ($arguments['amount_max'] ?? null) !== null
                || ($arguments['date_from'] ?? null) !== null
                || ($arguments['date_to'] ?? null) !== null
                || (($arguments['status'] ?? 'all') !== 'all');
            if (!$hasCriteria) {
                $validator->errors()->add('invoice_number', 'Indica número, entidad, monto, fecha o estado para buscar facturas.');
            }
        });
        $validated = $validator->validate();

        $partyType = $validated['party_type'] ?? 'all';
        $dateBasis = $validated['date_basis'] ?? 'issued';
        $dateColumn = match ($dateBasis) {
            'due' => 'expiry',
            'payment' => 'pay_date',
            'created' => 'created_at',
            'modified' => 'updated_at',
            default => 'date',
        };
        $query = Factura::query()->with(['cliente:id,name', 'proveedor:id,name']);

        if ($partyType === 'client') {
            $query->whereNotNull('client_id');
        } elseif ($partyType === 'provider') {
            $query->whereNotNull('provider_id');
        }

        $partyName = trim((string) ($validated['party_name'] ?? ''));
        if ($partyName !== '') {
            $like = $this->likePattern($partyName);
            $query->where(function ($query) use ($partyType, $like): void {
                if ($partyType !== 'provider') {
                    $query->whereHas('cliente', fn ($party) => $party->where('name', 'like', $like));
                }
                if ($partyType === 'provider') {
                    $query->whereHas('proveedor', fn ($party) => $party->where('name', 'like', $like));
                } elseif ($partyType === 'all') {
                    $query->orWhereHas('proveedor', fn ($party) => $party->where('name', 'like', $like));
                }
            });
        }

        $invoiceNumber = trim((string) ($validated['invoice_number'] ?? ''));
        if ($invoiceNumber !== '') {
            $query->where('invoice', 'like', $this->likePattern($invoiceNumber));
        }
        if (($validated['amount'] ?? null) !== null) {
            $query->where('amount', (int) $validated['amount']);
        }
        if (($validated['amount_min'] ?? null) !== null) {
            $query->where('amount', '>=', (int) $validated['amount_min']);
        }
        if (($validated['amount_max'] ?? null) !== null) {
            $query->where('amount', '<=', (int) $validated['amount_max']);
        }

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;
        if ($dateFrom !== null && $dateTo !== null) {
            $query->whereDate($dateColumn, '>=', $dateFrom)->whereDate($dateColumn, '<=', $dateTo);
        } elseif ($dateFrom !== null || $dateTo !== null) {
            $query->whereDate($dateColumn, $dateFrom ?? $dateTo);
        }

        $status = $validated['status'] ?? 'all';
        if ($status === 'paid') {
            $query->where('status', 1);
        } elseif ($status === 'pending') {
            $query->where('status', 0);
        } elseif ($status === 'overdue') {
            $query->where('status', 0)->whereNotNull('expiry')->whereDate('expiry', '<', now()->toDateString());
        }

        $count = (clone $query)->count();
        $invoices = $query->orderByDesc($dateColumn)->limit((int) $validated['limit'])
            ->get(['id', 'invoice', 'client_id', 'provider_id', 'date', 'expiry', 'pay_date', 'amount', 'status', 'created_at', 'updated_at'])
            ->values();
        $auditLogs = $this->invoiceAuditLogsFor($invoices);
        $records = $invoices->map(function (Factura $invoice) use ($auditLogs): array {
            $audit = $this->closestInvoiceAudit($invoice, $auditLogs);

            return [
                'id' => $invoice->id,
                'number' => $invoice->invoice,
                'party_type' => $invoice->client_id ? 'client' : 'provider',
                'party_name' => $invoice->client_id ? $invoice->cliente?->name : $invoice->proveedor?->name,
                'issued_date' => $invoice->date?->toDateString(),
                'due_date' => $invoice->expiry?->toDateString(),
                'paid_date' => $invoice->pay_date?->toDateString(),
                'created_at' => $invoice->created_at?->toDateTimeString(),
                'updated_at' => $invoice->updated_at?->toDateTimeString(),
                'modified_by' => $audit?->user?->name,
                'modified_by_user_id' => $audit?->user_id !== null ? (int) $audit->user_id : null,
                'modification_audit_id' => $audit?->id,
                'modification_audit_action' => $audit?->action,
                'amount' => (int) $invoice->amount,
                'currency' => 'CLP',
                'status' => $this->invoiceStatus($invoice),
            ];
        })->all();

        return [
            'filters' => [
                'party_type' => $partyType,
                'party_name' => $partyName !== '' ? $partyName : null,
                'invoice_number' => $invoiceNumber !== '' ? $invoiceNumber : null,
                'amount' => $validated['amount'] ?? null,
                'amount_min' => $validated['amount_min'] ?? null,
                'amount_max' => $validated['amount_max'] ?? null,
                'date_basis' => $dateBasis,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
            ],
            'count' => $count,
            'returned' => count($records),
            'truncated' => $count > count($records),
            'records' => $records,
        ];
    }

    /** Load matching audit records in one query and only near each invoice update time. */
    private function invoiceAuditLogsFor($invoices)
    {
        $updatedAtTimestamps = $invoices
            ->pluck('updated_at')
            ->filter()
            ->map(fn ($date) => $date->getTimestamp());
        $invoiceIds = $invoices->pluck('id')->map(fn ($id) => (string) $id)->all();

        if ($updatedAtTimestamps->isEmpty() || $invoiceIds === []) {
            return collect();
        }

        $referenceTime = now();
        $from = $referenceTime->copy()->setTimestamp($updatedAtTimestamps->min() - 300);
        $to = $referenceTime->copy()->setTimestamp($updatedAtTimestamps->max() + 300);

        return AuditLog::query()
            ->with('user:id,name')
            ->where('module', 'facturas')
            ->whereIn('action', ['update', 'restore', 'create'])
            ->whereIn('entity_id', $invoiceIds)
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'user_id', 'action', 'entity_id', 'created_at'])
            ->groupBy(fn (AuditLog $log) => (string) $log->entity_id);
    }

    /** Return an audit record only when it closely matches this invoice's updated_at. */
    private function closestInvoiceAudit(Factura $invoice, $auditLogs): ?AuditLog
    {
        if (!$invoice->updated_at) {
            return null;
        }

        $logs = $auditLogs->get((string) $invoice->id, collect());
        if ($logs->isEmpty()) {
            return null;
        }

        $updatedAt = $invoice->updated_at->getTimestamp();
        $closest = $logs->sortBy(
            fn (AuditLog $log) => abs($log->created_at->getTimestamp() - $updatedAt)
        )->first();

        if (!$closest || abs($closest->created_at->getTimestamp() - $updatedAt) > 300) {
            return null;
        }

        return $closest;
    }

    private function normalizeInvoiceDate(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value) && !is_int($value)) {
            throw ValidationException::withMessages([$field => 'La fecha debe enviarse como texto.']);
        }
        if (trim((string) $value) === '') return null;

        $input = mb_strtolower(trim(str_replace(',', '', Str::ascii((string) $value))));
        $input = preg_replace('/^(el|la)\\s+/', '', $input);
        $today = now()->startOfDay();
        if (in_array($input, ['hoy', 'today'], true)) {
            return $today->toDateString();
        }
        if (in_array($input, ['ayer', 'yesterday'], true)) {
            return $today->subDay()->toDateString();
        }
        if (in_array($input, ['anteayer'], true)) {
            return $today->subDays(2)->toDateString();
        }
        if (in_array($input, ['manana', 'tomorrow'], true)) {
            return $today->addDay()->toDateString();
        }

        $year = now()->year;
        $day = null;
        $month = null;
        if (preg_match('/^(\\d{1,2})[\\/-](\\d{1,2})(?:[\\/-](\\d{2,4}))?$/', $input, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            if (!empty($matches[3])) {
                $year = (int) $matches[3];
                if ($year < 100) $year += 2000;
            }
        } elseif (preg_match('/^(\\d{1,2})\\s+(?:de\\s+)?([a-z]+)(?:\\s+(?:(?:de|del)\\s+)?(\\d{4}))?$/', $input, $matches)) {
            $day = (int) $matches[1];
            $month = [
                'ene' => 1, 'enero' => 1, 'feb' => 2, 'febrero' => 2,
                'mar' => 3, 'marzo' => 3, 'abr' => 4, 'abril' => 4,
                'may' => 5, 'mayo' => 5, 'jun' => 6, 'junio' => 6,
                'jul' => 7, 'julio' => 7, 'ago' => 8, 'agosto' => 8,
                'sep' => 9, 'sept' => 9, 'septiembre' => 9, 'setiembre' => 9,
                'oct' => 10, 'octubre' => 10, 'nov' => 11, 'noviembre' => 11,
                'dic' => 12, 'diciembre' => 12,
            ][$matches[2]] ?? null;
            if (!empty($matches[3])) $year = (int) $matches[3];
        } elseif (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $input)) {
            [$isoYear, $isoMonth, $isoDay] = array_map('intval', explode('-', $input));
            if (checkdate($isoMonth, $isoDay, $isoYear)) {
                return CarbonImmutable::create($isoYear, $isoMonth, $isoDay)->toDateString();
            }
        }

        if ($day !== null && $month !== null && checkdate($month, $day, $year)) {
            return CarbonImmutable::create($year, $month, $day)->toDateString();
        }

        throw ValidationException::withMessages([
            $field => 'Usa hoy, ayer o una fecha como 10/11, 10-11-2026 o 10 de noviembre.',
        ]);
    }

    private function normalizeInvoiceAmount(mixed $value, string $field): ?int
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value) && !is_int($value)) {
            throw ValidationException::withMessages([$field => 'El monto debe enviarse como texto o número entero.']);
        }
        if (trim((string) $value) === '') return null;

        $digits = preg_replace('/\\D/', '', (string) $value);
        if ($digits === '' || strlen($digits) > 12) {
            throw ValidationException::withMessages([
                $field => 'Indica un monto en pesos, con o sin separadores de miles.',
            ]);
        }

        return (int) $digits;
    }

    /** List provider invoices due this month, overdue invoices, or all pending invoices. */
    public function invoiceSchedule(array $arguments): array
    {
        $validated = Validator::make($arguments, [
            'mode' => ['required', Rule::in(['due_before_month_end', 'overdue', 'pending'])],
            'party_name' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'limit' => ['required', 'integer', 'min:1', 'max:20'],
        ])->validate();

        $today = now()->toDateString();
        $year = (int) ($validated['year'] ?? now()->year);
        $startOfYear = now()->setDate($year, 1, 1)->startOfYear();
        $endOfYear = $startOfYear->copy()->endOfYear();
        $query = Factura::query()->with(['cliente:id,name', 'proveedor:id,name'])
            ->where('status', 0);

        if ($validated['mode'] === 'due_before_month_end') {
            $monthStart = $year === now()->year
                ? now()->startOfDay()
                : $startOfYear->copy()->month(now()->month)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $query->whereBetween('expiry', [$monthStart->toDateString(), $monthEnd->toDateString()]);
        } elseif ($validated['mode'] === 'overdue') {
            $query->whereNotNull('expiry')->whereYear('expiry', $year)->whereDate('expiry', '<', $today);
        } else {
            $query->where(fn ($q) => $q
                ->whereYear('expiry', $year)
                ->orWhere(fn ($q) => $q->whereNull('expiry')->whereYear('date', $year)));
        }

        $partyName = trim((string) ($validated['party_name'] ?? ''));
        if ($partyName !== '') {
            $like = $this->likePattern($partyName);
            $query->where(fn ($q) => $q
                ->whereHas('cliente', fn ($party) => $party->where('name', 'like', $like))
                ->orWhereHas('proveedor', fn ($party) => $party->where('name', 'like', $like)));
        }

        $records = $query->orderByRaw('expiry IS NULL')->orderBy('expiry')->limit((int) $validated['limit'])
            ->get(['id', 'invoice', 'client_id', 'provider_id', 'date', 'expiry', 'amount', 'status'])
            ->map(fn (Factura $invoice) => [
                'id' => $invoice->id, 'number' => $invoice->invoice,
                'party_type' => $invoice->client_id ? 'client' : 'provider',
                'party_name' => $invoice->client_id ? $invoice->cliente?->name : $invoice->proveedor?->name,
                'invoice_date' => $invoice->date?->toDateString(),
                'due_date' => $invoice->expiry?->toDateString(),
                'amount' => (int) $invoice->amount, 'currency' => 'CLP',
                'status' => $invoice->expiry && $invoice->expiry->lt(now()->startOfDay()) ? 'overdue' : 'pending',
                'days_overdue' => $invoice->expiry && $invoice->expiry->lt(now()->startOfDay())
                    ? (int) $invoice->expiry->diffInDays(now()->startOfDay()) : 0,
            ])->all();

        return ['mode' => $validated['mode'], 'year' => $year, 'as_of' => $today, 'count' => count($records), 'records' => $records];
    }

    /** Aggregate unpaid invoice amounts by provider, including overdue portions. */
    public function providerBalances(array $arguments): array
    {
        $validated = Validator::make($arguments, [
            'limit' => ['required', 'integer', 'min:1', 'max:20'],
        ])->validate();

        $today = now()->toDateString();
        $records = \Illuminate\Support\Facades\DB::table('invoices')
            ->join('providers', 'providers.id', '=', 'invoices.provider_id')
            ->whereNotNull('invoices.provider_id')->where('invoices.status', 0)
            ->groupBy('providers.id', 'providers.name')
            ->orderByDesc('pending_amount')
            ->limit((int) $validated['limit'])
            ->get([
                'providers.id as provider_id', 'providers.name as provider_name',
                \Illuminate\Support\Facades\DB::raw('COUNT(invoices.id) as pending_count'),
                \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(invoices.amount), 0) as pending_amount'),
                \Illuminate\Support\Facades\DB::raw("COALESCE(SUM(CASE WHEN invoices.expiry IS NOT NULL AND invoices.expiry < '{$today}' THEN invoices.amount ELSE 0 END), 0) as overdue_amount"),
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN invoices.expiry IS NOT NULL AND invoices.expiry < '{$today}' THEN 1 ELSE 0 END) as overdue_count"),
            ])->map(fn ($row) => [
                'provider_id' => (int) $row->provider_id, 'provider_name' => $row->provider_name,
                'pending_count' => (int) $row->pending_count, 'pending_amount' => (int) $row->pending_amount,
                'overdue_count' => (int) $row->overdue_count, 'overdue_amount' => (int) $row->overdue_amount,
                'currency' => 'CLP',
            ])->all();

        return ['as_of' => $today, 'count' => count($records), 'providers' => $records];
    }

    /** Find a few short excerpts in previously processed invoice PDFs. */
    public function searchInvoiceDocuments(array $arguments): array
    {
        $validated = Validator::make($arguments, [
            'term' => ['required', 'string', 'min:2', 'max:120'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'limit' => ['required', 'integer', 'min:1', 'max:3'],
        ])->validate();

        $term = trim($validated['term']);
        $words = preg_split('/[^\pL\pN]+/u', mb_strtolower($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terms = array_slice(array_values(array_unique(array_filter($words, fn ($word) => mb_strlen($word) >= 3))), 0, 5);
        if ($terms === []) {
            $terms = [$term];
        }

        $invoiceNumber = trim((string) ($validated['invoice_number'] ?? ''));
        $baseQuery = InvoiceDocumentContext::query()
            ->with(['invoice:id,invoice'])
            ->when($invoiceNumber !== '', fn ($query) => $query->whereHas(
                'invoice',
                fn ($invoice) => $invoice->where('invoice', $invoiceNumber)
            ));

        $limit = (int) $validated['limit'];
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql') {
            $documents = (clone $baseQuery)
                ->whereFullText('markdown', implode(' ', $terms))
                ->latest('processed_at')
                ->limit($limit)
                ->get(['id', 'invoice_id', 'file_registry_id', 'markdown', 'processed_at']);
        } else {
            $documents = collect();
        }

        if ($documents->isEmpty()) {
            $documents = (clone $baseQuery)
                ->where(function ($query) use ($terms) {
                    foreach ($terms as $word) {
                        $query->orWhere('markdown', 'like', $this->likePattern($word));
                    }
                })
                ->latest('processed_at')
                ->limit($limit)
                ->get(['id', 'invoice_id', 'file_registry_id', 'markdown', 'processed_at']);
        }

        return [
            'count' => $documents->count(),
            'documents' => $documents->map(fn (InvoiceDocumentContext $document) => [
                'invoice_number' => $document->invoice?->invoice,
                'file_id' => $document->file_registry_id,
                'processed_at' => $document->processed_at?->toDateString(),
                'excerpt' => $this->excerpt($document->markdown, $terms),
            ])->all(),
        ];
    }

    private function searchInvoices(string $term, string $like, int $limit): array
    {
        $query = Factura::query()
            ->with(['cliente:id,name', 'proveedor:id,name'])
            ->select(['id', 'invoice', 'client_id', 'provider_id', 'amount', 'date', 'expiry', 'status']);

        if ($term !== '') {
            $query->where(function ($query) use ($term, $like) {
                $query->where('invoice', 'like', $like)
                    ->orWhereHas('cliente', fn ($party) => $party->where('name', 'like', $like))
                    ->orWhereHas('proveedor', fn ($party) => $party->where('name', 'like', $like));

                $digits = preg_replace('/[^0-9]/', '', $term);
                if ($digits !== '' && ctype_digit($digits)) {
                    $query->orWhere('amount', (int) $digits);
                }
            });
        }

        return $query->orderByDesc('date')->limit($limit)->get()->map(fn (Factura $invoice) => [
            'id' => $invoice->id,
            'number' => $invoice->invoice,
            'party_type' => $invoice->client_id ? 'client' : 'provider',
            'party_name' => $invoice->client_id ? $invoice->cliente?->name : $invoice->proveedor?->name,
            'amount' => (int) $invoice->amount,
            'currency' => 'CLP',
            'date' => $invoice->date?->toDateString(),
            'due_date' => $invoice->expiry?->toDateString(),
            'status' => $this->invoiceStatus($invoice),
        ])->all();
    }

    private function searchQuotations(string $term, string $like, int $limit): array
    {
        $query = Cotizacion::query()->with('cliente:id,name,rut')
            ->select(['id', 'client_id', 'agent', 'date', 'total', 'work']);

        if ($term !== '') {
            $query->where(function ($query) use ($term, $like) {
                $query->where('work', 'like', $like)
                    ->orWhere('agent', 'like', $like)
                    ->orWhereHas('cliente', fn ($client) => $client
                        ->where('name', 'like', $like)->orWhere('rut', 'like', $like));

                if (ctype_digit($term)) {
                    $query->orWhere('id', (int) $term)->orWhere('total', (int) $term);
                }
            });
        }

        return $query->orderByDesc('date')->limit($limit)->get()->map(fn (Cotizacion $quote) => [
            'id' => $quote->id, 'number' => (string) $quote->id,
            'client_id' => $quote->client_id, 'client_name' => $quote->cliente?->name,
            'client_rut' => $quote->cliente?->rut, 'date' => $quote->date?->toDateString(),
            'work' => $quote->work, 'amount' => (int) $quote->total, 'currency' => 'CLP',
        ])->all();
    }

    private function formatInvoiceDetails(Factura $invoice): array
    {
        $party = $invoice->client_id ? $invoice->cliente : $invoice->proveedor;
        $contexts = $invoice->documentContexts->keyBy('file_registry_id');
        $files = $invoice->archivos->map(function ($file) use ($contexts) {
            $context = $contexts->get($file->id);
            $extension = strtolower((string) pathinfo($file->file_name ?: $file->path, PATHINFO_EXTENSION));
            $isPdf = $extension === 'pdf' || strtolower((string) $file->mime_type) === 'application/pdf';

            return [
                'file_id' => $file->id, 'name' => $file->file_name,
                'mime_type' => $file->mime_type, 'size_bytes' => (int) $file->size,
                'is_pdf' => $isPdf, 'pdf_processed' => $isPdf && $context !== null && $context->processed_at !== null,
                'processed_at' => $context?->processed_at?->toDateString(),
            ];
        })->values();

        return [
            'id' => $invoice->id, 'number' => $invoice->invoice,
            'party_type' => $invoice->client_id ? 'client' : 'provider',
            'party' => $party ? [
                'id' => $party->id, 'name' => $party->name, 'rut' => $party->rut,
                'email' => $party->email, 'phone' => $party->phone, 'address' => $party->address,
            ] : null,
            'date' => $invoice->date?->toDateString(), 'due_date' => $invoice->expiry?->toDateString(),
            'paid_date' => $invoice->pay_date?->toDateString(), 'amount' => (int) $invoice->amount,
            'currency' => 'CLP', 'status' => $this->invoiceStatus($invoice),
            'payment_verified' => (bool) $invoice->check, 'payment_method' => $invoice->metodoPago?->name,
            'detail' => $invoice->detail, 'extra' => $invoice->extra,
            'files' => $files->all(),
            'processed_pdf_count' => $invoice->documentContexts
                ->whereNotNull('processed_at')->count(),
        ];
    }

    private function likePattern(string $term): string
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return '%' . $escaped . '%';
    }

    private function invoiceStatus(Factura $invoice): string
    {
        if ((int) $invoice->status === 1) {
            return 'paid';
        }

        return $invoice->expiry && $invoice->expiry->lt(now()->startOfDay()) ? 'overdue' : 'pending';
    }

    private function excerpt(string $markdown, array $terms): string
    {
        $position = false;
        foreach ($terms as $term) {
            $position = mb_stripos($markdown, $term);
            if ($position !== false) {
                break;
            }
        }

        $length = mb_strlen($markdown);
        $start = $position === false ? 0 : max(0, $position - 300);
        $excerpt = trim(mb_substr($markdown, $start, 1200));

        if ($start > 0) {
            $excerpt = '…' . $excerpt;
        }
        if ($start + 1200 < $length) {
            $excerpt .= '…';
        }

        return $excerpt;
    }
}
