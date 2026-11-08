<?php

namespace App\Http\Middleware;

use App\Models\AssistantUsageLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditAssistantApiUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);
        $token = $request->attributes->get('chat_api_token');
        $query = json_encode([
            'action' => $request->route()?->getName(),
            'parameters' => $request->except(['password', 'api_key', 'token']),
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        try {
            AssistantUsageLog::query()->create([
                'user_id' => $token?->user_id,
                'user_name' => $token?->user?->name,
                'user_message' => '[ChatGPT Action] ' . mb_substr((string) $query, 0, 4000),
                'model' => 'ChatGPT Action (tokens no informados por ChatGPT)',
                'tool_calls' => 1,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'status' => $response->getStatusCode() < 400 ? 'success' : 'failed',
                'error_type' => $response->getStatusCode() >= 400 ? 'HTTP ' . $response->getStatusCode() : null,
            ]);
        } catch (Throwable $exception) {
            logger()->warning('Assistant API usage audit could not be saved', [
                'exception' => get_class($exception),
            ]);
        }

        return $response;
    }
}
