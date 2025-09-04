<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginRegisterController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\FacturasController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\R2Controller;
use App\Http\Controllers\CotizacionesController;

Route::controller(LoginRegisterController::class)->group(function() {
    // Route::get('/register', 'register')->name('register');
    Route::post('/store', 'store')->name('store');
    Route::get('/login', 'login')->name('login');
    Route::post('/authenticate', 'authenticate')->name('authenticate');
    Route::get('/home', 'home')->name('home');
    // (Eliminada) Route::post('/logout', 'logout')->name('logout');
});

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', [LoginRegisterController::class, 'home'])->name('home');

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
    Route::get('/facturas/clientes', [FacturasController::class, 'clienteIndex'])->name('facturas.clientes.index');
    Route::get('/facturas/proveedores', [FacturasController::class, 'proveedorIndex'])->name('facturas.proveedores.index');
    
    // APIs para DataTables
    Route::get('facturas/data', [FacturasController::class, 'getData'])->name('facturas.data');
    Route::get('facturas/clientes/data', [FacturasController::class, 'getClienteData'])->name('facturas.clientes.data');
    Route::get('facturas/proveedores/data', [FacturasController::class, 'getProveedorData'])->name('facturas.proveedores.data');
    Route::get('metodos-pago/data', [MetodoPagoController::class, 'getData'])->name('metodos-pago.data');
    
    Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes.index');
    Route::get('/proveedores', [ProveedoresController::class, 'index'])->name('proveedores.index');

    // APIs para DataTables de clientes y proveedores
    Route::get('clientes/data', [ClientesController::class, 'getData'])->name('clientes.data');
    Route::get('proveedores/data', [ProveedoresController::class, 'getData'])->name('proveedores.data');
    
    // Rutas del buscador global
    Route::get('buscar', [BusquedaController::class, 'buscar'])->name('buscar');
    Route::get('{entidad}/search-data', [BusquedaController::class, 'datosParaCache'])->name('search.data');
    Route::post('search/clear-cache', [BusquedaController::class, 'limpiarCache'])->name('search.clear-cache');
    Route::get('search/stats', [BusquedaController::class, 'estadisticas'])->name('search.stats');
    
    // Recursos completos para CRUD
    Route::resource('facturas', FacturasController::class)->except(['index']);
    Route::resource('clientes', ClientesController::class)
        ->parameters(['clientes' => 'cliente'])
        ->except(['index']);
    Route::resource('proveedores', ProveedoresController::class)
        ->parameters(['proveedores' => 'proveedor'])
        ->except(['index']);
    Route::resource('metodos-pago', MetodoPagoController::class);
    
    // Rutas adicionales para métodos de pago
    Route::post('metodos-pago/{metodoPago}/toggle-status', [MetodoPagoController::class, 'toggleStatus'])->name('metodos-pago.toggle-status');

    Route::get('/r2/upload', [R2Controller::class, 'upload']);
    Route::get('/r2/list', [R2Controller::class, 'list']);
    Route::get('/r2/download/{path}', [R2Controller::class, 'downloadFile'])->where('path', '.*')->name('r2.download');
    
    // Rutas para gestión de archivos
    Route::post('/files/upload', [R2Controller::class, 'uploadFile'])->name('files.upload');
    Route::get('/files/list', [R2Controller::class, 'getFiles'])->name('files.list');
    Route::delete('/files/{id}', [R2Controller::class, 'deleteFile'])->name('files.delete');
    Route::delete('/r2/delete/{path}', [R2Controller::class, 'deleteFileByPath'])->where('path', '.*')->name('r2.delete');
    Route::get('/files/download/{path}', [R2Controller::class, 'downloadFile'])->where('path', '.*')->name('files.download');

    // Cotizaciones
    Route::get('/cotizaciones', [CotizacionesController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/create', [CotizacionesController::class, 'create'])->name('cotizaciones.create');
    Route::post('/cotizaciones', [CotizacionesController::class, 'store'])->name('cotizaciones.store');
    Route::get('/cotizaciones/data', [CotizacionesController::class, 'getData'])->name('cotizaciones.data');
    Route::get('/cotizaciones/{cotizacion}', [CotizacionesController::class, 'show'])->name('cotizaciones.show');

    // Vista rápida y rutas por tipo para factura completa
    Route::get('/facturas/clientes/{factura}', [FacturasController::class, 'show'])->name('facturas.clientes.show');
    Route::get('/facturas/proveedores/{factura}', [FacturasController::class, 'show'])->name('facturas.proveedores.show');

    // Administración
    Route::prefix('admin')->name('admin.')->middleware('admin.role')->group(function(){
        Route::get('/users', [\App\Http\Controllers\Admin\UsersController::class, 'index'])->name('users.index');
        Route::post('/users', [\App\Http\Controllers\Admin\UsersController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\UsersController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UsersController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/reset-password', [\App\Http\Controllers\Admin\UsersController::class, 'resetPassword'])->name('users.reset');

        Route::get('/roles', [\App\Http\Controllers\Admin\RolesController::class, 'index'])->name('roles.index');
        Route::post('/roles', [\App\Http\Controllers\Admin\RolesController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [\App\Http\Controllers\Admin\RolesController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [\App\Http\Controllers\Admin\RolesController::class, 'destroy'])->name('roles.destroy');
    });
});

