<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\FilesRegistry;
use App\Services\AuditLogger;

class ProveedoresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Solo pasar datos estáticos, no los proveedores para optimizar la carga inicial
        $data["asset_css"] = ['comun/tablas', 'proveedores/proveedores'];
        $data["asset_js"] = ['proveedores/proveedores'];
        return view('proveedores.index', $data);
    }

    /**
     * Get data for DataTables via API
     */
    public function getData()
    {
        $proveedores = Proveedor::select(['id', 'rut', 'name', 'email', 'phone', 'address', 'created_at', 'updated_at'])
                               ->orderBy('created_at', 'desc')
                               ->get();
        
        return response()->json([
            'data' => $proveedores
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('proveedores.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Aceptar nombres del modal y alias legacy
        $validated = $request->validate([
            'rut' => 'required|string|max:20|unique:providers,rut',
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

        $proveedor = Proveedor::create($mapped);
        
        // Log auditoría
        AuditLogger::log($request, 'create', 'proveedores', $proveedor->id, 'Creó proveedor ' . $proveedor->name);
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'proveedor' => $proveedor]);
        }
        
        return redirect()->route('proveedores.index')->with('success', 'Proveedor creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Proveedor $proveedor)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['proveedor' => $proveedor]);
        }
        // Cargar archivos asociados
        $files = FilesRegistry::where('model_type','App\\Provider')
            ->where('model_id', $proveedor->id)
            ->orderByDesc('created_at')
            ->get();
        $data = [
            'proveedor' => $proveedor,
            'files' => $files,
            'asset_js' => ['proveedores/proveedor-show']
        ];
        return view('proveedores.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.edit', compact('proveedor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        // Aceptar ambos nombres de campos y validar rut único ignorando el actual
        $validated = $request->validate([
            'rut' => 'required|string|max:20|unique:providers,rut,' . $proveedor->id,
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

        $proveedor->update($mapped);
        
        // Log auditoría
        AuditLogger::log($request, 'update', 'proveedores', $proveedor->id, 'Actualizó proveedor ' . $proveedor->name);
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'proveedor' => $proveedor]);
        }
        
        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proveedor $proveedor)
    {
        $id = $proveedor->id;
        $name = $proveedor->name;
        $proveedor->delete();
        
        // Log auditoría
        AuditLogger::log(request(), 'delete', 'proveedores', $id, 'Eliminó proveedor ' . $name);
        
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Proveedor eliminado exitosamente.']);
        }
        
        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado exitosamente.');
    }
}
