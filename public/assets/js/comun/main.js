/**
 * ============================================
 * SISTEMA GLOBAL DE ALMACENAMIENTO DE DATOS
 * ============================================
 * 
 * Sistema centralizado para almacenar y acceder a datos de entidades
 * cargados desde DataTables. Permite acceso rápido sin necesidad de
 * hacer peticiones AJAX adicionales.
 */

// Verificar si ya está inicializado para evitar redeclaraciones
if (typeof window.ENTITY_DATA === 'undefined') {

/**
 * Objeto global para almacenar datos de todas las entidades
 */
window.ENTITY_DATA = {
    clientes: [],
    proveedores: [],
    facturas: [],
    usuarios: [],
    productos: []
};

/**
 * Funciones de acceso rápido (aliases globales)
 */
window.CLIENTES = () => window.ENTITY_DATA.clientes;
window.PROVEEDORES = () => window.ENTITY_DATA.proveedores;
window.FACTURAS = () => window.ENTITY_DATA.facturas;
window.USUARIOS = () => window.ENTITY_DATA.usuarios;
window.PRODUCTOS = () => window.ENTITY_DATA.productos;

} // Fin de la verificación de inicialización para ENTITY_DATA

/**
 * Manager para el almacenamiento y gestión de datos de entidades
 * (Se define fuera del bloque condicional para estar siempre disponible)
 */
if (typeof window.EntityDataManager === 'undefined') {

class EntityDataManager {
    /**
     * Almacenar datos de una entidad
     * @param {string} entity - Nombre de la entidad (plural)
     * @param {Array} data - Array de datos
     */
    static setEntityData(entity, data) {
        if (window.ENTITY_DATA.hasOwnProperty(entity)) {
            window.ENTITY_DATA[entity] = Array.isArray(data) ? data : [];
            console.log(`✓ Datos cargados para ${entity}: ${window.ENTITY_DATA[entity].length} registros`);
        } else {
            console.warn(`⚠️ Entidad '${entity}' no está configurada en ENTITY_DATA`);
        }
    }

    /**
     * Obtener todos los datos de una entidad
     * @param {string} entity - Nombre de la entidad (plural)
     * @returns {Array} Array de datos
     */
    static getEntityData(entity) {
        return window.ENTITY_DATA[entity] || [];
    }

    /**
     * Buscar un registro específico por ID
     * @param {string} entity - Nombre de la entidad (plural)
     * @param {number|string} id - ID del registro
     * @returns {Object|null} Registro encontrado o null
     */
    static findById(entity, id) {
        const data = this.getEntityData(entity);
        return data.find(item => item.id == id) || null;
    }

    /**
     * Agregar un nuevo registro
     * @param {string} entity - Nombre de la entidad (plural)
     * @param {Object} item - Registro a agregar
     */
    static addItem(entity, item) {
        if (window.ENTITY_DATA.hasOwnProperty(entity)) {
            window.ENTITY_DATA[entity].push(item);
        }
    }

    /**
     * Actualizar un registro existente
     * @param {string} entity - Nombre de la entidad (plural)
     * @param {number|string} id - ID del registro
     * @param {Object} updatedData - Datos actualizados
     */
    static updateItem(entity, id, updatedData) {
        const data = this.getEntityData(entity);
        const index = data.findIndex(item => item.id == id);
        if (index !== -1) {
            window.ENTITY_DATA[entity][index] = { ...data[index], ...updatedData };
        }
    }

    /**
     * Eliminar un registro
     * @param {string} entity - Nombre de la entidad (plural)
     * @param {number|string} id - ID del registro
     */
    static removeItem(entity, id) {
        const data = this.getEntityData(entity);
        const index = data.findIndex(item => item.id == id);
        if (index !== -1) {
            window.ENTITY_DATA[entity].splice(index, 1);
        }
    }

    /**
     * Filtrar datos por criterios
     * @param {string} entity - Nombre de la entidad (plural)
     * @param {Function} filterFn - Función de filtro
     * @returns {Array} Array filtrado
     */
    static filter(entity, filterFn) {
        return this.getEntityData(entity).filter(filterFn);
    }

    /**
     * Buscar datos por texto (búsqueda simple en campos principales)
     * @param {string} entity - Nombre de la entidad (plural)
     * @param {string} searchTerm - Término de búsqueda
     * @returns {Array} Array de resultados
     */
    static search(entity, searchTerm) {
        const data = this.getEntityData(entity);
        const term = searchTerm.toLowerCase();
        
        return data.filter(item => {
            // Buscar en campos principales comunes
            const searchFields = ['name', 'email', 'phone', 'address', 'description'];
            return searchFields.some(field => {
                return item[field] && item[field].toString().toLowerCase().includes(term);
            });
        });
    }

    /**
     * Obtener estadísticas básicas de una entidad
     * @param {string} entity - Nombre de la entidad (plural)
     * @returns {Object} Objeto con estadísticas
     */
    static getStats(entity) {
        const data = this.getEntityData(entity);
        return {
            total: data.length,
            active: data.filter(item => item.status === 1 || item.status === '1' || item.status === true).length,
            inactive: data.filter(item => item.status === 0 || item.status === '0' || item.status === false).length
        };
    }

    /**
     * Limpiar datos de una entidad
     * @param {string} entity - Nombre de la entidad (plural)
     */
    static clearEntityData(entity) {
        if (window.ENTITY_DATA.hasOwnProperty(entity)) {
            window.ENTITY_DATA[entity] = [];
        }
    }

    /**
     * Limpiar todos los datos
     */
    static clearAllData() {
        Object.keys(window.ENTITY_DATA).forEach(entity => {
            window.ENTITY_DATA[entity] = [];
        });
    }
}

// Exponer EntityDataManager globalmente
window.EntityDataManager = EntityDataManager;

/**
 * Helpers para acceso rápido a funciones comunes
 */
window.EntityHelpers = {
    // Buscar cliente por ID
    getCliente: (id) => EntityDataManager.findById('clientes', id),
    
    // Buscar proveedor por ID
    getProveedor: (id) => EntityDataManager.findById('proveedores', id),
    
    // Buscar factura por ID
    getFactura: (id) => EntityDataManager.findById('facturas', id),
    
    // Buscar usuario por ID
    getUsuario: (id) => EntityDataManager.findById('usuarios', id),
    
    // Obtener clientes activos
    getClientesActivos: () => EntityDataManager.filter('clientes', c => c.status === 1 || c.status === '1'),
    
    // Obtener proveedores activos
    getProveedoresActivos: () => EntityDataManager.filter('proveedores', p => p.status === 1 || p.status === '1'),
    
    // Buscar clientes por texto
    buscarClientes: (term) => EntityDataManager.search('clientes', term),
    
    // Buscar proveedores por texto
    buscarProveedores: (term) => EntityDataManager.search('proveedores', term)
};

} // Fin de la verificación de inicialización

