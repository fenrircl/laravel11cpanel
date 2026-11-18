$(function(){
    const providerId = $('meta[name="proveedor-id"]').attr('content');
    if (!providerId) return;

    // Configurar columnas de la tabla de facturas del proveedor
    const columns = [
        {data: 'invoice', name: 'invoice', title: 'Folio'},
        {data: 'date', name: 'date', title: 'Fecha', render: function(d, type, row) {
            if (type === 'sort' || type === 'filter') return d;  // Ordenar por valor original
            return formatTableDate(d, false);  // Mostrar en formato Chile
        }},
        {data: 'expiry', name: 'expiry', title: 'Vence', render: function(d, type, row) {
            if (type === 'sort' || type === 'filter') return d;
            return d ? formatTableDate(d, false) : 'N/A';
        }},
        {data: 'pay_date', name: 'pay_date', title: 'Pagado', render: function(d, type, row) {
            if (type === 'sort' || type === 'filter') return d;
            return d ? formatTableDate(d, false) : 'N/A';
        }},
        {data: 'amount', name: 'amount', title: 'Monto', render: d => formatCurrency(d)},
        {data: 'status', name: 'status', title: 'Estado', render: s => `<span class="badge ${s===1?'bg-success':'bg-warning'}">${s===1?'Pagado':'Pendiente'}</span>`},
        {data: 'extra', name: 'extra', title: 'Datos Extra', render: d => d ? `<small class="text-muted" title="${d}">${d.substring(0, 30)}${d.length > 30 ? '...' : ''}</small>` : '<span class="text-muted">—</span>'},
        {
            data: null, name: 'action', orderable:false, searchable:false, title: 'Acciones',
            render: (data, type, row) => {
                const opts = {};
                if (!row.has_file || !row.file_path) { (opts.exclude || (opts.exclude=[])).push('download'); }
                const actionButtons = generateActionButtons(row.id, 'facturas', opts);
                const invoiceUrl = buildApiUrl(`facturas/${encodeURIComponent(row.id)}`);
                const viewButton = `<a href="${invoiceUrl}" class="btn btn-sm btn-outline-info" title="Ver factura #${$('<div>').text(row.invoice || row.id).html()}" aria-label="Ver factura #${$('<div>').text(row.invoice || row.id).html()}"><i class="fas fa-eye" aria-hidden="true"></i></a>`;
                return `${viewButton} ${actionButtons}`;
            }
        }
    ];

    const tableOptions = {
        ajax: {
            url: buildApiUrl('facturas/proveedores/data'),
            type: 'GET',
            cache: false, // evitar caché
            data: function(d){ d._ = Date.now(); }, // cache-busting
            dataSrc: function(json){
                const list = (json && json.data) ? json.data : [];
                // Filtrar por proveedor y deduplicar por ID
                const filtered = list.filter(f => String(f.provider_id) === String(providerId));
                const seen = new Set();
                const unique = [];
                for (const r of filtered) {
                    if (r && r.id != null && !seen.has(r.id)) { seen.add(r.id); unique.push(r); }
                }
                EntityDataManager.setEntityData('facturas', unique);
                return unique;
            },
            error: function(xhr){
                console.error('Error cargando facturas de proveedor', xhr);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar las facturas del proveedor.' });
            }
        },
        order: [[1, 'desc']]  // Ordenar por columna de fecha (índice 1) descendente (más reciente primero)
    };

    // Evitar doble inicialización
    if ($.fn.DataTable.isDataTable('#proveedor-facturas-table')) {
        $('#proveedor-facturas-table').DataTable().ajax.reload(null, false);
    } else {
        initDataTable('proveedor-facturas-table', null, columns, tableOptions);
    }

    window.verFactura = function(id) {
        const factura = EntityHelpers.getFactura(id);
        if (!factura) {
            Swal.fire('Error', 'No se encontraron los datos de la factura.', 'error');
            return;
        }

        const escapeHtml = value => $('<div>').text(value ?? '').html();
        const providerName = factura.proveedor?.name || 'Proveedor';
        const lineItems = Array.isArray(factura.line_items) ? factura.line_items : [];
        const itemsHtml = lineItems.length
            ? `<div class="table-responsive mt-3"><h6>Productos y servicios extraídos</h6><table class="table table-sm table-striped"><thead><tr><th>Descripción</th><th>Cantidad</th><th>Precio unitario</th><th>Total</th></tr></thead><tbody>${lineItems.map(item => {
                const description = item.description || item.description_original || 'Sin descripción';
                const quantity = item.quantity == null ? '—' : `${item.quantity} ${item.unit || ''}`.trim();
                const unitPrice = item.unit_price == null ? '—' : formatCurrency(item.unit_price);
                const total = item.amount ?? item.total_price;
                return `<tr><td>${escapeHtml(description)}</td><td>${escapeHtml(quantity)}</td><td>${total == null && item.unit_price == null ? '—' : escapeHtml(unitPrice)}</td><td>${total == null ? '—' : formatCurrency(total)}</td></tr>`;
            }).join('')}</tbody></table></div>`
            : '<p class="text-muted mt-3 mb-0">No hay ítems extraídos desde el PDF.</p>';

        const invoiceUrl = buildApiUrl(`facturas/${factura.id}`);
        const detailsHtml = `
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div><div class="text-muted small">Factura #${escapeHtml(factura.invoice)}</div><h5 class="mb-0">${escapeHtml(providerName)}</h5></div>
                <span class="badge ${Number(factura.status) === 1 ? 'bg-success' : 'bg-warning text-dark'}">${Number(factura.status) === 1 ? 'Pagada' : 'Pendiente'}</span>
            </div>
            <div class="row g-2">
                <div class="col-sm-6"><strong>Fecha:</strong> ${escapeHtml(formatTableDate(factura.date, false))}</div>
                <div class="col-sm-6"><strong>Vencimiento:</strong> ${factura.expiry ? escapeHtml(formatTableDate(factura.expiry, false)) : '—'}</div>
                <div class="col-sm-6"><strong>Pago:</strong> ${factura.pay_date ? escapeHtml(formatTableDate(factura.pay_date, false)) : 'Pendiente'}</div>
                <div class="col-sm-6"><strong>Monto:</strong> ${formatCurrency(factura.amount)}</div>
                <div class="col-sm-6"><strong>Método:</strong> ${escapeHtml(factura.metodo_pago?.name || 'No especificado')}</div>
                <div class="col-12"><strong>Detalle:</strong><div class="text-muted">${escapeHtml(factura.detail || 'Sin detalle adicional')}</div></div>
            </div>
            ${itemsHtml}
            <div class="d-flex justify-content-end mt-3"><a class="btn btn-primary btn-sm" href="${escapeHtml(invoiceUrl)}"><i class="fas fa-arrow-up-right-from-square me-1"></i>Ir a la factura</a></div>`;

        $('#facturaDetailsContent').html(detailsHtml);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('facturaDetailsModal')).show();
    };

    // Toggle archivos
    $('#filesToggleProv').on('click', function(){ $('#filesCollapseProv').collapse('toggle'); });

    // Descargas de archivos asociados
    $(document).on('click', '[data-download-path]', function(){
        const path = $(this).data('download-path');
        if (!path) return;
        // Codificar correctamente la ruta para URL
        const encodedPath = encodeURIComponent(path).replace(/%2F/g, '/');
        window.open(buildApiUrl(`r2/download/${encodedPath}`), '_blank');
    });
});
