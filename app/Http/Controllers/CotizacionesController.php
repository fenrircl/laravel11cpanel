<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogger;

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
            'items.*.amount' => 'required|integer|min:1',
            'items.*.price' => 'required', // se normaliza
        ]);

        DB::transaction(function() use ($request, $validated, $normalizeCLP) {
            // Calcular totales
            $itemsData = collect($validated['items'])->map(function($it) use ($normalizeCLP){
                $qty = (int) $it['amount'];
                $unit = $normalizeCLP($it['price']);
                $total = $qty * $unit;
                return [
                    'description' => $it['description'],
                    'amount' => $qty,
                    'price' => $unit,
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

            // Registrar auditoría (dentro de la transacción para contar con el ID)
            AuditLogger::log($request, 'create', 'cotizaciones', $cot->id, 'Creó cotización #' . $cot->id);
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
        // Log de visualización opcional
        AuditLogger::log(request(), 'view', 'cotizaciones', $cotizacion->id, 'Vio cotización #' . $cotizacion->id);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['cotizacion' => $cotizacion]);
        }
        return view('cotizacion.show', compact('cotizacion'));
    }

    public function edit(Cotizacion $cotizacion)
    {
        $cotizacion->load('items');
        $clientes = Cliente::select('id','name','rut')->orderBy('name')->get();
        return view('cotizacion.edit', compact('cotizacion','clientes'));
    }

    public function update(Request $request, Cotizacion $cotizacion)
    {
        $normalizeCLP = fn($v) => (int) preg_replace('/[^0-9]/', '', (string)($v ?? '0'));

        $validated = $request->validate([
            'agent' => 'required|string|max:100',
            'date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'work' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:cotizacion_items,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|integer|min:1',
            'items.*.price' => 'required',
        ]);

        DB::transaction(function() use ($request, $validated, $normalizeCLP, $cotizacion) {
            $itemsData = collect($validated['items'])->map(function($it) use ($normalizeCLP){
                $qty = (int) $it['amount'];
                $unit = $normalizeCLP($it['price']);
                $total = $qty * $unit;
                return [
                    'id' => $it['id'] ?? null,
                    'description' => $it['description'],
                    'amount' => $qty,
                    'price' => $unit,
                    'total' => $total,
                ];
            });

            $total = $itemsData->sum('total');

            $cotizacion->update([
                'agent' => $validated['agent'],
                'date' => $validated['date'],
                'client_id' => $validated['client_id'],
                'work' => $validated['work'] ?? null,
                'total' => $total,
            ]);

            // Sincronizar items: actualizar/crear según id, y eliminar los no presentes
            $existingIds = $cotizacion->items()->pluck('id')->toArray();
            $sentIds = [];

            foreach ($itemsData as $row) {
                if (!empty($row['id'])) {
                    $item = $cotizacion->items()->where('id', $row['id'])->first();
                    if ($item) {
                        $item->update($row);
                        $sentIds[] = $item->id;
                    }
                } else {
                    $item = $cotizacion->items()->create($row);
                    $sentIds[] = $item->id;
                }
            }

            $toDelete = array_diff($existingIds, $sentIds);
            if (!empty($toDelete)) {
                $cotizacion->items()->whereIn('id', $toDelete)->delete();
            }

            AuditLogger::log($request, 'update', 'cotizaciones', $cotizacion->id, 'Actualizó cotización #' . $cotizacion->id);
        });

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('cotizaciones.show', $cotizacion)->with('success', 'Cotización actualizada');
    }

    public function pdf(Cotizacion $cotizacion)
    {
        $cotizacion->load(['cliente', 'items']);
        if (method_exists($cotizacion, 'setRelation')) {
            $cotizacion->setRelation('client', $cotizacion->cliente);
        }
        $items = $cotizacion->items;
        $cityname = optional($cotizacion->cliente)->cityname;

        AuditLogger::log(request(), 'export_pdf', 'cotizaciones', $cotizacion->id, 'Exportó PDF cotización #' . $cotizacion->id);

        $data = [
            'asset_css' => ['cotizaciones/pdf'],
            'quotation' => $cotizacion,
            'item' => $items,
            'cityname' => $cityname,
        ];

        return view('cotizacion.pdf', $data);
    }

}
