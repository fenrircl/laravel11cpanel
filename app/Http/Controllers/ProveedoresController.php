<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;

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
        $proveedores = Proveedor::select(['id', 'name', 'email', 'phone', 'address', 'created_at', 'updated_at'])
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
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:500',
        ]);

        // Mapear los datos al formato de la BD
        $mappedData = [
            'name' => $validatedData['nombre'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['telefono'],
            'address' => $validatedData['direccion'],
        ];

        $proveedor = Proveedor::create($mappedData);
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'proveedor' => $proveedor]);
        }
        
        return redirect()->route('proveedores.index')->with('success', 'Proveedor creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Proveedor $proveedor)
    {
        return view('proveedores.show', compact('proveedor'));
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
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:500',
        ]);

        // Mapear los datos al formato de la BD
        $mappedData = [
            'name' => $validatedData['nombre'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['telefono'],
            'address' => $validatedData['direccion'],
        ];

        $proveedor->update($mappedData);
        
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
        $proveedor->delete();
        
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Proveedor eliminado exitosamente.']);
        }
        
        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado exitosamente.');
    }
}
