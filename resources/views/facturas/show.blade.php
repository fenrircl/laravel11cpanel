@extends('layouts.app')

@section('content')
<div class="container py-4">
    <meta name="factura-id" content="{{ $factura->id }}">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Factura #{{ $factura->invoice }}</h3>
        <div>
            <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">Volver</a>
            <a href="{{ route('facturas.edit', $factura) }}" class="btn btn-primary btn-sm">Editar</a>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-2"><strong>N° Factura:</strong> {{ $factura->invoice }}</div>
                    <div class="mb-2"><strong>Fecha:</strong> {{ $factura->date }}</div>
                    <div class="mb-2"><strong>Vencimiento:</strong> {{ $factura->expiry ?? '—' }}</div>
                    <div class="mb-2"><strong>Pagado:</strong> {{ $factura->pay_date ?? '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2"><strong>Entidad:</strong> {{ $factura->cliente?->name ?? $factura->proveedor?->name }}</div>
                    <div class="mb-2"><strong>Método de pago:</strong> {{ $factura->metodoPago?->name ?? '—' }}</div>
                    <div class="mb-2"><strong>Monto:</strong> {{ number_format($factura->amount, 0, ',', '.') }}</div>
                    <div class="mb-2"><strong>Estado:</strong> {{ $factura->status === 1 ? 'Pagado' : 'Pendiente' }}</div>
                </div>
            </div>
            <div class="mt-3">
                <strong>Detalle:</strong>
                <div class="border rounded p-2 bg-light">{{ $factura->detail ?: 'Sin detalles' }}</div>
            </div>
            <div class="mt-3">
                <strong>Extra:</strong>
                <div class="border rounded p-2 bg-light">{{ $factura->extra ?: 'Sin información adicional' }}</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="h6 mb-0">Archivos adjuntos</h4>
            <span class="badge text-bg-secondary">{{ $factura->archivos->count() }}</span>
        </div>
        <div class="card-body">
            @if($factura->archivos->isNotEmpty())
                <div class="list-group list-group-flush">
                    @foreach($factura->archivos as $archivo)
                        <div class="list-group-item px-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-semibold">{{ $archivo->file_name ?: basename((string) $archivo->path) }}</div>
                                <div class="small text-muted">
                                    {{ $archivo->size ? number_format($archivo->size / 1024, 0, ',', '.') . ' KB · ' : '' }}
                                    {{ $archivo->created_at ?: 'Fecha no registrada' }}
                                </div>
                            </div>
                            <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"
                               href="{{ route('files.registry.download', ['file' => $archivo->id]) }}">
                                Abrir archivo
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted mb-0">No hay archivos asociados a esta factura.</p>
            @endif
        </div>
    </div>
</div>
@endsection