/**
 * Obtener la URL base de la aplicación
 * @returns {string} Base URL
 */
function getBaseUrl() {
    // Obtener la base URL desde una meta tag o construirla
    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content');
    if (baseUrl) {
        return baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;
    }
    
    // Fallback: construir desde la URL actual
    const protocol = window.location.protocol;
    const host = window.location.host;
    const pathname = window.location.pathname;
    
    // Si estamos en una subcarpeta como /test/public/, detectarla
    if (pathname.includes('/test/public/')) {
        return `${protocol}//${host}/test/public`;
    }
    
    return `${protocol}//${host}`;
}

/**
 * Construir URL completa para las APIs
 * @param {string} endpoint - El endpoint relativo (ej: 'clientes/data')
 * @returns {string} URL completa
 */
function buildApiUrl(endpoint) {
    const baseUrl = getBaseUrl();
    return `${baseUrl}/${endpoint}`;
}

/**
 * Función reutilizable para inicializar DataTables
 * @param {string} tableId - ID de la tabla
 * @param {Array} data - Datos para la tabla (null si usa AJAX)
 * @param {Array} columns - Configuración de columnas
 * @param {Object} options - Opciones adicionales
 */
function initDataTable(tableId, data, columns, options = {}) {
    const defaultOptions = {
        processing: true,
        serverSide: false,
        columns: columns,
        language: {
            url: buildApiUrl('assets/js/comun/plugins/datatable/es-ES.json')
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

    // Si se proporcionan datos, los usamos en lugar de AJAX
    if (data !== null) {
        defaultOptions.data = data;
    }

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
 * Factory para crear configuraciones de botones de acción
 * Siguiendo el principio de Responsabilidad Única (SOLID)
 */
class ActionButtonFactory {
    /**
     * Mapeo de entidades a su configuración específica
     */
    static entityConfig = {
        'proveedores': {
            singular: 'proveedor',
            display: 'Proveedor',
            actions: ['view', 'edit', 'delete']
        },
        'clientes': {
            singular: 'cliente',
            display: 'Cliente',
            actions: ['view', 'edit', 'delete']
        },
        'facturas': {
            singular: 'factura',
            display: 'Factura',
            actions: ['view', 'edit', 'delete', 'download']
        },
        'usuarios': {
            singular: 'usuario',
            display: 'Usuario',
            actions: ['view', 'edit', 'delete']
        },
        'productos': {
            singular: 'producto',
            display: 'Producto',
            actions: ['view', 'edit', 'delete']
        }
    };

    /**
     * Templates de botones base
     */
    static buttonTemplates = {
        view: {
            class: 'btn btn-sm btn-action btn-view',
            icon: 'fas fa-eye',
            variant: 'info',
            title: 'Ver detalles'
        },
        edit: {
            class: 'btn btn-sm btn-action btn-edit',
            icon: 'fas fa-edit',
            variant: 'warning',
            title: 'Editar'
        },
        delete: {
            class: 'btn btn-sm btn-action btn-delete',
            icon: 'fas fa-trash',
            variant: 'danger',
            title: 'Eliminar'
        },
        download: {
            class: 'btn btn-sm btn-action btn-download',
            icon: 'fas fa-download',
            variant: 'success',
            title: 'Descargar PDF'
        },
        duplicate: {
            class: 'btn btn-sm btn-action btn-duplicate',
            icon: 'fas fa-copy',
            variant: 'secondary',
            title: 'Duplicar'
        },
        activate: {
            class: 'btn btn-sm btn-action btn-activate',
            icon: 'fas fa-check',
            variant: 'success',
            title: 'Activar'
        },
        deactivate: {
            class: 'btn btn-sm btn-action btn-deactivate',
            icon: 'fas fa-times',
            variant: 'warning',
            title: 'Desactivar'
        }
    };

    /**
     * Crear configuración de botón según el tipo
     * @param {string} type - Tipo de botón
     * @param {number} id - ID del registro
     * @param {string} entity - Entidad
     * @param {Object} customConfig - Configuración personalizada
     */
    static createButton(type, id, entity, customConfig = {}) {
        const template = this.buttonTemplates[type];
        if (!template) {
            console.warn(`Button type '${type}' not found in templates`);
            return null;
        }

        const entityInfo = this.entityConfig[entity];
        if (!entityInfo) {
            console.warn(`Entity '${entity}' not found in configuration`);
            return null;
        }

        const button = {
            ...template,
            ...customConfig
        };

        // Agregar clases específicas de la entidad
        button.class += ` ${type}-${entityInfo.singular}`;
        
        // Configurar eventos según el tipo
        switch (type) {
            case 'view':
                button.onclick = `ver${entityInfo.display}(${id})`;
                break;
            case 'edit':
                if (entity === 'facturas') {
                    // Detectar si la tabla es de cliente o proveedor por el contexto del botón
                    button.onclick = `
                        var tipo = (document.getElementById('facturas-clientes-table')) ? 'cliente' : 'proveedor';
                        openEditFacturaModal(tipo, ${id});
                    `;
                } else {
                    button.onclick = `editar${entityInfo.display}(${id})`;
                }
                break;
            case 'delete':
                button['data-id'] = id;
                button['data-entity'] = entityInfo.singular;
                break;
            case 'download':
                button.onclick = `descargarPDF(${id})`;
                break;
            case 'duplicate':
                button.onclick = `duplicar${entityInfo.display}(${id})`;
                break;
            default:
                if (customConfig.onclick) {
                    button.onclick = customConfig.onclick;
                }
        }

        return button;
    }
}

    // Descargar PDF de factura
    window.descargarPDF = function(id) {
        const factura = EntityHelpers.getFactura(id);
        
        if (!factura) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontraron los datos de la factura para descargar.'
            });
            return;
        }

        // Priorizar archivo desde R2 si existe
        if (factura.has_file && factura.file_path) {
            const downloadUrl = buildApiUrl(`r2/download/${factura.file_path}`);
            window.open(downloadUrl, '_blank');
            
            Swal.fire({
                title: 'Abriendo archivo',
                text: 'El archivo de la factura se está abriendo en una nueva pestaña.',
                icon: 'success',
                timer: 2500,
                showConfirmButton: false
            });
        } else {
            // Fallback si no hay archivo en R2
            Swal.fire({
                icon: 'info',
                title: 'Archivo no encontrado',
                text: 'Esta factura no tiene un archivo asociado en el registro.'
            });
        }
    };
    
