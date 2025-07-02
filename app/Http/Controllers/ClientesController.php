<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente; 

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
        $clientes = Cliente::select(['id', 'name', 'email', 'phone', 'address', 'created_at', 'updated_at'])
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
        // Validar los datos del request
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

        $cliente = Cliente::create($mappedData);
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'cliente' => $cliente]);
        }
        
        // Redirigir o retornar JSON según sea necesario
        return redirect()->route('clientes.index')->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente)
    {
        return view('clientes.show', compact('cliente'));
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
        // Validar los datos del request
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

        $cliente->update($mappedData);
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'cliente' => $cliente]);
        }
        
        // Redirigir o retornar JSON según sea necesario
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

