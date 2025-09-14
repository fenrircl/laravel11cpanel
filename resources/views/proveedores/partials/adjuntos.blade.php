<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Archivos adjuntos</span>
    <div>
      <input type="file" id="fileProveedorAdjunto" class="d-none" />
      <button class="btn btn-sm btn-primary" id="btnSubirAdjuntoProveedor">Subir archivo</button>
    </div>
  </div>
  <div class="card-body">
    <div id="listaAdjuntosProveedor"></div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  function initAdjuntosProveedor(){
    var $ = window.jQuery;
    if (!$) return;

    const proveedorId = {{ $proveedor->id ?? 'null' }};
    if (!proveedorId) return;
    const $list = $('#listaAdjuntosProveedor');

    const renderList = (files) => {
      if (!files || !files.length) { $list.html('<p class="text-muted">Sin archivos</p>'); return; }
      $list.html('<ul class="list-group"></ul>');
      const ul = $list.find('ul');
      files.forEach(f => {
        const sizeKB = f.size ? Math.round(f.size/1024) + ' KB' : '';
        const li = $(
          `<li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <i class="fas fa-file me-2"></i>
              <a href="${f.download_url}" target="_blank">${f.name}</a>
              <small class="text-muted ms-2">${sizeKB}</small>
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
      fetch(routeUrl('proveedores.files', proveedorId), { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r=>r.json()).then(j=>renderList(j.files||[]))
        .catch(()=> $list.html('<p class="text-muted">No se pudieron cargar los archivos</p>'));
    }

    load();

    $('#btnSubirAdjuntoProveedor').on('click', ()=> $('#fileProveedorAdjunto').trigger('click'));
    $('#fileProveedorAdjunto').on('change', function(){
      const file = this.files[0];
      if (!file) return;
      const fd = new FormData();
      fd.append('file', file);
      fd.append('model_type', 'App\\Proveedor');
      fd.append('model_id', String(proveedorId));
      Swal.fire({title:'Subiendo...', didOpen:()=>Swal.showLoading(), allowOutsideClick:false});
      fetch(routeUrl('files.upload'), { method:'POST', body: fd, headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }})
        .then(r=>r.json()).then(j=>{ Swal.close(); if (!j.success) throw new Error(j.message||'Error'); load(); })
        .catch(e=>{ Swal.close(); Swal.fire({icon:'error', title:'Error', text:e.message||'No se pudo subir el archivo'}); });
    });
  }

  function onReady(cb){ if (document.readyState !== 'loading') cb(); else document.addEventListener('DOMContentLoaded', cb); }
  function waitForjQuery(){ if (!window.jQuery) return setTimeout(waitForjQuery, 50); initAdjuntosProveedor(); }
  onReady(waitForjQuery);
})();
</script>
@endpush
