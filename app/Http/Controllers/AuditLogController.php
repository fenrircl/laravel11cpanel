<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class AuditLogController extends Controller
{
    public function getModuleLogs(string $module, ?string $entity_id = null, int $limit = 10)
    {
        $query = AuditLog::with('user')
            ->where('module', $module)
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($entity_id !== null) {
            $query->where('entity_id', $entity_id);
        }

        $logs = $query->get();
        return $this->enrichLogsWithEntityInfo($logs);
    }

    public function getClienteLogs(?int $clienteId = null, int $limit = 10)
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($clienteId) {
            $query->where(function($q) use ($clienteId) {
                $q->where(function($subQ) use ($clienteId) {
                    $subQ->where('module', 'clientes')
                         ->where('entity_id', $clienteId);
                })
                ->orWhere(function($subQ) use ($clienteId) {
                    $subQ->where('module', 'facturas')
                         ->where('description', 'like', '%cliente%')
                         ->where('description', 'like', '%' . $clienteId . '%');
                });
            });
        } else {
            $query->where(function($q) {
                $q->where('module', 'clientes')
                  ->orWhere(function($subQ) {
                      $subQ->where('module', 'facturas')
                           ->where('description', 'like', '%cliente%');
                  });
            });
        }

        $logs = $query->limit($limit)->get();
        return $this->enrichLogsWithEntityInfo($logs);
    }

    public function getProveedorLogs(?int $proveedorId = null, int $limit = 10)
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($proveedorId) {
            $query->where(function($q) use ($proveedorId) {
                $q->where(function($subQ) use ($proveedorId) {
                    $subQ->where('module', 'proveedores')
                         ->where('entity_id', $proveedorId);
                })
                ->orWhere(function($subQ) use ($proveedorId) {
                    $subQ->where('module', 'facturas')
                         ->where('description', 'like', '%proveedor%')
                         ->where('description', 'like', '%' . $proveedorId . '%');
                });
            });
        } else {
            $query->where(function($q) {
                $q->where('module', 'proveedores')
                  ->orWhere(function($subQ) {
                      $subQ->where('module', 'facturas')
                           ->where('description', 'like', '%proveedor%');
                  });
            });
        }

        $logs = $query->limit($limit)->get();
        return $this->enrichLogsWithEntityInfo($logs);
    }

    public function getFacturasClientesLogs(int $limit = 10)
    {
        // Por ahora mostrar todos los logs de facturas
        $logs = AuditLog::with('user')
            ->where('module', 'facturas')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
            
        return $this->enrichLogsWithEntityInfo($logs);
    }

    public function getFacturasProveedoresLogs(int $limit = 10)
    {
        // Por ahora mostrar todos los logs de facturas
        $logs = AuditLog::with('user')
            ->where('module', 'facturas')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
            
        return $this->enrichLogsWithEntityInfo($logs);
    }

    public function getFacturasGeneralLogs(int $limit = 10)
    {
        $logs = AuditLog::with('user')
            ->where('module', 'facturas')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
            
        return $this->enrichLogsWithEntityInfo($logs);
    }

    public function getCotizacionesLogs(int $limit = 10)
    {
        $logs = AuditLog::with('user')
            ->where('module', 'cotizaciones')
            ->orWhere(function($q) {
                $q->where('module', 'archivos')
                  ->where('description', 'like', '%cotizacion%');
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
            
        return $this->enrichLogsWithEntityInfo($logs);
    }

    public function apiLogs(Request $request)
    {
        $module = $request->get('module');
        $entity_id = $request->get('entity_id');
        $limit = min($request->get('limit', 10), 50);
        
        $logs = $this->getModuleLogs($module, $entity_id, $limit);
        
        return response()->json([
            'success' => true,
            'logs' => $logs->map(function($log) {
                return [
                    'id' => $log->id,
                    'created_at' => $log->created_at?->timezone('America/Santiago')->format('d/m/Y H:i'),
                    'user_name' => $log->user?->name ?? '—',
                    'action' => $log->action,
                    'module' => $log->module,
                    'entity_id' => $log->entity_id,
                    'entity_display_info' => $log->entity_display_info,
                    'description' => $log->description,
                    'changes' => is_array($log->changes) ? $log->changes : json_decode($log->changes ?? '[]', true)
                ];
            })
        ]);
    }

    private function enrichLogsWithEntityInfo($logs)
    {
        $clienteIds = [];
        $proveedorIds = [];
        $facturaIds = [];
        $cotizacionIds = [];

        foreach ($logs as $log) {
            if ($log->module === 'clientes' && !empty($log->entity_id)) {
                $clienteIds[] = $log->entity_id;
            } elseif ($log->module === 'proveedores' && !empty($log->entity_id)) {
                $proveedorIds[] = $log->entity_id;
            } elseif ($log->module === 'facturas' && !empty($log->entity_id)) {
                $facturaIds[] = $log->entity_id;
            } elseif ($log->module === 'cotizaciones' && !empty($log->entity_id)) {
                $cotizacionIds[] = $log->entity_id;
            }
        }

        $clientes = [];
        $proveedores = [];
        $facturas = [];
        $cotizaciones = [];

        if (!empty($clienteIds)) {
            $clientes = \App\Models\Cliente::whereIn('id', array_unique($clienteIds))
                ->select('id', 'rut', 'name')
                ->get()
                ->keyBy('id');
        }

        if (!empty($proveedorIds)) {
            $proveedores = \App\Models\Proveedor::whereIn('id', array_unique($proveedorIds))
                ->select('id', 'rut', 'name')
                ->get()
                ->keyBy('id');
        }

        if (!empty($facturaIds)) {
            $facturas = \App\Models\Factura::whereIn('id', array_unique($facturaIds))
                ->select('id', 'invoice')
                ->get()
                ->keyBy('id');
        }

        if (!empty($cotizacionIds)) {
            $cotizaciones = \App\Models\Cotizacion::whereIn('id', array_unique($cotizacionIds))
                ->select('id', 'work', 'client_id')
                ->with('cliente:id,name,rut')
                ->get()
                ->keyBy('id');
        }

        foreach ($logs as $log) {
            $log->entity_display_info = null;
            
            if ($log->module === 'clientes' && isset($clientes[$log->entity_id])) {
                $cliente = $clientes[$log->entity_id];
                $log->entity_display_info = [
                    'type' => 'cliente',
                    'rut' => $cliente->rut,
                    'name' => $cliente->name,
                    'display' => $cliente->rut
                ];
            } elseif ($log->module === 'proveedores' && isset($proveedores[$log->entity_id])) {
                $proveedor = $proveedores[$log->entity_id];
                $log->entity_display_info = [
                    'type' => 'proveedor',
                    'rut' => $proveedor->rut,
                    'name' => $proveedor->name,
                    'display' => $proveedor->rut
                ];
            } elseif ($log->module === 'facturas' && isset($facturas[$log->entity_id])) {
                $factura = $facturas[$log->entity_id];
                $log->entity_display_info = [
                    'type' => 'factura',
                    'invoice' => $factura->invoice,
                    'display' => $factura->invoice
                ];
            } elseif ($log->module === 'cotizaciones' && isset($cotizaciones[$log->entity_id])) {
                $cotizacion = $cotizaciones[$log->entity_id];
                $log->entity_display_info = [
                    'type' => 'cotizacion',
                    'numero' => $cotizacion->id,
                    'cliente_rut' => $cotizacion->cliente?->rut,
                    'cliente_name' => $cotizacion->cliente?->name,
                    'work' => substr($cotizacion->work, 0, 50),
                    'display' => $cotizacion->id
                ];
            }
        }

        return $logs;
    }

    /**
     * Restaurar cambios en facturas (disponible para usuarios autenticados)
     */
    public function restoreFactura(Request $request, AuditLog $log)
    {
        // Verificar que el log sea de facturas y reversible
        if (!$log->reversible || $log->module !== 'facturas') {
            $message = 'Este registro no es reversible.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
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
                
                // Para eliminación, no necesitamos verificar si existe el número de factura
                // porque la factura original fue eliminada permanentemente
                $restored = \App\Models\Factura::create($attrs);

                \App\Services\AuditLogger::log($request, 'restore', 'facturas', $restored->id, 'Restauró factura desde eliminación (log #'.$log->id.')', [
                    'model' => \App\Models\Factura::class,
                    'data_before' => null,
                    'data_after' => $restored->toArray(),
                    'changes' => ['restored_from_log' => $log->id],
                    'reversible' => false,
                ]);
                
                // Redirigir con éxito
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Factura restaurada correctamente.',
                        'factura' => $restored
                    ]);
                }
                
                return back()->with('success', 'Factura restaurada correctamente.');
            }

            if ($action === 'update') {
                // Revertir la factura a su estado anterior
                $entityId = $log->entity_id;
                $factura = \App\Models\Factura::find($entityId);
                if (!$factura) {
                    $message = 'Factura no encontrada para revertir.';
                    if ($request->expectsJson()) {
                        return response()->json(['success' => false, 'message' => $message], 404);
                    }
                    return back()->with('error', $message);
                }

                $attrs = $before ?: [];
                $attrs = Arr::except($attrs, ['id','created_at','updated_at']);
                $factura->update($attrs);

                \App\Services\AuditLogger::log($request, 'restore', 'facturas', $factura->id, 'Revirtió cambios de factura (log #'.$log->id.')', [
                    'model' => \App\Models\Factura::class,
                    'data_before' => $after,
                    'data_after' => $factura->fresh()->toArray(),
                    'changes' => ['reverted_from_log' => $log->id],
                    'reversible' => true,
                ]);
                
                $message = 'Cambios revertidos correctamente.';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'factura' => $factura->fresh()
                    ]);
                }
                
                return back()->with('success', $message);
            }
        } catch (\Throwable $e) {
            report($e);
            $message = 'No se pudo completar la restauración.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }

        $message = 'Acción de auditoría no soportada para revertir.';
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 400);
        }
        return back()->with('error', $message);
    }
}
