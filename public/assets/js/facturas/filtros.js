;(function(){
  if (window.attachInvoiceFilters) return;

  // Registro global de filtros por tabla
  const REGISTRY = {};

  // Gestión de estado de filtros
  const FilterStateManager = {
    getStorageKey: (tableId) => `invoice-filters-${tableId}`,
    
    // Obtener filtros desde URL o estado temporal
    getInitialFilters: function(tableId) {
      const urlParams = new URLSearchParams(window.location.search);
      const tempFilters = sessionStorage.getItem(`temp-filters-${tableId}`);
      
      // Prioridad: filtros temporales > parámetros URL
      if (tempFilters) {
        try {
          const parsed = JSON.parse(tempFilters);
          sessionStorage.removeItem(`temp-filters-${tableId}`); // Limpiar después de usar
          return parsed;
        } catch (e) {
          console.warn('Error parsing temp filters:', e);
        }
      }
      
      // Leer desde URL
      return {
        filter: urlParams.get('filter') || '',
        estado: urlParams.get('estado') || '',
        tipo: urlParams.get('tipo') || '',
        entidadId: urlParams.get('entidad') || '',
        from: urlParams.get('from') || '',
        to: urlParams.get('to') || '',
        dateField: urlParams.get('dateField') || 'date'
      };
    },
    
    // Guardar estado temporal (para mantener entre aplicaciones de filtro)
    saveTempState: function(tableId, filters) {
      sessionStorage.setItem(`temp-filters-${tableId}`, JSON.stringify(filters));
    },
    
    // Actualizar URL sin recargar página
    updateURL: function(filters) {
      const url = new URL(window.location);
      Object.keys(filters).forEach(key => {
        if (filters[key] && filters[key] !== '') {
          url.searchParams.set(key, filters[key]);
        } else {
          url.searchParams.delete(key);
        }
      });
      window.history.replaceState({}, '', url);
    },
    
    // Limpiar completamente la URL de todos los parámetros de filtro
    clearURL: function() {
      const url = new URL(window.location);
      const filterParams = ['filter', 'estado', 'tipo', 'entidad', 'from', 'to', 'dateField'];
      
      filterParams.forEach(param => {
        url.searchParams.delete(param);
      });
      
      window.history.replaceState({}, '', url);
    }
  };

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

    // Obtener filtros iniciales desde URL o estado temporal
    const initialFilters = FilterStateManager.getInitialFilters(tableId);

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

    // Aplicar filtro especial "pending" desde URL si existe
    let defaultEstado = initialFilters.estado;
    if (initialFilters.filter === 'pending') {
      defaultEstado = '0'; // Solo pendientes
    }

    const html = `
      <!-- Filtros activos (se muestra solo si hay filtros aplicados) -->
      <div id="${tableId}-active-filters" class="alert alert-info py-2 mb-2" style="display: none;">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <div class="filter-summary-wrapper">
            <small><i class="fas fa-filter me-1"></i><strong>Filtros activos:</strong> <span id="${tableId}-filter-summary"></span></small>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary mt-1 mt-sm-0" data-action="clear">
            <i class="fas fa-times me-1"></i><span class="d-none d-sm-inline">Limpiar todo</span><span class="d-inline d-sm-none">Limpiar</span>
          </button>
        </div>
      </div>
      
      <div class="row g-2 align-items-end filtros-form-row">
        <!-- Estado -->
        <div class="col-12 col-sm-6 col-md-2 filter-field">
          <label class="form-label mb-1" for="${estadoSel}">Estado</label>
          <select id="${estadoSel}" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="0" ${defaultEstado === '0' ? 'selected' : ''}>Pendiente</option>
            <option value="1" ${defaultEstado === '1' ? 'selected' : ''}>Pagado</option>
          </select>
        </div>
        
        <!-- Tipo (solo para mixed) -->
        ${hasMixed ? `
        <div class="col-12 col-sm-6 col-md-2 filter-field">
          <label class="form-label mb-1" for="${tipoId}">Tipo</label>
          <select id="${tipoId}" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="cliente" ${initialFilters.tipo === 'cliente' ? 'selected' : ''}>Clientes</option>
            <option value="proveedor" ${initialFilters.tipo === 'proveedor' ? 'selected' : ''}>Proveedores</option>
          </select>
        </div>` : ''}
        
        <!-- Entidad -->
        <div class="col-12 ${hasMixed ? 'col-sm-12' : 'col-sm-6'} col-md-3 filter-field">
          <label class="form-label mb-1" for="${entidadId}">${isCliente? 'Cliente' : isProveedor? 'Proveedor' : 'Entidad'}</label>
          <select id="${entidadId}" class="form-select form-select-sm entidad-select" ${hasMixed? 'disabled' : ''}>
            <option value="">Todos</option>
          </select>
        </div>
        
        <!-- Campo fecha -->
        <div class="col-12 col-sm-6 col-md-2 filter-field">
          <label class="form-label mb-1" for="${dateFieldId}">Campo fecha</label>
          <select id="${dateFieldId}" class="form-select form-select-sm">
            <option value="date" ${initialFilters.dateField === 'date' ? 'selected' : ''}>Fecha</option>
            <option value="expiry" ${initialFilters.dateField === 'expiry' ? 'selected' : ''}>Vencimiento</option>
          </select>
        </div>
        
        <!-- Fechas -->
        <div class="col-12 col-sm-6 col-lg-1 filter-field date-field">
          <label class="form-label mb-1" for="${fromId}">Desde</label>
          <input id="${fromId}" type="date" class="form-control form-control-sm" value="${initialFilters.from || ''}"/>
        </div>
        <div class="col-12 col-sm-6 col-lg-1 filter-field date-field">
          <label class="form-label mb-1" for="${toId}">Hasta</label>
          <input id="${toId}" type="date" class="form-control form-control-sm" value="${initialFilters.to || ''}"/>
        </div>
        
        <!-- Botones de acción -->
        <div class="col-12 col-sm-6 col-lg-1 filter-actions">
          <div class="d-grid">
            <button class="btn btn-sm btn-primary" data-action="apply">
              <i class="fas fa-search d-lg-none"></i><span class="d-none d-lg-inline">Filtrar</span><span class="d-inline d-lg-none"> Filtrar</span>
            </button>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-1 filter-actions">
          <div class="d-grid">
            <button class="btn btn-sm btn-outline-secondary" data-action="clear">
              <i class="fas fa-eraser d-lg-none"></i><span class="d-none d-lg-inline">Limpiar</span><span class="d-inline d-lg-none"> Limpiar</span>
            </button>
          </div>
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
        const optsSel2 = { 
          width: '100%',
          dropdownParent: $sel.parent(),
          placeholder: 'Seleccione...',
          allowClear: true,
          language: {
            noResults: function() { return 'No se encontraron resultados'; },
            searching: function() { return 'Buscando...'; }
          }
        };
        $sel.select2(optsSel2);
        
        // Optimizar para pantalla actual
        setTimeout(() => ResponsiveUtils.optimizeSelect2($sel), 50);
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
            
            // Después de llenar el select, sincronizar con el registro si hay filtros activos
            setTimeout(() => {
              const r = REGISTRY[tableId];
              if (r && r.entidadId) {
                $(`#${entidadId}`).val(r.entidadId).trigger('change');
                updateActiveFiltersDisplay();
              }
            }, 100);
          }
        });
      }
    } catch(e){}

    // Estado inicial en Registro con valores de URL/estado temporal
    REGISTRY[tableId] = REGISTRY[tableId] || { 
      estado: defaultEstado || '', 
      tipo: hasMixed ? (initialFilters.tipo || '') : (isCliente ? 'cliente' : 'proveedor'), 
      entidadId: initialFilters.entidadId || '', 
      from: initialFilters.from || '', 
      to: initialFilters.to || '', 
      dateField: initialFilters.dateField || 'date' 
    };

    // Funciones para manejar filtros activos
    function updateActiveFiltersDisplay() {
      const r = REGISTRY[tableId];
      const activeFilters = [];
      const $alert = $(`#${tableId}-active-filters`);
      const $summary = $(`#${tableId}-filter-summary`);

      // Construir resumen de filtros activos
      if (r.estado) activeFilters.push(`Estado: ${r.estado === '0' ? 'Pendiente' : 'Pagado'}`);
      if (r.tipo && hasMixed) activeFilters.push(`Tipo: ${r.tipo === 'cliente' ? 'Clientes' : 'Proveedores'}`);
      if (r.entidadId) {
        const entidadName = $(`#${entidadId} option:selected`).text();
        activeFilters.push(`${isCliente ? 'Cliente' : isProveedor ? 'Proveedor' : 'Entidad'}: ${entidadName}`);
      }
      if (r.from || r.to) {
        const dateFieldName = r.dateField === 'expiry' ? 'Vencimiento' : 'Fecha';
        if (r.from && r.to) {
          activeFilters.push(`${dateFieldName}: ${r.from} a ${r.to}`);
        } else if (r.from) {
          activeFilters.push(`${dateFieldName}: desde ${r.from}`);
        } else if (r.to) {
          activeFilters.push(`${dateFieldName}: hasta ${r.to}`);
        }
      }

      if (activeFilters.length > 0) {
        $summary.text(activeFilters.join(', '));
        $alert.show();
      } else {
        $alert.hide();
      }
    }

    // Función para sincronizar campos del formulario con el registro
    function syncFormWithRegistry() {
      const r = REGISTRY[tableId];
      
      // Sincronizar todos los campos con los valores del registro
      $(`#${estadoSel}`).val(r.estado || '');
      
      if (hasMixed) {
        $(`#${tipoId}`).val(r.tipo || '');
        
        // Manejar estado del select de entidad según tipo
        if (r.tipo) {
          $(`#${entidadId}`).prop('disabled', false);
          fillEntidadSelect(r.tipo);
          // Esperar un momento para que se llene el select antes de establecer el valor
          setTimeout(() => {
            $(`#${entidadId}`).val(r.entidadId || '').trigger('change');
          }, 50);
        } else {
          $(`#${entidadId}`).prop('disabled', true).val('');
        }
      } else {
        // Para vistas específicas (cliente/proveedor), siempre llenar select
        setTimeout(() => {
          $(`#${entidadId}`).val(r.entidadId || '').trigger('change');
        }, 50);
      }
      
      $(`#${fromId}`).val(r.from || '');
      $(`#${toId}`).val(r.to || '');
      $(`#${dateFieldId}`).val(r.dateField || 'date');
    }

    // Aplicar filtros iniciales si existen
    if (initialFilters.filter === 'pending' || defaultEstado || initialFilters.tipo || initialFilters.entidadId || initialFilters.from || initialFilters.to) {
      setTimeout(() => {
        // Sincronizar formulario con valores iniciales
        syncFormWithRegistry();
        updateActiveFiltersDisplay();
        const table = $('#' + tableId).DataTable();
        if (table) table.draw();
      }, 150);
    }

    // Botones
    $(c).on('click', '[data-action="apply"]', function(){
      const r = REGISTRY[tableId];
      const newFilters = {
        estado: ($('#' + estadoSel).val() || '').trim(),
        tipo: hasMixed ? (($('#' + tipoId).val() || '').trim()) : (isCliente? 'cliente' : 'proveedor'),
        entidadId: ($('#' + entidadId).val() || '').trim(),
        from: ($('#' + fromId).val() || '').trim(),
        to: ($('#' + toId).val() || '').trim(),
        dateField: ($('#' + dateFieldId).val() || 'date').trim()
      };
      
      // Actualizar registro
      Object.assign(r, newFilters);
      
      // Guardar estado temporal para mantener filtros
      FilterStateManager.saveTempState(tableId, newFilters);
      
      // Actualizar URL
      FilterStateManager.updateURL({
        estado: newFilters.estado,
        tipo: hasMixed ? newFilters.tipo : '',
        entidad: newFilters.entidadId,
        from: newFilters.from,
        to: newFilters.to,
        dateField: newFilters.dateField !== 'date' ? newFilters.dateField : ''
      });
      
      // Sincronizar formulario con registro (mantener valores visibles)
      setTimeout(() => {
        syncFormWithRegistry();
        updateActiveFiltersDisplay();
      }, 50);
      
      // Aplicar filtros
      const table = $('#' + tableId).DataTable();
      table.draw();
    });

    $(c).on('click', '[data-action="clear"]', function(){
      const r = REGISTRY[tableId];
      
      // Limpiar registro completamente
      r.estado = ''; 
      r.tipo = hasMixed ? '' : (isCliente ? 'cliente' : 'proveedor'); 
      r.entidadId = ''; 
      r.from = ''; 
      r.to = ''; 
      r.dateField = 'date';
      
      // Limpiar formulario completamente
      $(`#${estadoSel}`).val('');
      
      if (hasMixed) { 
        $(`#${tipoId}`).val(''); 
        $(`#${entidadId}`).prop('disabled', true).val('').trigger('change'); 
      } else {
        // Para vistas específicas, mantener el select habilitado pero limpiar valor
        $(`#${entidadId}`).val('').trigger('change');
      }
      
      $(`#${fromId}`).val('');
      $(`#${toId}`).val('');
      $(`#${dateFieldId}`).val('date');
      
      // Limpiar estado temporal y URL completamente
      sessionStorage.removeItem(`temp-filters-${tableId}`);
      FilterStateManager.clearURL(); // Usar la nueva función para limpiar URL completamente
      
      // Ocultar filtros activos
      $(`#${tableId}-active-filters`).hide();
      
      // Aplicar limpieza
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
    
    // Inicializar handlers responsive (solo una vez)
    if (!window.invoiceFiltersResponsiveInitialized) {
      ResponsiveUtils.initResizeHandlers();
      window.invoiceFiltersResponsiveInitialized = true;
    }
    
    // Optimizar layout inicial
    setTimeout(() => {
      ResponsiveUtils.optimizeFilterLayout(opts.tableId);
    }, 100);
  }

  // Utilidades para optimización responsive
  const ResponsiveUtils = {
    // Detectar si estamos en una pantalla pequeña
    isMobileView: () => window.innerWidth <= 575.98,
    isTabletView: () => window.innerWidth > 575.98 && window.innerWidth <= 991.98,
    isDesktopView: () => window.innerWidth >= 992,
    
    // Optimizar Select2 para diferentes tamaños de pantalla
    optimizeSelect2: function(selector) {
      const $select = $(selector);
      if (!$select.length || !$select.hasClass('select2-hidden-accessible')) return;
      
      try {
        const isMobile = this.isMobileView();
        const config = {
          width: '100%',
          dropdownParent: $select.parent(),
          placeholder: 'Seleccione...',
          allowClear: true,
          language: {
            noResults: function() { return 'No se encontraron resultados'; },
            searching: function() { return 'Buscando...'; }
          }
        };
        
        // En móvil, ajustar dropdown
        if (isMobile) {
          config.dropdownAutoWidth = true;
          config.minimumResultsForSearch = 5; // Mostrar búsqueda solo si hay muchas opciones
        }
        
        $select.select2('destroy').select2(config);
      } catch(e) {
        console.warn('Error optimizing Select2:', e);
      }
    },
    
    // Colapsar filtros en móvil si hay muchos activos
    optimizeFilterLayout: function(tableId) {
      const container = document.getElementById(tableId + '-filters');
      if (!container) return;
      
      const isMobile = this.isMobileView();
      const activeFilters = document.getElementById(tableId + '-active-filters');
      
      if (isMobile && activeFilters && !activeFilters.style.display.includes('none')) {
        // En móvil, hacer más compacto el display de filtros activos
        const summary = activeFilters.querySelector('.filter-summary-wrapper small');
        if (summary && summary.textContent.length > 50) {
          const text = summary.textContent;
          const shortText = text.substring(0, 47) + '...';
          summary.textContent = shortText;
          summary.title = text; // Tooltip con texto completo
        }
      }
    },
    
    // Inicializar eventos de resize
    initResizeHandlers: function() {
      let resizeTimeout;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
          // Re-optimizar todos los Select2 visibles
          $('.entidad-select').each((i, el) => {
            this.optimizeSelect2(el);
          });
          
          // Re-optimizar layouts de filtros
          Object.keys(REGISTRY).forEach(tableId => {
            this.optimizeFilterLayout(tableId);
          });
        }, 250);
      });
    }
  };

  // Función de utilidad para debugging
  window.debugFilters = function(tableId) {
    console.log('=== Debug Filters ===');
    console.log('Table ID:', tableId);
    console.log('Registry:', REGISTRY[tableId]);
    console.log('Current URL:', window.location.href);
    console.log('URL Params:', Object.fromEntries(new URLSearchParams(window.location.search)));
    console.log('Temp Storage:', sessionStorage.getItem(`temp-filters-${tableId}`));
    console.log('Screen Info:', {
      width: window.innerWidth,
      isMobile: ResponsiveUtils.isMobileView(),
      isTablet: ResponsiveUtils.isTabletView(),
      isDesktop: ResponsiveUtils.isDesktopView()
    });
    
    // Estado actual de los campos del formulario
    const idBase = tableId + '-flt-';
    console.log('Form Fields:');
    console.log('- Estado:', $(`#${idBase}estado`).val());
    console.log('- Tipo:', $(`#${idBase}tipo`).val());
    console.log('- Entidad:', $(`#${idBase}entidad`).val());
    console.log('- From:', $(`#${idBase}from`).val());
    console.log('- To:', $(`#${idBase}to`).val());
    console.log('- DateField:', $(`#${idBase}datefield`).val());
    console.log('=====================');
  };

  // Exponer
  window.attachInvoiceFilters = attachInvoiceFilters;
})();
