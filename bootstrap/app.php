<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin.role' => \App\Http\Middleware\AdminRoleMiddleware::class,
            'user.context' => \App\Http\Middleware\UserContextMiddleware::class,
        ]);
        $middleware->appendToGroup('web', [
            'user.context',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
