<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\McpController;

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

// Proxy simple hacia n8n usando N8N_WEBHOOK_URL del .env
Route::post('/mcp/webhook', function (Request $request) {
    $url = config('services.n8n.webhook_url');
    if (!$url) {
        return response()->json(['error' => 'Webhook no configurado'], 500);
    }

    try {
        $payload = $request->all();
        $bearer = config('services.mcp.bearer');
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if ($bearer) {
            $headers['Authorization'] = 'Bearer ' . $bearer;
        }

        $res = Http::withHeaders($headers)->post($url, $payload);

        $contentType = $res->header('Content-Type', 'application/json');
        $status = $res->status();
        if (str_contains($contentType, 'application/json')) {
            return response()->json($res->json(), $status);
        }
        return response($res->body(), $status)->header('Content-Type', $contentType);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Error al contactar n8n',
            'message' => $e->getMessage(),
        ], 502);
    }
});


