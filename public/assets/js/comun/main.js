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
                button.onclick = `editar${entityInfo.display}(${id})`;
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
    
    // Si se especifican botones específicos a incluir
    if (options.include && Array.isArray(options.include)) {
        options.include.forEach(buttonType => {
            const customConfig = options.config && options.config[buttonType] ? options.config[buttonType] : {};
            builder.addButton(buttonType, customConfig);
        });
    } else {
        // Usar configuración por defecto de la entidad
        builder.buildDefault();
    }
    
    // Excluir botones si se especifica
    if (options.exclude) {
        builder.exclude(options.exclude);
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
            setTimeout(() => element.style.display = 'none', 150);
        }
    }
}

/**
 * ============================================
 * FUNCIONES DE MANEJO DE EVENTOS GENÉRICAS
 * ============================================
 */

/**
 * Inicializar eventos para botones de acción
 * Debe llamarse después de inicializar DataTables
 */
function initActionButtonEvents() {
    // Inicializar tooltips de Bootstrap si está disponible
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Event delegation para botones de eliminar
    $(document).off('click', '.btn-delete').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const entity = $(this).data('entity');
        
        if (id && entity) {
            const entityDisplay = ActionButtonFactory.entityConfig[entity + 's']?.display || entity;
            handleDelete(entityDisplay.toLowerCase(), id, buildApiUrl(`${entity}s/${id}`), function() {
                // Recargar la tabla específica
                const tableId = `${entity}s-table`;
                if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
                    $(`#${tableId}`).DataTable().ajax.reload();
                } else {
                    location.reload();
                }
            });
        }
    });

    // Event delegation para botones con loading state
    $(document).off('click', '.btn-action').on('click', '.btn-action', function() {
        const $btn = $(this);
        if (!$btn.hasClass('btn-delete')) { // No aplicar loading a botones de eliminar
            $btn.addClass('loading').prop('disabled', true);
            
            // Remover loading después de 2 segundos (timeout de seguridad)
            setTimeout(() => {
                $btn.removeClass('loading').prop('disabled', false);
            }, 2000);
        }
    });
}

/**
 * Función genérica para abrir modales de vista
 * @param {string} entityName - Nombre de la entidad
 * @param {number} id - ID del registro
 * @param {string} url - URL para obtener los datos
 */
function openViewModal(entityName, id, url) {
    Swal.fire({
        title: `Ver ${entityName}`,
        text: 'Cargando datos...',
        icon: 'info',
        showConfirmButton: false,
        allowOutsideClick: false,
        onBeforeOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            const data = response.data || response;
            
            let htmlContent = '<div class="entity-details">';
            Object.entries(data).forEach(([key, value]) => {
                if (key !== 'id' && value !== null && value !== undefined) {
                    const label = formatLabel(key);
                    const formattedValue = formatValue(key, value);
                    htmlContent += `
                        <div class="detail-row">
                            <strong>${label}:</strong> 
                            <span>${formattedValue}</span>
                        </div>
                    `;
                }
            });
            htmlContent += '</div>';

            Swal.fire({
                title: `${entityName} #${id}`,
                html: htmlContent,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                width: '600px',
                customClass: {
                    container: 'entity-view-modal'
                }
            });
        },
        error: function(xhr) {
            Swal.fire({
                title: 'Error',
                text: `No se pudo cargar la información del ${entityName.toLowerCase()}`,
                icon: 'error'
            });
        }
    });
}

/**
 * Funciones auxiliares para formatear datos en vistas
 */
function formatLabel(key) {
    const labelMap = {
        'name': 'Nombre',
        'email': 'Email',
        'phone': 'Teléfono',
        'address': 'Dirección',
        'created_at': 'Fecha de Creación',
        'updated_at': 'Última Actualización',
        'status': 'Estado',
        'amount': 'Monto',
        'date': 'Fecha',
        'expiry': 'Vencimiento',
        'invoice': 'Número de Factura',
        'tipo': 'Tipo'
    };
    
    return labelMap[key] || key.charAt(0).toUpperCase() + key.slice(1);
}

function formatValue(key, value) {
    if (value === null || value === undefined) return 'N/A';
    
    if (key.includes('date') || key.includes('_at')) {
        return formatTableDate(value, true);
    }
    
    if (key === 'amount') {
        return formatCurrency(value);
    }
    
    if (key === 'status') {
        return value === 1 || value === '1' || value === true ? 'Activo' : 'Inactivo';
    }
    
    return value;
}

/**
 * Función para formatear moneda
 * @param {number} amount - Cantidad a formatear
 */
function formatCurrency(amount) {
    if (!amount) return '$0.00';
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(amount);
}

/**
 * Inicializar eventos cuando el documento esté listo
 */
$(document).ready(function() {
    // Llamar a la inicialización de eventos después de un pequeño delay
    // para asegurar que las DataTables estén inicializadas
    setTimeout(() => {
        initActionButtonEvents();
    }, 100);
});
