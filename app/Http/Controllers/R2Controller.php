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
            } elseif ($modelType === 'App\\Cotizacion') {
                $cotizacion = Cotizacion::find($modelId);
                if (!$cotizacion) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cotización no encontrada'
                    ], 404);
                }
                // Por cliente (preferencia del usuario)
                $storageBasePath = "cotizaciones/clientes/{$cotizacion->client_id}/{$cotizacion->id}";
                $facturaPath = $storageBasePath;
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
            // Soporte para búsqueda por invoice number (facturas)
            if ($request->has('invoice')) {
                $invoice = $request->input('invoice');
                
                // Buscar la factura por número de invoice
                $factura = DB::table('invoices')->where('invoice', $invoice)->first();
                
                if (!$factura) {
                    return response()->json([
                        'status' => 'success',
                        'files' => []
                    ]);
                }
                
                $files = FilesRegistry::where('model_type', 'App\\Invoice')
                    ->where('model_id', $factura->id)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($file) {
                        return [
                            'id' => $file->id,
                            'name' => $file->file_name,
                            'size' => $file->size,
                            'mime_type' => $file->mime_type,
                            'created_at' => $file->created_at->format('d/m/Y H:i'),
                            'download_url' => route('files.download', ['path' => $file->path])
                        ];
                    });

                return response()->json([
                    'status' => 'success',
                    'files' => $files
                ]);
            }

            // Método original por model_type y model_id
            $modelType = $request->input('model_type');
            $modelId = $request->input('model_id');

            if (!$modelType || !$modelId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Parámetros requeridos: model_type y model_id o invoice'
                ], 400);
            }

            $files = FilesRegistry::where('model_type', $modelType)
                ->where('model_id', $modelId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->file_name,
                        'size' => $file->size,
                        'mime_type' => $file->mime_type,
                        'created_at' => $file->created_at->format('d/m/Y H:i'),
                        'download_url' => route('files.download', ['path' => $file->path])
                    ];
                });

            return response()->json([
                'status' => 'success',
                'files' => $files
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener archivos: ' . $e->getMessage()
            ], 500);
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
            $fileRegistry = FilesRegistry::findOrFail($id);
            
            // Delete from R2 storage
            if (Storage::disk('r2')->exists($fileRegistry->path)) {
                Storage::disk('r2')->delete($fileRegistry->path);
            }
            
            // Delete from database
            $fileRegistry->delete();

            // Auditoría
            AuditLogger::log(request(), 'delete', 'archivos', $id, 'Eliminó archivo ' . $fileRegistry->file_name);

            return response()->json([
                'status' => 'success',
                'message' => 'Archivo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar archivo: ' . $e->getMessage()
            ], 500);
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
            // Decode the path in case it was URL encoded
            $decodedPath = urldecode($path);
            
            // Find the file registry entry by path
            $fileRegistry = FilesRegistry::where('path', $decodedPath)->first();
            
            if (!$fileRegistry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado en el registro'
                ], 404);
            }
            
            // Delete from R2 storage
            if (Storage::disk('r2')->exists($fileRegistry->path)) {
                Storage::disk('r2')->delete($fileRegistry->path);
            }
            
            // Delete from database
            $fileRegistry->delete();

            // Auditoría
            AuditLogger::log(request(), 'delete', 'archivos', $fileRegistry->id, 'Eliminó archivo por ruta ' . $fileRegistry->file_name);

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar archivo: ' . $e->getMessage()
            ], 500);
        }
    }
}
