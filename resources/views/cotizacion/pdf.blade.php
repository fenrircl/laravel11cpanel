@extends('layouts.app')

@section('content')
<div class="pdf-container">
  <div class="no-print no-export d-flex justify-content-end mb-2">
    <button id="btnDownloadPdf" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Descargar PDF</button>
  </div>
  <div id="pdfPrintable">
    <div class="pdf-header">
      <div class="brand">
        <img src="{{ asset('img/logo.png') }}" alt="Logo">
        <div class="brand-info">
          <h1>Sociedad Aceros Era Ltda.</h1>
          <div>RUT 76.150.341-3</div>
          <div>Alessandri 109, Tierras Blancas, Coquimbo</div>
          <div>contacto@acerosera.cl — (051) 2249328</div>
        </div>
      </div>
      <div class="doc-info">
        <h2>Cotización #{{ $quotation->id }}</h2>
        <div>Fecha: {{ optional($quotation->date)->format('d-m-Y') }}</div>
        <div>Agente: {{ $quotation->agent }}</div>
      </div>
    </div>

    <div class="section">
      <h3>Cliente</h3>
      <div class="client-grid">
        <div><strong>Nombre:</strong><br>{{ $quotation->client->name ?? 'N/A' }}</div>
        <div><strong>RUT:</strong><br>{{ $quotation->client->rut ?? 'N/A' }}</div>
        <div><strong>Dirección:</strong><br>{{ $quotation->client->address ?? 'N/A' }}</div>
        <div><strong>Ciudad:</strong><br>{{ $cityname ?? 'N/A' }}</div>
        <div><strong>Teléfono:</strong><br>{{ $quotation->client->phone ?? 'N/A' }}</div>
        <div><strong>Email:</strong><br>{{ $quotation->client->email ?? 'N/A' }}</div>
      </div>
    </div>

    @if(!empty($quotation->work))
    <div class="section">
      <h3>Trabajo</h3>
      <div class="work-box">{{ $quotation->work }}</div>
    </div>
    @endif

    <div class="section">
      <h3>Detalle</h3>
      <table class="items-table">
        <thead>
          <tr>
            <th style="width: 60%">Descripción</th>
            <th class="num" style="width: 10%">Cant.</th>
            <th class="num" style="width: 15%">Precio</th>
            <th class="num" style="width: 15%">Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach($item as $it)
          <tr>
            <td>{{ $it->description }}</td>
            <td class="num">{{ number_format($it->amount, 0, ',', '.') }}</td>
            <td class="num">${{ number_format($it->price, 0, ',', '.') }}</td>
            <td class="num">${{ number_format($it->total, 0, ',', '.') }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <div class="summary">
        <table>
          @php
            $neto = round($quotation->total / 1.19);
            $iva = $neto * 0.19;
            $total = $quotation->total;
          @endphp
          <tr>
            <th>NETO</th>
            <td>${{ number_format($neto, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <th>IVA (19%)</th>
            <td>${{ number_format($iva, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <th>TOTAL</th>
            <td>${{ number_format($total, 0, ',', '.') }}</td>
          </tr>
        </table>
      </div>
    </div>

    <div class="pdf-footer">
      <footer>
        <div class="container">
          <section class="main row">
            <div class="col-md-12">
              Hacer todos los cheques pagaderos a  Sociedad Aceros Era Ltda.<br>
              Si tiene alguna pregunta relacionada con esta factura, le rogamos se ponga en contacto con:<br>
              Ximena Valledor R.  Celular 9 - 88 63 192  E-Mail: contacto@acerosera.cl<br>
              <br>
              <p>Datos de transferencia : sociedad Aceros ERA Ltda<br />
                Rut 76.150.341-3<br />
                Cuenta corriente banco chile N*1201237809<br />
                Administracion@acerosera.cl</p>
            </div>
          </section>
        </div>
      </footer>
    </div>
  </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/cotizaciones/pdf.css') }}">
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.getElementById('btnDownloadPdf');
  if (!btn) { return; }
  
  btn.addEventListener('click', function() {
    const element = document.getElementById('pdfPrintable');
    if (!element || typeof html2pdf === 'undefined') { return; }

    // Aumentar resolución equilibrada para buena nitidez sin peso excesivo
    const scale = Math.min(2, Math.max(1.5, window.devicePixelRatio || 1));

    const options = {
      margin: 0.5,
      filename: 'cotizacion_{{ $quotation->id }}.pdf',
      image: { type: 'jpeg', quality: 1 },
      html2canvas: {
        scale: scale, // 1.5–2 mejora nitidez con peso moderado
        useCORS: true,
        allowTaint: true,
        backgroundColor: '#ffffff',
        logging: false
      },
      jsPDF: {
        unit: 'in',
        format: 'a4',
        orientation: 'portrait',
        compress: true
      }
    };

    try {
      html2pdf(element, options);
    } catch (error) {
      console.error('Error al generar PDF:', error);
      alert('Error al generar el PDF: ' + error.message);
    }
  });
});
</script>
@endpush
