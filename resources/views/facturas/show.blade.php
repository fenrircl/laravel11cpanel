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
        </div>
    </div>
</div>
@endsection
