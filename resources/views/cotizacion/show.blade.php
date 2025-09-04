@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Cotización #{{ $cotizacion->id }}</h4>
    <div>
      <a href="{{ route('cotizaciones.edit', $cotizacion) }}" class="btn btn-primary btn-sm">Editar</a>
      <button id="btnExportPdf" class="btn btn-outline-secondary btn-sm">Exportar PDF</button>
      <a href="{{ route('cotizaciones.index') }}" class="btn btn-secondary btn-sm">Volver</a>
    </div>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-4"><strong>Cliente:</strong> {{ $cotizacion->cliente?->name }}</div>
        <div class="col-md-4"><strong>Fecha:</strong> {{ $cotizacion->date }}</div>
        <div class="col-md-4"><strong>Agente:</strong> {{ $cotizacion->agent }}</div>
      </div>
      <div class="mb-3"><strong>Trabajo:</strong> {{ $cotizacion->work ?: '-' }}</div>
      <div id="cotizacionPrintable">
        <table class="table table-sm">
          <thead><tr><th>Descripción</th><th class="text-end">Cant.</th><th class="text-end">Precio</th><th class="text-end">Total</th></tr></thead>
          <tbody>
          @foreach($cotizacion->items as $it)
            <tr>
              <td>{{ $it->description }}</td>
              <td class="text-end">{{ $it->amount }}</td>
              <td class="text-end">{{ number_format($it->price,0,',','.') }}</td>
              <td class="text-end">{{ number_format($it->total,0,',','.') }}</td>
            </tr>
          @endforeach
          </tbody>
          <tfoot>
            <tr>
              <th colspan="3" class="text-end">Total</th>
              <th class="text-end">{{ number_format($cotizacion->total,0,',','.') }}</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Dependencias jsPDF o html2pdf.js desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.9.3/dist/html2pdf.bundle.min.js"></script>
<script>
  document.getElementById('btnExportPdf').addEventListener('click', function(){
    const element = document.getElementById('cotizacionPrintable');
    const opt = {
      margin:       10,
      filename:     'cotizacion_{{ $cotizacion->id }}.pdf',
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2, useCORS: true },
      jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
  });
</script>
@endsection
