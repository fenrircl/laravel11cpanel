<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes de prueba
|--------------------------------------------------------------------------
| Aquí puedes definir rutas simples para comprobar que /api funciona.
| Las rutas estarán disponibles en https://tudominio.com/api/...
|
*/

// Ruta de prueba básica
Route::get('/ping', function () {
    return response()->json(['message' => 'pong 🏓']);
});

// Ruta con parámetros
Route::get('/saludo/{nombre}', function ($nombre) {
    return response()->json(['saludo' => "Hola, $nombre 👋"]);
});

// Ruta POST para recibir datos
Route::post('/echo', function (Request $request) {
    return response()->json([
        'recibido' => $request->all()
    ]);
});
