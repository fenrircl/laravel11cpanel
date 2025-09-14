<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Factura;
use Illuminate\Database\Eloquent\Builder;

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
}
