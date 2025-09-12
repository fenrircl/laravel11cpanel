<?php
use App\Http\Controllers\FacturasController;
use App\Http\Controllers\BackupController;
// ... otras rutas

Route::resource('facturas', FacturasController::class);
