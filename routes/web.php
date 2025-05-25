<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginRegisterController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\FacturasController;
use App\Http\Controllers\ClientesController;

Route::controller(LoginRegisterController::class)->group(function() {
    // Route::get('/register', 'register')->name('register');
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

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth'])->group(function () {
    Route::get('/home', [LoginRegisterController::class, 'home'])->name('home');
    Route::post('/logout', [LoginRegisterController::class, 'logout'])->name('logout');

    // Rutas para Facturas (ahora protegidas)
    Route::get('/facturas', [FacturasController::class, 'index'])->name('facturas.index');
    Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes.index');

    // Si usas Route::resource para facturas, también iría aquí:
    // Route::resource('facturas', FacturasController::class);

    // Puedes agregar más rutas protegidas aquí
    // Ruta para DataTables de clientes
Route::get('clientes/data', [ClientesController::class, 'getData'])->name('clientes.data');
});

