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
        $proveedores = Proveedor::all();
        $data["proveedores"] = $proveedores;
        // Agregar CSS y JS específicos y generales
        $data["asset_css"] = ['tablas', 'proveedores'];
        $data["asset_js"] = ['main', 'proveedores'];
        return view('proveedores.index', $data);
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
        // Redirigir o retornar JSON según sea necesario
        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado exitosamente.');
    }
}
