<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
}
