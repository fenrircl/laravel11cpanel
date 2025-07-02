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
                // Usar configuración específica para facturas con botón de descarga
                return generateActionButtons(row.id, 'facturas', {
                    include: ['view', 'edit', 'download', 'delete'],
                    custom: {
                        duplicate: {
                            class: 'btn btn-sm btn-action btn-duplicate',
                            icon: 'fas fa-copy',
                            title: 'Duplicar Factura',
                            onclick: `duplicarFactura(${row.id})`
                        }
                    }
                });
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
            // Crear modal con los datos almacenados
            const modalContent = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> ${factura.id}</p>
                        <p><strong>Número:</strong> ${factura.numero_factura || 'No especificado'}</p>
                        <p><strong>Cliente:</strong> ${factura.cliente_nombre || 'No especificado'}</p>
                        <p><strong>Total:</strong> ${formatCurrency(factura.total)}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Fecha:</strong> ${formatTableDate(factura.fecha, false)}</p>
                        <p><strong>Vencimiento:</strong> ${formatTableDate(factura.fecha_vencimiento, false)}</p>
                        <p><strong>Estado:</strong> ${factura.estado || 'No especificado'}</p>
                        <p><strong>Fecha de creación:</strong> ${formatTableDate(factura.created_at, true)}</p>
                    </div>
                </div>
            `;
            
            Swal.fire({
                title: 'Detalles de la Factura',
                html: modalContent,
                width: '600px',
                showCloseButton: true,
                showConfirmButton: false
            });
        } else {
            console.error('Factura no encontrada:', id);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontraron los datos de la factura.'
            });
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
    
    // Descargar PDF de factura
    window.descargarPDF = function(id) {
        const factura = EntityHelpers.getFactura(id);
        if (factura) {
            // Mostrar loading en el botón
            const $btn = $(`.btn-download[onclick*="${id}"]`);
            $btn.addClass('loading');
            
            const url = buildApiUrl(`facturas/${id}/pdf`);
            
            // Crear enlace temporal para descarga
            const link = document.createElement('a');
            link.href = url;
            link.download = `factura_${factura.numero_factura || id}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Remover loading
            setTimeout(() => {
                $btn.removeClass('loading');
            }, 1000);
            
            Swal.fire({
                title: 'Descarga iniciada',
                text: 'El PDF de la factura se está descargando.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontraron los datos de la factura para descargar.'
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