/**
 * Builder para construir grupos de botones de acción
 * Siguiendo el principio Abierto/Cerrado (SOLID)
 */
class ActionButtonBuilder {
    constructor(id, entity) {
        this.id = id;
        this.entity = entity;
        this.buttons = [];
        this.customButtons = {};
        this.excludedButtons = new Set();
    }

    /**
     * Agregar botón estándar
     */
    addButton(type, customConfig = {}) {
        if (!this.excludedButtons.has(type)) {
            const button = ActionButtonFactory.createButton(type, this.id, this.entity, customConfig);
            if (button) {
                this.buttons.push(button);
            }
        }
        return this;
    }

    /**
     * Agregar botón personalizado
     */
    addCustomButton(key, config) {
        this.customButtons[key] = config;
        return this;
    }

    /**
     * Excluir botones específicos
     */
    exclude(buttonTypes) {
        if (Array.isArray(buttonTypes)) {
            buttonTypes.forEach(type => this.excludedButtons.add(type));
        } else {
            this.excludedButtons.add(buttonTypes);
        }
        return this;
    }

    /**
     * Construir los botones automáticamente según la entidad
     */
    buildDefault() {
        const entityConfig = ActionButtonFactory.entityConfig[this.entity];
        if (entityConfig && entityConfig.actions) {
            entityConfig.actions.forEach(action => {
                // Llamar a addButton que ya respeta la lista de exclusión
                this.addButton(action);
            });
        }
        return this;
    }

    /**
     * Renderizar los botones como HTML
     */
    render() {
        // Agregar botones personalizados
        Object.values(this.customButtons).forEach(button => {
            this.buttons.push(button);
        });

        if (this.buttons.length === 0) {
            return '<span class="text-muted">Sin acciones</span>';
        }

        let html = '<div class="btn-group btn-group-action" role="group" aria-label="Acciones">';
        
        this.buttons.forEach(button => {
            html += this.renderButton(button);
        });
        
        html += '</div>';
        return html;
    }

    /**
     * Renderizar un botón individual
     */
    renderButton(button) {
        let html = `<button type="button" class="${button.class}"`;
        
        if (button.title) {
            html += ` title="${button.title}" data-bs-toggle="tooltip"`;
        }
        
        if (button.onclick) {
            html += ` onclick="${button.onclick}"`;
        }
        
        if (button['data-id']) {
            html += ` data-id="${button['data-id']}"`;
        }
        
        if (button['data-entity']) {
            html += ` data-entity="${button['data-entity']}"`;
        }
        
        // Agregar otros atributos data-*
        Object.keys(button).forEach(key => {
            if (key.startsWith('data-') && !['data-id', 'data-entity'].includes(key)) {
                html += ` ${key}="${button[key]}"`;
            }
        });
        
        html += `><i class="${button.icon}"></i></button>`;
        return html;
    }
}

/**
 * Función principal para generar botones de acción (API pública)
 * @param {number} id - ID del registro
 * @param {string} entity - Nombre de la entidad
 * @param {Object} options - Opciones de configuración
 * @param {Array} options.include - Botones a incluir específicamente
 * @param {Array} options.exclude - Botones a excluir
 * @param {Object} options.custom - Botones personalizados
 * @param {Object} options.config - Configuración personalizada para botones existentes
 */
