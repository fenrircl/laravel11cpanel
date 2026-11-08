<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAssistantEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('services.openai.enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'El asistente está deshabilitado.',
            ], 503);
        }

        return $next($request);
    }
}
