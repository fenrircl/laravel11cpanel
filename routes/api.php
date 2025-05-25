<?php
use App\Http\Controllers\FacturasController;

// ... otras rutas

Route::resource('facturas', FacturasController::class);