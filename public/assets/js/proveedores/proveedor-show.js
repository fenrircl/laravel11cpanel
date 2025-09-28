$(function(){
    const providerId = $('meta[name="proveedor-id"]').attr('content');
    if (!providerId) return;

    // Configurar columnas de la tabla de facturas del proveedor
    const columns = [
        {data: 'invoice', name: 'invoice', title: 'Número'},
        {data: 'date', name: 'date', title: 'Fecha', render: d => formatTableDate(d, false)},
        {data: 'expiry', name: 'expiry', title: 'Vence', render: d => d ? formatTableDate(d, false) : 'N/A'},
        {data: 'pay_date', name: 'pay_date', title: 'Pagado', render: d => d ? formatTableDate(d, false) : 'N/A'},
        {data: 'amount', name: 'amount', title: 'Monto', render: d => formatCurrency(d)},
        {data: 'status', name: 'status', title: 'Estado', render: s => `<span class="badge ${s===1?'bg-success':'bg-warning'}">${s===1?'Pagado':'Pendiente'}</span>`},
        {
            data: 'action', name: 'action', orderable:false, searchable:false, title: 'Acciones',
            render: (data, type, row) => {
                const opts = { exclude: ['view'] };
                if (!row.has_file || !row.file_path) { (opts.exclude || (opts.exclude=[])).push('download'); }
                return generateActionButtons(row.id, 'facturas', opts);
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
        order: [[0, 'desc']]
    };

    // Evitar doble inicialización
    if ($.fn.DataTable.isDataTable('#proveedor-facturas-table')) {
        $('#proveedor-facturas-table').DataTable().ajax.reload(null, false);
    } else {
        initDataTable('proveedor-facturas-table', null, columns, tableOptions);
    }

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
