/**
 * Función reutilizable para inicializar DataTables
 * @param {string} tableId - ID de la tabla
 * @param {Array} data - Datos para la tabla
 * @param {Array} columns - Configuración de columnas
 * @param {Object} options - Opciones adicionales
 */
function initDataTable(tableId, data, columns, options = {}) {
    const defaultOptions = {
        data: data,
        processing: true,
        serverSide: false,
        columns: columns,
        language: {
            url: 'assets/js/plugins/datatable/es-ES.json'
        },
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                text: 'Copiar',
                className: 'btn btn-secondary btn-sm'
            },
            {
                extend: 'excel',
                text: 'Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'pdf',
                text: 'PDF',
                className: 'btn btn-danger btn-sm'
            },
            {
                extend: 'print',
                text: 'Imprimir',
                className: 'btn btn-info btn-sm'
            }
        ],
        autoWidth: false,
        scrollX: false,
        columnDefs: [
            {
                targets: '_all',
                className: 'text-center'
            }
        ]
    };

    // Combinar opciones por defecto con opciones personalizadas
    const finalOptions = { ...defaultOptions, ...options };
    
    const table = $(`#${tableId}`).DataTable(finalOptions);
    
    // Forzar redimensionamiento después de la inicialización con verificación de responsive
    setTimeout(function() {
        table.columns.adjust();
        // Verificar si responsive está disponible antes de llamar a recalc()
        if (table.responsive && typeof table.responsive.recalc === 'function') {
            table.responsive.recalc();
        }
    }, 100);
    
    return table;
}

/**
 * Función para generar botones de acción estándar
 * @param {number} id - ID del registro
 * @param {string} entity - Nombre de la entidad (ej: 'clientes', 'proveedores')
 * @param {Object} customButtons - Botones personalizados adicionales
 */
function generateActionButtons(id, entity, customButtons = {}) {
    // Mapeo específico para entidades en español
    const entityMapping = {
        'proveedores': 'Proveedor',
        'clientes': 'Cliente',
        'usuarios': 'Usuario',
        'productos': 'Producto',
        'facturas': 'Factura'
    };
    
    // Obtener el nombre singular capitalizado correctamente
    const entityCapitalized = entityMapping[entity] || entity.charAt(0).toUpperCase() + entity.slice(1, -1);
    const entitySingular = entity.slice(0, -1); // Para clases CSS
    
    const defaultButtons = {
        view: {
            class: 'btn btn-sm btn-info',
            icon: 'fas fa-eye',
            title: 'Ver detalles',
            onclick: `view${entityCapitalized}(${id})`
        },
        edit: {
            class: 'btn btn-sm btn-warning',
            icon: 'fas fa-edit',
            title: 'Editar',
            onclick: `openEditModal(${id})`
        },
        delete: {
            class: `btn btn-sm btn-danger delete-${entitySingular}`,
            icon: 'fas fa-trash',
            title: 'Eliminar',
            'data-id': id
        }
    };

    const buttons = { ...defaultButtons, ...customButtons };
    let buttonsHtml = '<div class="btn-group" role="group">';
    
    Object.values(buttons).forEach(button => {
        buttonsHtml += `<button type="button" class="${button.class}" `;
        if (button.onclick) buttonsHtml += `onclick="${button.onclick}" `;
        if (button['data-id']) buttonsHtml += `data-id="${button['data-id']}" `;
        if (button.title) buttonsHtml += `title="${button.title}" `;
        buttonsHtml += `><i class="${button.icon}"></i></button>`;
    });
    
    buttonsHtml += '</div>';
    return buttonsHtml;
}

/**
 * Función para redimensionar todas las DataTables
 */
function resizeAllDataTables() {
    if ($.fn.DataTable) {
        const tables = $.fn.dataTable.tables({ visible: true, api: true });
        tables.columns.adjust();
        // Verificar si responsive está disponible antes de llamar a recalc()
        tables.iterator('table', function(context) {
            const api = $(context.nTable).DataTable();
            if (api.responsive && typeof api.responsive.recalc === 'function') {
                api.responsive.recalc();
            }
        });
    }
}

/**
 * Función para formatear fechas en las tablas
 * @param {string} dateString - Fecha en formato string
 * @param {boolean} includeTime - Si incluir la hora
 */
function formatTableDate(dateString, includeTime = true) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    const dateFormatted = date.toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit' 
    });
    
    if (includeTime) {
        const timeFormatted = date.toLocaleTimeString('es-ES');
        return `${dateFormatted} ${timeFormatted}`;
    }
    
    return dateFormatted;
}

/**
 * Función genérica para manejar eliminación con SweetAlert2
 * @param {string} entity - Nombre de la entidad
 * @param {number} id - ID del registro
 * @param {string} url - URL para la petición DELETE
 * @param {Function} successCallback - Callback de éxito
 */
function handleDelete(entity, id, url, successCallback = null) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esta acción!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'DELETE',
                data: {
                    "_token": $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire(
                        '¡Eliminado!',
                        `El ${entity} ha sido eliminado correctamente.`,
                        'success'
                    );
                    
                    if (successCallback) {
                        successCallback(response);
                    } else {
                        location.reload();
                    }
                },
                error: function(xhr) {
                    Swal.fire(
                        'Error',
                        `Ocurrió un error al eliminar el ${entity}.`,
                        'error'
                    );
                }
            });
        }
    });
}

/**
 * Función para manejar envío de formularios AJAX
 * @param {string} formId - ID del formulario
 * @param {string} url - URL para enviar el formulario
 * @param {string} method - Método HTTP
 * @param {Function} successCallback - Callback de éxito
 * @param {Function} errorCallback - Callback de error
 */
function handleFormSubmit(formId, url, method, successCallback, errorCallback = null) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    
    // Agregar método para PUT/PATCH
    if (method !== 'POST') {
        formData.append('_method', method);
    }
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (successCallback) {
                successCallback(response);
            }
        },
        error: function(xhr) {
            if (errorCallback) {
                errorCallback(xhr);
            } else {
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la solicitud',
                    icon: 'error'
                });
            }
        }
    });
}

/**
 * Función para mostrar notificaciones de éxito
 * @param {string} message - Mensaje a mostrar
 * @param {Function} callback - Callback después de cerrar
 */
function showSuccessMessage(message, callback = null) {
    Swal.fire({
        title: 'Éxito',
        text: message,
        icon: 'success'
    }).then(() => {
        if (callback) {
            callback();
        }
    });
}

/**
 * Función para limpiar formularios
 * @param {string} formId - ID del formulario
 */
function clearForm(formId) {
    document.getElementById(formId).reset();
}

/**
 * Función para poblar formularios con datos
 * @param {Object} data - Datos para poblar
 * @param {string} prefix - Prefijo para los IDs de los campos
 */
function populateForm(data, prefix = '') {
    Object.keys(data).forEach(key => {
        const element = document.getElementById(prefix + key);
        if (element) {
            element.value = data[key] || '';
        }
    });
}
