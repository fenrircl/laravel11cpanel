<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\FilesRegistry;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogger;
use App\Models\Cotizacion; // agregar import para cotizaciones

class R2Controller extends Controller
{
    // Subir un archivo de prueba a R2
    public function upload()
    {
        $filename = 'pruebas/archivo_' . time() . '.txt';
        Storage::disk('r2')->put($filename, 'Hola desde Laravel en cPanel + R2 🚀');

        return "Archivo subido a R2: {$filename}";
    }

    // Listar archivos en la carpeta "pruebas"
    public function list()
    {
        $files = Storage::disk('r2')->files('pruebas');

        if (empty($files)) {
            return "No hay archivos en la carpeta 'pruebas'.";
        }

        $output = "<h3>Archivos en R2:</h3><ul>";
        foreach ($files as $file) {
            $url = Storage::disk('r2')->url($file);
            $output .= "<li><a href='{$url}' target='_blank'>{$file}</a></li>";
        }
        $output .= "</ul>";

        return $output;
    }

    /**
     * Stream a file from R2 storage.
     *
     * @param string $path
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
     */
    public function downloadFile($path)
    {
        // Basic security check to prevent directory traversal
        if (str_contains($path, '..')) {
            abort(400, 'Ruta inválida.');
        }

        if (!Storage::disk('r2')->exists($path)) {
            abort(404, 'Archivo no encontrado.');
        }

        // Usar response() para que el navegador maneje el archivo (lo muestra en línea o lo descarga)
        return Storage::disk('r2')->response($path);
    }

