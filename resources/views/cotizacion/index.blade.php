@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Cotizaciones</h4>
                    <div class="btn-group" role="group">
                        <a href="{{ route('cotizaciones.create') }}" class="btn btn-primary btn-sm">Nueva Cotización</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="cotizaciones-table" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Agente</th>
                                    <th>Trabajo</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Host oculto para la plantilla PDF (no afecta el layout) -->
    <div id="pdfHiddenHost" aria-hidden="true" style="position:fixed; left:-200vw; top:0; width:1000px; background:#fff; pointer-events:none; z-index:-1;">
        @include('cotizacion.pdf')
    </div>
</div>

<!-- Modal Detalles -->
<div class="modal fade" id="cotizacionDetailsModal" tabindex="-1" aria-labelledby="cotizacionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cotizacionDetailsModalLabel">Detalles de Cotización</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="cotizacionDetailsContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/cotizaciones/pdf.css') }}">
<style>
  /* Evita parpadeos en algunos navegadores cuando se genera el PDF */
  #pdfHiddenHost { contain: content; }
</style>
@endpush

