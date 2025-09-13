<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Factura;

class HomeController extends Controller
{
    /**
     * Página de inicio con tablas de facturas vencidas (clientes y proveedores)
     */
    public function index(Request $request)
    {
        // Facturas de clientes vencidas (pendientes), más antiguas primero
        $clientesVencidas = Factura::with(['cliente:id,name,rut'])
            ->whereNotNull('client_id')
            ->pending()
            ->whereNotNull('expiry')
            ->where('expiry', '<', now())
            ->orderBy('expiry', 'asc')
            ->limit(10)
            ->get();

        // Facturas de proveedores vencidas (pendientes), más antiguas primero
        $proveedoresVencidas = Factura::with(['proveedor:id,name,rut'])
            ->whereNotNull('provider_id')
            ->pending()
            ->whereNotNull('expiry')
            ->where('expiry', '<', now())
            ->orderBy('expiry', 'asc')
            ->limit(10)
            ->get();

        // Normalizar datos para DataTables (Home)
        $clientesVencidasData = $clientesVencidas->map(function($f){
            return [
                'id' => $f->id,
                'entidad' => $f->cliente?->name,
                'rut' => $f->cliente?->rut,
                'invoice' => $f->invoice,
                'expiry' => optional($f->expiry)->timezone('America/Santiago')->format('Y-m-d'),
                'amount' => $f->amount,
                'tipo' => 'cliente'
            ];
        })->values();

        $proveedoresVencidasData = $proveedoresVencidas->map(function($f){
            return [
                'id' => $f->id,
                'entidad' => $f->proveedor?->name,
                'rut' => $f->proveedor?->rut,
                'invoice' => $f->invoice,
                'expiry' => optional($f->expiry)->timezone('America/Santiago')->format('Y-m-d'),
                'amount' => $f->amount,
                'tipo' => 'proveedor'
            ];
        })->values();

        return view('auth.home', [
            'clientesVencidas' => $clientesVencidas,
            'proveedoresVencidas' => $proveedoresVencidas,
            'clientesVencidasData' => $clientesVencidasData,
            'proveedoresVencidasData' => $proveedoresVencidasData,
            // Cargar estilos comunes de tablas
            'asset_css' => ['comun/tablas'],
            'asset_js' => ['home/home'],
        ]);
    }
}
