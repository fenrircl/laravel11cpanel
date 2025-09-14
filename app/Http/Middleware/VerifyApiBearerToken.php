<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyApiBearerToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) (config('services.mcp.bearer') ?? '');

        // Extract bearer from Authorization header
        $provided = null;
        $auth = $request->header('Authorization');
        if ($auth && preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            $provided = trim($m[1]);
        }

        if (!$expected || !$provided || !hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'No autorizado: token inválido o ausente.'
            ], 401);
        }

        return $next($request);
    }
}
