<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->orWhere(function($q) {
                $q->where('module', 'archivos')
                  ->where('description', 'like', '%factura%');
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

        foreach ($logs as $log) {
            if ($log->module === 'clientes' && !empty($log->entity_id)) {
                $clienteIds[] = $log->entity_id;
            } elseif ($log->module === 'proveedores' && !empty($log->entity_id)) {
                $proveedorIds[] = $log->entity_id;
            } elseif ($log->module === 'facturas' && !empty($log->entity_id)) {
                $facturaIds[] = $log->entity_id;
            }
        }

        $clientes = [];
        $proveedores = [];
        $facturas = [];

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
            }
        }

        return $logs;
    }
}