function generateActionButtons(id, entity, options = {}) {
    const builder = new ActionButtonBuilder(id, entity);

    // Primero, procesar las exclusiones para que se apliquen antes de construir
    if (options.exclude) {
        builder.exclude(options.exclude);
    }
    
    // Si se especifican botones específicos a incluir
    if (options.include && Array.isArray(options.include)) {
        options.include.forEach(buttonType => {
            const customConfig = options.config && options.config[buttonType] ? options.config[buttonType] : {};
            builder.addButton(buttonType, customConfig);
        });
    } else {
        // Usar configuración por defecto de la entidad (que ahora respetará las exclusiones)
        builder.buildDefault();
    }
    
    // Agregar botones personalizados
    if (options.custom) {
        Object.entries(options.custom).forEach(([key, config]) => {
            builder.addCustomButton(key, config);
        });
    }
    
    return builder.render();
}

/**
 * Función auxiliar para configuraciones rápidas comunes
 */
const ActionButtonPresets = {
    /**
     * Solo ver y editar (sin eliminar)
     */
    readOnly: (id, entity) => generateActionButtons(id, entity, {
        exclude: ['delete']
    }),
    
    /**
     * Solo ver
     */
    viewOnly: (id, entity) => generateActionButtons(id, entity, {
        include: ['view']
    }),
    
    /**
     * Para facturas con descarga
     */
    invoice: (id, entity) => generateActionButtons(id, entity, {
        include: ['view', 'edit', 'download', 'delete']
    }),
    
    /**
     * Para usuarios con activar/desactivar
     */
    user: (id, entity) => generateActionButtons(id, entity, {
        custom: {
            toggle: {
                class: 'btn btn-sm btn-action btn-toggle',
                icon: 'fas fa-toggle-on',
                title: 'Activar/Desactivar',
                onclick: `toggleUser(${id})`
            }
        }
    })
};

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

/**
 * Función para formatear valores como moneda
 * @param {number} amount - Cantidad a formatear
 * @param {string} currency - Código de moneda (por defecto COP)
 */
function formatCurrency(amount, currency = 'COP') {
    if (!amount || amount === 0) return '$0';
    
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

/**
 * Función para formatear números con separadores de miles
 * @param {number} number - Número a formatear
 */
function formatNumber(number) {
    if (!number || number === 0) return '0';
    
    return new Intl.NumberFormat('es-CO').format(number);
}

/**
 * Función para obtener datos de un registro específico
 * @param {string} url - URL para obtener los datos
 * @param {Function} successCallback - Callback de éxito
 * @param {Function} errorCallback - Callback de error
 */
function fetchRecord(url, successCallback, errorCallback = null) {
    $.ajax({
        url: url,
        type: 'GET',
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
                    text: 'No se pudo obtener la información del registro',
                    icon: 'error'
                });
            }
        }
    });
}

/**
 * Función para validar campos requeridos de un formulario
 * @param {string} formId - ID del formulario
 * @returns {boolean} - True si todos los campos requeridos están llenos
 */
