$(function(){
    // Inicializar DataTable de facturas del cliente
    const clientId = $('meta[name="cliente-id"]').attr('content');
    if (!clientId) return;

    const columns = [
        {data: 'invoice', name: 'invoice', title: 'Número'},
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
        {data: 'status', name: 'status', title: 'Estado', render: (s)=> `<span class="badge ${s===1?'bg-success':'bg-warning'}">${s===1?'Pagado':'Pendiente'}</span>`},
        {data: 'extra', name: 'extra', title: 'Datos Extra', render: d => d ? `<small class="text-muted" title="${d}">${d.substring(0, 30)}${d.length > 30 ? '...' : ''}</small>` : '<span class="text-muted">—</span>'},
        {data: null, name: 'action', orderable:false, searchable:false, title: 'Acciones', render: (data, type, row) => {
            const opts = { exclude: ['view'] };
            if (!row.has_file || !row.file_path) { (opts.exclude || (opts.exclude=[])).push('download'); }
            return generateActionButtons(row.id, 'facturas', opts);
        }}
    ];

    const tableOptions = {
        ajax: {
            url: buildApiUrl(`facturas/clientes/data`),
            type: 'GET',
            cache: false, // evitar respuestas cacheadas
            data: function(d){ d._ = Date.now(); }, // cache-busting
            dataSrc: function(json){
                const list = (json && json.data) ? json.data : [];
                const filtered = list.filter(f => String(f.client_id) === String(clientId));
                const seen = new Set();
                const unique = [];
                for (const r of filtered) {
                    if (r && r.id != null && !seen.has(r.id)) { seen.add(r.id); unique.push(r); }
                }
                EntityDataManager.setEntityData('facturas', unique);
                return unique;
            }
        },
        order: [[1, 'desc']]  // Ordenar por columna de fecha (índice 1) descendente (más reciente primero)
    };

    initDataTable('cliente-facturas-table', null, columns, tableOptions);

    // Toggle del accordion de archivos
    $('#filesToggle').on('click', function(){
        $('#filesCollapse').collapse('toggle');
    });

    // Delegar descargas por data-attrs
    $(document).on('click', '[data-download-path]', function(){
        const path = $(this).data('download-path');
        if (!path) return;
        // Codificar correctamente la ruta para URL
        const encodedPath = encodeURIComponent(path).replace(/%2F/g, '/');
        window.open(buildApiUrl(`r2/download/${encodedPath}`), '_blank');
    });
});
