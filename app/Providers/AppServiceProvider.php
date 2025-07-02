<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El archivo menu.php en config/ se carga automáticamente por Laravel
        // No necesitamos mergeConfigFrom() para archivos en config/
    }
}
