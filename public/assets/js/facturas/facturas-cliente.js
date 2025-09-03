$(document).ready(function() {
    console.log('Facturas Cliente DataTable initialized');

    // Precargar datos necesarios para esta vista
    if (window.ReferenceDataManager) {
        ReferenceDataManager.ensureLoaded(['clientes', 'metodosPago']);
    }

    // Refrescar datasets si hay cambios en clientes o métodos de pago
    document.addEventListener('clientes:updated', () => ReferenceDataManager.refresh('clientes'));
    document.addEventListener('metodosPago:updated', () => ReferenceDataManager.refresh('metodosPago'));
    
    // Configuración de columnas para la tabla de facturas de clientes
    const columns = [
        // {data: 'id', name: 'id'},
        {data: 'invoice', name: 'invoice', title: 'Número Factura'},
        {
            data: 'cliente.name', 
            name: 'cliente.name',
            title: 'Cliente',
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
            url: buildApiUrl('facturas/clientes/data'),
            type: 'GET',
            dataSrc: function(json) {
                // Almacenar los datos en el sistema global
                EntityDataManager.setEntityData('facturas', json.data);
                return json.data;
            },
            error: function(xhr, error, code) {
                console.error('Error loading facturas clientes data:', error);
                console.log('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los datos de facturas de clientes. Verifique la conexión.'
                });
            }
        },
        order: [[0, 'desc']] // Ordenar por ID descendente (más recientes primero)
    };
    
    // Inicializar DataTable usando la función reutilizable
    initDataTable('facturas-clientes-table', null, columns, tableOptions);

    // Manejo de eliminación usando la función reutilizable
    $(document).on('click', '.delete-factura', function() {
        const id = $(this).data('id');
        handleDelete('factura', id, buildApiUrl(`facturas/${id}`), function() {
            // Recargar la tabla después de eliminar
            $('#facturas-clientes-table').DataTable().ajax.reload();
        });
    });

    // Ver detalles de la factura en un modal
    window.verFactura = function(id) {
        const factura = EntityHelpers.getFactura(id);
        if (factura) {
            // Generar enlace de archivo si existe
            const archivoSection = factura.has_file && factura.file_path ? `
                <hr>
                <div class="archivo-asociado mt-3">
                    <p><strong><i class="fas fa-file-download me-2"></i>Archivo Asociado:</strong></p>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="descargarPDF(${factura.id})">
                            <i class="fas fa-download me-1"></i> Descargar Archivo
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarArchivoFactura(${factura.id})">
                            <i class="fas fa-trash me-1"></i> Eliminar Archivo
                        </button>
                        <small class="text-muted">Archivo disponible para descarga y eliminación</small>
                    </div>
                </div>
            ` : '';

            const detailsHtml = `
                <p><strong>ID:</strong> ${factura.id}</p>
                <p><strong>Número de Factura:</strong> ${factura.invoice}</p>
                <p><strong>Cliente:</strong> ${factura.cliente.name}</p>
                <p><strong>Fecha:</strong> ${formatTableDate(factura.date, false)}</p>
                <p><strong>Vencimiento:</strong> ${factura.expiry ? formatTableDate(factura.expiry, false) : 'N/A'}</p>
                <p><strong>Fecha de Pago:</strong> ${factura.pay_date ? formatTableDate(factura.pay_date, false) : 'N/A'}</p>
                <p><strong>Monto:</strong> ${formatCurrency(factura.amount)}</p>
                <p><strong>Método de Pago:</strong> ${factura.metodo_pago.name}</p>
                <p><strong>Estado:</strong> <span class="badge ${factura.status === 1 ? 'bg-success' : 'bg-warning'}">${factura.status === 1 ? 'Pagado' : 'Pendiente'}</span></p>
                <p><strong>Detalle:</strong> ${factura.detail || 'Sin detalles'}</p>
                ${archivoSection}
            `;
            $('#facturaDetailsContent').html(detailsHtml);
            new bootstrap.Modal(document.getElementById('facturaDetailsModal')).show();
        } else {
            Swal.fire('Error', 'No se pudieron encontrar los detalles de la factura.', 'error');
        }
    };
});
