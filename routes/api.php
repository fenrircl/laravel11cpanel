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

// Endpoint MCP dinámico protegido con Bearer
Route::post('/query', [\App\Http\Controllers\McpController::class, 'handle'])
    ->middleware(\App\Http\Middleware\VerifyApiBearerToken::class)
    ->name('api.query');

// Endpoints MCP (resources, tools, query)
Route::get('/mcp/resources', [\App\Http\Controllers\McpController::class, 'resources'])
    ->middleware(\App\Http\Middleware\VerifyApiBearerToken::class);
Route::get('/mcp/tools', [\App\Http\Controllers\McpController::class, 'tools'])
    ->middleware(\App\Http\Middleware\VerifyApiBearerToken::class);
Route::post('/mcp/query', [\App\Http\Controllers\McpController::class, 'query'])
    ->middleware(\App\Http\Middleware\VerifyApiBearerToken::class);

// Proxy local para webhook externo (evita CORS desde frontend)
Route::post('/mcp/webhook', [\App\Http\Controllers\McpController::class, 'webhookProxy']);
