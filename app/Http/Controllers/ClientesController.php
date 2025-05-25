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
        $clientes = Cliente::all();
        // Retornar una vista o JSON según sea necesario
        return view('clientes.index', compact('clientes')); 
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
            // Definir reglas de validación aquí
            // Ejemplo: 'nombre' => 'required|string|max:255',
        ]);

        $cliente = Cliente::create($validatedData);
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
            // Definir reglas de validación aquí
        ]);

        $cliente->update($validatedData);
        // Redirigir o retornar JSON según sea necesario
        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        // Redirigir o retornar JSON según sea necesario
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado exitosamente.');
    }
}

