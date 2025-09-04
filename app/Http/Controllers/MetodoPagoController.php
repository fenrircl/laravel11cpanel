<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\Request;
use App\Services\AuditLogger;

class MetodoPagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $metodosPago = MetodoPago::active()->orderBy('name')->get();
        return view('metodos-pago.index', compact('metodosPago'));
    }

    /**
     * Get payment methods data for API/AJAX requests
     */
    public function getData()
    {
        $metodosPago = MetodoPago::active()
                                ->select(['id', 'name',  'is_active'])
                                ->orderBy('name')
                                ->get();
        
        return response()->json([
            'data' => $metodosPago
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('metodos-pago.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100|unique:payment_methods',
            'is_active' => 'boolean'
        ]);

        $metodoPago = MetodoPago::create($validatedData);
        
        // Log auditoría
        AuditLogger::log($request, 'create', 'metodos_pago', $metodoPago->id, 'Creó método de pago ' . $metodoPago->name);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true, 
                'metodoPago' => $metodoPago
            ]);
        }
        
        return redirect()->route('metodos-pago.index')->with('success', 'Método de pago creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(MetodoPago $metodoPago)
    {
        if (request()->ajax()) {
            return response()->json(['metodoPago' => $metodoPago]);
        }
        
        return view('metodos-pago.show', compact('metodoPago'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MetodoPago $metodoPago)
    {
        return view('metodos-pago.edit', compact('metodoPago'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MetodoPago $metodoPago)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100|unique:payment_methods,name,' . $metodoPago->id,
            'is_active' => 'boolean'
        ]);

        $metodoPago->update($validatedData);
        
        // Log auditoría
        AuditLogger::log($request, 'update', 'metodos_pago', $metodoPago->id, 'Actualizó método de pago ' . $metodoPago->name);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true, 
                'metodoPago' => $metodoPago
            ]);
        }
        
        return redirect()->route('metodos-pago.index')->with('success', 'Método de pago actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MetodoPago $metodoPago)
    {
        // Verificar si el método de pago está siendo usado en facturas
        if ($metodoPago->facturas()->count() > 0) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar este método de pago porque está siendo usado en facturas.'
                ], 422);
            }
            
            return redirect()->route('metodos-pago.index')
                           ->with('error', 'No se puede eliminar este método de pago porque está siendo usado en facturas.');
        }
        
        $id = $metodoPago->id;
        $name = $metodoPago->name;
        $metodoPago->delete();
        
        // Log auditoría
        AuditLogger::log(request(), 'delete', 'metodos_pago', $id, 'Eliminó método de pago ' . $name);
        
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Método de pago eliminado exitosamente.']);
        }
        
        return redirect()->route('metodos-pago.index')->with('success', 'Método de pago eliminado exitosamente.');
    }

    /**
     * Toggle active status of payment method
     */
    public function toggleStatus(MetodoPago $metodoPago)
    {
        $metodoPago->update(['is_active' => !$metodoPago->is_active]);
        
        $status = $metodoPago->is_active ? 'activado' : 'desactivado';
        
        // Log auditoría
        AuditLogger::log(request(), 'update', 'metodos_pago', $metodoPago->id, 'Estado ' . $status . ' de método de pago ' . $metodoPago->name);
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Método de pago {$status} exitosamente.",
                'is_active' => $metodoPago->is_active
            ]);
        }
        
        return redirect()->route('metodos-pago.index')->with('success', "Método de pago {$status} exitosamente.");
    }
}
