<?php

namespace App\Http\Controllers;

use App\Models\Factura; 
use Illuminate\Http\Request;

class FacturasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $invoices = Factura::all();
        // Aquí retornarías una vista con todas las facturas, por ejemplo:
        // return view('invoices.index', compact('invoices'));
        dd($invoices[2]);
        return view('facturas.facturas', compact('invoices'));
        return response()->json($invoices); // O devolver JSON si es una API
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Aquí retornarías una vista con el formulario para crear una nueva factura
        // return view('invoices.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validación de los datos del request
        $validatedData = $request->validate([
            // Define tus reglas de validación aquí
            // Ejemplo: 'numero_factura' => 'required|unique:invoices|max:255',
            // 'cliente_id' => 'required|exists:clients,id',
            // 'monto' => 'required|numeric',
        ]);

        $invoice = Invoice::create($validatedData);
        // Redirigir a alguna parte o devolver una respuesta
        // return redirect()->route('invoices.show', $invoice)->with('success', 'Factura creada exitosamente.');
        return response()->json($invoice, 201); // O devolver JSON si es una API
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice) // Inyección de modelo de ruta
    {
        // Laravel automáticamente encontrará la factura por su ID
        // Aquí retornarías una vista con los detalles de la factura
        // return view('invoices.show', compact('invoice'));
        return response()->json($invoice); // O devolver JSON si es una API
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice) // Inyección de modelo de ruta
    {
        // Aquí retornarías una vista con el formulario para editar la factura
        // return view('invoices.edit', compact('invoice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice) // Inyección de modelo de ruta
    {
        // Validación de los datos del request
        $validatedData = $request->validate([
            // Define tus reglas de validación aquí
        ]);

        $invoice->update($validatedData);
        // Redirigir a alguna parte o devolver una respuesta
        // return redirect()->route('invoices.show', $invoice)->with('success', 'Factura actualizada exitosamente.');
        return response()->json($invoice); // O devolver JSON si es una API
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice) // Inyección de modelo de ruta
    {
        $invoice->delete();
        // Redirigir a alguna parte o devolver una respuesta
        // return redirect()->route('invoices.index')->with('success', 'Factura eliminada exitosamente.');
        return response()->json(null, 204); // O devolver JSON si es una API
    }
}
