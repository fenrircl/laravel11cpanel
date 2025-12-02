$(function(){
  // ====================
  // Filtros de fecha (gráfico)
  // ====================
  const $from = $('#chart-from');
  const $to = $('#chart-to');
  const today = new Date();
  const toStr = today.toISOString().slice(0,10);
  const fromDate = new Date(today); fromDate.setMonth(fromDate.getMonth() - 3);
  const fromStr = fromDate.toISOString().slice(0,10);
  if (!$from.val()) $from.val(fromStr);
  if (!$to.val()) $to.val(toStr);

  // ====================
  // Inicializar ECharts
  // ====================
  // const chartDom = document.getElementById('home-invoices-chart');
  // if (chartDom) {
  //   const chart = echarts.init(chartDom);

  //   function fetchAndRender(){
  //     const params = $.param({ from: $from.val(), to: $to.val() });
  //     $.get(buildApiUrl('charts/facturas/pendientes') + '?' + params)
  //       .done((resp)=>{
  //         const clientes = resp.clientes || [];
  //         const proveedores = resp.proveedores || [];
  //         const namesC = clientes.map(x=> x.name);
  //         const dataC = clientes.map(x=> (x.total||0)/100);
  //         const namesP = proveedores.map(x=> x.name);
  //         const dataP = proveedores.map(x=> (x.total||0)/100);

  //         chart.setOption({
  //           tooltip: { trigger: 'axis' },
  //           legend: { data: ['Clientes', 'Proveedores'] },
  //           grid: { left: '3%', right: '3%', bottom: '8%', containLabel: true },
  //           xAxis: [
  //             { type: 'category', data: namesC, axisLabel: { interval: 0, rotate: 25, overflow: 'truncate' } },
  //             { type: 'category', data: namesP, axisLabel: { show: false } }
  //           ],
  //           yAxis: { type: 'value', name: 'Monto (CLP)' },
  //           series: [
  //             { name: 'Clientes', type: 'bar', xAxisIndex: 0, data: dataC },
  //             { name: 'Proveedores', type: 'bar', xAxisIndex: 1, data: dataP }
  //           ]
  //         });
  //       })
  //       .fail(()=>{
  //         Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos del gráfico.' });
  //       });
  //   }

  //   fetchAndRender();

  //   $('#chart-apply').on('click', function(){ fetchAndRender(); });
  //   $(window).on('resize', function(){ chart.resize(); });
  // }

  // ====================
  // Utilidades Home (helpers comunes)
  // ====================
  // Mantener datasets locales como respaldo para la vista rápida
  window.__HOME_CLIENTES_VENCIDAS__ = window.__HOME_CLIENTES_VENCIDAS__ || [];
  window.__HOME_PROVEEDORES_VENCIDAS__ = window.__HOME_PROVEEDORES_VENCIDAS__ || [];

  function getTipoFromRow(r){
    if (r == null) return 'cliente';
    if (r.tipo) return String(r.tipo).toLowerCase();
    if (r.client_id || r.cliente) return 'cliente';
    if (r.provider_id || r.proveedor) return 'proveedor';
    // Heurística: si viene del dataset de proveedores
    return (window.__HOME_PROVEEDORES_VENCIDAS__.find(x=> x.id===r.id)) ? 'proveedor' : 'cliente';
  }

  function getEntidadName(r){
    return (
      r.entidad ||
      r.cliente?.name || r.proveedor?.name ||
      r.cliente_name || r.proveedor_name ||
      r.name || '—'
    );
  }

  // Vista rápida local (fallback) usando datasets filtrados
  window.openInvoiceQuickViewHome = function(id){
    const modalEl = document.getElementById('invoiceQuickViewModal');
    const bodyEl = document.getElementById('invoiceQuickViewBody');
    const linkEl = document.getElementById('invoiceFullViewLink');
    if (!modalEl || !bodyEl || !linkEl) return;
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl);

    const findIn = (arr)=> arr.find(x=> String(x.id) === String(id) || String(x.invoice) === String(id));
    const r = findIn(window.__HOME_CLIENTES_VENCIDAS__) || findIn(window.__HOME_PROVEEDORES_VENCIDAS__);
    if (!r) {
      if (typeof window.openInvoiceQuickView === 'function') return window.openInvoiceQuickView(id);
      bodyEl.innerHTML = '<div class="text-danger">No se encontraron datos de la factura.</div>';
      modal.show();
      return;
    }
    const tipo = getTipoFromRow(r);
    const entidad = getEntidadName(r);
    const metodo = r.metodo_pago?.name || r.metodoPago?.name || r.payment_method_name || '—';
    const estadoHtml = (typeof renderInvoiceStatusBadge === 'function') ? renderInvoiceStatusBadge(r.status ?? 0, r.expiry, r.extra) : '';

    const html = `
      <div class="row g-3">
        <div class="col-md-6">
          <div><strong>N° Factura:</strong> ${r.invoice || '—'}</div>
          <div><strong>Fecha:</strong> ${r.date ? formatTableDate(r.date, false) : '—'}</div>
          <div><strong>Vencimiento:</strong> ${r.expiry ? formatTableDate(r.expiry, false) : '—'}</div>
          <div><strong>Pagado:</strong> ${r.pay_date ? formatTableDate(r.pay_date, false) : '—'}</div>
        </div>
        <div class="col-md-6">
          <div><strong>Entidad:</strong> ${entidad}</div>
          <div><strong>Método de pago:</strong> ${metodo}</div>
          <div><strong>Monto:</strong> ${formatCurrency(r.amount || 0)}</div>
          <div><strong>Estado:</strong> ${estadoHtml}</div>
        </div>
        <div class="col-12">
          <div><strong>Detalle:</strong></div>
          <div class="border rounded p-2 bg-light">${(r.detail||'').toString().trim() || '<span class="text-muted">Sin detalles</span>'}</div>
        </div>
      </div>`;
    bodyEl.innerHTML = html;
    linkEl.href = buildApiUrl('facturas/' + (tipo==='cliente'?'clientes':'proveedores') + '/' + (r.id || r.invoice));
    modal.show();
  };

  // ====================
  // Tablas Home (AJAX + filtros en cliente)
  // ====================
  try {
    if (typeof initDataTable !== 'function') return;

    // Controles de filtro
    const $fromTbl = $('#home-filter-from');
    const $toTbl = $('#home-filter-to');
    const todayTbl = new Date();
    const toStrTbl = todayTbl.toISOString().slice(0,10);
    const fromDateTbl = new Date(todayTbl); fromDateTbl.setFullYear(fromDateTbl.getFullYear() - 1);
    const fromStrTbl = fromDateTbl.toISOString().slice(0,10);
    // Establecer fecha "hasta" por defecto con 30 días en el futuro para incluir facturas próximas a vencer
    const futureDate = new Date(todayTbl); futureDate.setDate(futureDate.getDate() + 30);
    const futureStr = futureDate.toISOString().slice(0,10);
    
    if (!$fromTbl.val()) $fromTbl.val(fromStrTbl);
    if (!$toTbl.val()) $toTbl.val(futureStr); // Usar fecha futura por defecto

    function inRange(dateStr, from, to){
      if (!dateStr) return false;
      try {
        const d = new Date(dateStr);
        const f = new Date(from);
        const t = new Date(to);
        d.setHours(0,0,0,0); f.setHours(0,0,0,0); t.setHours(0,0,0,0);
        return d >= f && d <= t;
      } catch { return false; }
    }

    function getRange(){
      const f = $fromTbl.val() || '1900-01-01';
      const t = $toTbl.val() || '2999-12-31';
      return { from: f, to: t };
    }

    // Función para calcular días hasta/desde vencimiento
    function calculateDaysToExpiry(expiryDate) {
      if (!expiryDate) return null;
      const today = new Date();
      const expiry = new Date(expiryDate);
      today.setHours(0, 0, 0, 0);
      expiry.setHours(0, 0, 0, 0);
      const diffTime = expiry - today;
      return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }

    // Función para renderizar el contador de días
    function renderDaysCounter(expiryDate, status) {
      // Solo mostrar días si la factura está pendiente (status = 0)
      // En Home ya se filtran solo pendientes, pero mantener consistencia
      if (status !== undefined && status !== 0 && status !== '0') {
        return '—'; // Factura pagada, no mostrar días
      }
      
      const days = calculateDaysToExpiry(expiryDate);
      if (days === null) return '—';
      
      if (days > 0) {
        // Próximo a vencer
        if (days <= 7) {
          return `<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>${days} días para vencer</span>`;
        } else if (days <= 30) {
          return `<span class="badge bg-info"><i class="fas fa-clock me-1"></i>${days} días para vencer</span>`;
        } else {
          return `<span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>${days} días para vencer</span>`;
        }
      } else if (days === 0) {
        return `<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Hoy</span>`;
      } else {
        // Vencida
        const daysOverdue = Math.abs(days);
        return `<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>${daysOverdue} días vencida</span>`;
      }
    }

    const cols = [
      { data: null, title: 'Entidad', className: 'text-start', render: (d, t, r) => {
          const name = getEntidadName(r);
          const rut = r.rut ? `<small class="text-muted d-block">${r.rut}</small>` : '';
          const tipo = getTipoFromRow(r);
          const entidadId = r.client_id || r.provider_id || r.id;
          const baseUrl = getBaseUrl();
          const link = tipo === 'cliente' 
            ? `${baseUrl}/clientes/${entidadId}` 
            : `${baseUrl}/proveedores/${entidadId}`;
          return `<a href="${link}" class="link-primary text-decoration-none fw-500 text-truncate d-inline-block" style="max-width:250px" title="${name}">${name}</a>${rut}`;
        }
      },
      { data: 'invoice', title: 'Factura', width: '120px', className: 'text-center', render: (d, t, r) => {
          if (t === 'sort' || t === 'type') return d; // usar como está para ordenar
          const tipo = getTipoFromRow(r);
          const baseUrl = getBaseUrl();
          const link = tipo === 'cliente' 
            ? `${baseUrl}/facturas/clientes/${r.id}` 
            : `${baseUrl}/facturas/proveedores/${r.id}`;
          return `<a href="${link}" class="link-primary text-decoration-none fw-500" title="Ver factura">${d}</a>`;
        }
      },
      { data: 'expiry', title: 'Vencimiento', width: '130px', className: 'text-center', render: (data, type, row)=> {
          const raw = row?.expiry || row?.date || '';
          if (type === 'sort' || type === 'type') return raw; // usar ISO para ordenar
          return raw ? formatTableDate(raw, false) : '—';
        }
      },
      { data: null, title: 'Días', width: '140px', className: 'text-center', render: (d, t, r) => {
          const expiryDate = r?.expiry || r?.date || '';
          if (t === 'sort' || t === 'type') {
            // Para ordenamiento, retornar los días calculados solo si está pendiente
            if (r.status !== 0 && r.status !== '0') {
              return 999999; // Facturas pagadas van al final
            }
            return calculateDaysToExpiry(expiryDate) || 999999;
          }
          return renderDaysCounter(expiryDate, r.status);
        }
      },
      { data: 'amount', title: 'Monto', width: '140px', className: 'text-end', render: (d)=> typeof formatCurrency === 'function' ? formatCurrency(d) : d },
      { data: null, title: 'Estado', width: '110px', className: 'text-center', render: (d,t,r)=>{
          return (typeof renderInvoiceStatusBadge === 'function') ? renderInvoiceStatusBadge(r.status ?? 0, r.expiry, r.extra) : '';
        }
      },
      { data: null, title: 'Acciones', orderable:false, searchable:false, className:'text-end nowrap', width: '150px', render: (d,t,r)=>{
          const id = r.id || r.invoice;
          const tipo = getTipoFromRow(r);
          const viewAction = (typeof window.openInvoiceQuickView === 'function') ? `openInvoiceQuickView(${id})` : `openInvoiceQuickViewHome(${id})`;
          const viewBtn = `<button type="button" class="btn btn-sm btn-outline-secondary me-1" title="Vista rápida" onclick="${viewAction}"><i class="fas fa-eye"></i></button>`;
          const editBtn = (typeof window.openEditFacturaModal === 'function')
            ? `<button type="button" class="btn btn-sm btn-outline-primary" title="Editar" onclick="openEditFacturaModal('${tipo}', ${id})"><i class="fas fa-pen"></i></button>`
            : '';
          return viewBtn + editBtn;
        }
      }
    ];

    function makeOptions(mode){
      const url = mode === 'cliente' ? 'facturas/clientes/data' : 'facturas/proveedores/data';
      return {
        ajax: {
          url: buildApiUrl(url),
          type: 'GET',
          dataSrc: function(json){
            const { from, to } = getRange();
            const today = new Date(); today.setHours(0,0,0,0);
            const thirtyDaysFromNow = new Date(today);
            thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30); // Próximas a vencer en 30 días
            
            const data = (json && json.data) ? json.data : [];
            
            const filtered = data.filter(r => {
              const status = parseInt(r.status, 10) || 0;
              if (status !== 0) return false; // Solo facturas pendientes
              
              const dStr = r.expiry || r.date;
              if (!dStr) return false;
              
              const d = new Date(dStr); d.setHours(0,0,0,0);
              
              // Incluir facturas vencidas O próximas a vencer (dentro de 30 días)
              const isOverdue = d < today;
              const isDueSoon = d >= today && d <= thirtyDaysFromNow;
              const inDateRange = inRange(dStr, from, to);
              
              return (isOverdue || isDueSoon) && inDateRange;
            }).map(r => {
              r.amount = (typeof r.amount === 'number') ? Math.round(r.amount) : parseInt(r.amount||0,10);
              return r;
            });

            if (mode === 'cliente') window.__HOME_CLIENTES_VENCIDAS__ = filtered;
            else window.__HOME_PROVEEDORES_VENCIDAS__ = filtered;
            return filtered;
          },
          error: function(){
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar las facturas vencidas.' });
          }
        },
        searching:false,
        paging:false,
        info:false,
        order:[[3,'asc']], // ordenar por días: vencidas (negativos) y próximas a vencer primero 
        columnDefs: [
          { targets: 0, responsivePriority: 4 }, // Entidad
          { targets: 1, responsivePriority: 6 }, // Factura
          { targets: 2, responsivePriority: 5 }, // Vencimiento
          { targets: 3, responsivePriority: 2 }, // Días (importante)
          { targets: 4, responsivePriority: 7 }, // Monto
          { targets: 5, responsivePriority: 3 }, // Estado
          { targets: 6, responsivePriority: 1 }  // Acciones
        ]
      };
    }

    // Inicializar una sola vez vía AJAX (evita re-init warnings)
    initDataTable('home-clientes-vencidas', null, cols, makeOptions('cliente'));
    initDataTable('home-proveedores-vencidas', null, cols, makeOptions('proveedor'));

    function reloadHomeTables(){
      if ($.fn.DataTable.isDataTable('#home-clientes-vencidas')) $('#home-clientes-vencidas').DataTable().ajax.reload(null, false);
      if ($.fn.DataTable.isDataTable('#home-proveedores-vencidas')) $('#home-proveedores-vencidas').DataTable().ajax.reload(null, false);
    }

    // Botones de filtro
    $('#home-filter-apply').on('click', function(){ reloadHomeTables(); });
    $('#home-filter-clear').on('click', function(){
      $fromTbl.val('');
      $toTbl.val('');
      reloadHomeTables();
    });
  } catch(e) {
    console.error('Error inicializando tablas de Home:', e);
  }
});