function validateRequiredFields(formId) {
    const form = document.getElementById(formId);
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * Función para mostrar/ocultar elementos con animación
 * @param {string} elementId - ID del elemento
 * @param {boolean} show - True para mostrar, false para ocultar
 */
function toggleElement(elementId, show) {
    const element = document.getElementById(elementId);
    if (element) {
        if (show) {
            element.style.display = 'block';
            setTimeout(() => element.classList.add('show'), 10);
        } else {
            element.classList.remove('show');
            setTimeout(() => element.style.display = 'none', 300);
        }
    }
}

/**
 * ============================================
 * HELPER DE FORMATO/VALIDACIÓN CHILENO (CLP/FECHA)
 * ============================================
 *\
 * Uso rápido en inputs:
 *  - data-format="clp"       => Mantiene valor numérico en el input y muestra pista formateada ($ 1.234.567)
 *  - data-format="date-cl"   => Acepta y valida DD-MM-AAAA en inputs de texto; muestra pista desde type=date
 *
 * Opcional: data-formatted-target="#selector" para indicar dónde pintar la pista.
 */
(function(){
    if (window.CLFormat) return; // evitar doble definición

    const CLFormat = {
        // Números/moneda
        formatNumberCL(n) {
            const num = Number(n || 0);
            return new Intl.NumberFormat('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
        },
        formatCurrencyCL(n, withSymbol = true) {
            const num = Number(n || 0);
            return withSymbol
                ? new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num)
                : CLFormat.formatNumberCL(num);
        },
        // Formatear solo con separadores de miles (sin símbolo $)
        formatCLPInput(n) {
            const num = Number(n || 0);
            return new Intl.NumberFormat('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
        },
        parseCLP(str) {
            if (str === null || str === undefined) return 0;
            if (typeof str === 'number') return Math.floor(str);
            
            // Convertir a string y manejar decimales correctamente
            const strValue = String(str);
            
            // Si contiene punto decimal, tomar solo la parte entera
            if (strValue.includes('.')) {
                const beforeDecimal = strValue.split('.')[0];
                const digits = beforeDecimal.replace(/[^0-9]/g, '');
                return digits ? parseInt(digits, 10) : 0;
            }
            
            // Si no tiene decimales, remover todo excepto dígitos
            const digits = strValue.replace(/[^0-9]/g, '');
            return digits ? parseInt(digits, 10) : 0;
        },
        // Fechas
        formatDateCL(value) {
            if (!value) return '';
            // Acepta Date o string ISO/aaaa-mm-dd
            let d;
            if (value instanceof Date) {
                d = value;
            } else if (/^\d{4}-\d{2}-\d{2}$/.test(String(value))) {
                const [y,m,day] = String(value).split('-').map(Number);
                d = new Date(y, m - 1, day);
            } else {
                const iso = CLFormat.parseDateCLToISO(String(value));
                if (!iso) return '';
                const [y,m,day] = iso.split('-').map(Number);
                d = new Date(y, m - 1, day);
            }
            const dd = String(d.getDate()).padStart(2,'0');
            const mm = String(d.getMonth()+1).padStart(2,'0');
            const yyyy = d.getFullYear();
            return `${dd}-${mm}-${yyyy}`;
        },
        parseDateCLToISO(str) {
            if (!str) return '';
            const s = String(str).trim();
            const m = s.match(/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/);
            if (!m) return '';
            const dd = parseInt(m[1],10), mm = parseInt(m[2],10), yyyy = parseInt(m[3],10);
            if (!CLFormat.isValidDateParts(dd, mm, yyyy)) return '';
            return `${yyyy}-${String(mm).padStart(2,'0')}-${String(dd).padStart(2,'0')}`;
        },
        isValidDateCL(str) {
            const iso = CLFormat.parseDateCLToISO(str);
            return !!iso;
        },
        isValidDateParts(dd, mm, yyyy) {
            if (yyyy < 1900 || yyyy > 2999) return false;
            if (mm < 1 || mm > 12) return false;
            const daysInMonth = new Date(yyyy, mm, 0).getDate();
            if (dd < 1 || dd > daysInMonth) return false;
            return true;
        }
    };

    const CLInputFormatter = {
        init() {
            // Vincular inputs existentes
            document.querySelectorAll('input[data-format], textarea[data-format]').forEach(el => this.bind(el));
            // Delegación para elementos que aparezcan dinámicamente (modales)
            document.addEventListener('focusin', (e) => {
                const el = e.target;
                if (el && el.matches && el.matches('input[data-format], textarea[data-format]')) {
                    this.bind(el);
                }
            });
            // Normalizar antes de enviar formularios marcados
            document.querySelectorAll('form').forEach(f => {
                if (f.__clNormalizeHooked) return;
                f.addEventListener('submit', (e) => this.normalizeOnSubmit(e));
                f.__clNormalizeHooked = true;
            });
        },
        bind(el) {
            if (el.__clBound) return;
            const fmt = (el.getAttribute('data-format') || '').toLowerCase();
            if (fmt === 'clp-inline') {
                // Formato CLP inline: valor visible con separadores/$ en blur, dígitos al editar
                el.addEventListener('focus', () => this.onCLPFocus(el));
                el.addEventListener('input', () => this.onCLPInput(el));
                el.addEventListener('blur', () => this.onCLPInlineBlur(el));
                // Solo formatear al cargar si el valor no está ya formateado
                if (el.value && !el.value.includes('$') && !el.value.includes('.')) {
                    this.onCLPInlineBlur(el);
                }
            } else if (fmt === 'clp') {
                // Formato CLP directo en el input (sin símbolo $)
                el.addEventListener('focus', () => this.onCLPFocus(el));
                el.addEventListener('input', () => this.onCLPInput(el));
                el.addEventListener('blur', () => this.onCLPInlineBlur(el));
                // Formatear al cargar si hay valor numérico
                if (el.value && !isNaN(el.value.replace(/[^\d.]/g, ''))) {
                    this.onCLPInlineBlur(el);
                }
            } else if (fmt === 'date-cl') {
                if (el.type === 'text') {
                    el.addEventListener('input', () => this.onDateInput(el));
                    el.addEventListener('blur', () => this.onDateBlur(el));
                }
                this.updateHint(el);
            }
            el.__clBound = true;
        },
        normalizeOnSubmit(e) {
            const form = e.target;
            // Antes de serializar por jQuery, normalizamos valores visibles
            form.querySelectorAll('input[data-format], textarea[data-format], select[data-format]').forEach(el => {
                const fmt = (el.getAttribute('data-format') || '').toLowerCase();
                if (fmt === 'clp' || fmt === 'clp-inline') {
                    // Forzar valor numérico entero sin separadores
                    const val = CLFormat.parseCLP(el.value);
                    el.value = String(val);
                } else if (fmt === 'date-cl') {
                    if (el.type === 'text') {
                        const iso = CLFormat.parseDateCLToISO(el.value);
                        if (iso) el.value = iso; // enviar en formato ISO que espera el backend
                    }
                }
            });
        },
        // CLP handlers
        onCLPInput(el) {
            // Formatear en tiempo real mientras se escribe
            const cursorPos = el.selectionStart;
            const value = el.value;
            
            // Limpiar el valor: solo dígitos (los puntos son separadores de miles, no decimales)
            const cleanValue = value.replace(/[^\d]/g, '');
            
            // Si no hay dígitos, limpiar el campo
            if (!cleanValue) {
                if (value !== '') {
                    el.value = '';
                }
                return;
            }
            
            // Formatear con separadores de miles
            const formattedValue = CLFormat.formatCLPInput(parseInt(cleanValue));
            
            // Calcular nueva posición del cursor
            // Contar cuántos separadores hay antes de la posición actual
            const valueBeforeCursor = value.substring(0, cursorPos);
            const digitsBeforeCursor = valueBeforeCursor.replace(/[^\d]/g, '').length;
            
            // Encontrar la nueva posición basada en los dígitos
            let newPos = 0;
            let digitCount = 0;
            for (let i = 0; i < formattedValue.length && digitCount < digitsBeforeCursor; i++) {
                if (/\d/.test(formattedValue[i])) {
                    digitCount++;
                }
                newPos = i + 1;
            }
            
            // Actualizar valor solo si cambió
            if (formattedValue !== value) {
                el.value = formattedValue;
                // Restaurar posición del cursor
                setTimeout(() => {
                    el.setSelectionRange(newPos, newPos);
                }, 0);
            }
        },
        onCLPFocus(el) {
            // Al enfocar, mantener el formato actual
            // Solo seleccionar todo el texto para fácil reemplazo
            setTimeout(() => el.select(), 10);
        },
        onCLPInlineBlur(el) {
            // En blur, asegurarse de que esté bien formateado
            const cleanValue = el.value.replace(/[^\d]/g, '');
            if (cleanValue) {
                el.value = CLFormat.formatCLPInput(parseInt(cleanValue));
            } else {
                el.value = '';
            }
            
            // Crear o actualizar símbolo $ si no existe
            // this.updateCLPSymbol(el); // Comentado: no mostrar CLP a la derecha
        },
        onCLPBlur(el) {
            // Formatear en el mismo input (como onCLPInlineBlur)
            const val = CLFormat.parseCLP(el.value);
            if (el.type !== 'number') {
                el.value = val ? CLFormat.formatCurrencyCL(val, true) : '';
            } else {
                // Para type=number no es posible mostrar separadores, mantener dígitos
                el.value = String(val);
            }
        },
        // Función para agregar símbolo $ a la derecha del input
        updateCLPSymbol(el) {
            // Solo agregar símbolo si el formato es 'clp' (no clp-inline)
            const fmt = (el.getAttribute('data-format') || '').toLowerCase();
            if (fmt !== 'clp') return;
            
            // Buscar si ya existe el símbolo
            let symbolSpan = el.nextElementSibling;
            if (symbolSpan && symbolSpan.classList && symbolSpan.classList.contains('clp-symbol')) {
                // Ya existe, no hacer nada
                return;
            }
            
            // Crear el símbolo $ a la derecha
            symbolSpan = document.createElement('span');
            symbolSpan.className = 'clp-symbol';
            symbolSpan.textContent = ' CLP';
            symbolSpan.style.cssText = 'color: #6c757d; font-size: 0.875rem; margin-left: 0.25rem; user-select: none;';
            
            // Insertar después del input
            el.parentNode.insertBefore(symbolSpan, el.nextSibling);
        },
        // Date handlers (DD-MM-AAAA en inputs de texto)
        onDateInput(el) {
            // Permitir solo dígitos y separadores, auto-insertar '-'
            let s = el.value.replace(/[^0-9-/.]/g, '').replace(/[/.]/g, '-');
            // Autoformato básico dd-mm-aaaa
            s = s.replace(/(\d{2})(\d)/, '$1-$2').replace(/(\d{2}-\d{2})(\d)/, '$1-$2');
            el.value = s.substring(0, 10);
        },
        onDateBlur(el) {
            const iso = CLFormat.parseDateCLToISO(el.value);
            if (iso) {
                // Mostrar como dd-mm-aaaa pero enviar ISO en submit (normalizeOnSubmit)
                el.value = CLFormat.formatDateCL(iso);
                el.classList.remove('is-invalid');
            } else if (el.value.trim() !== '') {
                el.classList.add('is-invalid');
            } else {
                el.classList.remove('is-invalid');
            }
            this.updateHint(el);
        },
        // Hints
        ensureHint(el) {
            const targetSel = el.getAttribute('data-formatted-target');
            if (targetSel) {
                const node = document.querySelector(targetSel);
                if (node) return node;
            }
            // Buscar siguiente elemento con clase .formatted-hint
            let next = el.nextElementSibling;
            if (next && next.classList && next.classList.contains('formatted-hint')) return next;
            // Buscar span específico conocido
            if (el.id && el.id.toLowerCase() === 'amount') {
                const fixed = document.getElementById('amountFormatted');
                if (fixed) return fixed;
            }
            // No crear hint automáticamente para CLP
            return null;
        },
        updateHint(el) {
            const fmt = (el.getAttribute('data-format') || '').toLowerCase();
            if (fmt === 'clp-inline' || fmt === 'clp') {
                // En modo inline o CLP normal no usamos hint
                return;
            }
            if (fmt === 'date-cl') {
                const hint = this.ensureHint(el);
                const value = el.type === 'date' ? el.value : CLFormat.parseDateCLToISO(el.value);
                const show = value ? CLFormat.formatDateCL(value) : '';
                if (hint) hint.textContent = show;
            }
        },
        refreshAllHints() {
            document.querySelectorAll('input[data-format], textarea[data-format]').forEach(el => this.updateHint(el));
        }
    };

    // Exponer globalmente
    window.CLFormat = CLFormat;
    window.CLInputFormatter = CLInputFormatter;
})();

/**
 * Inicializar eventos cuando el documento esté listo
 */
$(document).ready(function() {
    // Llamar a la inicialización de eventos después de un pequeño delay
    // para asegurar que las DataTables estén inicializadas
    // setTimeout(() => {
    //     //initActionButtonEvents();
    // }, 100);
    // Inicializar formateadores chilenos para inputs marcados con data-format
    if (window.CLInputFormatter) {
        window.CLInputFormatter.init();
    }
    // Refrescar pistas formateadas cuando se abren modales (por si el DOM se crea dinámicamente)
    $(document).on('shown.bs.modal', function(){
        if (window.CLInputFormatter) window.CLInputFormatter.refreshAllHints();
    });
});

/**
 * Abrir modal de edición de factura y cargar datos
 * @param {string} entity - 'cliente' o 'proveedor'
 * @param {number} id - ID de la factura
 */
function openEditFacturaModal(entity, id) {
    let factura = null;
    if (entity === 'cliente') {
        factura = EntityDataManager.findById('facturas', id);
    } else if (entity === 'proveedor') {
        factura = EntityDataManager.findById('facturas', id);
    }
    if (!factura) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró la factura.'
        });
        return;
    }
    console.log(factura)
    // Poblar el formulario del modal con los datos
    populateForm(factura, '');
    // Formatear el campo monto
    const amountEl = document.getElementById('amount');
    if (amountEl) {
        const fmt = (amountEl.getAttribute('data-format') || '').toLowerCase();
        if (factura.amount !== undefined && factura.amount !== null) {
            // Usar CLFormat.parseCLP para procesar correctamente el valor
            let valorNumerico = 0;
            if (window.CLFormat) {
                valorNumerico = window.CLFormat.parseCLP(factura.amount);
            } else {
                valorNumerico = parseInt(String(factura.amount).replace(/[^\d]/g, '')) || 0;
            }
            
            if ((fmt === 'clp-inline' || fmt === 'clp') && window.CLFormat) {
                // Para ambos modos CLP, mostrar solo el valor formateado sin símbolo $
                amountEl.value = valorNumerico > 0 ? window.CLFormat.formatCLPInput(valorNumerico) : '';
                // Agregar símbolo CLP a la derecha si es formato 'clp'
                // if (fmt === 'clp' && window.CLInputFormatter) {
                //     setTimeout(() => window.CLInputFormatter.updateCLPSymbol(amountEl), 100);
                // }
            } else {
                amountEl.value = valorNumerico > 0 ? String(valorNumerico) : '';
                if (window.CLInputFormatter) {
                    window.CLInputFormatter.updateHint(amountEl);
                } else if (document.getElementById('amountFormatted')) {
                    document.getElementById('amountFormatted').textContent = valorNumerico > 0 ? valorNumerico.toLocaleString('es-CL') : '';
                }
            }
        } else {
            amountEl.value = '';
            if (fmt !== 'clp-inline' && fmt !== 'clp') {
                if (window.CLInputFormatter) {
                    window.CLInputFormatter.updateHint(amountEl);
                } else if (document.getElementById('amountFormatted')) {
                    document.getElementById('amountFormatted').textContent = '';
                }
            }
        }
    }
    // Abrir el modal
    $('#facturaModal').modal('show');
}

