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
        
        // Normalizar Unicode para resolver diferencias de acentos
        // NFD (Normalization Form Decomposed) + quitar diacríticos si es necesario
        $normalizedPath = \Normalizer::normalize($path, \Normalizer::FORM_D);
        if ($normalizedPath !== $path) {
            \Log::info('Unicode normalizado', [
                'original' => $path,
                'normalized' => $normalizedPath
            ]);
            $path = $normalizedPath;
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
        
        // Primero sanitizar para JSON
        $filename = $this->sanitizeForJson($filename);
        
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

    /**
     * Find alternative file path by trying different Unicode normalizations
     * Helps resolve accent differences between database and R2 storage
     *
     * @param string $path
     * @return string|null
     */
    private function findAlternativeFilePath($path)
    {
        $parentDir = dirname($path);
        $fileName = basename($path);
        
        // No buscar si el directorio padre no existe
        if (!Storage::disk('r2')->exists($parentDir)) {
            return null;
        }
        
        // Obtener todos los archivos del directorio
        $filesInDir = Storage::disk('r2')->files($parentDir);
        
        // Crear variaciones del nombre del archivo para comparar
        $variations = [
            $fileName, // Original
            $this->removeAccents($fileName), // Sin acentos
            $this->addCommonAccents($fileName), // Con acentos comunes
            \Normalizer::normalize($fileName, \Normalizer::FORM_C), // NFC
            \Normalizer::normalize($fileName, \Normalizer::FORM_D), // NFD
        ];
        
        // Agregar variación sin espacios y con guiones bajos
        foreach ($variations as $var) {
            $variations[] = str_replace(' ', '_', $var);
            $variations[] = str_replace('_', ' ', $var);
        }
        
        // Eliminar duplicados
        $variations = array_unique($variations);
        
        \Log::info('Buscando variaciones de archivo', [
            'original_filename' => $fileName,
            'variations' => $variations,
            'files_in_directory' => array_map('basename', $filesInDir)
        ]);
        
        // Buscar coincidencia exacta primero
        foreach ($variations as $variation) {
            $testPath = $parentDir . '/' . $variation;
            if (in_array($testPath, $filesInDir)) {
                return $testPath;
            }
        }
        
        // Buscar coincidencia fuzzy (comparar sin acentos ni espacios)
        $baseComparison = $this->normalizeForComparison($fileName);
        
        foreach ($filesInDir as $filePath) {
            $fileNameInDir = basename($filePath);
            $normalizedFileInDir = $this->normalizeForComparison($fileNameInDir);
            
            if ($baseComparison === $normalizedFileInDir) {
                \Log::info('Encontrada coincidencia fuzzy', [
                    'requested' => $fileName,
                    'found' => $fileNameInDir,
                    'normalized_requested' => $baseComparison,
                    'normalized_found' => $normalizedFileInDir
                ]);
                return $filePath;
            }
        }
        
        return null;
    }
    
    /**
     * Remove accents from string
     * @param string $str
     * @return string
     */
    private function removeAccents($str)
    {
        $str = \Normalizer::normalize($str, \Normalizer::FORM_D);
        $str = preg_replace('/[\x{0300}-\x{036f}]/u', '', $str); // Remove combining diacritical marks
        return \Normalizer::normalize($str, \Normalizer::FORM_C);
    }
    
    /**
     * Add common Spanish accents to vowels
     * @param string $str
     * @return string
     */
    private function addCommonAccents($str)
    {
        $replacements = [
            'Electronico' => 'Electrónico',
            'electronico' => 'electrónico',
            'Recibido' => 'Recibido', // No cambia
            'Documento' => 'Documento', // No cambia
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $str);
    }
    
    /**
     * Normalize string for comparison (remove accents, spaces, convert to lowercase)
     * @param string $str
     * @return string
     */
    private function normalizeForComparison($str)
    {
        $str = strtolower($str);
        $str = $this->removeAccents($str);
        $str = preg_replace('/[^a-z0-9._-]/', '', $str); // Keep only alphanumeric and common file chars
        return $str;
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
                // Intentar variaciones del nombre para resolver diferencias de acentos
                $alternativePath = $this->findAlternativeFilePath($path);
                
                if ($alternativePath && Storage::disk('r2')->exists($alternativePath)) {
                    \Log::info('Encontrado archivo alternativo', [
                        'requested_path' => $path,
                        'found_path' => $alternativePath
                    ]);
                    $path = $alternativePath;
                } else {
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
                        'alternative_attempted' => $alternativePath,
                        'parent_directory' => $parentDir,
                        'directory_exists' => Storage::disk('r2')->exists($parentDir),
                        'files_in_directory' => array_slice($similarFiles, 0, 10) // Limitar para logs
                    ]);
                    
                    abort(404, 'Archivo no encontrado en R2: ' . $path);
                }
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
            \Log::info('=== INICIO UPLOAD FILE ===', [
                'method' => $request->method(),
                'url' => $request->url(),
                'has_invoice' => $request->has('invoice'),
                'has_file' => $request->hasFile('file'),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            // Soporte para facturas por invoice number
            if ($request->has('invoice')) {
                \Log::info('Procesando upload por número de factura');
                
                // Validación para facturas por invoice
                $request->validate([
                    'file' => 'required|file|max:10240', // Max 10MB
                    'invoice' => 'required|string'
                ]);

                $file = $request->file('file');
                $invoice = $request->input('invoice');

                \Log::info('Datos validados:', [
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getMimeType(),
                    'invoice' => $invoice
                ]);

                // Buscar la factura por número de invoice
                $factura = DB::table('invoices')->where('invoice', $invoice)->first();
                
                if (!$factura) {
                    \Log::error('Factura no encontrada', ['invoice' => $invoice]);
                    return $this->safeJsonResponse([
                        'status' => 'error',
                        'message' => 'Factura no encontrada'
                    ], 404);
                }

                \Log::info('Factura encontrada:', [
                    'id' => $factura->id,
                    'invoice' => $factura->invoice,
                    'client_id' => $factura->client_id,
                    'provider_id' => $factura->provider_id
                ]);

                // Determinar el tipo de factura (cliente o proveedor) y crear el path apropiado
                $tipoFactura = $factura->client_id ? 'clientes' : 'proveedores';
                $numeroFactura = $factura->invoice; // Usar número de factura en lugar del ID
                
                \Log::info('Configuración de subida:', [
                    'tipo_factura' => $tipoFactura,
                    'numero_factura' => $numeroFactura
                ]);
                
                // Generate a unique filename with sanitization
                $originalName = $this->sanitizeFilename($file->getClientOriginalName());
                $extension = $file->getClientOriginalExtension();
                $fileName = pathinfo($originalName, PATHINFO_FILENAME);
                $fileName = $this->sanitizeFilename($fileName); // Sanitizar también el nombre base
                $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                
                // Create the storage path: facturas/clientes/{numero_factura} o facturas/proveedores/{numero_factura}
                $storagePath = "facturas/{$tipoFactura}/{$numeroFactura}/{$uniqueFileName}";

                \Log::info('Archivos procesados:', [
                    'original_name' => $originalName,
                    'unique_filename' => $uniqueFileName,
                    'storage_path' => $storagePath
                ]);

                // Upload to R2
                $uploaded = Storage::disk('r2')->putFileAs(
                    "facturas/{$tipoFactura}/{$numeroFactura}",
                    $file,
                    $uniqueFileName
                );

                \Log::info('Resultado upload a R2:', [
                    'uploaded' => $uploaded ? 'success' : 'failed',
                    'path' => $uploaded
                ]);

                if (!$uploaded) {
                    \Log::error('Falló la subida a R2');
                    return $this->safeJsonResponse([
                        'status' => 'error',
                        'message' => 'Error al subir el archivo'
                    ], 500);
                }

                // Register in database
                \Log::info('Registrando archivo en base de datos');
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

                \Log::info('Archivo registrado en base de datos:', [
                    'file_registry_id' => $fileRegistry->id,
                    'model_type' => $fileRegistry->model_type,
                    'model_id' => $fileRegistry->model_id,
                    'real_id' => $fileRegistry->real_id,
                    'path' => $fileRegistry->path
                ]);

                // Auditoría
                AuditLogger::log($request, 'upload', 'archivos', $factura->id, 'Subió archivo a factura #' . $factura->invoice);

                \Log::info('=== UPLOAD COMPLETADO EXITOSAMENTE ===');
                return $this->safeJsonResponse([
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
                    return $this->safeJsonResponse([
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
                    return $this->safeJsonResponse([
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
                return $this->safeJsonResponse([
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

            return $this->safeJsonResponse([
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
            \Log::error('=== ERROR EN UPLOAD FILE ===', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['file']), // Excluir archivo del log
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);
            
            $errorMessage = $this->sanitizeForJson('Error al procesar el archivo: ' . $e->getMessage());
            
            return $this->safeJsonResponse([
                'success' => false,
                'status' => 'error',
                'message' => $errorMessage
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
                    'name' => $this->sanitizeForJson($f->file_name),
                    'size' => $f->size,
                    'mime_type' => $this->sanitizeForJson($f->mime_type),
                    'created_at' => $f->created_at,
                    'path' => $this->sanitizeForJson($f->path),
                    'download_url' => route('files.download', ['path' => $f->path])
                ];
            });
            return $this->safeJsonResponse(['success' => true, 'files' => $files]);
        } catch (\Exception $e) {
            $errorMessage = $this->sanitizeForJson('Error al obtener archivos: ' . $e->getMessage());
            return $this->safeJsonResponse(['success' => false, 'message' => $errorMessage], 500);
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
            
            \Log::info('Eliminando archivo por ID', [
                'id' => $id,
                'path' => $f->path,
                'filename' => $f->file_name
            ]);
            
            if (Storage::disk('r2')->exists($f->path)) {
                Storage::disk('r2')->delete($f->path);
                \Log::info('Archivo eliminado de R2', ['path' => $f->path]);
            }
            
            $f->delete();
            \Log::info('Registro eliminado de BD', ['id' => $id]);
            
            return $this->safeJsonResponse([
                'success' => true,
                'message' => 'Archivo eliminado correctamente'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al eliminar archivo por ID', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = $this->sanitizeForJson('Error al eliminar el archivo: ' . $e->getMessage());
            
            return $this->safeJsonResponse([
                'success' => false, 
                'message' => $errorMessage
            ], 500);
        }
    }

    /**
     * Sanitize string for JSON response to prevent UTF-8 encoding issues
     *
     * @param string $string
     * @return string
     */
    private function sanitizeForJson($string)
    {
        if (!is_string($string)) {
            return $string;
        }
        
        // Limpiar caracteres nulos y de control
        $string = str_replace("\x00", '', $string);
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
        
        // Asegurar codificación UTF-8 válida
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'auto');
        }
        
        // Limpiar caracteres UTF-8 inválidos
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        
        return trim($string);
    }

    /**
     * Create a safe JSON response
     *
     * @param array $data
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    private function safeJsonResponse(array $data, int $status = 200)
    {
        // Sanitizar recursivamente todos los strings en el array
        array_walk_recursive($data, function(&$value) {
            if (is_string($value)) {
                $value = $this->sanitizeForJson($value);
            }
        });
        
        return response()->json($data, $status, [
            'Content-Type' => 'application/json; charset=utf-8'
        ], JSON_UNESCAPED_UNICODE);
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
            
            return $this->safeJsonResponse([
                'success' => true, 
                'message' => 'Archivo eliminado correctamente'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al eliminar archivo desde R2', [
                'path' => $path ?? 'null',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = $this->sanitizeForJson('Error al eliminar el archivo: ' . $e->getMessage());
            
            return $this->safeJsonResponse([
                'success' => false, 
                'message' => $errorMessage
            ], 500);
        }
    }

    // Listar archivos adjuntos de un cliente
    public function listClienteFiles($clienteId)
    {
        try {
            $files = FilesRegistry::where('model_type', 'App\\Client')
                ->where('model_id', $clienteId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $this->sanitizeForJson($file->file_name),
                        'size' => $file->size,
                        'mime_type' => $this->sanitizeForJson($file->mime_type),
                        'created_at' => optional($file->created_at)->format('d/m/Y H:i'),
                        'download_url' => route('files.download', ['path' => $file->path])
                    ];
                });
            return $this->safeJsonResponse(['status' => 'success', 'files' => $files]);
        } catch (\Exception $e) {
            $errorMessage = $this->sanitizeForJson('Error al listar archivos del cliente: ' . $e->getMessage());
            return $this->safeJsonResponse(['status' => 'error', 'message' => $errorMessage], 500);
        }
    }

    // Listar archivos adjuntos de un proveedor
    public function listProveedorFiles($proveedorId)
    {
        try {
            $files = FilesRegistry::where('model_type', 'App\\Provider')
                ->where('model_id', $proveedorId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $this->sanitizeForJson($file->file_name),
                        'size' => $file->size,
                        'mime_type' => $this->sanitizeForJson($file->mime_type),
                        'created_at' => optional($file->created_at)->format('d/m/Y H:i'),
                        'download_url' => route('files.download', ['path' => $file->path])
                    ];
                });
            return $this->safeJsonResponse(['status' => 'success', 'files' => $files]);
        } catch (\Exception $e) {
            $errorMessage = $this->sanitizeForJson('Error al listar archivos del proveedor: ' . $e->getMessage());
            return $this->safeJsonResponse(['status' => 'error', 'message' => $errorMessage], 500);
        }
    }

    // Listar PDFs de cotizaciones de un cliente (en base a FilesRegistry de App\Cotizacion)
    public function listClientCotizacionFiles($clienteId)
    {
        try {
            $cotIds = \App\Models\Cotizacion::where('client_id', $clienteId)->pluck('id');
            if ($cotIds->isEmpty()) {
                return $this->safeJsonResponse(['status' => 'success', 'files' => []]);
            }
            $files = FilesRegistry::where('model_type', 'App\\Quotation')
                ->whereIn('model_id', $cotIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $this->sanitizeForJson($file->file_name),
                        'size' => $file->size,
                        'mime_type' => $this->sanitizeForJson($file->mime_type),
                        'created_at' => optional($file->created_at)->format('d/m/Y H:i'),
                        'download_url' => route('files.download', ['path' => $file->path])
                    ];
                });
            return $this->safeJsonResponse(['status' => 'success', 'files' => $files]);
        } catch (\Exception $e) {
            $errorMessage = $this->sanitizeForJson('Error al listar archivos de cotizaciones: ' . $e->getMessage());
            return $this->safeJsonResponse(['status' => 'error', 'message' => $errorMessage], 500);
        }
    }

    /**
     * Debug function to test file paths and R2 storage
     * 
     * @param string $path
     * @return \Illuminate\Http\JsonResponse
     */
    public function debugPath($path)
    {
        try {
            $originalPath = $path;
            $decodedPath = urldecode($path);
            
            $debug = [
                'original_path' => $this->sanitizeForJson($originalPath),
                'decoded_path' => $this->sanitizeForJson($decodedPath),
                'exists_original' => Storage::disk('r2')->exists($originalPath),
                'exists_decoded' => Storage::disk('r2')->exists($decodedPath),
                'r2_files' => [],
            ];
            
            // Intentar listar archivos en el directorio padre
            try {
                $parentDir = dirname($decodedPath);
                $debug['parent_dir'] = $this->sanitizeForJson($parentDir);
                $files = Storage::disk('r2')->files($parentDir);
                $debug['r2_files'] = array_map([$this, 'sanitizeForJson'], $files);
            } catch (\Exception $e) {
                $debug['r2_error'] = $this->sanitizeForJson($e->getMessage());
            }
            
            return $this->safeJsonResponse($debug);
        } catch (\Exception $e) {
            $errorMessage = $this->sanitizeForJson('Error en debug: ' . $e->getMessage());
            return $this->safeJsonResponse(['error' => $errorMessage], 500);
        }
    }
}
