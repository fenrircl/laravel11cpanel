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
                        <th>IP</th>
                        <th>Agente</th>
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
                        <td>{{ $log->ip_address ?? '—' }}</td>
                        <td style="max-width: 260px;" class="text-truncate" title="{{ $log->user_agent }}">{{ $log->user_agent }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
