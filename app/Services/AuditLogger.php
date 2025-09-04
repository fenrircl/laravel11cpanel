<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * Registra un log de auditoría usando el user_id de la sesión/userContext.
     */
    public static function log(Request $request, string $action, string $module, $entityId = null, ?string $description = null): void
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

        AuditLog::create([
            'user_id' => (int) $userId,
            'action' => $action,
            'module' => $module,
            'entity_id' => isset($entityId) ? (string) $entityId : null,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => url()->current(),
            'method' => $request->method(),
        ]);
    }
}
