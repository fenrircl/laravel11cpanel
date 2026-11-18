<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantUsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class KpiController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) $request->query('year', now()->year);
        if ($year < 2000 || $year > now()->year + 1) {
            $year = now()->year;
        }

        $metrics = Cache::remember('admin.kpi.dashboard.' . $year, now()->addMinutes(5), function () use ($year): array {
            $from = sprintf('%04d-01-01', $year);
            $until = sprintf('%04d-01-01', $year + 1);
            $hasClients = Schema::hasTable('clients');
            $hasProviders = Schema::hasTable('providers');
            $hasInvoices = Schema::hasTable('invoices');
            $hasQuotations = Schema::hasTable('quotations');
            $hasFiles = Schema::hasTable('files_registry');
            $hasExtractions = Schema::hasTable('invoice_extractions');
            $hasLineItems = Schema::hasTable('invoice_line_items');
            $hasIngestions = Schema::hasTable('document_ingestions');
            $hasAssistantUsage = Schema::hasTable('assistant_usage_logs');

            $invoices = static fn () => DB::table('invoices')->where('date', '>=', $from)->where('date', '<', $until);
            $quotations = static fn () => DB::table('quotations')->where('date', '>=', $from)->where('date', '<', $until);
            $monthly = [
                'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                'quotation_count' => array_fill(0, 12, 0),
                'quotation_amount' => array_fill(0, 12, 0),
                'client_invoice_count' => array_fill(0, 12, 0),
                'client_invoice_amount' => array_fill(0, 12, 0),
                'provider_invoice_count' => array_fill(0, 12, 0),
                'provider_invoice_amount' => array_fill(0, 12, 0),
            ];
            $monthExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%m', date) AS INTEGER)"
                : 'MONTH(date)';

            if ($hasQuotations) {
                $monthlyQuotes = $quotations()
                    ->selectRaw($monthExpression . ' as month_number, COUNT(*) as row_count, COALESCE(SUM(total), 0) as amount_sum')
                    ->groupBy('month_number')->get();
                foreach ($monthlyQuotes as $row) {
                    $index = (int) $row->month_number - 1;
                    if ($index >= 0 && $index < 12) {
                        $monthly['quotation_count'][$index] = (int) $row->row_count;
                        $monthly['quotation_amount'][$index] = (float) $row->amount_sum;
                    }
                }
            }

            $invoiceTypes = [
                'client' => ['column' => 'client_id', 'count_key' => 'client_invoice_count', 'amount_key' => 'client_invoice_amount'],
                'provider' => ['column' => 'provider_id', 'count_key' => 'provider_invoice_count', 'amount_key' => 'provider_invoice_amount'],
            ];
            if ($hasInvoices) {
                foreach ($invoiceTypes as $type) {
                    $rows = $invoices()->whereNotNull($type['column'])
                        ->selectRaw($monthExpression . ' as month_number, COUNT(*) as row_count, COALESCE(SUM(amount), 0) as amount_sum')
                        ->groupBy('month_number')->get();
                    foreach ($rows as $row) {
                        $index = (int) $row->month_number - 1;
                        if ($index >= 0 && $index < 12) {
                            $monthly[$type['count_key']][$index] = (int) $row->row_count;
                            $monthly[$type['amount_key']][$index] = (float) $row->amount_sum;
                        }
                    }
                }
            }

            $values = [
                'users' => DB::table('users')->count(),
                'active_users' => Schema::hasColumn('users', 'is_active') ? DB::table('users')->where('is_active', true)->count() : DB::table('users')->count(),
                'clients' => $hasClients ? DB::table('clients')->count() : 0,
                'providers' => $hasProviders ? DB::table('providers')->count() : 0,
                'quotations' => $hasQuotations ? $quotations()->count() : 0,
                'quotation_amount' => $hasQuotations ? (float) $quotations()->sum('total') : 0,
                'client_invoices' => $hasInvoices ? $invoices()->whereNotNull('client_id')->count() : 0,
                'client_invoice_amount' => $hasInvoices ? (float) $invoices()->whereNotNull('client_id')->sum('amount') : 0,
                'provider_invoices' => $hasInvoices ? $invoices()->whereNotNull('provider_id')->count() : 0,
                'provider_invoice_amount' => $hasInvoices ? (float) $invoices()->whereNotNull('provider_id')->sum('amount') : 0,
                'invoice_pdfs' => 0,
                'pdf_files' => 0,
                'pdf_processed_invoices' => 0,
                'pdf_line_items' => 0,
                'pdf_pending_items' => 0,
                'ingestion_statuses' => [],
                'assistant_requests' => 0,
                'assistant_input_tokens' => 0,
                'assistant_output_tokens' => 0,
                'assistant_total_tokens' => 0,
                'assistant_failed' => 0,
            ];

            if ($hasInvoices && $hasFiles) {
                $pdfLinks = DB::table('files_registry as f')
                    ->select('f.id', 'f.model_id')
                    ->where('f.model_type', 'App\\Invoice')
                    ->where(function ($query): void {
                        $query->where('f.mime_type', 'application/pdf')
                            ->orWhere('f.file_name', 'like', '%.pdf')
                            ->orWhere('f.path', 'like', '%.pdf');
                    });
                $pdfInvoiceIds = (clone $pdfLinks)->select('model_id')->distinct();
                $values['pdf_files'] = DB::table('files_registry as f')
                    ->join('invoices as i', 'i.id', '=', 'f.model_id')
                    ->where('f.model_type', 'App\\Invoice')
                    ->where(function ($query): void {
                        $query->where('f.mime_type', 'application/pdf')
                            ->orWhere('f.file_name', 'like', '%.pdf')
                            ->orWhere('f.path', 'like', '%.pdf');
                    })
                    ->where('i.date', '>=', $from)->where('i.date', '<', $until)->count();
                $values['invoice_pdfs'] = $invoices()->whereIn('id', $pdfInvoiceIds)->count();

                if ($hasExtractions && $hasLineItems) {
                    $processed = DB::table('invoice_extractions as e')
                        ->join('invoice_line_items as li', 'li.extraction_id', '=', 'e.id')
                        ->join('invoices as i', 'i.id', '=', 'e.invoice_id')
                        ->where('e.validation_status', 'validated')
                        ->where('e.is_active', true)
                        ->where('i.date', '>=', $from)->where('i.date', '<', $until);
                    $values['pdf_processed_invoices'] = (clone $processed)->distinct('e.invoice_id')->count('e.invoice_id');
                    $values['pdf_line_items'] = (clone $processed)->count('li.id');
                    $values['pdf_pending_items'] = max(0, $values['invoice_pdfs'] - $values['pdf_processed_invoices']);
                }
            }

            if ($hasIngestions) {
                $values['ingestion_statuses'] = DB::table('document_ingestions')
                    ->selectRaw('status, COUNT(*) as row_count')
                    ->where('created_at', '>=', $from)->where('created_at', '<', $until)
                    ->groupBy('status')->orderBy('status')->pluck('row_count', 'status')->map(fn ($count) => (int) $count)->all();
            }

            if ($hasAssistantUsage) {
                $usage = AssistantUsageLog::query()->where('created_at', '>=', $from)->where('created_at', '<', $until);
                $values['assistant_requests'] = (clone $usage)->count();
                $values['assistant_input_tokens'] = (int) (clone $usage)->sum('input_tokens');
                $values['assistant_output_tokens'] = (int) (clone $usage)->sum('output_tokens');
                $values['assistant_total_tokens'] = (int) (clone $usage)->sum('total_tokens');
                $values['assistant_failed'] = (clone $usage)->where('status', 'failed')->count();
            }

            return ['values' => $values, 'monthly' => $monthly];
        });

        return view('admin.kpi.index', [
            'year' => $year,
            'yearOptions' => range(max(2020, now()->year - 5), now()->year),
            'metrics' => $metrics['values'],
            'monthly' => $metrics['monthly'],
            'asset_js' => [],
        ]);
    }
}
