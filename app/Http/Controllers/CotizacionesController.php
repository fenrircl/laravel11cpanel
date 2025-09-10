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
            // Calcular totales NETOS (sin IVA) a partir de los items
            $itemsData = collect($validated['items'])->map(function($it) use ($normalizeCLP){
                $qty = (int) $it['amount'];
                $unit = $normalizeCLP($it['price']); // Precio NETO unitario
                $total = $qty * $unit; // Total NETO por ítem
                return [
                    'description' => $it['description'],
                    'amount' => $qty,
                    'price' => $unit,
                    'total' => $total, // Neto
                ];
            });

            $netTotal = $itemsData->sum('total'); // Neto
            $grossTotal = (int) round($netTotal * 1.19); // Con IVA 19%

            $cot = Cotizacion::create([
                'agent' => $validated['agent'],
                'date' => $validated['date'],
                'client_id' => $validated['client_id'],
                'work' => $validated['work'] ?? null,
                'total' => $grossTotal, // Guardamos total con IVA
            ]);

            foreach ($itemsData as $row) {
                $cot->items()->create($row);
            }

            AuditLogger::log($request, 'create', 'cotizaciones', $cot->id, 'Creó cotización #'.$cot->id.' (Neto: '.number_format($netTotal,0,',','.').' IVA: '.number_format($grossTotal-$netTotal,0,',','.').' Total: '.number_format($grossTotal,0,',','.').')');
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
            'items.*.id' => 'nullable|integer|exists:quotation_items,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|integer|min:1',
            'items.*.price' => 'required',
        ]);

        DB::transaction(function() use ($request, $validated, $normalizeCLP, $cotizacion) {
            $itemsData = collect($validated['items'])->map(function($it) use ($normalizeCLP){
                $qty = (int) $it['amount'];
                $unit = $normalizeCLP($it['price']); // Precio NETO unitario
                $total = $qty * $unit; // Neto por ítem
                return [
                    'id' => $it['id'] ?? null,
                    'description' => $it['description'],
                    'amount' => $qty,
                    'price' => $unit,
                    'total' => $total, // Neto
                ];
            });

            $netTotal = $itemsData->sum('total');
            $grossTotal = (int) round($netTotal * 1.19);

            $cotizacion->update([
                'agent' => $validated['agent'],
                'date' => $validated['date'],
                'client_id' => $validated['client_id'],
                'work' => $validated['work'] ?? null,
                'total' => $grossTotal,
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

            AuditLogger::log($request, 'update', 'cotizaciones', $cotizacion->id, 'Actualizó cotización #'.$cotizacion->id.' (Neto: '.number_format($netTotal,0,',','.').' IVA: '.number_format($grossTotal-$netTotal,0,',','.').' Total: '.number_format($grossTotal,0,',','.').')');
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

    public function destroy(Request $request, Cotizacion $cotizacion)
    {
        $id = $cotizacion->id;
        DB::transaction(function() use ($request, $cotizacion, $id) {
            // Borrar items explícitamente si la relación no tiene cascade
            if (method_exists($cotizacion, 'items')) {
                $cotizacion->items()->delete();
            }
            $cotizacion->delete();
            AuditLogger::log($request, 'delete', 'cotizaciones', $id, 'Eliminó cotización #' . $id);
        });

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización eliminada');
    }
}
