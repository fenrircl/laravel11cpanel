@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ url('/cotizaciones') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <button id="btnDownloadPdf" class="btn btn-primary btn-sm no-print no-export">
            <i class="fas fa-download"></i> Descargar PDF
        </button>
    </div>
  <div class="pdf-container">
    @include('cotizacion.pdf')
  </div>
</div>
@endsection

