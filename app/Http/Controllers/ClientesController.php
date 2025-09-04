<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente; 
use App\Models\FilesRegistry;

class ClientesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Solo pasar datos estáticos, no los clientes para optimizar la carga inicial
        $data["asset_css"] = ['comun/tablas', 'clientes/clientes'];
        $data["asset_js"] = ['clientes/clientes'];
        return view('clientes.index', $data);
    }

    /**
     * Get data for DataTables via API
     */
    public function getData()
    {
        $clientes = Cliente::select(['id', 'rut', 'name', 'email', 'phone', 'address', 'created_at', 'updated_at'])
                          ->orderBy('created_at', 'desc')
                          ->get();
        
        return response()->json([
            'data' => $clientes
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('clientes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Aceptar nombres del modal y alias legacy
        $validated = $request->validate([
            'rut' => 'required|string|max:20|unique:clients,rut',
            'name' => 'required_without:nombre|string|max:255',
            'nombre' => 'required_without:name|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'direccion' => 'nullable|string|max:500',
        ]);

        $mapped = [
            'rut' => $validated['rut'],
            'name' => $validated['name'] ?? $validated['nombre'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? ($validated['telefono'] ?? null),
            'address' => $validated['address'] ?? ($validated['direccion'] ?? null),
        ];

        $cliente = Cliente::create($mapped);
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'cliente' => $cliente]);
        }
        
        return redirect()->route('clientes.index')->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente)
    {
        if (request()->ajax()) {
            return response()->json(['cliente' => $cliente]);
        }

        // Archivos asociados en files_registry con model_type 'App\\Client'
        $files = FilesRegistry::where('model_type', 'App\\Client')
            ->where('model_id', $cliente->id)
            ->orderByDesc('created_at')
            ->get();

        $data = [
            'cliente' => $cliente,
            'files' => $files,
            'asset_js' => ['clientes/cliente-show']
        ];
        return view('clientes.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cliente $cliente)
    {
        // Aceptar ambos nombres de campos
        $validated = $request->validate([
            'rut' => 'required|string|max:20|unique:clients,rut,' . $cliente->id,
            'name' => 'required_without:nombre|string|max:255',
            'nombre' => 'required_without:name|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'direccion' => 'nullable|string|max:500',
        ]);

        $mapped = [
            'rut' => $validated['rut'],
            'name' => $validated['name'] ?? $validated['nombre'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? ($validated['telefono'] ?? null),
            'address' => $validated['address'] ?? ($validated['direccion'] ?? null),
        ];

        $cliente->update($mapped);
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'cliente' => $cliente]);
        }
        
        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Cliente eliminado exitosamente.']);
        }
        
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado exitosamente.');
    }
}

