<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // ...existing code...
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            // ...existing code...
        ],

        'api' => [
            // ...existing code...
        ],
    ];

    /**
     * The application's route middleware aliases.
     * In Laravel 11 se usa $middlewareAliases en lugar de $routeMiddleware.
     *
     * @var array
     */
    protected $middlewareAliases = [
        // ...existing code...
        'admin.role' => \App\Http\Middleware\AdminRoleMiddleware::class,
        'auth.bearer' => \App\Http\Middleware\VerifyApiBearerToken::class,
    ];
}