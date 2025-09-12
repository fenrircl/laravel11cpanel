<?php
use App\Http\Controllers\FacturasController;
use App\Http\Controllers\BackupController;
// ... otras rutas

Route::resource('facturas', FacturasController::class);

Route::middleware('auth:api')->get('/backup-db', [BackupController::class, 'download']);
