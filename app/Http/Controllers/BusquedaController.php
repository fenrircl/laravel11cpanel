<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Factura;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Controlador para búsqueda global del sistema
 * Optimizado para servidor básico de cPanel con MySQL
 */
class BusquedaController extends Controller
{
    /**
     * Tiempo de caché en minutos
     */
    const CACHE_TIME = 30;
    
    /**
     * Límite máximo de resultados por entidad
     */
    const MAX_RESULTS_PER_ENTITY = 20;
    
    /**
     * Búsqueda global en todas las entidades
     */
    public function buscar(Request $request)
    {
        $query = $request->get('q', '');
        $entity = $request->get('entity', 'all');
        $limit = min($request->get('limit', 50), 100); // Máximo 100 resultados
        
        // Validar query mínimo
        if (strlen(trim($query)) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'La consulta debe tener al menos 2 caracteres',
                'results' => []
            ], 400);
        }
        
        try {
            $results = [];
            
            if ($entity === 'all') {
                // Buscar en todas las entidades
                $results['clientes'] = $this->buscarClientes($query, self::MAX_RESULTS_PER_ENTITY);
                $results['proveedores'] = $this->buscarProveedores($query, self::MAX_RESULTS_PER_ENTITY);
                $results['facturas'] = $this->buscarFacturas($query, self::MAX_RESULTS_PER_ENTITY);
            } else {
                // Buscar solo en la entidad especificada
                switch ($entity) {
                    case 'clientes':
                        $results['clientes'] = $this->buscarClientes($query, $limit);
                        break;
                    case 'proveedores':
                        $results['proveedores'] = $this->buscarProveedores($query, $limit);
                        break;
                    case 'facturas':
                        $results['facturas'] = $this->buscarFacturas($query, $limit);
                        break;
                }
            }
            
            // Calcular total de resultados
            $totalResults = array_sum(array_map('count', $results));
            
            return response()->json([
                'success' => true,
                'query' => $query,
                'entity' => $entity,
                'total' => $totalResults,
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en búsqueda global', [
                'query' => $query,
                'entity' => $entity,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'results' => []
            ], 500);
        }
    }
    
    /**
     * Buscar clientes
     */
    private function buscarClientes($query, $limit)
    {
        return Cliente::select(['id', 'name', 'email', 'phone', 'rut', 'created_at'])
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->orWhere('phone', 'LIKE', "%{$query}%");
            })
            ->orderByRaw("
                CASE 
                    WHEN name LIKE '{$query}%' THEN 1
                    WHEN name LIKE '%{$query}%' THEN 2
                    WHEN email LIKE '{$query}%' THEN 3
                    ELSE 4
                END
            ")
            ->limit($limit)
            ->get()
            ->map(function($cliente) use ($query) {
                return array_merge($cliente->toArray(), [
                    'relevance' => $this->calculateRelevance($cliente, $query, ['name', 'rut', 'email', 'phone'])
                ]);
            });
    }
    
    /**
     * Buscar proveedores
     */
    private function buscarProveedores($query, $limit)
    {
        return Proveedor::select(['id', 'name', 'email', 'rut', 'phone', 'created_at'])
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->orWhere('phone', 'LIKE', "%{$query}%");
            })
            ->orderByRaw("
                CASE 
                    WHEN name LIKE '{$query}%' THEN 1
                    WHEN name LIKE '%{$query}%' THEN 2
                    WHEN email LIKE '{$query}%' THEN 3
                    ELSE 4
                END
            ")
            ->limit($limit)
            ->get()
            ->map(function($proveedor) use ($query) {
                return array_merge($proveedor->toArray(), [
                    'relevance' => $this->calculateRelevance($proveedor, $query, ['name', 'rut','email', 'phone'])
                ]);
            });
    }
    
    /**
     * Buscar facturas
     */
    private function buscarFacturas($query, $limit)
    {
        return Factura::select([
                'invoices.id', 
                'invoices.invoice as numero', 
                'invoices.client_id', 
                'invoices.provider_id', 
                'invoices.amount as total', 
                'invoices.date as fecha_emision', 
                'invoices.expiry as fecha_vencimiento',
                'clients.name as cliente_name',
                'providers.name as proveedor_name'
            ])
            ->leftJoin('clients', 'invoices.client_id', '=', 'clients.id')
            ->leftJoin('providers', 'invoices.provider_id', '=', 'providers.id')
            ->where(function($q) use ($query) {
                $q->where('invoices.invoice', 'LIKE', "%{$query}%")
                  ->orWhere('invoices.amount', 'LIKE', "%{$query}%")
                  ->orWhere('clients.name', 'LIKE', "%{$query}%")
                  ->orWhere('providers.name', 'LIKE', "%{$query}%")
                  ->orWhere('invoices.date', 'LIKE', "%{$query}%")
                  ->orWhere('invoices.expiry', 'LIKE', "%{$query}%");
            })
            ->orderByRaw("
                CASE 
                    WHEN invoices.invoice LIKE '{$query}%' THEN 1
                    WHEN clients.name LIKE '{$query}%' THEN 2
                    WHEN providers.name LIKE '{$query}%' THEN 3
                    WHEN invoices.amount LIKE '%{$query}%' THEN 4
                    ELSE 5
                END
            ")
            ->limit($limit)
            ->get()
            ->map(function($factura) use ($query) {
                return array_merge($factura->toArray(), [
                    'relevance' => $this->calculateRelevance($factura, $query, ['numero', 'total', 'cliente_name', 'proveedor_name'])
                ]);
            });
    }
    
    /**
     * Calcular relevancia de un resultado
     */
    private function calculateRelevance($item, $query, $fields)
    {
        $relevance = 0;
        $query = strtolower($query);
        
        foreach ($fields as $field) {
            $value = strtolower($item->{$field} ?? '');
            
            if (empty($value)) continue;
            
            // Coincidencia exacta al inicio: máximo puntaje
            if (strpos($value, $query) === 0) {
                $relevance += 10;
            }
            // Coincidencia en cualquier parte
            elseif (strpos($value, $query) !== false) {
                $relevance += 5;
            }
            
            // Bonus para campos principales
            if (in_array($field, ['name', 'numero'])) {
                $relevance += 2;
            }
        }
        
        return $relevance;
    }
    
    /**
     * Endpoint para obtener datos esenciales para caché
     */
    public function datosParaCache($entidad)
    {
        $cacheKey = "search_data_{$entidad}";
        
        $data = Cache::remember($cacheKey, self::CACHE_TIME, function() use ($entidad) {
            switch ($entidad) {
                case 'clientes':
                    return Cliente::select(['id', 'name', 'rut', 'email', 'phone', 'created_at'])
                        ->orderBy('created_at', 'desc')
                        ->limit(1000)
                        ->get();
                        
                case 'proveedores':
                    return Proveedor::select(['id', 'name', 'rut', 'email', 'phone', 'created_at'])
                        ->orderBy('created_at', 'desc')
                        ->limit(1000)
                        ->get();
                        
                case 'facturas':
                    return Factura::select([
                            'invoices.id', 
                            'invoices.invoice as numero', 
                            'invoices.client_id', 
                            'invoices.provider_id', 
                            'invoices.amount as total', 
                            'invoices.date as fecha_emision', 
                            'invoices.expiry as fecha_vencimiento'
                        ])
                        ->orderBy('date', 'desc')
                        ->limit(100)
                        ->get();
                        
                default:
                    return collect();
            }
        });
        
        // Generar hash para detectar cambios
        $hash = md5(json_encode($data));
        
        return response()->json([
            'success' => true,
            'entity' => $entidad,
            'data' => $data,
            'hash' => $hash,
            'count' => $data->count(),
            'cached_at' => now()->toISOString()
        ]);
    }
    
    /**
     * Limpiar caché de búsqueda
     */
    public function limpiarCache()
    {
        $entities = ['clientes', 'proveedores', 'facturas'];
        
        foreach ($entities as $entity) {
            Cache::forget("search_data_{$entity}");
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Caché de búsqueda limpiado correctamente'
        ]);
    }
    
    /**
     * Obtener estadísticas de búsqueda
     */
    public function estadisticas()
    {
        $stats = [
            'clientes' => [
                'total' => Cliente::count(),
                'activos' => Cliente::where('status', 1)->count(),
            ],
            'proveedores' => [
                'total' => Proveedor::count(),
                'activos' => Proveedor::where('status', 1)->count(),
            ],
            'facturas' => [
                'total' => Factura::count(),
                'este_mes' => Factura::whereMonth('fecha_emision', now()->month)->count(),
            ]
        ];
        
        return response()->json([
            'success' => true,
            'stats' => $stats,
            'updated_at' => now()->toISOString()
        ]);
    }
}
