@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
            {{ $factura->client_id ? 'Editar factura de cliente' : 'Editar factura de proveedor' }}
        </h3>
        <div>
            <a href="{{ route('facturas.show', $factura) }}" class="btn btn-secondary btn-sm">Volver</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('facturas.update', $factura) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">N° Factura</label>
                        <input type="text" name="invoice" class="form-control" value="{{ old('invoice', $factura->invoice) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', $factura->date ? \Illuminate\Support\Carbon::parse($factura->date)->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vencimiento</label>
                        <input type="date" name="expiry" class="form-control" value="{{ old('expiry', $factura->expiry ? \Illuminate\Support\Carbon::parse($factura->expiry)->format('Y-m-d') : '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Fecha de pago</label>
                        <input type="date" name="pay_date" class="form-control" value="{{ old('pay_date', $factura->pay_date ? \Illuminate\Support\Carbon::parse($factura->pay_date)->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Monto</label>
                        <input type="text" name="amount" class="form-control" value="{{ old('amount', number_format($factura->amount, 0, ',', '.')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cheque</label>
                        <input type="text" name="check" class="form-control" value="{{ old('check', $factura->check) }}">
                    </div>

                    @if($factura->client_id)
                        <div class="col-md-6">
                            <label class="form-label">Cliente</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">Seleccionar cliente</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}" {{ (int) old('client_id', $factura->client_id) === (int) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="provider_id" value="">
                    @else
                        <input type="hidden" name="client_id" value="">
                        <div class="col-md-6">
                            <label class="form-label">Proveedor</label>
                            <select name="provider_id" class="form-select" required>
                                <option value="">Seleccionar proveedor</option>
                                @foreach($proveedores as $p)
                                    <option value="{{ $p->id }}" {{ (int) old('provider_id', $factura->provider_id) === (int) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-6">
                        <label class="form-label">Método de pago</label>
                        <select name="payment_method_id" class="form-select" required>
                            <option value="">Seleccionar método</option>
                            @foreach($metodosPago as $m)
                                <option value="{{ $m->id }}" {{ (int) old('payment_method_id', $factura->payment_method_id) === (int) $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Detalle</label>
                        <textarea name="detail" class="form-control" rows="3">{{ old('detail', $factura->detail) }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select" required>
                            <option value="0" {{ (string) old('status', (string) $factura->status) === '0' ? 'selected' : '' }}>Pendiente</option>
                            <option value="1" {{ (string) old('status', (string) $factura->status) === '1' ? 'selected' : '' }}>Pagado</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('facturas.show', $factura) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