/**
 * Guardar cambios de edición de factura (cliente/proveedor)
 * @param {string} entity - 'cliente' o 'proveedor'
 */
function saveFacturaEdit(entity) {
    const form = $('#facturaForm');
    if (!validateRequiredFields('facturaForm')) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos requeridos',
            text: 'Por favor completa todos los campos obligatorios.'
        });
        return;
    }
    const formData = form.serialize();
    let url = '';
    if (entity === 'cliente') {
        url = buildApiUrl('facturas/clientes/update');
    } else if (entity === 'proveedor') {
        url = buildApiUrl('facturas/proveedores/update');
    }
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        success: function(response) {
            showSuccessMessage('Factura actualizada correctamente', function() {
                $('#facturaModal').modal('hide');
                // Recargar la tabla de facturas
                if (entity === 'cliente') {
                    $('#facturas-clientes-table').DataTable().ajax.reload();
                } else {
                    $('#facturas-proveedores-table').DataTable().ajax.reload();
                }
            });
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo actualizar la factura.'
            });
        }
    });
}

/**
 * ============================================
 * HELPER DE FORMATO/VALIDACIÓN CHILENO (CLP/FECHA)
 * ============================================
 *\
 * Uso rápido en inputs:
 *  - data-format="clp"       => Mantiene valor numérico en el input y muestra pista formateada ($ 1.234.567)
 *  - data-format="date-cl"   => Acepta y valida DD-MM-AAAA en inputs de texto; muestra pista desde type=date
 *
 * Opcional: data-formatted-target="#selector" para indicar dónde pintar la pista.
 */
