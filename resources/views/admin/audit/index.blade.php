@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Logs de auditoría</h3>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2" method="GET">
                <div class="col-md-2">
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Buscar...">
                </div>
                <div class="col-md-2">
                    <input type="text" name="module" class="form-control" value="{{ request('module') }}" placeholder="Módulo">
                </div>
                <div class="col-md-2">
                    <input type="text" name="action" class="form-control" value="{{ request('action') }}" placeholder="Acción">
                </div>
                <div class="col-md-2">
                    <input type="number" name="user_id" class="form-control" value="{{ request('user_id') }}" placeholder="Usuario ID">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.audit.index') }}" class="btn btn-secondary w-100">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Módulo</th>
                        <th>Entidad</th>
                        <th>Descripción</th>
                        <th>Detalles</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td class="text-muted">#{{ $log->id }}</td>
                        <td>{{ $log->created_at?->timezone('America/Santiago')->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? '—' }} <span class="text-muted">({{ $log->user_id ?? '—' }})</span></td>
                        <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                        <td>{{ $log->module ?? '—' }}</td>
                        <td>{{ $log->entity_id ?? '—' }}</td>
                        <td style="max-width: 380px;" class="text-truncate" title="{{ $log->description }}">{{ $log->description }}</td>
                        <td>
                            @php
                                $changes = is_array($log->changes) ? $log->changes : json_decode($log->changes ?? '[]', true);
                            @endphp
                            @if($log->module === 'facturas' && !empty($log->entity_id))
                                @php
                                    $f = isset($facturas) ? ($facturas[$log->entity_id] ?? null) : null;
                                @endphp
                                @if($f)
                                    @if($f->client_id)
                                        <span class="badge bg-info">Cliente</span>
                                        <a href="{{ route('clientes.show', $f->cliente) }}" target="_blank">
                                            {{ $f->cliente?->name }}
                                        </a>
                                        @if($f->cliente?->rut)
                                            <span class="text-muted">({{ $f->cliente->rut }})</span>
                                        @endif
                                    @elseif($f->provider_id)
                                        <span class="badge bg-warning text-dark">Proveedor</span>
                                        <a href="{{ route('proveedores.show', $f->proveedor) }}" target="_blank">
                                            {{ $f->proveedor?->name }}
                                        </a>
                                        @if($f->proveedor?->rut)
                                            <span class="text-muted">({{ $f->proveedor->rut }})</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                                @if(!empty($changes))
                                    <hr class="my-2">
                                @endif
                            @endif

                            @if(!empty($changes))
                                <details>
                                    <summary>Ver cambios</summary>
                                    <ul class="small mb-0">
                                        @foreach($changes as $field => $diff)
                                            <li>
                                                <strong>{{ $field }}:</strong>
                                                @if(is_array($diff) && array_key_exists('from', $diff) && array_key_exists('to', $diff))
                                                    <span class="text-muted">{{ is_scalar($diff['from']) ? $diff['from'] : json_encode($diff['from']) }}</span>
                                                    <i class="mx-1 fas fa-arrow-right"></i>
                                                    <span>{{ is_scalar($diff['to']) ? $diff['to'] : json_encode($diff['to']) }}</span>
                                                @else
                                                    {{ is_scalar($diff) ? $diff : json_encode($diff) }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @else
                                @if(!($log->module === 'facturas' && !empty($log->entity_id)))
                                    <span class="text-muted">—</span>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if(($log->module === 'facturas') && $log->reversible)
                                <form method="POST" action="{{ route('admin.audit.restore', $log->id) }}" onsubmit="return confirm('¿Restaurar este cambio?');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-undo"></i> Revertir
                                    </button>
                                </form>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $logs->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Fix tamaño de íconos en paginación si el theme aplica estilos globales a svg/img */
.card-footer .pagination svg,
.card-footer .pagination img {
  width: 1rem !important;
  height: 1rem !important;
}
.card-footer .pagination .page-link {
  display: inline-flex;
  align-items: center;
  gap: .25rem;
}
details summary {
  cursor: pointer;
}
</style>
@endpush
