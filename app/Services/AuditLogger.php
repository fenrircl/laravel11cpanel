<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * Registra un log de auditoría usando el user_id de la sesión/userContext.
     * Permite incluir snapshots before/after y diff de cambios.
     */
    public static function log(Request $request, string $action, string $module, $entityId = null, ?string $description = null, array $extra = []): void
    {
        // Obtener user_id preferentemente desde sesión poblada por el middleware
        $userId = $request->session()->get('user_id');
        if ($userId === null) {
            $ctx = $request->attributes->get('userContext');
            if (is_array($ctx) && isset($ctx['id'])) {
                $userId = (int) $ctx['id'];
            }
        }

        // Si no hay usuario (p. ej., durante login fallido), no registrar
        if ($userId === null) {
            return;
        }

        // Normalizar la descripción para evitar problemas de codificación
        if ($description) {
            $description = self::normalizeDescription($description);
        }

        $payload = [
            'user_id' => (int) $userId,
            'action' => $action,
            'module' => $module,
            'entity_id' => isset($entityId) ? (string) $entityId : null,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => url()->current(),
            'method' => $request->method(),
        ];

        // Campos extendidos opcionales: model, data_before, data_after, changes, reversible
        foreach (['model','data_before','data_after','changes','reversible'] as $key) {
            if (array_key_exists($key, $extra)) {
                $payload[$key] = is_array($extra[$key]) ? json_encode($extra[$key], JSON_UNESCAPED_UNICODE) : $extra[$key];
            }
        }

        AuditLog::create($payload);
    }

    /**
     * Calcula un diff básico campo por campo entre arreglos (solo escalares/strings)
     */
    public static function simpleDiff(array $before, array $after): array
    {
        $changes = [];
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($keys as $k) {
            $b = $before[$k] ?? null;
            $a = $after[$k] ?? null;
            if ($b instanceof \DateTimeInterface) $b = $b->format('Y-m-d H:i:s');
            if ($a instanceof \DateTimeInterface) $a = $a->format('Y-m-d H:i:s');
            if ($b !== $a) {
                $changes[$k] = ['from' => $b, 'to' => $a];
            }
        }
        return $changes;
    }

    /**
     * Normaliza la descripción para evitar problemas de codificación UTF-8
     */
    private static function normalizeDescription(string $description): string
    {
        // Asegurar codificación UTF-8 correcta
        $description = mb_convert_encoding($description, 'UTF-8', 'UTF-8');
        
        // Corregir caracteres mal codificados comunes
        $replacements = [
            'Ã±' => 'ñ', 'Ã'' => 'Ñ', 'Ã³' => 'ó', 'Ã©' => 'é', 'Ã­' => 'í', 
            'Ãº' => 'ú', 'Ã¡' => 'á', 'Ã¹' => 'ù', 'Ã ' => 'à', 'Ã¨' => 'è',
            'Ãª' => 'ê', 'Ã´' => 'ô', 'Ã¢' => 'â', 'Ãç' => 'ç', 'Ã¼' => 'ü',
            'CreÃ³' => 'Creó', 'EditÃ³' => 'Editó', 'EliminÃ³' => 'Eliminó',
            'ActualizÃ³' => 'Actualizó', 'SubiÃ³' => 'Subió', 'DescargÃ³' => 'Descargó'
        ];
        
        $description = str_replace(array_keys($replacements), array_values($replacements), $description);
        
        // Limpiar caracteres de control no imprimibles
        $description = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $description);
        
        // Decodificar entidades HTML si existen
        $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
        
        return trim($description);
    }
}
