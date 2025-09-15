<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Página de inicio con tablas de facturas vencidas (clientes y proveedores)
     * Ahora los datos se cargan desde el cliente vía AJAX.
     */
    public function index(Request $request)
    {
        return view('auth.home', [
            // Cargar estilos comunes de tablas
            'asset_css' => ['comun/tablas'],
            'asset_js' => ['home/home'],
        ]);
    }
}
