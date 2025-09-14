<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Factura;
use App\Models\Cotizacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;

class McpController extends Controller
{
    /**
     * Endpoint dinámico tipo MCP para consultas de clientes, proveedores y facturas.
     */
    public function handle(Request $request)
    {
        try {
            $tipo = strtolower((string) $request->input('tipo'));
            $accion = strtolower((string) $request->input('accion'));
            $filtros = (array) $request->input('filtros', []);

            if (!$tipo || !$accion) {
                return $this->error('Parámetros "tipo" y "accion" son requeridos.', 422);
            }

            switch ($tipo) {
                case 'cliente':
                case 'clientes':
                    $data = $this->handleCliente($accion, $filtros);
                    break;
                case 'proveedor':
                case 'proveedores':
                    $data = $this->handleProveedor($accion, $filtros);
                    break;
                case 'factura':
                case 'facturas':
                    $data = $this->handleFactura($accion, $filtros);
                    break;
                default:
                    return $this->error('Tipo no soportado: ' . $tipo, 422);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Consulta realizada correctamente'
            ]);
        } catch (\Throwable $e) {
            Log::error('MCP error', [ 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString() ]);
            return $this->error('Error procesando la consulta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lista de recursos MCP disponibles.
     */
    public function resources(Request $request): JsonResponse
    {
        $resources = [
            [
                'id' => 'clientes',
                'name' => 'Clientes',
                'description' => 'Gestión de clientes: búsqueda y facturas asociadas',
            ],
            [
                'id' => 'proveedores',
                'name' => 'Proveedores',
                'description' => 'Gestión de proveedores: búsqueda y facturas asociadas',
            ],
            [
                'id' => 'facturas',
                'name' => 'Facturas',
                'description' => 'Búsqueda, listados y cálculos de facturas',
            ],
            [
                'id' => 'cotizaciones',
                'name' => 'Cotizaciones',
                'description' => 'Presupuestos y propuestas a clientes',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $resources,
            'error' => null,
        ]);
    }

    /**
     * Herramientas/acciones MCP por recurso.
     */
    public function tools(Request $request): JsonResponse
    {
        $tools = [
            'clientes' => [
                [
                    'id' => 'buscar',
                    'name' => 'Buscar clientes',
                    'description' => 'Buscar por nombre o RUT',
                    'params' => [
                        'required' => [],
                        'optional' => ['q', 'nombre', 'rut', 'per_page', 'page'],
                    ],
                ],
                [
                    'id' => 'listar_facturas',
                    'name' => 'Listar facturas de cliente',
                    'description' => 'Listar facturas de un cliente con filtros opcionales',
                    'params' => [
                        'required' => ['cliente_id'],
                        'optional' => ['estado', 'desde', 'hasta', 'per_page', 'page', 'sort_by', 'sort_dir'],
                    ],
                ],
                [
                    'id' => 'pendientes',
                    'name' => 'Facturas pendientes de cliente',
                    'description' => 'Lista de facturas pendientes (estado=pendiente) para un cliente',
                    'params' => [
                        'required' => ['cliente_id'],
                        'optional' => ['per_page', 'page', 'sort_by', 'sort_dir'],
                    ],
                ],
                [
                    'id' => 'factura_mayor',
                    'name' => 'Factura de mayor monto (cliente)',
                    'description' => 'Obtiene la factura pendiente de mayor monto. Se puede filtrar por estado.',
                    'params' => [
                        'required' => ['cliente_id'],
                        'optional' => ['estado'],
                    ],
                ],
                [
                    'id' => 'total_pendiente',
                    'name' => 'Total pendiente (cliente)',
                    'description' => 'Suma de montos pendientes del cliente',
                    'params' => [
                        'required' => ['cliente_id'],
                        'optional' => [],
                    ],
                ],
            ],
            'proveedores' => [
                [
                    'id' => 'buscar',
                    'name' => 'Buscar proveedores',
                    'description' => 'Buscar por nombre o RUT',
                    'params' => [
                        'required' => [],
                        'optional' => ['q', 'nombre', 'rut', 'per_page', 'page'],
                    ],
                ],
                [
                    'id' => 'listar_facturas',
                    'name' => 'Listar facturas de proveedor',
                    'description' => 'Listar facturas de un proveedor con filtros opcionales',
                    'params' => [
                        'required' => ['proveedor_id'],
                        'optional' => ['estado', 'desde', 'hasta', 'per_page', 'page', 'sort_by', 'sort_dir'],
                    ],
                ],
                [
                    'id' => 'pendientes',
                    'name' => 'Facturas pendientes de proveedor',
                    'description' => 'Lista de facturas pendientes (estado=pendiente) para un proveedor',
                    'params' => [
                        'required' => ['proveedor_id'],
                        'optional' => ['per_page', 'page', 'sort_by', 'sort_dir'],
                    ],
                ],
                [
                    'id' => 'factura_mayor',
                    'name' => 'Factura de mayor monto (proveedor)',
                    'description' => 'Obtiene la factura pendiente de mayor monto. Se puede filtrar por estado.',
                    'params' => [
                        'required' => ['proveedor_id'],
                        'optional' => ['estado'],
                    ],
                ],
                [
                    'id' => 'total_pendiente',
                    'name' => 'Total pendiente (proveedor)',
                    'description' => 'Suma de montos pendientes del proveedor',
                    'params' => [
                        'required' => ['proveedor_id'],
                        'optional' => [],
                    ],
                ],
            ],
            'facturas' => [
                [
                    'id' => 'buscar',
                    'name' => 'Buscar facturas',
                    'description' => 'Buscar por número o rango de fechas',
                    'params' => [
                        'required' => [],
                        'optional' => ['q', 'numero', 'estado', 'desde', 'hasta', 'tipo', 'cliente_id', 'proveedor_id', 'per_page', 'page', 'sort_by', 'sort_dir'],
                    ],
                ],
                [
                    'id' => 'listar',
                    'name' => 'Listar facturas',
                    'description' => 'Listado general de facturas con filtros',
                    'params' => [
                        'required' => [],
                        'optional' => ['estado', 'desde', 'hasta', 'tipo', 'cliente_id', 'proveedor_id', 'per_page', 'page', 'sort_by', 'sort_dir'],
                    ],
                ],
                [
                    'id' => 'pendientes',
                    'name' => 'Facturas pendientes',
                    'description' => 'Lista de facturas pendientes con filtros opcionales por tipo o entidad',
                    'params' => [
                        'required' => [],
                        'optional' => ['tipo', 'cliente_id', 'proveedor_id', 'desde', 'hasta', 'per_page', 'page', 'sort_by', 'sort_dir'],
                    ],
                ],
                [
                    'id' => 'factura_mayor',
                    'name' => 'Factura de mayor monto',
                    'description' => 'Obtiene la factura de mayor monto con filtros opcionales',
                    'params' => [
                        'required' => [],
                        'optional' => ['estado', 'tipo', 'cliente_id', 'proveedor_id'],
                    ],
                ],
                [
                    'id' => 'total_pendiente',
                    'name' => 'Total pendiente general',
                    'description' => 'Suma de montos pendientes con filtros opcionales',
                    'params' => [
                        'required' => [],
                        'optional' => ['tipo', 'cliente_id', 'proveedor_id', 'desde', 'hasta'],
                    ],
                ],
            ],
            'cotizaciones' => [
                [
                    'id' => 'buscar',
                    'name' => 'Buscar cotizaciones',
                    'description' => 'Buscar por cliente (nombre o RUT) o por texto de trabajo',
                    'params' => [
                        'required' => [],
                        'optional' => ['q', 'cliente_id', 'desde', 'hasta', 'per_page', 'page', 'sort_by', 'sort_dir'],
                    ],
                ],
                [
                    'id' => 'listar',
                    'name' => 'Listar cotizaciones',
                    'description' => 'Listado de cotizaciones con filtros por cliente y fecha',
                    'params' => [
                        'required' => [],
                        'optional' => ['cliente_id', 'desde', 'hasta', 'per_page', 'page', 'sort_by', 'sort_dir'],
                    ],
                ],
                [
                    'id' => 'detalle',
                    'name' => 'Detalle de cotización',
                    'description' => 'Obtiene una cotización con sus ítems',
                    'params' => [
                        'required' => ['id'],
                        'optional' => [],
                    ],
                ],
                [
                    'id' => 'total',
                    'name' => 'Total de cotizaciones',
                    'description' => 'Suma de totales en el rango o por cliente',
                    'params' => [
                        'required' => [],
                        'optional' => ['cliente_id', 'desde', 'hasta'],
                    ],
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $tools,
            'error' => null,
        ]);
    }

    /**
     * Ejecuta una consulta MCP dinámica.
     */
    public function query(Request $request): JsonResponse
    {
        try {
            $resource = strtolower((string) $request->input('recurso'));
            $action = strtolower((string) $request->input('accion'));
            $filters = (array) $request->input('filtros', []);

            if ($resource === '' || $action === '') {
                return $this->mcpError('Parámetros "recurso" y "accion" son requeridos.', 422);
            }

            // Normalizar recurso (acepta singular/plural)
            $resourceNorm = match ($resource) {
                'cliente', 'clientes' => 'clientes',
                'proveedor', 'proveedores' => 'proveedores',
                'factura', 'facturas' => 'facturas',
                'cotizacion', 'cotizaciones' => 'cotizaciones',
                default => ''
            };
            if ($resourceNorm === '') {
                return $this->mcpError('Recurso no soportado: ' . $resource, 422);
            }

            // Validar acción conocida según tools()
            $allowed = array_column($this->tools(new Request())->getData(true)['data'][$resourceNorm], 'id');
            if (!in_array($action, $allowed, true)) {
                return $this->mcpError("Acción '{$action}' no soportada para recurso '{$resourceNorm}'.", 422);
            }

            // Delegar por recurso
            $data = match ($resourceNorm) {
                'clientes' => $this->handleClienteAction($action, $filters),
                'proveedores' => $this->handleProveedorAction($action, $filters),
                'facturas' => $this->handleFacturaAction($action, $filters),
                'cotizaciones' => $this->handleCotizacionAction($action, $filters),
            };

            return response()->json([
                'success' => true,
                'data' => $data,
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('MCP query error', ['error' => $e->getMessage()]);
            return $this->mcpError('Error procesando la consulta: ' . $e->getMessage(), 500);
        }
    }

    // ==== Implementaciones por recurso para MCP ====
    private function handleClienteAction(string $accion, array $filtros)
    {
        return match ($accion) {
            'buscar' => $this->handleCliente('buscar', $filtros),
            'listar_facturas' => $this->handleCliente('listar_facturas', $filtros),
            'pendientes' => $this->listFacturas(array_merge($filtros, ['estado' => 'pendiente'] + (isset($filtros['cliente_id']) ? [] : []))),
            'factura_mayor' => $this->handleCliente('factura_mayor', $filtros),
            'total_pendiente' => $this->handleCliente('total_pendiente', $filtros),
            default => throw new \InvalidArgumentException('Acción no soportada para clientes'),
        };
    }

    private function handleProveedorAction(string $accion, array $filtros)
    {
        return match ($accion) {
            'buscar' => $this->handleProveedor('buscar', $filtros),
            'listar_facturas' => $this->handleProveedor('listar_facturas', $filtros),
            'pendientes' => $this->listFacturas(array_merge($filtros, ['estado' => 'pendiente'] + (isset($filtros['proveedor_id']) ? [] : []))),
            'factura_mayor' => $this->handleProveedor('factura_mayor', $filtros),
            'total_pendiente' => $this->handleProveedor('total_pendiente', $filtros),
            default => throw new \InvalidArgumentException('Acción no soportada para proveedores'),
        };
    }

    private function handleFacturaAction(string $accion, array $filtros)
    {
        return match ($accion) {
            'buscar' => $this->handleFactura('buscar', $filtros),
            'listar', 'listar_facturas' => $this->handleFactura('listar', $filtros),
            'pendientes' => $this->handleFactura('listar', array_merge($filtros, ['estado' => 'pendiente'])),
            'factura_mayor' => $this->handleFactura('factura_mayor', $filtros),
            'total_pendiente' => $this->handleFactura('total_pendiente', $filtros),
            default => throw new \InvalidArgumentException('Acción no soportada para facturas'),
        };
    }

    private function handleCotizacionAction(string $accion, array $filtros)
    {
        return match ($accion) {
            'buscar' => $this->handleCotizacion('buscar', $filtros),
            'listar' => $this->handleCotizacion('listar', $filtros),
            'detalle' => $this->handleCotizacion('detalle', $filtros),
            'total' => $this->handleCotizacion('total', $filtros),
            default => throw new \InvalidArgumentException('Acción no soportada para cotizaciones'),
        };
    }

    // ==== Respuesta de error unificada para MCP ====
    private function mcpError(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => $message,
        ], $status);
    }

    /* ===================== Clientes ===================== */
    private function handleCliente(string $accion, array $filtros)
    {
        switch ($accion) {
            case 'buscar':
                $q = trim((string)($filtros['q'] ?? $filtros['nombre'] ?? $filtros['name'] ?? $filtros['rut'] ?? ''));
                $query = Cliente::query();
                if ($q !== '') {
                    $query->where(function (Builder $b) use ($q) {
                        $b->where('name', 'like', "%{$q}%")
                          ->orWhere('rut', 'like', "%{$q}%");
                    });
                }
                return $this->paginateAndTransform($query->orderByDesc('id'), $filtros, fn($c) => $this->transformCliente($c));

            case 'listar_facturas':
                $clienteId = (int) ($filtros['cliente_id'] ?? $filtros['id'] ?? 0);
                if (!$clienteId) throw new \InvalidArgumentException('cliente_id requerido');
                $filtrosFact = array_merge($filtros, [ 'cliente_id' => $clienteId ]);
                return $this->listFacturas($filtrosFact);

            case 'factura_mayor':
                $clienteId = (int) ($filtros['cliente_id'] ?? $filtros['id'] ?? 0);
                if (!$clienteId) throw new \InvalidArgumentException('cliente_id requerido');
                $row = Factura::query()
                    ->where('client_id', $clienteId)
                    ->when($this->statusFromFiltro($filtros['estado'] ?? 'pendiente') !== null, function(Builder $q) use ($filtros){
                        $status = $this->statusFromFiltro($filtros['estado'] ?? 'pendiente');
                        if ($status !== null) { $q->where('status', $status); }
                    }, function(Builder $q){ $q->where('status', 0); })
                    ->orderByDesc('amount')
                    ->first();
                return $row ? $this->transformFactura($row) : null;

            case 'total_pendiente':
                $clienteId = (int) ($filtros['cliente_id'] ?? $filtros['id'] ?? 0);
                if (!$clienteId) throw new \InvalidArgumentException('cliente_id requerido');
                $sum = Factura::query()->where('client_id', $clienteId)->where('status', 0)->sum('amount');
                return [ 'cliente_id' => $clienteId, 'total_pendiente' => (int) $sum ];

            default:
                throw new \InvalidArgumentException('Acción no soportada para cliente: ' . $accion);
        }
    }

    /* ===================== Proveedores ===================== */
    private function handleProveedor(string $accion, array $filtros)
    {
        switch ($accion) {
            case 'buscar':
                $q = trim((string)($filtros['q'] ?? $filtros['nombre'] ?? $filtros['name'] ?? $filtros['rut'] ?? ''));
                $query = Proveedor::query();
                if ($q !== '') {
                    $query->where(function (Builder $b) use ($q) {
                        $b->where('name', 'like', "%{$q}%")
                          ->orWhere('rut', 'like', "%{$q}%");
                    });
                }
                return $this->paginateAndTransform($query->orderByDesc('id'), $filtros, fn($p) => $this->transformProveedor($p));

            case 'listar_facturas':
                $proveedorId = (int) ($filtros['proveedor_id'] ?? $filtros['id'] ?? 0);
                if (!$proveedorId) throw new \InvalidArgumentException('proveedor_id requerido');
                $filtrosFact = array_merge($filtros, [ 'proveedor_id' => $proveedorId ]);
                return $this->listFacturas($filtrosFact);

            case 'factura_mayor':
                $proveedorId = (int) ($filtros['proveedor_id'] ?? $filtros['id'] ?? 0);
                if (!$proveedorId) throw new \InvalidArgumentException('proveedor_id requerido');
                $row = Factura::query()
                    ->where('provider_id', $proveedorId)
                    ->when($this->statusFromFiltro($filtros['estado'] ?? 'pendiente') !== null, function(Builder $q) use ($filtros){
                        $status = $this->statusFromFiltro($filtros['estado'] ?? 'pendiente');
                        if ($status !== null) { $q->where('status', $status); }
                    }, function(Builder $q){ $q->where('status', 0); })
                    ->orderByDesc('amount')
                    ->first();
                return $row ? $this->transformFactura($row) : null;

            case 'total_pendiente':
                $proveedorId = (int) ($filtros['proveedor_id'] ?? $filtros['id'] ?? 0);
                if (!$proveedorId) throw new \InvalidArgumentException('proveedor_id requerido');
                $sum = Factura::query()->where('provider_id', $proveedorId)->where('status', 0)->sum('amount');
                return [ 'proveedor_id' => $proveedorId, 'total_pendiente' => (int) $sum ];

            default:
                throw new \InvalidArgumentException('Acción no soportada para proveedor: ' . $accion);
        }
    }

    /* ===================== Facturas ===================== */
    private function handleFactura(string $accion, array $filtros)
    {
        switch ($accion) {
            case 'buscar':
                // Buscar por número (invoice) o rango de fechas
                $query = $this->buildFacturaBaseQuery($filtros);
                $q = trim((string)($filtros['q'] ?? $filtros['numero'] ?? ''));
                if ($q !== '') {
                    $query->where('invoice', 'like', "%{$q}%");
                }
                return $this->paginateAndTransform(
                    $query->orderByDesc('date'),
                    $filtros,
                    fn($f) => $this->transformFactura($f)
                );

            case 'listar':
            case 'listar_facturas':
                return $this->listFacturas($filtros);

            case 'factura_mayor':
                $row = $this->buildFacturaBaseQuery($filtros)
                    ->when(isset($filtros['estado']), function(Builder $q) use ($filtros){
                        $status = $this->statusFromFiltro($filtros['estado']);
                        if ($status !== null) { $q->where('status', $status); }
                    })
                    ->orderByDesc('amount')
                    ->first();
                return $row ? $this->transformFactura($row) : null;

            case 'total_pendiente':
                $sum = $this->buildFacturaBaseQuery($filtros)
                    ->where('status', 0)
                    ->sum('amount');
                return [ 'total_pendiente' => (int) $sum ];

            default:
                throw new \InvalidArgumentException('Acción no soportada para factura: ' . $accion);
        }
    }

    private function buildFacturaBaseQuery(array $filtros): Builder
    {
        $query = Factura::query()->with(['cliente:id,name,rut', 'proveedor:id,name,rut']);

        // Por tipo (cliente / proveedor)
        $tipo = isset($filtros['tipo']) ? strtolower((string)$filtros['tipo']) : null;
        if ($tipo === 'cliente') {
            $query->whereNotNull('client_id');
        } elseif ($tipo === 'proveedor') {
            $query->whereNotNull('provider_id');
        }

        // Por entidad específica
        if (!empty($filtros['cliente_id'])) {
            $query->where('client_id', (int)$filtros['cliente_id']);
        }
        if (!empty($filtros['proveedor_id'])) {
            $query->where('provider_id', (int)$filtros['proveedor_id']);
        }

        // Estado: 'pendiente' | 'pagado' | 0 | 1
        if (isset($filtros['estado'])) {
            $status = $this->statusFromFiltro($filtros['estado']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        // Rango de fechas (sobre campo date)
        if (!empty($filtros['desde'])) {
            $query->whereDate('date', '>=', $filtros['desde']);
        }
        if (!empty($filtros['hasta'])) {
            $query->whereDate('date', '<=', $filtros['hasta']);
        }

        return $query;
    }

    private function listFacturas(array $filtros)
    {
        $query = $this->buildFacturaBaseQuery($filtros);
        // Orden
        $sortBy = in_array(($filtros['sort_by'] ?? 'date'), ['date','amount','invoice','id']) ? $filtros['sort_by'] : 'date';
        $sortDir = strtolower(($filtros['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $this->paginateAndTransform($query, $filtros, fn($f) => $this->transformFactura($f));
    }

    /* ===================== Cotizaciones ===================== */
    private function handleCotizacion(string $accion, array $filtros)
    {
        switch ($accion) {
            case 'buscar':
                $query = $this->buildCotizacionBaseQuery($filtros);
                $q = trim((string)($filtros['q'] ?? ''));
                if ($q !== '') {
                    $query->where(function (Builder $b) use ($q) {
                        $b->whereHas('cliente', function (Builder $bc) use ($q) {
                            $bc->where('name', 'like', "%{$q}%")
                               ->orWhere('rut', 'like', "%{$q}%");
                        })
                        ->orWhere('work', 'like', "%{$q}%");
                    });
                }
                $sortBy = in_array(($filtros['sort_by'] ?? 'date'), ['date','total','id']) ? $filtros['sort_by'] : 'date';
                $sortDir = strtolower(($filtros['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
                $query->orderBy($sortBy, $sortDir);
                return $this->paginateAndTransform($query, $filtros, fn($c) => $this->transformCotizacion($c));

            case 'listar':
                $query = $this->buildCotizacionBaseQuery($filtros);
                $sortBy = in_array(($filtros['sort_by'] ?? 'date'), ['date','total','id']) ? $filtros['sort_by'] : 'date';
                $sortDir = strtolower(($filtros['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
                $query->orderBy($sortBy, $sortDir);
                return $this->paginateAndTransform($query, $filtros, fn($c) => $this->transformCotizacion($c));

            case 'detalle':
                $id = (int) ($filtros['id'] ?? 0);
                if (!$id) throw new \InvalidArgumentException('id requerido');
                $c = Cotizacion::with(['cliente:id,name,rut,email,phone', 'items'])->findOrFail($id);
                return $this->transformCotizacion($c, true);

            case 'total':
                $query = $this->buildCotizacionBaseQuery($filtros);
                $sum = (int) $query->sum('total');
                return [ 'total' => $sum ];

            default:
                throw new \InvalidArgumentException('Acción no soportada para cotización: ' . $accion);
        }
    }

    private function buildCotizacionBaseQuery(array $filtros): Builder
    {
        $query = Cotizacion::query()->with(['cliente:id,name,rut']);
        if (!empty($filtros['cliente_id'])) {
            $query->where('client_id', (int) $filtros['cliente_id']);
        }
        if (!empty($filtros['desde'])) {
            $query->whereDate('date', '>=', $filtros['desde']);
        }
        if (!empty($filtros['hasta'])) {
            $query->whereDate('date', '<=', $filtros['hasta']);
        }
        return $query;
    }

    private function listCotizaciones(array $filtros)
    {
        $query = $this->buildCotizacionBaseQuery($filtros);
        $sortBy = in_array(($filtros['sort_by'] ?? 'date'), ['date','total','id']) ? $filtros['sort_by'] : 'date';
        $sortDir = strtolower(($filtros['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);
        return $this->paginateAndTransform($query, $filtros, fn($c) => $this->transformCotizacion($c));
    }

    private function transformCotizacion(Cotizacion $c, bool $withItems = false): array
    {
        $data = [
            'id' => $c->id,
            'date' => optional($c->date)->toDateString(),
            'total' => (int) $c->total,
            'agent' => $c->agent,
            'work' => $c->work,
            'email' => $c->email,
            'cliente' => $c->cliente ? [ 'id' => $c->cliente->id, 'name' => $c->cliente->name, 'rut' => $c->cliente->rut ] : null,
        ];
        if ($withItems) {
            $data['items'] = ($c->items ?? collect())->map(function($it){
                return [
                    'id' => $it->id,
                    'description' => $it->description,
                    'amount' => (int) $it->amount,
                    'price' => (int) $it->price,
                    'total' => (int) $it->total,
                ];
            })->toArray();
        }
        return $data;
    }

    /* ===================== Helpers ===================== */
    private function paginateAndTransform(Builder $query, array $filtros, callable $transform)
    {
        $perPage = (int) ($filtros['per_page'] ?? $filtros['limit'] ?? 20);
        $perPage = max(1, min($perPage, 100));
        $page = (int) ($filtros['page'] ?? 1);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $items = array_map($transform, $paginator->items());

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]
        ];
    }

    private function transformCliente(Cliente $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'rut' => $c->rut,
            'email' => $c->email,
            'phone' => $c->phone,
        ];
    }

    private function transformProveedor(Proveedor $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'rut' => $p->rut,
            'email' => $p->email,
            'phone' => $p->phone,
        ];
    }

    private function transformFactura(Factura $f): array
    {
        return [
            'id' => $f->id,
            'invoice' => $f->invoice,
            'date' => optional($f->date)->toDateString(),
            'expiry' => optional($f->expiry)->toDateString(),
            'amount' => (int) $f->amount,
            'status' => (int) $f->status,
            'status_text' => $f->status_text,
            'tipo' => $f->tipo,
            'cliente' => $f->cliente ? [ 'id' => $f->cliente->id, 'name' => $f->cliente->name, 'rut' => $f->cliente->rut ] : null,
            'proveedor' => $f->proveedor ? [ 'id' => $f->proveedor->id, 'name' => $f->proveedor->name, 'rut' => $f->proveedor->rut ] : null,
        ];
    }

    private function statusFromFiltro($v): ?int
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) {
            $n = (int) $v;
            return $n === 0 ? 0 : 1;
        }
        $v = strtolower((string)$v);
        return in_array($v, ['pagado','paid','1'], true) ? 1 : (in_array($v, ['pendiente','pending','0'], true) ? 0 : null);
    }

    private function error(string $message, int $status = 400)
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
        ], $status);
    }

    /**
     * Proxy local hacia el webhook externo para evitar CORS en el navegador.
     */
    public function webhookProxy(Request $request): JsonResponse
    {
        $url = (string) env('MCP_WEBHOOK_URL');
        if ($url === '') {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => 'MCP_WEBHOOK_URL no configurado',
            ], 500);
        }

        try {
            $payload = $request->except(['webhook']);

            $client = Http::timeout(15)->asJson();
            $token = (string) (config('services.mcp.bearer') ?? env('MCP_BEARER', ''));
            if ($token !== '') {
                $client = $client->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ]);
            }

            $resp = $client->post($url, $payload);
            $json = $resp->json() ?? [ 'message' => $resp->body() ];
            return response()->json($json, $resp->status());
        } catch (\Throwable $e) {
            Log::error('Webhook proxy error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => 'Error llamando al webhook: ' . $e->getMessage(),
            ], 502);
        }
    }
}