(function(){
    // Evitar redefinir si ya existe
    if (!window.CLFormat || !window.CLInputFormatter) return;

    // =====================
    // Extensiones a CLFormat
    // =====================
    const CLFormat = window.CLFormat;
    Object.assign(CLFormat, {
        // Email
        stripDiacritics(str) {
            return String(str || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        },
        normalizeEmail(str) {
            const s = CLFormat.stripDiacritics(String(str || '').trim().toLowerCase());
            return s.replace(/\s+/g, '');
        },
        isValidEmail(str) {
            const s = CLFormat.normalizeEmail(str);
            // Regex simple, suficiente para UI
            return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(s);
        },
        isValidEmailTLD(str, tld = 'cl') {
            const s = CLFormat.normalizeEmail(str);
            return CLFormat.isValidEmail(s) && s.endsWith(`.${tld.toLowerCase()}`);
        },
        // RUT
        cleanRut(str) {
            const s = String(str || '').replace(/[^0-9kK]/g, '').toUpperCase();
            return s;
        },
        calcRutDV(numStr) {
            let suma = 0, mul = 2;
            for (let i = numStr.length - 1; i >= 0; i--) {
                suma += parseInt(numStr.charAt(i), 10) * mul;
                mul = (mul === 7) ? 2 : mul + 1;
            }
            const res = 11 - (suma % 11);
            if (res === 11) return '0';
            if (res === 10) return 'K';
            return String(res);
        },
        formatRut(str) {
            const clean = CLFormat.cleanRut(str);
            if (clean.length < 2) return clean;
            const body = clean.slice(0, -1);
            const dv = clean.slice(-1);
            let rev = body.split('').reverse().join('');
            let chunks = rev.match(/.{1,3}/g) || [];
            let withDots = chunks.map(c => c.split('').reverse().join('')).reverse().join('.');
            return `${withDots}-${dv}`;
        },
        isValidRut(str) {
            const clean = CLFormat.cleanRut(str);
            if (clean.length < 2) return false;
            const body = clean.slice(0, -1);
            const dv = clean.slice(-1).toUpperCase();
            return CLFormat.calcRutDV(body) === dv;
        },
        // Teléfono Chile (9 dígitos móviles). Acepta 8-9 dígitos para hint.
        cleanPhoneCL(str) {
            return String(str || '').replace(/[^0-9]/g, '');
        },
        isValidPhoneCL(str) {
            const d = CLFormat.cleanPhoneCL(str);
            return d.length === 9; // validación simple
        },
        formatPhoneCL(str) {
            const d = CLFormat.cleanPhoneCL(str);
            if (d.length < 4) return `+56 ${d}`;
            if (d.length <= 5) return `+56 ${d[0]} ${d.slice(1)}`;
            if (d.length <= 9) return `+56 ${d[0]} ${d.slice(1,5)} ${d.slice(5)}`;
            return `+56 ${d}`;
        }
    });

    // =====================
    // Extensiones a CLInputFormatter
    // =====================
    const F = window.CLInputFormatter;
    const _bindOrig = F.bind.bind(F);
    F.bind = function(el){
        if (el.__clBound) return;
        const fmt = (el.getAttribute('data-format') || '').toLowerCase();
        if (fmt === 'email' || fmt === 'email-cl') {
            el.addEventListener('input', () => F.onEmailInput(el));
            el.addEventListener('blur', () => F.onEmailBlur(el));
            F.updateHint(el);
        } else if (fmt === 'rut') {
            el.addEventListener('input', () => F.onRutInput(el));
            el.addEventListener('blur', () => F.onRutBlur(el));
            F.updateHint(el);
        } else if (fmt === 'phone-cl') {
            el.addEventListener('input', () => F.onPhoneInput(el));
            el.addEventListener('blur', () => F.onPhoneBlur(el));
            F.updateHint(el);
        } else {
            _bindOrig(el);
            return; // el.__clBound lo setea _bindOrig
        }
        el.__clBound = true;
    };

    const _normalizeOrig = F.normalizeOnSubmit.bind(F);
    F.normalizeOnSubmit = function(e){
        const form = e.target;
        let blocked = false;
        form.querySelectorAll('input[data-format], textarea[data-format], select[data-format]').forEach(el => {
            const fmt = (el.getAttribute('data-format') || '').toLowerCase();
            if (fmt === 'email' || fmt === 'email-cl') {
                el.value = CLFormat.normalizeEmail(el.value);
                const tld = el.getAttribute('data-email-tld') || 'cl';
                const valid = (fmt === 'email') ? CLFormat.isValidEmail(el.value) : CLFormat.isValidEmailTLD(el.value, tld);
                if (!valid && el.required) { el.classList.add('is-invalid'); blocked = true; }
            } else if (fmt === 'rut') {
                // enviar limpio (sin puntos, con dv)
                const clean = CLFormat.cleanRut(el.value);
                el.value = clean;
                if (!CLFormat.isValidRut(clean) && el.required) { el.classList.add('is-invalid'); blocked = true; }
            } else if (fmt === 'phone-cl') {
                el.value = CLFormat.cleanPhoneCL(el.value);
                if (!CLFormat.isValidPhoneCL(el.value) && el.required) { el.classList.add('is-invalid'); blocked = true; }
            }
        });
        if (blocked) {
            e.preventDefault();
        }
        // Delegar normalizaciones existentes (clp, date-cl)
        _normalizeOrig(e);
    };

    // Handlers Email
    F.onEmailInput = function(el){
        el.value = CLFormat.normalizeEmail(el.value);
        F.updateHint(el);
    };
    F.onEmailBlur = function(el){
        const fmt = (el.getAttribute('data-format') || '').toLowerCase();
        const tld = el.getAttribute('data-email-tld') || 'cl';
        const valid = (fmt === 'email') ? CLFormat.isValidEmail(el.value) : CLFormat.isValidEmailTLD(el.value, tld);
        el.classList.toggle('is-invalid', !!el.value && !valid);
        F.updateHint(el);
    };

    // Handlers RUT
    F.onRutInput = function(el){
        const clean = CLFormat.cleanRut(el.value);
        el.value = clean;
        F.updateHint(el);
    };
    F.onRutBlur = function(el){
        const clean = CLFormat.cleanRut(el.value);
        const valid = CLFormat.isValidRut(clean);
        el.classList.toggle('is-invalid', !!el.value && !valid);
        F.updateHint(el);
    };

    // Handlers Teléfono
    F.onPhoneInput = function(el){
        const clean = CLFormat.cleanPhoneCL(el.value);
        el.value = clean;
        F.updateHint(el);
    };
    F.onPhoneBlur = function(el){
        const clean = CLFormat.cleanPhoneCL(el.value);
        const valid = CLFormat.isValidPhoneCL(clean);
        el.classList.toggle('is-invalid', !!el.value && !valid);
        F.updateHint(el);
    };

    // Extender updateHint para nuevos formatos
    const _updateHintOrig = F.updateHint.bind(F);
    F.updateHint = function(el){
        const fmt = (el.getAttribute('data-format') || '').toLowerCase();
        if (fmt === 'email' || fmt === 'email-cl') {
            const hint = F.ensureHint(el);
            const tld = el.getAttribute('data-email-tld') || 'cl';
            const valid = (fmt === 'email') ? CLFormat.isValidEmail(el.value) : CLFormat.isValidEmailTLD(el.value, tld);
            hint && (hint.textContent = (!valid && el.value) ? (fmt === 'email' ? 'Email no válido' : `Email debe terminar en .${tld}`) : '');
            return;
        }
        if (fmt === 'rut') {
            const hint = F.ensureHint(el);
            hint && (hint.textContent = el.value ? CLFormat.formatRut(el.value) : '');
            return;
        }
        if (fmt === 'phone-cl') {
            const hint = F.ensureHint(el);
            hint && (hint.textContent = el.value ? CLFormat.formatPhoneCL(el.value) : '');
            return;
        }
        _updateHintOrig(el);
    };
})();

