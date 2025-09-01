<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacturasController extends Controller
{
    /**
     * Display a listing of all invoices (clients and providers)
     */
    public function index()
    {
        $data["asset_css"] = ['comun/tablas', 'facturas/facturas'];
        $data["asset_js"] = ['facturas/facturas'];
        return view('facturas.index', $data);
    }

    /**
     * Display client invoices only
     */
    public function clienteIndex()
    {
        $data["asset_css"] = ['comun/tablas', 'facturas/facturas'];
        $data["asset_js"] = ['facturas/facturas-cliente'];
        return view('facturas.clientes.index', $data);
    }

    /**
     * Display provider invoices only
     */
    public function proveedorIndex()
    {
        $data["asset_css"] = ['comun/tablas', 'facturas/facturas'];
        $data["asset_js"] = ['facturas/facturas-proveedor'];
        return view('facturas.proveedores.index', $data);
    }

    /**
     * Get all invoices data for DataTables via API
     */
    public function getData()
    {
        $facturas = Factura::with(['cliente:id,name', 'proveedor:id,name', 'metodoPago:id,name', 'archivo'])
                          ->select(['id', 'invoice', 'client_id', 'provider_id', 'date', 'expiry', 'pay_date', 'amount', 'payment_method_id', 'status', 'created_at', 'updated_at', 'detail'])
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->map(function ($factura) {
                              // Agregar tipo de factura según si tiene cliente o proveedor
                              $factura->tipo = $factura->client_id ? 'cliente' : 'proveedor';
                              $factura->entidad_nombre = $factura->client_id ? $factura->cliente?->name : $factura->proveedor?->name;
                              // Agregar información del archivo
                              $factura->has_file = $factura->archivo ? true : false;
                              $factura->file_path = $factura->archivo->path ?? null;
                              return $factura;
                          });
        
        return response()->json([
            'data' => $facturas
        ]);
    }

    /**
     * Get client invoices data for DataTables via API
     */
    public function getClienteData()
    {
        $facturas = Factura::with(['cliente:id,name', 'metodoPago:id,name', 'archivo'])
                          ->whereNotNull('client_id')
                          ->select(['id', 'invoice', 'client_id', 'date', 'expiry', 'pay_date', 'amount', 'payment_method_id', 'status', 'created_at', 'updated_at','detail'])
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->map(function ($factura) {
                              $factura->has_file = $factura->archivo ? true : false;
                              $factura->file_path = $factura->archivo->path ?? null;
                              return $factura;
                          });
        
        return response()->json([
            'data' => $facturas
        ]);
    }

    /**
     * Get provider invoices data for DataTables via API
     */
    public function getProveedorData()
    {
        $facturas = Factura::with(['proveedor:id,name', 'metodoPago:id,name', 'archivo'])
                          ->whereNotNull('provider_id')
                          ->select(['id', 'invoice', 'provider_id', 'date', 'expiry', 'pay_date', 'amount', 'payment_method_id', 'status', 'created_at', 'updated_at','detail'])
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->map(function ($factura) {
                              $factura->has_file = $factura->archivo ? true : false;
                              $factura->file_path = $factura->archivo->path ?? null;
                              return $factura;
                          });
        
        return response()->json([
            'data' => $facturas
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clientes = Cliente::select('id', 'name')->orderBy('name')->get();
        $proveedores = Proveedor::select('id', 'name')->orderBy('name')->get();
        $metodosPago = MetodoPago::select('id', 'name')->orderBy('name')->get();
        return view('facturas.create', compact('clientes', 'proveedores', 'metodosPago'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar que solo tenga cliente O proveedor, no ambos
        if (($request->input('client_id') && $request->input('provider_id')) || 
            (!$request->input('client_id') && !$request->input('provider_id'))) {
            return response()->json([
                'success' => false, 
                'message' => 'Debe seleccionar un cliente O un proveedor, no ambos.'
            ], 422);
        }

        // Crear regla de validación unique condicional basada en si es cliente o proveedor
        $invoiceUniqueRule = Rule::unique('invoices', 'invoice');
        
        if ($request->input('client_id')) {
            // Para facturas de cliente: debe ser único entre facturas de cliente
            $invoiceUniqueRule->whereNotNull('client_id');
        } else {
            // Para facturas de proveedor: debe ser único entre facturas de proveedor
            $invoiceUniqueRule->whereNotNull('provider_id');
        }

        $validatedData = $request->validate([
            'invoice' => ['required', 'string', 'max:50', $invoiceUniqueRule],
            'client_id' => 'nullable|exists:clients,id',
            'provider_id' => 'nullable|exists:providers,id',
            'date' => 'required|date',
            'expiry' => 'nullable|date|after_or_equal:date',
            'pay_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'check' => 'nullable|string|max:100',
            'payment_method_id' => 'nullable|exists:payment_methods,id', // No requerido al crear
            'detail' => 'nullable|string|max:1000',
            'status' => 'required|in:0,1', // 0 = pendiente, 1 = pagado
        ]);

        $factura = Factura::create($validatedData);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true, 
                'factura' => $factura->load(['cliente', 'proveedor', 'metodoPago'])
            ]);
        }
        
        return redirect()->route('facturas.index')->with('success', 'Factura creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Factura $factura)
    {
        $factura->load(['cliente', 'proveedor', 'metodoPago']);
        
        if (request()->ajax()) {
            return response()->json(['factura' => $factura]);
        }
        
        return view('facturas.show', compact('factura'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Factura $factura)
    {
        $clientes = Cliente::select('id', 'name')->orderBy('name')->get();
        $proveedores = Proveedor::select('id', 'name')->orderBy('name')->get();
        $metodosPago = MetodoPago::select('id', 'name')->orderBy('name')->get();
        return view('facturas.edit', compact('factura', 'clientes', 'proveedores', 'metodosPago'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Factura $factura)
    {
        // Debug: Verificar que el modelo se esté resolviendo correctamente
        \Log::info('Updating factura', [
            'factura_id' => $factura->id,
            'current_invoice' => $factura->invoice,
            'new_invoice' => $request->input('invoice')
        ]);
        
        // Validar que solo tenga cliente O proveedor, no ambos
        if (($request->input('client_id') && $request->input('provider_id')) || 
            (!$request->input('client_id') && !$request->input('provider_id'))) {
            return response()->json([
                'success' => false, 
                'message' => 'Debe seleccionar un cliente O un proveedor, no ambos.'
            ], 422);
        }

        // Crear regla de validación unique condicional basada en si es cliente o proveedor
        $invoiceUniqueRule = Rule::unique('invoices', 'invoice')->ignore($factura->id);
        
        if ($request->input('client_id')) {
            // Para facturas de cliente: debe ser único entre facturas de cliente
            $invoiceUniqueRule->whereNotNull('client_id');
        } else {
            // Para facturas de proveedor: debe ser único entre facturas de proveedor
            $invoiceUniqueRule->whereNotNull('provider_id');
        }
        
        $validatedData = $request->validate([
            'invoice' => ['required', 'string', 'max:50', $invoiceUniqueRule],
            'client_id' => 'nullable|exists:clients,id',
            'provider_id' => 'nullable|exists:providers,id',
            'date' => 'required|date',
            'expiry' => 'nullable|date|after_or_equal:date',
            'pay_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'check' => 'nullable|string|max:100',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'detail' => 'nullable|string|max:1000',
            'status' => 'required|in:0,1',
        ]);

        $factura->update($validatedData);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true, 
                'factura' => $factura->load(['cliente', 'proveedor', 'metodoPago'])
            ]);
        }
        
        return redirect()->route('facturas.index')->with('success', 'Factura actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Factura $factura)
    {
        $factura->delete();
        
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Factura eliminada exitosamente.']);
        }
        
        return redirect()->route('facturas.index')->with('success', 'Factura eliminada exitosamente.');
    }
}
