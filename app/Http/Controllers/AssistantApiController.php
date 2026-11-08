<?php

namespace App\Http\Controllers;

use App\Services\AssistantDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantApiController extends Controller
{
    public function search(Request $request, AssistantDataService $data): JsonResponse
    {
        $arguments = $request->validate([
            'entity' => ['required', 'in:clients,providers,invoices,quotations'],
            'term' => ['nullable', 'string', 'max:120'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:5'],
        ]);
        $arguments['limit'] = (int) ($arguments['limit'] ?? 5);

        return response()->json(['data' => $data->searchRecords($arguments)]);
    }

    public function invoiceSummary(Request $request, AssistantDataService $data): JsonResponse
    {
        $arguments = $request->validate([
            'party_type' => ['required', 'in:all,client,provider'],
            'party_name' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:all,paid,pending,overdue'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'date_basis' => ['sometimes', 'in:invoice,due,payment'],
        ]);

        return response()->json(['data' => $data->summarizeInvoices($arguments)]);
    }

    public function partyRecords(Request $request, AssistantDataService $data): JsonResponse
    {
        $arguments = $request->validate([
            'party_type' => ['required', 'in:client,provider'],
            'party_id' => ['required', 'integer', 'min:1'],
            'record_type' => ['required', 'in:all,invoices,quotations'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);
        $arguments['limit'] = (int) ($arguments['limit'] ?? 10);

        return response()->json(['data' => $data->listPartyRecords($arguments)]);
    }

    public function invoiceDetails(Request $request, AssistantDataService $data): JsonResponse
    {
        $arguments = $request->validate([
            'invoice_id' => ['nullable', 'integer', 'min:1'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
        ]);

        return response()->json(['data' => $data->invoiceDetails($arguments)]);
    }

    public function quotationDetails(Request $request, AssistantDataService $data): JsonResponse
    {
        $arguments = $request->validate(['quotation_id' => ['required', 'integer', 'min:1']]);

        return response()->json(['data' => $data->quotationDetails($arguments)]);
    }

    public function invoiceSchedule(Request $request, AssistantDataService $data): JsonResponse
    {
        $arguments = $request->validate([
            'mode' => ['required', 'in:due_before_month_end,overdue,pending'],
            'party_name' => ['nullable', 'string', 'max:120'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);
        $arguments['limit'] = (int) ($arguments['limit'] ?? 20);

        return response()->json(['data' => $data->invoiceSchedule($arguments)]);
    }

    public function providerBalances(Request $request, AssistantDataService $data): JsonResponse
    {
        $arguments = $request->validate(['limit' => ['sometimes', 'integer', 'min:1', 'max:20']]);
        $arguments['limit'] = (int) ($arguments['limit'] ?? 20);

        return response()->json(['data' => $data->providerBalances($arguments)]);
    }
}
