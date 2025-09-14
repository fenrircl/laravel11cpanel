;(function(){
  if (window.attachInvoiceFilters) return;

  // Registro global de filtros por tabla
  const REGISTRY = {};

  function parseDateStr(val){
    if (!val) return null;
    if (val instanceof Date) return new Date(val.getFullYear(), val.getMonth(), val.getDate());
    if (typeof val === 'number') return new Date(val);
    const s = String(val);
    // YYYY-MM-DD[ T| ]HH:MM:SS
    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (m) return new Date(parseInt(m[1],10), parseInt(m[2],10)-1, parseInt(m[3],10));
    // DD-MM-YYYY
    const mc = s.match(/^(\d{2})-(\d{2})-(\d{4})$/);
    if (mc) return new Date(parseInt(mc[3],10), parseInt(mc[2],10)-1, parseInt(mc[1],10));
    const d = new Date(s);
    return isNaN(d) ? null : new Date(d.getFullYear(), d.getMonth(), d.getDate());
  }

  function ensureContainer(tableId){
    const table = document.getElementById(tableId);
    if (!table) return null;
    const existing = document.getElementById(tableId + '-filters');
    if (existing) return existing;
    const div = document.createElement('div');
    div.id = tableId + '-filters';
    div.className = 'dt-filters card mb-3';
    div.innerHTML = '<div class="card-body py-2"></div>';
    table.parentNode.insertBefore(div, table);
    return div;
  }

  function buildPanel(opts){
    const { mode, tableId } = opts; // mode: 'cliente' | 'proveedor' | 'mixed'
    const c = ensureContainer(tableId);
    if (!c) return null;
    const body = c.querySelector('.card-body');
    const idBase = tableId + '-flt-';

    // Controles comunes
    const estadoSel = `${idBase}estado`;
    const fromId = `${idBase}from`;
    const toId = `${idBase}to`;
    const dateFieldId = `${idBase}datefield`;

    // Controles de entidad
    const tipoId = `${idBase}tipo`;
    const entidadId = `${idBase}entidad`;

    const hasMixed = mode === 'mixed';
    const isCliente = mode === 'cliente';
    const isProveedor = mode === 'proveedor';

    const html = `
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-2">
          <label class="form-label mb-1">Estado</label>
          <select id="${estadoSel}" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="0">Pendiente</option>
            <option value="1">Pagado</option>
          </select>
        </div>
        ${hasMixed ? `
        <div class="col-12 col-md-2">
          <label class="form-label mb-1">Tipo</label>
          <select id="${tipoId}" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="cliente">Clientes</option>
            <option value="proveedor">Proveedores</option>
          </select>
        </div>` : ''}
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">${isCliente? 'Cliente' : isProveedor? 'Proveedor' : 'Entidad'}</label>
          <select id="${entidadId}" class="form-select form-select-sm" ${hasMixed? 'disabled' : ''}>
            <option value="">Todos</option>
          </select>
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label mb-1">Campo fecha</label>
          <select id="${dateFieldId}" class="form-select form-select-sm">
            <option value="date">Fecha</option>
            <option value="expiry">Vencimiento</option>
          </select>
        </div>
        <div class="col-6 col-md-1">
          <label class="form-label mb-1">Desde</label>
          <input id="${fromId}" type="date" class="form-control form-control-sm"/>
        </div>
        <div class="col-6 col-md-1">
          <label class="form-label mb-1">Hasta</label>
          <input id="${toId}" type="date" class="form-control form-control-sm"/>
        </div>
        <div class="col-12 col-md-1 text-end">
          <button class="btn btn-sm btn-primary w-100" data-action="apply">Filtrar</button>
        </div>
        <div class="col-12 col-md-1 text-end">
          <button class="btn btn-sm btn-outline-secondary w-100" data-action="clear">Limpiar</button>
        </div>
      </div>`;

    body.innerHTML = html;

    // Helpers de datos locales para el select de entidad
    function getFromTableData(kind){
      try {
        const $t = $('#' + tableId);
        if (!$t.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable($t)) return [];
        const api = $t.DataTable();
        const rows = api.rows().data().toArray();
        const map = new Map();
        rows.forEach(r => {
          if (kind === 'cliente') {
            const id = r.client_id ?? r.cliente?.id;
            const name = r.cliente?.name ?? r.cliente_name ?? r.name;
            const rut = r.cliente?.rut ?? r.rut;
            if (id != null) {
              map.set(String(id), { id, name, rut });
            }
          } else if (kind === 'proveedor') {
            const id = r.provider_id ?? r.proveedor?.id;
            const name = r.proveedor?.name ?? r.proveedor_name ?? r.name;
            const rut = r.proveedor?.rut ?? r.rut;
            if (id != null) {
              map.set(String(id), { id, name, rut });
            }
          }
        });
        return Array.from(map.values());
      } catch(e){ return []; }
    }

    // Inicializar selects de entidad
    function getLocalList(kind){
      try {
        // 1) Usar caché local si existe
        if (window.searchCacheManager && typeof window.searchCacheManager.getEntityData === 'function') {
          const key = (kind === 'cliente') ? 'clientes' : 'proveedores';
          const arr = window.searchCacheManager.getEntityData(key);
          if (Array.isArray(arr) && arr.length) return arr;
        }
        // 2) Fallback a datos ya cargados en memoria (sin forzar fetch)
        if (window.ReferenceDataManager && ReferenceDataManager.data) {
          const arr = (kind === 'cliente') ? ReferenceDataManager.data.clientes : ReferenceDataManager.data.proveedores;
          if (Array.isArray(arr) && arr.length) return arr;
        }
        // 3) Derivar desde datos presentes en la tabla actual
        const derived = getFromTableData(kind);
        if (Array.isArray(derived) && derived.length) return derived;
      } catch(e) { /* noop */ }
      return [];
    }

    function fillEntidadSelect(kind){
      const $sel = $('#' + entidadId);
      const reset = () => { $sel.empty().append('<option value="">Todos</option>'); };
      reset();

      const list = getLocalList(kind);
      (list||[]).forEach(it => {
        const label = it && it.rut ? `${it.name} (${it.rut})` : (it && it.name ? it.name : '');
        $sel.append(`<option value="${it.id}">${label}</option>`);
      });
      try { if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy'); } catch(e){}
      try {
        const optsSel2 = { width: '100%' };
        $sel.select2(optsSel2);
      } catch(e){}
    }

    if (hasMixed) {
      $('#' + tipoId).on('change', function(){
        const val = this.value;
        const $sel = $('#' + entidadId);
        if (!val) { $sel.prop('disabled', true).val('').trigger('change'); }
        else { $sel.prop('disabled', false); fillEntidadSelect(val); }
      });
    } else {
      fillEntidadSelect(isCliente ? 'cliente' : 'proveedor');
    }

    // Volver a poblar automáticamente cuando la tabla cargue/redibuje
    try {
      const $t = $('#' + tableId);
      if ($t.length) {
        $t.on('xhr.dt draw.dt', function(){
          const currentKind = hasMixed ? ($('#' + tipoId).val() || '') : (isCliente ? 'cliente' : 'proveedor');
          if (!hasMixed || currentKind) {
            fillEntidadSelect(currentKind);
          }
        });
      }
    } catch(e){}

    // Estado inicial en Registro
    REGISTRY[tableId] = REGISTRY[tableId] || { estado:'', tipo:'', entidadId:'', from:'', to:'', dateField:'date' };

    // Botones
    $(c).on('click', '[data-action="apply"]', function(){
      const r = REGISTRY[tableId];
      r.estado = ($('#' + estadoSel).val() || '').trim();
      r.tipo = hasMixed ? (($('#' + tipoId).val() || '').trim()) : (isCliente? 'cliente' : 'proveedor');
      r.entidadId = ($('#' + entidadId).val() || '').trim();
      r.from = ($('#' + fromId).val() || '').trim();
      r.to = ($('#' + toId).val() || '').trim();
      r.dateField = ($('#' + dateFieldId).val() || 'date').trim();
      const table = $('#' + tableId).DataTable();
      table.draw();
    });

    $(c).on('click', '[data-action="clear"]', function(){
      const r = REGISTRY[tableId];
      r.estado = ''; r.tipo = hasMixed ? '' : (isCliente? 'cliente' : 'proveedor'); r.entidadId = ''; r.from = ''; r.to = ''; r.dateField = 'date';
      $('#' + estadoSel).val('');
      if (hasMixed) { $('#' + tipoId).val(''); $('#' + entidadId).prop('disabled', true); }
      $('#' + entidadId).val('').trigger('change');
      $('#' + fromId).val('');
      $('#' + toId).val('');
      $('#' + dateFieldId).val('date');
      const table = $('#' + tableId).DataTable();
      table.search('');
      table.draw();
    });

    return c;
  }

  // Filtro global de DataTables evaluando por tabla
  if ($ && $.fn && $.fn.dataTable) {
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
      try {
        const table = new $.fn.dataTable.Api(settings);
        const idAttr = table.table().node().getAttribute('id');
        if (!idAttr || !REGISTRY[idAttr]) return true; // sin filtros para esa tabla
        const row = table.row(dataIndex).data() || {};
        const r = REGISTRY[idAttr];

        // 1) Estado
        if (r.estado !== '' && String(row.status) !== r.estado) return false;

        // 2) Tipo (solo en modo mixed)
        if (r.tipo) {
          const isCli = !!(row.client_id || row.cliente);
          const isProv = !!(row.provider_id || row.proveedor);
          if (r.tipo === 'cliente' && !isCli) return false;
          if (r.tipo === 'proveedor' && !isProv) return false;
        }

        // 3) Entidad
        if (r.entidadId) {
          if (r.tipo === 'cliente') {
            if (String(row.client_id || row.cliente?.id) !== String(r.entidadId)) return false;
          } else if (r.tipo === 'proveedor' || (!r.tipo && row.provider_id)) {
            if (String(row.provider_id || row.proveedor?.id) !== String(r.entidadId)) return false;
          } else {
            // si no hay tipo, intentar ambos
            const matchAny = String(row.client_id || row.cliente?.id) === String(r.entidadId) || String(row.provider_id || row.proveedor?.id) === String(r.entidadId);
            if (!matchAny) return false;
          }
        }

        // 4) Rango de fechas
        const field = r.dateField === 'expiry' ? (row.expiry || row.fecha_vencimiento || row.vencimiento) : (row.date || row.fecha || row.emision);
        if (r.from || r.to) {
          const d = parseDateStr(field);
          const from = parseDateStr(r.from);
          const to = parseDateStr(r.to);
          if (from && (!d || d < from)) return false;
          if (to) {
            const toEnd = new Date(to.getFullYear(), to.getMonth(), to.getDate(), 23, 59, 59, 999);
            if (!d || d > toEnd) return false;
          }
        }

        return true;
      } catch(e) { return true; }
    });
  }

  // API pública: adjuntar panel de filtros a una tabla
  function attachInvoiceFilters(options){
    const opts = Object.assign({ mode: 'mixed' }, options||{});
    buildPanel(opts);
  }

  // Exponer
  window.attachInvoiceFilters = attachInvoiceFilters;
})();
