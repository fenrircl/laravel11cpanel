<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Factura;

class ChartsController extends Controller
{
    /**
     * Datos para gráfico de facturas pendientes por cliente y proveedor
     * Query params: from=YYYY-MM-DD, to=YYYY-MM-DD
     */
    public function facturasPendientes(Request $request)
    {
        $to = $request->query('to');
        $from = $request->query('from');

        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->copy()->subMonths(3)->startOfDay();

        // Clientes pendientes
        $clientesQuery = Factura::with(['cliente:id,name'])
            ->whereNotNull('client_id')
            ->pending()
            ->whereBetween('date', [$fromDate, $toDate])
            ->get();

        $clientes = $clientesQuery
            ->groupBy('client_id')
            ->map(function ($group, $clientId) {
                $first = $group->first();
                return [
                    'id' => (int) $clientId,
                    'name' => $first->cliente?->name ?? '—',
                    'total' => (int) $group->sum('amount')
                ];
            })
            ->values()
            ->sortByDesc('total')
            ->values();

        // Proveedores pendientes
        $proveedoresQuery = Factura::with(['proveedor:id,name'])
            ->whereNotNull('provider_id')
            ->pending()
            ->whereBetween('date', [$fromDate, $toDate])
            ->get();

        $proveedores = $proveedoresQuery
            ->groupBy('provider_id')
            ->map(function ($group, $providerId) {
                $first = $group->first();
                return [
                    'id' => (int) $providerId,
                    'name' => $first->proveedor?->name ?? '—',
                    'total' => (int) $group->sum('amount')
                ];
            })
            ->values()
            ->sortByDesc('total')
            ->values();

        return response()->json([
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'clientes' => $clientes,
            'proveedores' => $proveedores,
            'totals' => [
                'clientes' => (int) $clientes->sum('total'),
                'proveedores' => (int) $proveedores->sum('total')
            ]
        ]);
    }
}
