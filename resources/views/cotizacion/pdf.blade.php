<div id="pdfPrintable" class="hidden">
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
      <h2>Cotización #<span id="q-id">—</span></h2>
      <div>Fecha: <span id="q-date">—</span></div>
      <div>Agente: <span id="q-agent">—</span></div>
    </div>
  </div>

  <div class="section">
    <h3>Cliente</h3>
    <div class="client-grid">
      <div><strong>Nombre:</strong><br><span id="c-name">—</span></div>
      <div><strong>RUT:</strong><br><span id="c-rut">—</span></div>
      <div><strong>Dirección:</strong><br><span id="c-address">—</span></div>
      <div><strong>Ciudad:</strong><br><span id="c-city">—</span></div>
      <div><strong>Teléfono:</strong><br><span id="c-phone">—</span></div>
      <div><strong>Email:</strong><br><span id="c-email">—</span></div>
    </div>
  </div>

  <div id="work-section" class="section" style="display:none">
    <h3>Trabajo</h3>
    <div class="work-box" id="q-work"></div>
  </div>

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
      <tbody id="items-body"></tbody>
    </table>

    <div class="summary">
      <table>
        <tr>
          <th>NETO</th>
          <td id="sum-net">$0</td>
        </tr>
        <tr>
          <th>IVA (19%)</th>
          <td id="sum-iva">$0</td>
        </tr>
        <tr>
          <th>TOTAL</th>
          <td id="sum-total">$0</td>
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

@push('styles')
 <link rel="stylesheet" href="{{ asset('assets/css/cotizaciones/pdf.css') }}">
@endpush
