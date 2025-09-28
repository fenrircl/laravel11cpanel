$(document).ready(function() {
    console.log('Métodos de Pago DataTable initialized');
    
    // Configuración de columnas para la tabla de métodos de pago
    const columns = [
        {data: 'id', name: 'id'},
        {data: 'name', name: 'name', title: 'Nombre'},
        {
            data: 'description', 
            name: 'description',
            title: 'Descripción',
            render: function(data, type, row) {
                return data || 'Sin descripción';
            }
        },
        {
            data: 'is_active', 
            name: 'is_active',
            title: 'Estado',
            render: function(data, type, row) {
                const badgeClass = data ? 'bg-success' : 'bg-secondary';
                const statusText = data ? 'Activo' : 'Inactivo';
                return `<span class="badge ${badgeClass}">${statusText}</span>`;
            }
        },
        {
            data: 'id', 
            name: 'id',
            title: 'ID',
            render: function(data, type, row) {
                return data;
            }
        },
        {
            data: null, 
            name: 'action', 
            orderable: false, 
            searchable: false,
            title: 'Acciones',
            render: function(data, type, row) {
                return `
                    <button class="btn btn-sm btn-action btn-view" title="Ver" onclick="verMetodoPago(${row.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-action btn-edit" title="Editar" onclick="editarMetodoPago(${row.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-action ${row.is_active ? 'btn-warning' : 'btn-success'}" title="${row.is_active ? 'Desactivar' : 'Activar'}" onclick="toggleMetodoPago(${row.id})">
                        <i class="fas ${row.is_active ? 'fa-toggle-off' : 'fa-toggle-on'}"></i>
                    </button>
                    <button class="btn btn-sm btn-action btn-delete" title="Eliminar" onclick="eliminarMetodoPago(${row.id})"><i class="fas fa-trash"></i></button>
                `;
            }
        }
    ];
    
    // Configuración específica para obtener datos de la API
    const tableOptions = {
        ajax: {
            url: buildApiUrl('metodos-pago/data'),
            type: 'GET',
            dataSrc: function(json) {
                // Almacenar los datos en el sistema global
                EntityDataManager.setEntityData('metodos-pago', json.data);
                return json.data;
            },
            error: function(xhr, error, code) {
                console.error('Error loading métodos de pago data:', error);
                console.log('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los datos de métodos de pago. Verifique la conexión.'
                });
            }
        },
        order: [[1, 'asc']] // Ordenar por nombre ascendente
    };
    
    // Inicializar DataTable usando la función reutilizable
    initDataTable('metodos-pago-table', null, columns, tableOptions);

    // Ver detalles del método de pago en un modal
    window.verMetodoPago = function(id) {
        const metodoPago = EntityHelpers.getMetodoPago(id);
        if (metodoPago) {
            const detailsHtml = `
                <p><strong>ID:</strong> ${metodoPago.id}</p>
                <p><strong>Nombre:</strong> ${metodoPago.name}</p>
                <p><strong>Descripción:</strong> ${metodoPago.description || 'Sin descripción'}</p>
                <p><strong>Estado:</strong> <span class="badge ${metodoPago.is_active ? 'bg-success' : 'bg-secondary'}">${metodoPago.is_active ? 'Activo' : 'Inactivo'}</span></p>
            `;
            $('#metodoPagoDetailsContent').html(detailsHtml);
            new bootstrap.Modal(document.getElementById('metodoPagoDetailsModal')).show();
        } else {
            Swal.fire('Error', 'No se pudieron encontrar los detalles del método de pago.', 'error');
        }
    };

    // Editar método de pago
    window.editarMetodoPago = function(id) {
        const metodoPago = EntityHelpers.getMetodoPago(id);
        if (metodoPago) {
            console.log('Editando método de pago:', metodoPago);
            
            // Poblar el formulario
            $('#metodo_pago_id').val(metodoPago.id);
            $('#name').val(metodoPago.name);
            $('#description').val(metodoPago.description || '');
            $('#is_active').prop('checked', metodoPago.is_active);
            
            // Cambiar título del modal
            $('#metodoPagoModalLabel').text('Editar Método de Pago');
            
            // Mostrar modal
            new bootstrap.Modal(document.getElementById('metodoPagoModal')).show();
        } else {
            Swal.fire('Error', 'No se encontró el método de pago.', 'error');
        }
    };

    // Toggle estado del método de pago
    window.toggleMetodoPago = function(id) {
        const metodoPago = EntityHelpers.getMetodoPago(id);
        if (metodoPago) {
            const action = metodoPago.is_active ? 'desactivar' : 'activar';
            
            Swal.fire({
                title: `¿${action.charAt(0).toUpperCase() + action.slice(1)} método de pago?`,
                text: `¿Está seguro de que desea ${action} "${metodoPago.name}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: `Sí, ${action}`,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: buildApiUrl(`metodos-pago/${id}/toggle-status`),
                        type: 'POST',
                        data: {
                            "_token": $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: '¡Actualizado!',
                                    text: response.message,
                                    icon: 'success'
                                });
                                $('#metodos-pago-table').DataTable().ajax.reload();
                            } else {
                                Swal.fire('Error', response.message || 'Error al actualizar el estado.', 'error');
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = 'Error al actualizar el estado.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire('Error', errorMessage, 'error');
                        }
                    });
                }
            });
        } else {
            Swal.fire('Error', 'No se encontró el método de pago.', 'error');
        }
    };

    // Eliminar método de pago
    window.eliminarMetodoPago = function(id) {
        const metodoPago = EntityHelpers.getMetodoPago(id);
        if (metodoPago) {
            Swal.fire({
                title: '¿Eliminar método de pago?',
                text: `¿Está seguro de que desea eliminar "${metodoPago.name}"? Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: buildApiUrl(`metodos-pago/${id}`),
                        type: 'DELETE',
                        data: {
                            "_token": $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: '¡Eliminado!',
                                    text: response.message,
                                    icon: 'success'
                                });
                                $('#metodos-pago-table').DataTable().ajax.reload();
                            } else {
                                Swal.fire('Error', response.message || 'Error al eliminar el método de pago.', 'error');
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = 'Error al eliminar el método de pago.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire('Error', errorMessage, 'error');
                        }
                    });
                }
            });
        } else {
            Swal.fire('Error', 'No se encontró el método de pago.', 'error');
        }
    };
});

