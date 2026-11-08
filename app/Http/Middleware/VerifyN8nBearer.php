<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyN8nBearer
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.n8n.ingest_bearer', '');
        $provided = (string) $request->bearerToken();

        if ($expected === '') {
            return response()->json(['message' => 'La integración n8n no está configurada.'], 503);
        }

        if ($provided === '' || !hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Bearer token inválido o ausente.'], 401, [
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        return $next($request);
    }
}
