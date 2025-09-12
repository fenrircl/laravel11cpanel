<?php
use App\Http\Controllers\FacturasController;
use App\Http\Controllers\BackupController;
// ... otras rutas

Route::resource('facturas', FacturasController::class);

// Público: la validación de token se hará en el controlador
Route::get('/backup-db', [BackupController::class, 'download']);
