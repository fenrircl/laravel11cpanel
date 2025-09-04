$(function(){
    // Inicializar DataTable de facturas del cliente
    const clientId = $('meta[name="cliente-id"]').attr('content');
    if (!clientId) return;

    const columns = [
        {data: 'invoice', name: 'invoice', title: 'Número'},
        {data: 'date', name: 'date', title: 'Fecha', render: d => formatTableDate(d, false)},
        {data: 'expiry', name: 'expiry', title: 'Vence', render: d => d ? formatTableDate(d, false) : 'N/A'},
        {data: 'pay_date', name: 'pay_date', title: 'Pagado', render: d => d ? formatTableDate(d, false) : 'N/A'},
        {data: 'amount', name: 'amount', title: 'Monto', render: d => formatCurrency(d)},
        {data: 'status', name: 'status', title: 'Estado', render: (s)=> `<span class="badge ${s===1?'bg-success':'bg-warning'}">${s===1?'Pagado':'Pendiente'}</span>`},
        {data: 'action', name: 'action', orderable:false, searchable:false, title: 'Acciones', render: (data, type, row) => {
            const opts = {};
            if (!row.has_file || !row.file_path) { opts.exclude = ['download']; }
            return generateActionButtons(row.id, 'facturas', opts);
        }}
    ];

    const tableOptions = {
        ajax: {
            url: buildApiUrl(`facturas/clientes/data`),
            type: 'GET',
            dataSrc: function(json){
                const rows = (json && json.data) ? json.data.filter(f => String(f.client_id) === String(clientId)) : [];
                EntityDataManager.setEntityData('facturas', rows);
                return rows;
            }
        },
        order: [[0, 'desc']]
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
        window.open(buildApiUrl(`r2/download/${path}`), '_blank');
    });
});
