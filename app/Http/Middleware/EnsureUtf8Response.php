<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUtf8Response
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo aplicar a respuestas JSON
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            // Asegurar UTF-8 en headers
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            
            // Obtener el contenido JSON y asegurar que sea UTF-8 válido
            $content = $response->getContent();
            
            if ($content && is_string($content)) {
                // Verificar si el contenido es UTF-8 válido
                if (!mb_check_encoding($content, 'UTF-8')) {
                    \Log::warning('Contenido JSON con codificación inválida detectado', [
                        'original_length' => strlen($content),
                        'first_100_chars' => substr($content, 0, 100)
                    ]);
                    
                    // Intentar convertir a UTF-8
                    $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                    
                    // Verificar nuevamente
                    if (!mb_check_encoding($content, 'UTF-8')) {
                        \Log::error('No se pudo convertir contenido a UTF-8 válido');
                        
                        // Como último recurso, limpiar caracteres problemáticos
                        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                    }
                    
                    $response->setContent($content);
                }
                
                // Asegurar que no haya caracteres de control problemáticos
                $cleanContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
                
                if ($cleanContent !== $content) {
                    \Log::info('Caracteres de control removidos del JSON response');
                    $response->setContent($cleanContent);
                }
            }
        }

        return $response;
    }
}