// Variables globales
var isEdit = false;
var currentMetodoPago = null;

// Función para abrir modal de creación
function openCreateModal() {
    isEdit = false;
    document.getElementById('metodoPagoModalLabel').textContent = 'Nuevo Método de Pago';
    document.getElementById('metodoPagoForm').reset();
    document.getElementById('metodo_pago_id').value = '';
    document.getElementById('is_active').checked = true; // Por defecto activo
}

// Función para guardar método de pago (crear o editar)
function saveMetodoPago() {
    const form = $('#metodoPagoForm');
    const metodoPagoId = $('#metodo_pago_id').val();
    const isEdit = metodoPagoId && metodoPagoId !== '';
    
    // Validar campos requeridos
    if (!validateRequiredFields('metodoPagoForm')) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos requeridos',
            text: 'Por favor completa todos los campos obligatorios.'
        });
        return;
    }
    
    // Preparar datos del formulario
    const formData = new FormData(form[0]);
    
    // Asegurar que el checkbox is_active se envíe correctamente
    if (!document.getElementById('is_active').checked) {
        formData.set('is_active', '0');
    }
    
    // Determinar URL y método según si es edición o creación
    let url = '';
    let method = 'POST';
    
    if (isEdit) {
        url = buildApiUrl(`metodos-pago/${metodoPagoId}`);
        formData.append('_method', 'PUT');
    } else {
        url = buildApiUrl('metodos-pago');
    }
    
    // Enviar datos
    $.ajax({
        url: url,
        type: method,
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: isEdit ? 'Método de pago actualizado correctamente' : 'Método de pago creado correctamente'
                }).then(() => {
                    $('#metodoPagoModal').modal('hide');
                    $('#metodos-pago-table').DataTable().ajax.reload();
                    
                    // Limpiar formulario
                    form[0].reset();
                    $('#metodo_pago_id').val('');
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al procesar la solicitud'
                });
            }
        },
        error: function(xhr) {
            let errorMessage = 'Error al procesar la solicitud';
            
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('\n');
                }
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage
            });
        }
    });
}
