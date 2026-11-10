<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // loads the routes here.
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'mcp',           // For streamable transport (default)
            'mcp/*',   // For legacy transport (if enabled)
        ]);

        // Aliases personalizados
        $middleware->alias([
            'admin.role' => \App\Http\Middleware\AdminRoleMiddleware::class,
            'auth.bearer' => \App\Http\Middleware\VerifyApiBearerToken::class,
            'auth.chat-api' => \App\Http\Middleware\VerifyChatApiToken::class,
            'auth.n8n' => \App\Http\Middleware\VerifyN8nBearer::class,
            'assistant.enabled' => \App\Http\Middleware\EnsureAssistantEnabled::class,
            'active.user' => \App\Http\Middleware\EnsureUserIsActive::class,
            'assistant.api.audit' => \App\Http\Middleware\AuditAssistantApiUsage::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
