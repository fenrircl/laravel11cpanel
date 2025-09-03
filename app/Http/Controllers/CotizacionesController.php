<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CotizacionesController extends Controller
{
    public function index()
    {
        $data["asset_css"] = ['comun/tablas', 'cotizaciones/cotizaciones'];
        $data["asset_js"] = ['cotizaciones/cotizaciones'];
        return view('cotizacion.index', $data);
    }

    public function create()
    {
        $clientes = Cliente::select('id','name','rut')->orderBy('name')->get();
        return view('cotizacion.create', compact('clientes'));
    }

    public function store(Request $request)
    {
        // Normalizar total y precios (CLP)
        $normalizeCLP = fn($v) => (int) preg_replace('/[^0-9]/', '', (string)($v ?? '0'));

        $validated = $request->validate([
            'agent' => 'required|string|max:100',
            'date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'work' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required', // se normaliza
        ]);

        DB::transaction(function() use ($request, $validated, $normalizeCLP) {
            // Calcular totales
            $itemsData = collect($validated['items'])->map(function($it) use ($normalizeCLP){
                $qty = (int) $it['quantity'];
                $unit = $normalizeCLP($it['unit_price']);
                $total = $qty * $unit;
                return [
                    'description' => $it['description'],
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'total' => $total,
                ];
            });

            $total = $itemsData->sum('total');

            $cot = Cotizacion::create([
                'agent' => $validated['agent'],
                'date' => $validated['date'],
                'client_id' => $validated['client_id'],
                'work' => $validated['work'] ?? null,
                'total' => $total,
            ]);

            foreach ($itemsData as $row) {
                $cot->items()->create($row);
            }
        });

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('cotizaciones.index')->with('success', 'Cotización creada');
    }

    public function getData()
    {
        $cotizaciones = Cotizacion::with(['cliente:id,name,rut'])
            ->select(['id','client_id','agent','date','total','work','created_at'])
            ->orderByDesc('created_at')
            ->get();
        return response()->json(['data' => $cotizaciones]);
    }

    public function show(Cotizacion $cotizacion)
    {
        $cotizacion->load(['cliente','items']);
        return view('cotizacion.show', compact('cotizacion'));
    }
}
