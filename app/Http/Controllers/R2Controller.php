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
    /**
     * Normalize and clean file path to prevent encoding issues
     *
     * @param string $path
     * @return string
     */
    private function normalizePath($path)
    {
        // Log inicial para debug
        \Log::info('Normalizando ruta', [
            'original' => $path,
            'length' => strlen($path),
            'hex' => bin2hex(substr($path, 0, 100)) // Solo primeros 100 caracteres para evitar logs muy largos
        ]);
        
        // Primera limpieza: caracteres nulos y de control más problemáticos
        $path = str_replace("\x00", '', $path); // Null bytes
        $path = str_replace("\x01", '', $path); // SOH - Start of Heading  
        $path = str_replace("\x03", '', $path); // ETX - End of Text
        
        // Limpiar TODOS los caracteres de control ASCII (0x00-0x1F) y DEL (0x7F)
        // Excluir solo: TAB (0x09), LF (0x0A), CR (0x0D)
        $path = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $path);
        
        // Limpiar caracteres de control Unicode adicionales que pueden causar problemas
        $path = preg_replace('/[\x{0080}-\x{009F}]/u', '', $path); // C1 control characters
        
        // Decodificar la ruta si viene codificada desde la URL
        $path = urldecode($path);
        
        // Asegurar codificación UTF-8 correcta
        if (!mb_check_encoding($path, 'UTF-8')) {
            $originalPath = $path;
            $path = mb_convert_encoding($path, 'UTF-8', 'auto');
            \Log::info('Ruta recodificada', [
                'original' => bin2hex($originalPath),
                'converted' => $path,
                'converted_hex' => bin2hex(substr($path, 0, 100))
            ]);
        }
        
        // Limpiar espacios adicionales y normalizar
        $path = trim($path);
        
        // Normalizar separadores de directorio
        $path = str_replace('\\', '/', $path);
        
        // Eliminar dobles barras
        $path = preg_replace('#/+#', '/', $path);
        
        // Limpiar espacios múltiples
        $path = preg_replace('/\s+/', ' ', $path);
        
        // Verificación final: asegurarse de que no queden caracteres problemáticos
        $cleanPath = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/', '', $path);
        
        \Log::info('Ruta normalizada', [
            'result' => $cleanPath,
            'final_length' => strlen($cleanPath),
            'final_hex' => bin2hex(substr($cleanPath, 0, 100)),
            'changes_made' => $path !== $cleanPath ? 'Caracteres adicionales removidos' : 'No se necesitaron cambios adicionales'
        ]);
        
        return $cleanPath;
    }

    /**
     * Sanitize filename to prevent filesystem issues
     * 
     * @param string $filename
     * @return string
     */
    private function sanitizeFilename($filename)
    {
        \Log::info('Sanitizando nombre de archivo', [
            'original' => $filename,
            'original_hex' => bin2hex(substr($filename, 0, 100))
        ]);
        
        // Primera limpieza: caracteres nulos y de control más problemáticos
        $filename = str_replace("\x00", '', $filename); // Null bytes
        $filename = str_replace("\x01", '', $filename); // SOH - Start of Heading  
        $filename = str_replace("\x03", '', $filename); // ETX - End of Text
        
        // Limpiar TODOS los caracteres de control ASCII (0x00-0x1F) y DEL (0x7F)
        $filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);
        
        // Limpiar caracteres de control Unicode C1 (0x80-0x9F)
        $filename = preg_replace('/[\x{0080}-\x{009F}]/u', '', $filename);
        
        // Caracteres problemáticos para nombres de archivo en Windows/Linux
        $filename = preg_replace('/[<>:"|?*\\\\\/]/', '', $filename);
        
        // Normalizar espacios múltiples
        $filename = preg_replace('/\s+/', ' ', trim($filename));
        
        // Limitar longitud (255 caracteres max para la mayoría de filesystems)
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $base = pathinfo($filename, PATHINFO_FILENAME);
            $maxBase = 255 - strlen($ext) - 1;
            $filename = substr($base, 0, $maxBase) . '.' . $ext;
        }
        
        // Verificación final: asegurarse de que no queden caracteres problemáticos
        $cleanFilename = preg_replace('/[\x00-\x1F\x7F-\x9F<>:"|?*\\\\\/]/', '', $filename);
        
        \Log::info('Nombre de archivo sanitizado', [
            'result' => $cleanFilename,
            'result_hex' => bin2hex(substr($cleanFilename, 0, 100)),
            'changes_made' => $filename !== $cleanFilename ? 'Caracteres adicionales removidos' : 'No se necesitaron cambios adicionales'
        ]);
        
        return $cleanFilename;
    }

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
        try {
            // Normalizar la ruta para evitar problemas de codificación
            $path = $this->normalizePath($path);
            
            // Basic security check to prevent directory traversal
            if (str_contains($path, '..')) {
                abort(400, 'Ruta inválida.');
            }

            if (!Storage::disk('r2')->exists($path)) {
                // Intentar buscar archivos similares para debug
                $parentDir = dirname($path);
                $fileName = basename($path);
                
                $similarFiles = [];
                if (Storage::disk('r2')->exists($parentDir)) {
                    $filesInDir = Storage::disk('r2')->files($parentDir);
                    $similarFiles = array_filter($filesInDir, function($file) use ($fileName) {
                        return strpos(basename($file), pathinfo($fileName, PATHINFO_FILENAME)) !== false;
                    });
                }
                
                \Log::warning('Archivo no encontrado en R2', [
                    'requested_path' => $path,
                    'parent_directory' => $parentDir,
                    'directory_exists' => Storage::disk('r2')->exists($parentDir),
                    'files_in_directory' => array_slice($similarFiles, 0, 10) // Limitar para logs
                ]);
                
                abort(404, 'Archivo no encontrado en R2: ' . $path);
            }

            // Usar response() para que el navegador maneje el archivo (lo muestra en línea o lo descarga)
            return Storage::disk('r2')->response($path);
            
        } catch (\Exception $e) {
            \Log::error('Error al descargar archivo desde R2', [
                'original_path' => $path ?? 'null',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            abort(500, 'Error interno al procesar el archivo: ' . $e->getMessage());
        }
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
                
                // Generate a unique filename with sanitization
                $originalName = $this->sanitizeFilename($file->getClientOriginalName());
                $extension = $file->getClientOriginalExtension();
                $fileName = pathinfo($originalName, PATHINFO_FILENAME);
                $fileName = $this->sanitizeFilename($fileName); // Sanitizar también el nombre base
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
            } elseif ($modelType === 'App\\Provider') {
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
            // Normalizar la ruta para evitar problemas de codificación
            $path = $this->normalizePath($path);
            $path = ltrim($path, '/');
            
            // Log para debug
            \Log::info('Intentando eliminar archivo desde R2', [
                'path' => $path,
                'exists' => Storage::disk('r2')->exists($path)
            ]);
            
            if (Storage::disk('r2')->exists($path)) {
                Storage::disk('r2')->delete($path);
                \Log::info('Archivo eliminado de R2', ['path' => $path]);
            }
            
            $deletedRecords = FilesRegistry::where('path', $path)->delete();
            \Log::info('Registros eliminados de BD', ['path' => $path, 'count' => $deletedRecords]);
            
            return response()->json(['success' => true, 'message' => 'Archivo eliminado correctamente']);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar archivo desde R2', [
                'path' => $path ?? 'null',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar el archivo: ' . $e->getMessage()], 500);
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
        $files = FilesRegistry::where('model_type', 'App\\Provider')
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

    /**
     * Debug function to test file paths and R2 storage
     * 
     * @param string $path
     * @return \Illuminate\Http\JsonResponse
     */
    public function debugPath($path)
    {
        $originalPath = $path;
        $decodedPath = urldecode($path);
        
        $debug = [
            'original_path' => $originalPath,
            'decoded_path' => $decodedPath,
            'exists_original' => Storage::disk('r2')->exists($originalPath),
            'exists_decoded' => Storage::disk('r2')->exists($decodedPath),
            'r2_files' => [],
        ];
        
        // Intentar listar archivos en el directorio padre
        try {
            $parentDir = dirname($decodedPath);
            $debug['parent_dir'] = $parentDir;
            $debug['r2_files'] = Storage::disk('r2')->files($parentDir);
        } catch (\Exception $e) {
            $debug['r2_error'] = $e->getMessage();
        }
        
        return response()->json($debug);
    }
}
