<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginRegisterController;
use Illuminate\Support\Facades\Artisan;

Route::controller(LoginRegisterController::class)->group(function() {
    Route::get('/register', 'register')->name('register');
    Route::post('/store', 'store')->name('store');
    Route::get('/login', 'login')->name('login');
    Route::post('/authenticate', 'authenticate')->name('authenticate');
    Route::get('/home', 'home')->name('home');
    Route::post('/logout', 'logout')->name('logout');
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/clear-laravel-11-caches', function () {
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('cache:clear'); // Caché general de la aplicación
    Artisan::call('event:clear'); // Si usas eventos cacheados
    // En Laravel 11, 'optimize:clear' agrupa muchas de estas:
    // Artisan::call('optimize:clear'); // Deshace config:cache, route:cache, view:cache, event:cache

    // Para estar seguros, ejecutar los individuales puede ser mejor al depurar
    return "Laravel 11 Caches (config, view, route, app cache, event) have been cleared!";
});