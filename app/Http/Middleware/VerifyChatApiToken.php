<?php

namespace App\Http\Middleware;

use App\Models\ChatApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyChatApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = (string) $request->bearerToken();
        if (!preg_match('/^(\d+)\|([A-Za-z0-9]{64})$/', $bearer, $matches)) {
            return $this->unauthorized();
        }

        $token = ChatApiToken::query()->with('user')->find($matches[1]);
        if (!$token || !$token->user || ($token->expires_at && $token->expires_at->isPast())) {
            return $this->unauthorized();
        }

        if (!hash_equals($token->token_hash, hash('sha256', $matches[2]))) {
            return $this->unauthorized();
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('chat_api_token', $token);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Bearer token ausente, inválido o vencido.',
        ], 401, ['WWW-Authenticate' => 'Bearer']);
    }
}