    /**
     * Upload a file to R2 storage and register it in the database.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadFile(Request $request)
    {
        try {
            // Soporte para facturas por invoice number
            if ($request->has('invoice')) {
                // Validación para facturas por invoice
                $request->validate([
                    'file' => 'required|file|max:10240', // Max 10MB
                    'invoice' => 'required|string'
                ]);

                $file = $request->file('file');
                $invoice = $request->input('invoice');

                // Buscar la factura por número de invoice
                $factura = DB::table('invoices')->where('invoice', $invoice)->first();
                
                if (!$factura) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Factura no encontrada'
                    ], 404);
                }

                // Determinar el tipo de factura (cliente o proveedor) y crear el path apropiado
                $tipoFactura = $factura->client_id ? 'clientes' : 'proveedores';
                $numeroFactura = $factura->invoice; // Usar número de factura en lugar del ID
                
                // Generate a unique filename
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = pathinfo($originalName, PATHINFO_FILENAME);
                $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                
                // Create the storage path: facturas/clientes/{numero_factura} o facturas/proveedores/{numero_factura}
                $storagePath = "facturas/{$tipoFactura}/{$numeroFactura}/{$uniqueFileName}";

                // Upload to R2
                $uploaded = Storage::disk('r2')->putFileAs(
                    "facturas/{$tipoFactura}/{$numeroFactura}",
                    $file,
                    $uniqueFileName
                );

                if (!$uploaded) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Error al subir el archivo'
                    ], 500);
                }

                // Register in database
                $fileRegistry = FilesRegistry::create([
                    'model_type' => 'App\\Invoice',
                    'model_id' => $factura->id,
                    'real_id' => $factura->invoice,
                    'path' => $storagePath,
                    'file_name' => $originalName,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'migrated' => 0,
                    'created_at' => now()
                ]);

                // Auditoría
                AuditLogger::log($request, 'upload', 'archivos', $factura->id, 'Subió archivo a factura #' . $factura->invoice);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Archivo subido exitosamente',
                    'file' => [
                        'id' => $fileRegistry->id,
                        'name' => $originalName,
                        'size' => $file->getSize(),
                        'path' => $storagePath
                    ]
                ]);
            }

            // Método original por model_type y model_id
            $request->validate([
                'file' => 'required|file|max:10240', // Max 10MB
                'model_type' => 'required|string',
                'model_id' => 'required|string',
                'real_id' => 'nullable|integer'
            ]);

            $file = $request->file('file');
            $modelType = $request->input('model_type');
            $modelId = $request->input('model_id');
            $realId = $request->input('real_id');

            // Si es una factura, necesitamos determinar si es de cliente o proveedor
            if ($modelType === 'App\\Invoice') {
                $factura = DB::table('invoices')->where('id', $modelId)->first();
                
                if (!$factura) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Factura no encontrada'
                    ], 404);
                }

                // Determinar el tipo de factura y usar número de factura en lugar del ID
                $tipoFactura = $factura->client_id ? 'clientes' : 'proveedores';
                $numeroFactura = $factura->invoice; // Usar número de factura
                $facturaPath = "facturas/{$tipoFactura}/{$numeroFactura}";
            } elseif ($modelType === 'App\\Quotation') {
                $cotizacion = Cotizacion::find($modelId);
                if (!$cotizacion) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cotización no encontrada'
                    ], 404);
                }
                // Path solicitado: cotizaciones/{idCotizacion}/nombrearchivo
                $facturaPath = "cotizaciones/{$cotizacion->id}";
            } elseif ($modelType === 'App\\Client') {
                // Adjuntos por cliente
                $facturaPath = "cliente/{$modelId}";
            } elseif ($modelType === 'App\\Proveedor') {
                // Adjuntos por proveedor
                $facturaPath = "proveedor/{$modelId}";
            } else {
                // Para otros tipos de modelos, usar estructura original
                $facturaPath = "facturas/{$modelId}";
            }

            // Generate a unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
            
            // Create the storage path
            $storagePath = "{$facturaPath}/{$uniqueFileName}";

            // Upload to R2
            $uploaded = Storage::disk('r2')->putFileAs(
                $facturaPath,
                $file,
                $uniqueFileName
            );

            if (!$uploaded) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir el archivo'
                ], 500);
            }

            // Register in database
            $fileRegistry = FilesRegistry::create([
                'model_type' => $modelType,
                'model_id' => $modelId,
                'real_id' => $realId,
                'path' => $storagePath,
                'file_name' => $originalName,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'migrated' => 0,
                'created_at' => now()
            ]);

            // Auditoría
            AuditLogger::log($request, 'upload', 'archivos', $modelId, 'Subió archivo a ' . $modelType . ' #' . $modelId);

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Archivo subido exitosamente',
                'file' => [
                    'id' => $fileRegistry->id,
                    'name' => $originalName,
                    'size' => $file->getSize(),
                    'path' => $storagePath,
                    'download_url' => route('files.download', ['path' => $storagePath])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get files associated with a model
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFiles(Request $request)
    {
        try {
            $modelType = $request->query('model_type');
            $modelId = $request->query('model_id');
            $q = FilesRegistry::query();
            if ($modelType) $q->where('model_type', $modelType);
            if ($modelId) $q->where('model_id', $modelId);
            $files = $q->orderBy('created_at','desc')->get()->map(function($f){
                return [
                    'id' => $f->id,
                    'name' => $f->file_name,
                    'size' => $f->size,
                    'mime_type' => $f->mime_type,
                    'created_at' => $f->created_at,
                    'path' => $f->path,
                    'download_url' => route('files.download', ['path' => $f->path])
                ];
            });
            return response()->json(['success' => true, 'files' => $files]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a file from storage and database
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFile($id)
    {
        try {
            $f = FilesRegistry::findOrFail($id);
            if (Storage::disk('r2')->exists($f->path)) {
                Storage::disk('r2')->delete($f->path);
            }
            $f->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a file from storage and database by path
     *
     * @param string $path
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFileByPath($path)
    {
        try {
            $path = ltrim($path, '/');
            if (Storage::disk('r2')->exists($path)) {
                Storage::disk('r2')->delete($path);
            }
            FilesRegistry::where('path', $path)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Listar archivos adjuntos de un cliente
    public function listClienteFiles($clienteId)
    {
        $files = FilesRegistry::where('model_type', 'App\\Client')
            ->where('model_id', $clienteId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->file_name,
                    'size' => $file->size,
                    'mime_type' => $file->mime_type,
                    'created_at' => optional($file->created_at)->format('d/m/Y H:i'),
                    'download_url' => route('files.download', ['path' => $file->path])
                ];
            });
        return response()->json(['status' => 'success', 'files' => $files]);
    }

    // Listar archivos adjuntos de un proveedor
    public function listProveedorFiles($proveedorId)
    {
        $files = FilesRegistry::where('model_type', 'App\\Proveedor')
            ->where('model_id', $proveedorId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->file_name,
                    'size' => $file->size,
                    'mime_type' => $file->mime_type,
                    'created_at' => optional($file->created_at)->format('d/m/Y H:i'),
                    'download_url' => route('files.download', ['path' => $file->path])
                ];
            });
        return response()->json(['status' => 'success', 'files' => $files]);
    }

    // Listar PDFs de cotizaciones de un cliente (en base a FilesRegistry de App\Cotizacion)
    public function listClientCotizacionFiles($clienteId)
    {
        $cotIds = \App\Models\Cotizacion::where('client_id', $clienteId)->pluck('id');
        if ($cotIds->isEmpty()) {
            return response()->json(['status' => 'success', 'files' => []]);
        }
        $files = FilesRegistry::where('model_type', 'App\\Quotation')
            ->whereIn('model_id', $cotIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->file_name,
                    'size' => $file->size,
                    'mime_type' => $file->mime_type,
                    'created_at' => optional($file->created_at)->format('d/m/Y H:i'),
                    'download_url' => route('files.download', ['path' => $file->path])
                ];
            });
        return response()->json(['status' => 'success', 'files' => $files]);
    }
}
