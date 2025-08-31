$(document).ready(function() {
    console.log('Facturas DataTable initialized');
    
    // Configuración de columnas para la tabla de facturas
    const columns = [
        {data: 'id', name: 'id'},
        {data: 'invoice', name: 'invoice', title: 'Número Factura'},
        {
            data: 'tipo', 
            name: 'tipo',
            title: 'Tipo',
            render: function(data, type, row) {
                const badgeClass = data === 'cliente' ? 'bg-primary' : 'bg-success';
                return `<span class="badge ${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
            }
        },
        {
            data: 'entidad_nombre', 
            name: 'entidad_nombre',
            title: 'Cliente/Proveedor',
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
            url: buildApiUrl('facturas/data'),
            type: 'GET',
            dataSrc: function(json) {
                // Almacenar los datos en el sistema global
                EntityDataManager.setEntityData('facturas', json.data);
                return json.data;
            },
            error: function(xhr, error, code) {
                console.error('Error loading facturas data:', error);
                console.log('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los datos de facturas. Verifique la conexión.'
                });
            }
        },
        order: [[0, 'desc']] // Ordenar por ID descendente (más recientes primero)
    };
    
    // Inicializar DataTable usando la función reutilizable
    initDataTable('facturas-table', null, columns, tableOptions);

    // Los eventos de eliminación ahora se manejan automáticamente 
    // a través de initActionButtonEvents() en main.js
    
    console.log('Facturas module loaded with new action buttons system');
    
    // Funciones específicas de facturas que usan el sistema de almacenamiento
    
    // Ver detalles de factura
    window.verFactura = function(id) {
        const factura = EntityHelpers.getFactura(id);
        if (factura) {
            const isCliente = !!factura.client_id;
            const entidadNombre = isCliente ? factura.cliente.name : factura.proveedor.name;
            const entidadLabel = isCliente ? 'Cliente' : 'Proveedor';

            const detailsHtml = `
                <p><strong>ID:</strong> ${factura.id}</p>
                <p><strong>Número de Factura:</strong> ${factura.invoice}</p>
                <p><strong>${entidadLabel}:</strong> ${entidadNombre}</p>
                <p><strong>Fecha:</strong> ${formatTableDate(factura.date, false)}</p>
                <p><strong>Vencimiento:</strong> ${factura.expiry ? formatTableDate(factura.expiry, false) : 'N/A'}</p>
                <p><strong>Fecha de Pago:</strong> ${factura.pay_date ? formatTableDate(factura.pay_date, false) : 'N/A'}</p>
                <p><strong>Monto:</strong> ${formatCurrency(factura.amount)}</p>
                <p><strong>Método de Pago:</strong> ${factura.metodoPago.name}</p>
                <p><strong>Estado:</strong> <span class="badge ${factura.status === 1 ? 'bg-success' : 'bg-warning'}">${factura.status === 1 ? 'Pagado' : 'Pendiente'}</span></p>
                <p><strong>Detalle:</strong> ${factura.detail || 'Sin detalles'}</p>
            `;
            $('#facturaDetailsContent').html(detailsHtml);
            new bootstrap.Modal(document.getElementById('facturaDetailsModal')).show();
        } else {
            Swal.fire('Error', 'No se pudieron encontrar los detalles de la factura.', 'error');
        }
    };
    
    // Editar factura
    window.editarFactura = function(id) {
        const factura = EntityHelpers.getFactura(id);
        if (factura) {
            // Aquí podrías usar los datos almacenados para pre-llenar un formulario
            console.log('Editando factura:', factura);
            Swal.fire({
                icon: 'info',
                title: 'Función en desarrollo',
                text: `Editando factura: ${factura.numero_factura || 'ID #' + factura.id}`
            });
        }
    };
    

});

/**
 * Función específica para duplicar facturas
 * @param {number} id - ID de la factura a duplicar
 */
function duplicarFactura(id) {
    Swal.fire({
        title: '¿Duplicar Factura?',
        text: `¿Está seguro de que desea duplicar la factura #${id}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, duplicar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: buildApiUrl(`facturas/${id}/duplicate`),
                type: 'POST',
                data: {
                    "_token": $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire({
                        title: '¡Duplicada!',
                        text: 'La factura ha sido duplicada correctamente.',
                        icon: 'success'
                    });
                    $('#facturas-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo duplicar la factura.',
                        icon: 'error'
                    });
                }
            });
        }
    });
}