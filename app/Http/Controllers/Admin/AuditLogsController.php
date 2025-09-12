<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AuditLogsController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderByDesc('id');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($w) use ($q) {
                $w->where('description', 'like', "%{$q}%")
                  ->orWhere('entity_id', 'like', "%{$q}%")
                  ->orWhere('url', 'like', "%{$q}%");
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'asset_css' => ['comun/tablas'],
        ]);
    }

    public function restore(Request $request, AuditLog $log)
    {
        if (!$log->reversible || $log->module !== 'facturas') {
            return back()->with('error', 'Este registro no es reversible.');
        }

        $action = $log->action;
        $before = is_array($log->data_before) ? $log->data_before : (json_decode($log->data_before, true) ?: []);
        $after = is_array($log->data_after) ? $log->data_after : (json_decode($log->data_after, true) ?: []);

        try {
            if ($action === 'delete') {
                // Restaurar creando una nueva factura desde snapshot "before"
                $attrs = $before ?: [];
                // Limpiar campos no asignables
                $attrs = Arr::except($attrs, ['id','created_at','updated_at']);
                // Resolver conflicto de número de factura si existe
                if (!empty($attrs['invoice'])) {
                    $exists = Factura::query()
                        ->where('invoice', $attrs['invoice'])
                        ->when(!empty($attrs['client_id']), fn($q)=>$q->whereNotNull('client_id'))
                        ->when(!empty($attrs['provider_id']), fn($q)=>$q->whereNotNull('provider_id'))
                        ->exists();
                    if ($exists) {
                        $attrs['invoice'] = $attrs['invoice'].'-RESTORE-'.$log->id;
                    }
                }
                $restored = Factura::create($attrs);

                \App\Services\AuditLogger::log($request, 'restore', 'facturas', $restored->id, 'Restauró factura desde eliminación (log #'.$log->id.')', [
                    'model' => Factura::class,
                    'data_before' => null,
                    'data_after' => $restored->toArray(),
                    'changes' => ['restored_from_log' => $log->id],
                    'reversible' => false,
                ]);
                return back()->with('success', 'Factura restaurada correctamente.');
            }

            if ($action === 'update') {
                // Revertir la factura a su estado anterior
                $entityId = $log->entity_id;
                $factura = Factura::find($entityId);
                if (!$factura) return back()->with('error', 'Factura no encontrada para revertir.');

                $attrs = $before ?: [];
                $attrs = Arr::except($attrs, ['id','created_at','updated_at']);
                $factura->update($attrs);

                \App\Services\AuditLogger::log($request, 'restore', 'facturas', $factura->id, 'Revirtió cambios de factura (log #'.$log->id.')', [
                    'model' => Factura::class,
                    'data_before' => $after,
                    'data_after' => $factura->fresh()->toArray(),
                    'changes' => ['reverted_from_log' => $log->id],
                    'reversible' => true,
                ]);
                return back()->with('success', 'Cambios revertidos correctamente.');
            }
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'No se pudo completar la restauración.');
        }

        return back()->with('error', 'Acción de auditoría no soportada para revertir.');
    }
}
