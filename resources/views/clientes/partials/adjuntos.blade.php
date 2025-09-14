<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Archivos adjuntos</span>
    <div>
      <input type="file" id="fileClienteAdjunto" class="d-none" />
      <button class="btn btn-sm btn-primary" id="btnSubirAdjuntoCliente">Subir archivo</button>
    </div>
  </div>
  <div class="card-body">
    <div id="listaAdjuntosCliente"></div>
    <hr/>
    <h6>PDFs de cotizaciones</h6>
    <div id="listaAdjuntosCotizaciones"></div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  function initAdjuntosCliente(){
    var $ = window.jQuery;
    if (!$) { return; }

    const clienteId = {{ $cliente->id ?? 'null' }};
    if (!clienteId) return;
    const $list = $('#listaAdjuntosCliente');
    const $listCot = $('#listaAdjuntosCotizaciones');
  
    const renderList = (el, files) => {
      if (!files || !files.length) { el.html('<p class="text-muted">Sin archivos</p>'); return; }
      el.html('<ul class="list-group"></ul>');
      const ul = el.find('ul');
      files.forEach(f => {
        const li = $(
          `<li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <i class="fas fa-file me-2"></i>
              <a href="${f.download_url}" target="_blank">${f.name}</a>
              <small class="text-muted ms-2">(${(f.size||0)} bytes)</small>
            </div>
            <button class="btn btn-sm btn-outline-danger" data-id="${f.id}"><i class="fas fa-trash"></i></button>
          </li>`
        );
        li.find('button').on('click', function(){
          const id = $(this).data('id');
          Swal.fire({title:'¿Eliminar archivo?', icon:'warning', showCancelButton:true, confirmButtonText:'Eliminar', cancelButtonText:'Cancelar'})
            .then(r => { if (!r.isConfirmed) return; fetch(routeUrl('files.delete', id), { method:'DELETE', headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }})
              .then(r=>r.json()).then(j=>{ if (!j.success) throw new Error(j.message||'Error'); load(); })
              .catch(e=>Swal.fire({icon:'error', title:'Error', text:e.message||'No se pudo eliminar'})); });
        });
        ul.append(li);
      });
    };
  
    function load(){
      fetch(routeUrl('clientes.files', clienteId), { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r=>r.json()).then(j=>renderList($list, j.files||[]));
      fetch(routeUrl('clientes.cotizaciones.files', clienteId), { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r=>r.json()).then(j=>renderList($listCot, j.files||[]));
    }
  
    load();
  
    $('#btnSubirAdjuntoCliente').on('click', ()=> $('#fileClienteAdjunto').trigger('click'));
    $('#fileClienteAdjunto').on('change', function(){
      const file = this.files[0];
      if (!file) return;
      const fd = new FormData();
      fd.append('file', file);
      fd.append('model_type', 'App\\Cliente');
      fd.append('model_id', String(clienteId));
      Swal.fire({title:'Subiendo...', didOpen:()=>Swal.showLoading(), allowOutsideClick:false});
      fetch(routeUrl('files.upload'), { method:'POST', body: fd, headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }})
        .then(r=>r.json()).then(j=>{ Swal.close(); if (!j.success) throw new Error(j.message||'Error'); load(); })
        .catch(e=>{ Swal.close(); Swal.fire({icon:'error', title:'Error', text:e.message||'No se pudo subir el archivo'}); });
    });
  }

  function onReady(cb){
    if (document.readyState !== 'loading') cb(); else document.addEventListener('DOMContentLoaded', cb);
  }
  function waitForjQuery(){
    if (!window.jQuery) return setTimeout(waitForjQuery, 50);
    initAdjuntosCliente();
  }
  onReady(waitForjQuery);
})();
</script>
@endpush
