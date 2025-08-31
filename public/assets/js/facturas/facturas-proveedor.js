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
                const options = {};
                // Excluir el botón de descarga si no hay archivo
                if (!row.has_file || !row.file_path) {
                    options.exclude = ['download'];
                }
                return generateActionButtons(row.id, 'facturas', options);
            }
        }
    ];
    
    // Configuración específica para obtener datos de la API
    const tableOptions = {
        ajax: {
            url: buildApiUrl('facturas/proveedores/data'),
            type: 'GET',
            dataSrc: function(json) {
                // Almacenar los datos en el sistema global
                EntityDataManager.setEntityData('facturas', json.data);
                return json.data;
            },
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

    // Ver detalles de la factura en un modal
    window.verFactura = function(id) {
        const factura = EntityHelpers.getFactura(id);
        if (factura) {
            console.log(factura)
            const detailsHtml = `
                <p><strong>ID:</strong> ${factura.id}</p>
                <p><strong>Número de Factura:</strong> ${factura.invoice}</p>
                <p><strong>Proveedor:</strong> ${factura.proveedor.name}</p>
                <p><strong>Fecha:</strong> ${formatTableDate(factura.date, false)}</p>
                <p><strong>Vencimiento:</strong> ${factura.expiry ? formatTableDate(factura.expiry, false) : 'N/A'}</p>
                <p><strong>Fecha de Pago:</strong> ${factura.pay_date ? formatTableDate(factura.pay_date, false) : 'N/A'}</p>
                <p><strong>Monto:</strong> ${formatCurrency(factura.amount)}</p>
                <p><strong>Método de Pago:</strong> ${factura.metodo_pago.name}</p>
                <p><strong>Estado:</strong> <span class="badge ${factura.status === 1 ? 'bg-success' : 'bg-warning'}">${factura.status === 1 ? 'Pagado' : 'Pendiente'}</span></p>
                <p><strong>Detalle:</strong> ${factura.detail || 'Sin detalles'}</p>
            `;
            $('#facturaDetailsContent').html(detailsHtml);
            new bootstrap.Modal(document.getElementById('facturaDetailsModal')).show();
        } else {
            Swal.fire('Error', 'No se pudieron encontrar los detalles de la factura.', 'error');
        }
    };
});
