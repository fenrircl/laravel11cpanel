<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\FilesRegistry;
use Illuminate\Support\Facades\DB;

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
            // Validate request
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

            // Generate a unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
            
            // Create the storage path
            $storagePath = "facturas/{$modelId}/{$uniqueFileName}";

            // Upload to R2
            $uploaded = Storage::disk('r2')->putFileAs(
                "facturas/{$modelId}",
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
                'migrated' => 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Archivo subido exitosamente',
                'file' => [
                    'id' => $fileRegistry->id,
                    'name' => $originalName,
                    'size' => $file->getSize(),
                    'path' => $storagePath
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
            $modelType = $request->input('model_type');
            $modelId = $request->input('model_id');

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
                'success' => true,
                'files' => $files
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
