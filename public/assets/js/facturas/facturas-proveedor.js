$(document).ready(function() {
    console.log('Facturas Proveedor DataTable initialized');
    
    // Configuración de columnas para la tabla de facturas de proveedores
    const columns = [
        {data: 'id', name: 'id'},
        {data: 'invoice', name: 'invoice', title: 'Número Factura'},
        {
            data: 'proveedor.name', 
            name: 'proveedor.name',
            title: 'Proveedor',
            render: function(data, type, row) {
                return data || 'N/A';
            }
        },
        {
            data: 'date', 
            name: 'date',
            title: 'Fecha',
            render: function(data, type, row) {
                return formatTableDate(data, false);
            }
        },
        {
            data: 'expiry', 
            name: 'expiry',
            title: 'Vencimiento',
            render: function(data, type, row) {
                return data ? formatTableDate(data, false) : 'N/A';
            }
        },
        {
            data: 'pay_date', 
            name: 'pay_date',
            title: 'Fecha Pago',
            render: function(data, type, row) {
                return data ? formatTableDate(data, false) : 'N/A';
            }
        },
        {
            data: 'amount', 
            name: 'amount',
            title: 'Monto',
            render: function(data, type, row) {
                return formatCurrency(data);
            }
        },
        {
            data: 'status', 
            name: 'status',
            title: 'Estado',
            render: function(data, type, row) {
                const badgeClass = data === 1 ? 'bg-success' : 'bg-warning';
                const statusText = data === 1 ? 'Pagado' : 'Pendiente';
                return `<span class="badge ${badgeClass}">${statusText}</span>`;
            }
        },
        {
            data: 'action', 
            name: 'action', 
            orderable: false, 
            searchable: false,
            title: 'Acciones',
            render: function(data, type, row) {
                return generateActionButtons(row.id, 'facturas');
            }
        }
    ];
    
    // Configuración específica para obtener datos de la API
    const tableOptions = {
        ajax: {
            url: buildApiUrl('facturas/proveedores/data'),
            type: 'GET',
            dataSrc: 'data',
            error: function(xhr, error, code) {
                console.error('Error loading facturas proveedores data:', error);
                console.log('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los datos de facturas de proveedores. Verifique la conexión.'
                });
            }
        },
        order: [[0, 'desc']] // Ordenar por ID descendente (más recientes primero)
    };
    
    // Inicializar DataTable usando la función reutilizable
    initDataTable('facturas-proveedores-table', null, columns, tableOptions);

    // Manejo de eliminación usando la función reutilizable
    $(document).on('click', '.delete-factura', function() {
        const id = $(this).data('id');
        handleDelete('factura', id, buildApiUrl(`facturas/${id}`), function() {
            // Recargar la tabla después de eliminar
            $('#facturas-proveedores-table').DataTable().ajax.reload();
        });
    });
});
