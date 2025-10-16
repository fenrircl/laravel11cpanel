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
    productos: [],
    cotizaciones: [],
    'metodos-pago': []
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
        if (!window.ENTITY_DATA.hasOwnProperty(entity)) {
            // Crear la entidad dinámicamente si no existe
            window.ENTITY_DATA[entity] = [];
        }
        window.ENTITY_DATA[entity] = Array.isArray(data) ? data : [];
        console.log(`✓ Datos cargados para ${entity}: ${window.ENTITY_DATA[entity].length} registros`);
    }

    /**
     * Obtener todos los datos de una entidad
     * @param {string} entity - Nombre de la entidad (plural)
     * @returns {Array} Array de datos
     */
    static getEntityData(entity) {
        if (!window.ENTITY_DATA.hasOwnProperty(entity)) {
            window.ENTITY_DATA[entity] = [];
        }
        return window.ENTITY_DATA[entity];
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
    
    // Buscar método de pago por ID
    getMetodoPago: (id) => EntityDataManager.findById('metodos-pago', id),
    
    // Buscar usuario por ID
    getUsuario: (id) => EntityDataManager.findById('usuarios', id),
    
    // Obtener clientes activos
    getClientesActivos: () => EntityDataManager.filter('clientes', c => c.status === 1 || c.status === '1'),
    
    // Obtener proveedores activos
    getProveedoresActivos: () => EntityDataManager.filter('proveedores', p => p.status === 1 || p.status === '1'),
    
    // Obtener métodos de pago activos
    getMetodosPagoActivos: () => EntityDataManager.filter('metodos-pago', m => m.is_active === 1 || m.is_active === true),
    
    // Buscar clientes por texto
    buscarClientes: (term) => EntityDataManager.search('clientes', term),
    
    // Buscar proveedores por texto
    buscarProveedores: (term) => EntityDataManager.search('proveedores', term),
    
    // Buscar métodos de pago por texto
    buscarMetodosPago: (term) => EntityDataManager.search('metodos-pago', term),

    // Buscar cotización por ID
    getCotizacion: (id) => EntityDataManager.findById('cotizaciones', id)
};

} // Fin de la verificación de inicialización

/**
 * Polyfill CLFormat (CLP y fechas) si no existe
 */
(function(){
    try {
        // Crear CLFormat si no existe
        if (!window.CLFormat) window.CLFormat = {};
        const CF = window.CLFormat;
        
        // Crear CLInputFormatter si no existe
        if (!window.CLInputFormatter) {
            window.CLInputFormatter = {
                bind: function(el) {
                    if (el.__clBound) return;
                    const fmt = (el.getAttribute('data-format') || '').toLowerCase();
                    if (fmt === 'clp') {
                        el.addEventListener('input', () => this.onClpInput(el));
                        el.addEventListener('blur', () => this.onClpBlur(el));
                        //this.updateHint(el);
                    }
                    el.__clBound = true;
                },
                onClpInput: function(el) {
                    // Solo números durante input
                    //const cleaned = String(el.value || '').replace(/[^\d]/g, '');
                    //el.value = cleaned;
                    //this.updateHint(el);
                },
                onClpBlur: function(el) {
                    // Quitar puntos antes de parsear
                    const cleaned = String(el.value || '').replace(/\./g, '');
                    const num = parseInt(cleaned || '0', 10);
                    if (num > 0) {
                        // Mostrar formato visual con puntos y peso
                        el.value = num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    } else {
                        el.value = '';
                    }
                    //this.updateHint(el);
                },
                updateHint: function(el) {
                    return
                    let hint = el.nextElementSibling;
                    if (!hint || !hint.classList.contains('format-hint')) {
                        hint = document.createElement('small');
                        hint.className = 'format-hint text-muted d-block';
                        el.parentNode.insertBefore(hint, el.nextSibling);
                    }
                    
                    const fmt = (el.getAttribute('data-format') || '').toLowerCase();
                    if (fmt === 'clp') {
                        const num = parseInt(el.value.replace(/[^\d]/g, '') || '0', 10);
                        if (num > 0) {
                            hint.textContent = 'Formato: $ ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        } else {
                            hint.textContent = 'Ingrese solo números';
                        }
                    }
                },
                ensureHint: function(el) {
                    return
                    let hint = el.nextElementSibling;
                    if (!hint || !hint.classList.contains('format-hint')) {
                        hint = document.createElement('small');
                        hint.className = 'format-hint text-muted d-block';
                        el.parentNode.insertBefore(hint, el.nextSibling);
                    }
                    return hint;
                },
                normalizeOnSubmit: function(e) {
                    const form = e.target;
                    form.querySelectorAll('input[data-format="clp"]').forEach(el => {
                        // Limpiar formato para envío - extraer solo números
                        const num = parseInt((el.value || '').replace(/[^\d]/g, ''), 10);
                        el.value = num > 0 ? String(num) : '';
                    });
                }
            };
        }
        
        // Definir funciones básicas si no existen
        if (typeof CF.formatCLPInput !== 'function') {
            CF.formatCLPInput = function(value){
                if (value === null || value === undefined || value === '') return '';
                const num = parseInt(String(value).replace(/[^\d]/g, ''), 10);
                if (!isFinite(num) || num <= 0) return '';
                return '$ ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };
        }
        if (typeof CF.parseCLP !== 'function') {
            CF.parseCLP = function(value){
                const n = parseInt(String(value || '').replace(/[^\d]/g, ''), 10);
                return isNaN(n) ? 0 : n;
            };
        }
        if (typeof CF.formatDateCL !== 'function') {
            CF.formatDateCL = function(input){
                try {
                    if (!input) return '';
                    if (input instanceof Date) {
                        const y = input.getFullYear();
                        const m = String(input.getMonth() + 1).padStart(2, '0');
                        const d = String(input.getDate()).padStart(2, '0');
                        return `${d}-${m}-${y}`;
                    }
                    const s = String(input);
                    const base = s.includes('T') ? s.split('T')[0] : s.split(' ')[0];
                    if (/^\d{4}-\d{2}-\d{2}$/.test(base)) {
                        const [y, m, d] = base.split('-');
                        return `${d}-${m}-${y}`;
                    }
                    if (/^\d{2}-\d{2}-\d{4}$/.test(s)) return s;
                    const d2 = new Date(s);
                    if (!isNaN(d2)) {
                        const y = d2.getFullYear();
                        const m = String(d2.getMonth() + 1).padStart(2, '0');
                        const dd = String(d2.getDate()).padStart(2, '0');
                        return `${dd}-${m}-${y}`;
                    }
                    return s;
                } catch(e){ return String(input || ''); }
            };
        }
    } catch(e) { /* noop */ }
})();

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
        },
        // CLP helpers
        formatCLPInput(value){
            if (value === null || value === undefined || value === '') return '';
            const num = parseInt(String(value).replace(/[^\d]/g, ''), 10);
            if (!isFinite(num) || num <= 0) return '';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },
        parseCLP(value){
            const n = parseInt(String(value || '').replace(/[^\d]/g, ''), 10);
            return isNaN(n) ? 0 : n;
        },
        formatDateCL(input){
            try {
            if (!input) return '';
            if (input instanceof Date) {
                const y = input.getFullYear();
                const m = String(input.getMonth() + 1).padStart(2, '0');
                const d = String(input.getDate()).padStart(2, '0');
                return `${d}-${m}-${y}`;
            }
            const s = String(input);
            const base = s.includes('T') ? s.split('T')[0] : s.split(' ')[0];
            if (/^\d{4}-\d{2}-\d{2}$/.test(base)) {
                const [y, m, d] = base.split('-');
                return `${d}-${m}-${y}`;
            }
            if (/^\d{2}-\d{2}-\d{4}$/.test(s)) return s;
            const d2 = new Date(s);
            if (!isNaN(d2)) {
                const y = d2.getFullYear();
                const m = String(d2.getMonth() + 1).padStart(2, '0');
                const dd = String(d2.getDate()).padStart(2, '0');
                return `${dd}-${m}-${y}`;
            }
            return s;
            } catch(e){ return String(input || ''); }
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
        if (fmt === 'clp') {
            el.addEventListener('input', () => F.onClpInput(el));
            el.addEventListener('blur', () => F.onClpBlur(el));
            F.updateHint(el);
        } else if (fmt === 'email' || fmt === 'email-cl') {
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
            // Para otros formatos, llamar al bind original
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
        // Formatear el valor visible al estilo chileno (12.345.678-9)
        el.value = CLFormat.formatRut(clean);
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
    // Detectar si estamos en local (localhost o 127.0.0.1)
    const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const defaultOptions = {
        processing: true,
        serverSide: false,
        columns: columns,
        language: getDTLanguage(isLocal),
        responsive: { 
            details: {
                display: $.fn.dataTable.Responsive.display.childRow
            },
            breakpoints: [
                { name: 'desktop',     width: Infinity },
                { name: 'tablet-l',    width: 1188 },
                { name: 'tablet',      width: 1024 },
                { name: 'fablet',      width: 768 },
                { name: 'phone',       width: 480 }
            ]
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copy',  text: 'Copiar',  className: 'btn btn-secondary btn-sm' },
            { extend: 'excel', text: 'Excel',   className: 'btn btn-success btn-sm' },
            { extend: 'pdf',   text: 'PDF',     className: 'btn btn-danger btn-sm' },
            { extend: 'print', text: 'Imprimir',className: 'btn btn-info btn-sm' }
        ],
        autoWidth: false,
        scrollX: true,
        columnDefs: [ { targets: '_all', className: 'text-center' } ]
    };

    if (data !== null) defaultOptions.data = data;
    const finalOptions = { ...defaultOptions, ...options };

    const $table = $(`#${tableId}`);
    // Asegurar contenedor con overflow para scrollX
    const $wrapper = $table.closest('.table-responsive');
    if ($wrapper.length) {
        $wrapper.css({ overflowX: 'auto' });
    }

    const table = $table.DataTable(finalOptions);

    // Ajuste inicial diferido
    setTimeout(function() {
        try {
            table.columns.adjust();
            if (table.responsive && typeof table.responsive.recalc === 'function') table.responsive.recalc();
        } catch(e) {}
    }, 100);

    // Reajustar al mostrar pestañas/bootstrap o modales que contengan la tabla
    $(document).on('shown.bs.tab shown.bs.modal', function(e){
        if ($(e.target).find(`#${tableId}`).length || $(e.relatedTarget).find(`#${tableId}`).length) {
            setTimeout(function(){
                try { table.columns.adjust(); if (table.responsive?.recalc) table.responsive.recalc(); } catch(e) {}
            }, 100);
        }
    });

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
                    // Detectar correctamente el contexto (cliente o proveedor)
                    button.onclick = `
                        (function(){
                            var tipo = document.getElementById('cliente-facturas-table') ? 'cliente'
                                : (document.getElementById('proveedor-facturas-table') ? 'proveedor' : 'cliente');
                            openEditFacturaModal(tipo, ${id});
                        })();
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
                button.onclick = `descargarPDF(${id}, event)`;
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
    window.descargarPDF = function(id, event) {
        // Prevenir ejecución múltiple
        if (window.descargarPDF.isExecuting) {
            console.warn('descargarPDF ya está ejecutándose, ignorando llamada duplicada');
            return;
        }
        
        // Prevenir propagación de eventos si viene de un event
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Marcar como ejecutándose
        window.descargarPDF.isExecuting = true;
        
        // Limpiar el flag después de un tiempo
        setTimeout(() => {
            window.descargarPDF.isExecuting = false;
        }, 1000);
        
        const factura = EntityHelpers.getFactura(id);
        
        if (!factura) {
            window.descargarPDF.isExecuting = false;
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontraron los datos de la factura para descargar.'
            });
            return;
        }

        // Priorizar archivo desde R2 si existe
        if (factura.has_file && factura.file_path) {
            // Limpiar y normalizar la ruta del archivo con función mejorada
            let filePath = cleanFilePathForDownload(factura.file_path);
            
            // Codificar correctamente la ruta para URL
            const encodedPath = encodeURIComponent(filePath).replace(/%2F/g, '/');
            const downloadUrl = buildApiUrl(`r2/download/${encodedPath}`);
            
            console.log('Descargando archivo:', {
                original: factura.file_path,
                cleaned: filePath,
                encoded: encodedPath,
                url: downloadUrl
            });
            
            // Crear enlace temporal y hacer clic automáticamente (evita bloqueadores de popup)
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.style.display = 'none';
            
            // Agregar al DOM temporalmente
            document.body.appendChild(link);
            
            // Hacer clic automáticamente
            link.click();
            
            // Remover del DOM
            setTimeout(() => {
                document.body.removeChild(link);
            }, 100);
            
            // Mostrar mensaje de confirmación
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

    // Eliminar archivo de factura
    window.eliminarArchivoFactura = function(id) {
        const factura = EntityHelpers.getFactura(id);
        
        if (!factura) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontraron los datos de la factura.'
            });
            return;
        }

        if (!factura.has_file || !factura.file_path) {
            Swal.fire({
                icon: 'info',
                title: 'Sin archivo',
                text: 'Esta factura no tiene un archivo asociado.'
            });
            return;
        }

        // Confirmación de eliminación
        Swal.fire({
            title: '¿Eliminar archivo?',
            text: `¿Está seguro de que desea eliminar el archivo asociado a la factura #${factura.invoice}? Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar archivo',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Realizar la eliminación con limpieza y codificación correcta de la ruta
                const cleanPath = cleanFilePathForDownload(factura.file_path);
                const encodedPath = encodeURIComponent(cleanPath).replace(/%2F/g, '/');
                
                $.ajax({
                    url: buildApiUrl(`r2/delete/${encodedPath}`),
                    type: 'DELETE',
                    data: {
                        "_token": $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: 'El archivo ha sido eliminado correctamente.',
                                icon: 'success',
                                timer: 2500,
                                showConfirmButton: false
                            }).then(() => {
                                reloadInvoiceTables();
                                // Actualizar modal de edición si está abierto
                                if ($('#facturaModal').hasClass('show')) {
                                    const facturaId = document.getElementById('factura_id')?.value;
                                    if (facturaId) {
                                        // Mostrar mensaje de "sin archivos" usando las nuevas funciones
                                        const noFileHtml = `
                                            <div class="no-archivo">
                                                <i class="fas fa-file me-2"></i>
                                                No hay archivos asociados a esta factura
                                            </div>
                                        `;
                                        
                                        const archivoAsociadoDiv = document.getElementById('archivo-asociado');
                                        if (archivoAsociadoDiv) {
                                            archivoAsociadoDiv.innerHTML = noFileHtml;
                                        }
                                        
                                        const filesListContainer = document.getElementById('files-list');
                                        if (filesListContainer) {
                                            filesListContainer.innerHTML = noFileHtml;
                                        }
                                    }
                                }
                                
                                // Cerrar modal de detalles para que se actualice la información
                                $('#facturaDetailsModal').modal('hide');
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'No se pudo eliminar el archivo.'
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Error eliminando archivo:', xhr);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error al eliminar el archivo. Por favor, inténtelo de nuevo.'
                        });
                    }
                });
            }
        });
    };

/**
 * Limpiar ruta de archivo para descargas, removiendo caracteres de control problemáticos
 * @param {string} filePath - Ruta del archivo a limpiar
 * @returns {string} Ruta limpia
 */
function cleanFilePathForDownload(filePath) {
    if (!filePath || typeof filePath !== 'string') {
        return '';
    }
    
    console.log('Limpiando ruta de archivo:', {
        original: filePath,
        length: filePath.length,
        charCodes: Array.from(filePath.slice(0, 50)).map(c => c.charCodeAt(0)) // Primeros 50 caracteres para debug
    });
    
    let cleanPath = filePath;
    
    // Limpiar caracteres nulos (problema principal identificado)
    cleanPath = cleanPath.replace(/\x00/g, '');
    
    // Limpiar caracteres de control ASCII problemáticos
    // \u0001 = SOH (Start of Heading), \u0003 = ETX (End of Text)
    cleanPath = cleanPath.replace(/\u0001/g, ''); // SOH
    cleanPath = cleanPath.replace(/\u0003/g, ''); // ETX
    
    // Limpiar todos los caracteres de control ASCII (0x00-0x1F) excepto TAB, LF, CR
    cleanPath = cleanPath.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');
    
    // Limpiar caracteres de control Unicode C1 (0x80-0x9F)
    cleanPath = cleanPath.replace(/[\u0080-\u009F]/g, '');
    
    // Asegurar codificación correcta
    try {
        // Si la ruta ya está codificada, decodificarla primero
        if (cleanPath.includes('%')) {
            cleanPath = decodeURIComponent(cleanPath);
        }
    } catch (e) {
        console.warn('Error al decodificar ruta:', e);
    }
    
    // Normalizar espacios múltiples
    cleanPath = cleanPath.replace(/\s+/g, ' ').trim();
    
    console.log('Ruta de archivo limpia:', {
        result: cleanPath,
        removed_chars: filePath.length - cleanPath.length,
        changes_made: filePath !== cleanPath
    });
    
    return cleanPath;
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
        try { tables.columns.adjust(); } catch(e) {}
        tables.iterator('table', function(context) {
            const api = $(context.nTable).DataTable();
            try {
                if (api.responsive && typeof api.responsive.recalc === 'function') api.responsive.recalc();
                // Forzar draw cuando hay scrollX y ancho cambió mucho
                api.draw(false);
            } catch(e) {}
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
 * Renderizar badge de estado según vencimiento
 * - status = 1 => Pagado (verde)
 * - status = 0 => Pendiente: si no vencida => warning; si vencida <=7 días => warning; si >7 días => danger
 */
function renderInvoiceStatusBadge(status, expiryDate, extra) {
    try {
        let statusBadge = '';
        
        if (status === 1 || status === '1' || status === true) {
            statusBadge = '<span class="badge bg-success">Pagado</span>';
        } else {
            // Pendiente
            if (!expiryDate) {
                statusBadge = '<span class="badge bg-warning">Pendiente</span>';
            } else {
                const exp = new Date(expiryDate);
                const today = new Date();
                // Normalizar a día
                exp.setHours(0,0,0,0);
                today.setHours(0,0,0,0);
                const diff = today.getTime() - exp.getTime();
                const days = Math.floor(diff / (1000*60*60*24));
                if (days <= 0) {
                    // Aún no vence
                    statusBadge = '<span class="badge bg-warning">Pendiente</span>';
                } else {
                    // Vencida hace >7 días => rojo
                    statusBadge = '<span class="badge bg-danger">Vencida</span>';
                }
            }
        }
        
        // Agregar badge de alerta si hay información adicional
        let extraBadge = '';
        if (extra && String(extra).trim() !== '') {
            extraBadge = ' <span class="badge bg-info" title="Tiene información adicional"><i class="fas fa-info-circle"></i></span>';
        }
        
        return statusBadge + extraBadge;
    } catch (e) {
        return '<span class="badge bg-secondary">—</span>';
    }
}

// Exponer global
window.renderInvoiceStatusBadge = renderInvoiceStatusBadge;

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
            let value = data[key];
            // Campo número de factura: solo readonly en modo edición
            if (key === 'invoice') {
                const isEditMode = !!document.getElementById('factura_id')?.value;
                const $inv = $('#' + prefix + key);
                $inv.prop('readonly', !!isEditMode);
            }
            // Manejar valores null/undefined
            if (value === null || value === undefined) {
                value = '';
            }
            //console.log(element.type == 'text' && element.getAttribute('data-format') == 'clp')
            // Manejar fechas en distintos formatos
            if (value instanceof Date) {
                // Si es objeto Date
                if (element.type === 'date') {
                    const yyyy = value.getFullYear();
                    const mm = String(value.getMonth()+1).padStart(2,'0');
                    const dd = String(value.getDate()).padStart(2,'0');
                    value = `${yyyy}-${mm}-${dd}`;
                } else if (element.type === 'text' && element.getAttribute('data-format') === 'date-cl') {
                    if (window.CLFormat) {
                        value = window.CLFormat.formatDateCL(value);
                    }
                }
            } else if (typeof value === 'number' || (typeof value === 'string' && /^\d{10,13}$/.test(value))) {
                // Timestamp en segundos o milisegundos
                const ts = Number(value);
                const ms = ts < 1e12 ? ts * 1000 : ts; // si parece segundos, convertir a ms
                const d = new Date(ms);
                if (element.type === 'date') {
                    const yyyy = d.getFullYear();
                    const mm = String(d.getMonth()+1).padStart(2,'0');
                    const dd = String(d.getDate()).padStart(2,'0');
                    value = `${yyyy}-${mm}-${dd}`;
                } else if (element.type === 'text' && element.getAttribute('data-format') === 'date-cl') {
                    if (window.CLFormat) value = window.CLFormat.formatDateCL(d);
                }
            } else if (typeof value === 'string') {
                // ISO con T
                if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(value)) {
                    if (element.type === 'date') {
                        value = value.split('T')[0];
                    } else if (element.type === 'text' && element.getAttribute('data-format') === 'date-cl') {
                        const dateOnly = value.split('T')[0];
                        if (window.CLFormat) {
                            value = window.CLFormat.formatDateCL(dateOnly);
                        } else {
                            const [year, month, day] = dateOnly.split('-');
                            value = `${day}-${month}-${year}`;
                        }
                    } else {
                        value = dateOnly;
                    }
                // Formato MySQL con espacio "YYYY-MM-DD HH:MM:SS" o solo fecha
                } else if (/^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}:\d{2})?$/.test(value)) {
                    const dateOnly = value.substring(0, 10);
                    if (element.type === 'date') {
                        value = dateOnly; // compatible con input[type=date]
                    } else if (element.type === 'text' && element.getAttribute('data-format') === 'date-cl') {
                        if (window.CLFormat) {
                            value = window.CLFormat.formatDateCL(dateOnly);
                        } else {
                            const [year, month, day] = dateOnly.split('-');
                            value = `${day}-${month}-${year}`;
                        }
                    } else {
                        value = dateOnly;
                    }
                }
            }
            
            // Manejar campos select
            if (element.tagName === 'SELECT') {
                // Buscar la opción correspondiente
                const option = element.querySelector(`option[value="${value}"]`);
                if (option) {
                    element.value = value;
                } else {
                    element.value = '';
                }
            } else {
                element.value = value;
            }

            // Manejar campos con formato CLP especialmente
            if (element.type == 'text' && element.getAttribute('data-format') == 'clp') {
                // Extraer valor numérico y formatear para mostrar al usuario
                const numericValue = window.CLFormat ? window.CLFormat.parseCLP(value) : parseInt(String(value).replace(/[^\d]/g, ''), 10) || 0;
                if (numericValue > 0) {
                    // Mostrar valor formateado al usuario ($ 1.234.567)
                    element.value = numericValue.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                } else {
                    element.value = '';
                }
            } 
            
            // Manejar formateo especial para campos con data-format
            const format = element.getAttribute('data-format');
            if (format) {
                setTimeout(() => {
                    if (window.CLInputFormatter) {
                        window.CLInputFormatter.bind(element);
                        //window.CLInputFormatter.updateHint(element);
                    }
                }, 50);
            }
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
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
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
 * Guardar factura (crear o editar)
 * Función llamada desde los modales de facturas
 */
function saveFactura() {
    const form = $('#facturaForm');
    // Determinar modo del formulario y resolver ID de forma robusta
    const modeAttr = (form.attr('data-mode') || '').toLowerCase();
    let facturaId = (String($('#factura_id').val() || '').trim());
    let isEdit = modeAttr === 'edit' || (facturaId !== '');

    // Si estamos en edición pero falta el ID oculto, intentar resolver por número de factura desde caché
    if (!facturaId && isEdit) {
        const invoiceVal = String($('#invoice').val() || '').trim();
        if (invoiceVal) {
            try {
                const list = (window.EntityDataManager && typeof window.EntityDataManager.getEntityData === 'function')
                    ? window.EntityDataManager.getEntityData('facturas')
                    : (typeof window.FACTURAS === 'function' ? window.FACTURAS() : []);
                const match = (list || []).find(f => String(f && f.invoice) === invoiceVal);
                if (match && match.id != null) {
                    facturaId = String(match.id);
                    $('#factura_id').val(facturaId);
                }
            } catch(e) { /* noop */ }
        }
    }
    isEdit = !!facturaId;


    // Validar campos requeridos
    if (!validateRequiredFields('facturaForm')) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos requeridos',
            text: 'Por favor completa todos los campos obligatorios.'
        });
        return;
    }

    // Validar que la fecha de emisión no sea superior al día actual
    const fechaInput = document.getElementById('date');
    if (fechaInput && fechaInput.value) {
        const fechaIngresada = new Date(fechaInput.value);
        const hoy = new Date();
        hoy.setHours(0,0,0,0);
        fechaIngresada.setHours(0,0,0,0);
        if (fechaIngresada > hoy) {
            Swal.fire({
                icon: 'warning',
                title: 'Fecha inválida',
                text: 'La fecha de emisión no puede ser posterior al día actual.'
            });
            return;
        }
    }

    // ===== VERIFICACIÓN DE ARCHIVO PENDIENTE =====
    // Verificar si hay un archivo seleccionado pero no subido
    const fileInput = document.getElementById('file-upload');
    const hasFileSelected = fileInput && fileInput.files && fileInput.files.length > 0;
    
    if (hasFileSelected) {
        console.log('Detectado archivo seleccionado pero no subido, preguntando al usuario');
        const fileName = fileInput.files[0].name;
        
        Swal.fire({
            title: '¿Subir archivo?',
            html: `Tienes un archivo seleccionado:<br><strong>"${fileName}"</strong><br><br>¿Deseas subirlo junto con la factura?`,
            icon: 'question',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Sí, subir archivo y guardar',
            denyButtonText: 'No, solo guardar factura',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745',
            denyButtonColor: '#6c757d',
            cancelButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                // Subir archivo primero, luego guardar factura
                console.log('Usuario eligió subir archivo y guardar');
                uploadFileBeforeSaving();
            } else if (result.isDenied) {
                // Solo guardar factura, ignorar archivo
                console.log('Usuario eligió solo guardar factura');
                proceedWithSaving();
            }
            // Si es cancelar, no hacer nada
        });
        return; // Salir aquí hasta que el usuario decida
    }
    
    // Si no hay archivo seleccionado, proceder normalmente
    proceedWithSaving();
    
    // Función para subir archivo antes de guardar
    function uploadFileBeforeSaving() {
        console.log('Subiendo archivo antes de guardar factura');
        
        // Obtener número de factura
        const invoiceNumber = document.querySelector('#facturaModal [name="invoice"]')?.value;
        if (!invoiceNumber) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo obtener el número de factura para subir el archivo'
            });
            return;
        }
        
        // Preparar FormData para subir archivo
        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('invoice', invoiceNumber);
        
        // Agregar información adicional según el contexto
        const clienteId = document.querySelector('meta[name="cliente-id"]')?.getAttribute('content');
        const proveedorId = document.querySelector('meta[name="proveedor-id"]')?.getAttribute('content');
        
        if (clienteId) formData.append('client_id', clienteId);
        if (proveedorId) formData.append('provider_id', proveedorId);
        
        // Mostrar loading
        Swal.fire({
            title: 'Subiendo archivo...',
            text: 'Por favor espera mientras se sube el archivo',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Subir archivo usando la función existente
        uploadFileWithFormData(formData, 
            // Callback de éxito
            (uploadResponse) => {
                console.log('Archivo subido exitosamente, procediendo a guardar factura');
                // Limpiar el input después de subir exitosamente
                fileInput.value = '';
                
                // Cerrar el loading y proceder a guardar
                Swal.close();
                proceedWithSaving();
            },
            // Callback de error
            (errorMessage) => {
                console.error('Error al subir archivo:', errorMessage);
                
                Swal.fire({
                    title: 'Error al subir archivo',
                    html: `No se pudo subir el archivo:<br><strong>${errorMessage}</strong><br><br>¿Deseas continuar guardando la factura sin el archivo?`,
                    icon: 'error',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar sin archivo',
                    denyButtonText: 'Intentar subir archivo otra vez',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#28a745',
                    denyButtonColor: '#ffc107',
                    cancelButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Guardar factura sin archivo
                        console.log('Usuario eligió guardar sin archivo');
                        // Limpiar el input para que no se detecte en futuras validaciones
                        fileInput.value = '';
                        proceedWithSaving();
                    } else if (result.isDenied) {
                        // Intentar subir archivo otra vez
                        console.log('Usuario eligió intentar subir archivo otra vez');
                        // No hacer nada, volver al modal principal para que pueda intentar otra vez
                    }
                    // Si es cancelar, no hacer nada
                });
            }
        );
    }
    
    // Función para proceder con el guardado normal
    function proceedWithSaving() {
        console.log('Procediendo con guardado de factura (sin archivo pendiente)');
        
        // Preparar datos del formulario
        const formData = new FormData(form[0]);
        // Determinar URL y método según si es edición o creación
        let url = '';
        let method = 'POST';
        
        if (isEdit) {
            url = buildApiUrl(`facturas/${encodeURIComponent(facturaId)}`);
            formData.append('_method', 'PUT');
        } else {
            url = buildApiUrl('facturas');
        }
        console.log(formData)

        // Enviar datos
        $.ajax({
            url: url,
            type: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Si el backend devuelve la factura actualizada/creada, refrescar caches locales
                    try {
                        const f = response.factura || response.data || null;
                        if (f && window.EntityDataManager) {
                            const exists = !!window.EntityDataManager.findById('facturas', f.id);
                            if (exists) {
                                window.EntityDataManager.updateItem('facturas', f.id, f);
                            } else {
                                window.EntityDataManager.addItem('facturas', f);
                            }
                            // Actualizar datasets del Home si están presentes
                            (function(){
                                const mergeInto = (arr, item) => {
                                    if (!Array.isArray(arr)) return;
                                    const idx = arr.findIndex(x => String(x.id) === String(item.id));
                                    if (idx !== -1) arr[idx] = { ...arr[idx], ...item };
                                    else arr.push(item);
                                };
                                mergeInto(window.__HOME_CLIENTES_VENCIDAS__, f);
                                mergeInto(window.__HOME_PROVEEDORES_VENCIDAS__, f);
                            })();
                        }
                    } catch(e) { /* noop */ }

                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: isEdit ? 'Factura actualizada correctamente' : 'Factura creada correctamente'
                    }).then(() => {
                        $('#facturaModal').modal('hide');
                        reloadInvoiceTables();
                        // Limpiar formulario
                        form[0].reset();
                        $('#factura_id').val('');
                        form.removeAttr('data-mode');
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
}

/**
 * Normaliza campos de fechas en una factura para asegurar que exista 'date', 'expiry' y 'pay_date'.
 * Acepta alias comunes desde API/cachés y transforma a los nombres esperados por el formulario.
 */
function normalizeFacturaDates(factura) {
    if (!factura || typeof factura !== 'object') return factura;
    const f = { ...factura };
    const pick = (...vals) => vals.find(v => v !== undefined && v !== null && String(v).trim() !== '');
    // date (emisión)
    f.date = pick(f.date, f.fecha, f.fecha_emision, f.emision, f.created_at);
    // expiry (vencimiento)
    f.expiry = pick(f.expiry, f.fecha_vencimiento, f.vencimiento, f.due_date);
    // pay_date (fecha de pago)
    f.pay_date = pick(f.pay_date, f.fecha_pago, f.pago, f.paid_at);
    return f;
}

function openEditFacturaModal(entity, id) {
    // Resolver ID numérico si llega el número de factura como string
    const tryResolveNumericId = () => {
        const strId = String(id).trim();
        if (/^\d+$/.test(strId)) return parseInt(strId, 10);
        // Buscar por número de factura en caches locales (Home datasets y ENTITY_DATA)
        const fromEntityData = (window.FACTURAS && typeof window.FACTURAS === 'function')
            ? window.FACTURAS().find(f => String(f.invoice) === strId)
            : null;
        if (fromEntityData?.id) return fromEntityData.id;
        const cArr = window.__HOME_CLIENTES_VENCIDAS__ || [];
        const pArr = window.__HOME_PROVEEDORES_VENCIDAS__ || [];
        const fromHome = cArr.concat(pArr).find(f => String(f.invoice) === strId);
        if (fromHome?.id) return fromHome.id;
        return strId; // fallback: usar tal cual (puede fallar en AJAX si no es ID)
    };

    const numericOrRawId = tryResolveNumericId();

    const tryGetFromCaches = () => {
        let f = null;
        try { f = window.EntityDataManager?.findById('facturas', numericOrRawId) || null; } catch(e) {}
        if (f) return f;
        const cArr = window.__HOME_CLIENTES_VENCIDAS__ || [];
        const pArr = window.__HOME_PROVEEDORES_VENCIDAS__ || [];
        return cArr.concat(pArr).find(x => String(x.id) === String(numericOrRawId) || String(x.invoice) === String(id)) || null;
    };

    const proceedWithFactura = (factura) => {
        if (!factura) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se encontró la factura.' });
            return;
        }
        // Marcar formulario en modo edición
        try { $('#facturaForm').attr('data-mode', 'edit'); } catch(e) { /* noop */ }
        // Normalizar fechas para asegurar 'date'
        let fNorm = normalizeFacturaDates(factura);
        if (!fNorm.date) {
            // Hidratar desde BD si falta la fecha
            $.ajax({
                url: buildApiUrl('facturas/' + encodeURIComponent(fNorm.id || numericOrRawId)),
                type: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .done(function(res){
                const full = res && res.factura ? res.factura : res;
                const merged = normalizeFacturaDates({ ...fNorm, ...full });
                // Actualizar cache global con datos completos
                try {
                    const exists = !!window.EntityDataManager?.findById('facturas', merged.id);
                    if (exists) {
                        window.EntityDataManager.updateItem('facturas', merged.id, merged);
                    } else {
                        window.EntityDataManager?.addItem('facturas', merged);
                    }
                } catch(e){}
                // Continuar flujo con datos completos
                proceedWithFactura(merged);
            })
            .fail(function(){
                // Continuar sin fecha si la API falla
                fNorm = fNorm; // noop
                // seguir flujo con lo que hay
                // (no return aquí para poder continuar)
            });
            // Detener esta ejecución hasta tener los datos completos
            return;
        }

        // Inferir entidad si no llega o es inválida
        let ent = entity;
        if (!ent || (ent !== 'cliente' && ent !== 'proveedor')) {
            ent = (fNorm.client_id != null) ? 'cliente' : (fNorm.provider_id != null ? 'proveedor' : '');
        }

        // Guardar/actualizar en cache global para futuros usos
        try {
            const existing = window.EntityDataManager?.findById('facturas', fNorm.id);
            if (existing) {
                window.EntityDataManager.updateItem('facturas', fNorm.id, fNorm);
            } else {
                window.EntityDataManager?.addItem('facturas', fNorm);
            }
        } catch(e) {}

        // Prefill detalle si existe
        if (fNorm.detail) {
            const detailEl = document.getElementById('detail');
            if (detailEl) detailEl.value = fNorm.detail;
        }

        // Prefill extra si existe
        if (fNorm.extra) {
            const extraEl = document.getElementById('extra');
            if (extraEl) extraEl.value = fNorm.extra;
        }

        // Cargar datos de selectores y popular formulario
        ReferenceDataManager.ensureLoaded(['clientes', 'proveedores', 'metodosPago']).then(() => {
            populateFacturaSelects($('#facturaModal'));
            const clientId = (fNorm.client_id != null) ? fNorm.client_id : (fNorm.cliente && fNorm.cliente.id != null ? fNorm.cliente.id : '');
            const providerId = (fNorm.provider_id != null) ? fNorm.provider_id : (fNorm.proveedor && fNorm.proveedor.id != null ? fNorm.proveedor.id : '');
            const metodoPagoId = (fNorm.payment_method_id != null) ? fNorm.payment_method_id : (fNorm.metodoPago && fNorm.metodoPago.id != null ? fNorm.metodoPago.id : '');
            const clientName = (fNorm.cliente && fNorm.cliente.name) ? fNorm.cliente.name : undefined;
            const providerName = (fNorm.proveedor && fNorm.proveedor.name) ? fNorm.proveedor.name : undefined;
            const metodoPagoName = (fNorm.metodoPago && fNorm.metodoPago.name) ? fNorm.metodoPago.name : undefined;

            if (typeof window.setSelect2Value === 'function') {
                setSelect2Value('#client_id', clientId, clientName);
                setSelect2Value('#provider_id', providerId, providerName);
                setSelect2Value('#payment_method_id', metodoPagoId, metodoPagoName);
            } else {
                if (clientId !== '') $('#client_id').val(clientId).trigger('change');
                if (providerId !== '') $('#provider_id').val(providerId).trigger('change');
                if (metodoPagoId !== '') $('#payment_method_id').val(metodoPagoId).trigger('change');
            }

            // Poblar formulario con datos normalizados
            populateForm(fNorm, '');
        });

        // Set ID oculto y bloquear invoice en edición
        const facturaIdField = document.getElementById('factura_id');
        if (facturaIdField) facturaIdField.value = fNorm.id;
        $('#invoice').prop('readonly', true);

        // Formatear monto si es necesario
        const amountEl = document.getElementById('amount');
        if (amountEl) {
            let valorNumerico = 0;
            if (window.CLFormat && typeof window.CLFormat.parseCLP === 'function') {
                valorNumerico = window.CLFormat.parseCLP(fNorm.amount);
            } else {
                valorNumerico = parseInt(String(fNorm.amount).replace(/[^\d]/g, '')) || 0;
            }
            amountEl.value = valorNumerico > 0 ? String(valorNumerico) : '';
            if (window.CLInputFormatter) window.CLInputFormatter.updateHint(amountEl);
        }

        // Título modal según entidad
        if (ent === 'cliente') {
            $('#facturaModalLabel').text('Editar Factura de Cliente');
            $('#client_id').closest('.mb-3, .col-md-6, .row').show();
            $('#provider_id').closest('.mb-3, .col-md-6, .row').hide();
        } else if (ent === 'proveedor') {
            $('#facturaModalLabel').text('Editar Factura de Proveedor');
            $('#provider_id').closest('.mb-3, .col-md-6, .row').show();
            $('#client_id').closest('.mb-3, .col-md-6, .row').hide();
        } else {
            $('#facturaModalLabel').text('Editar Factura');
            $('#client_id, #provider_id').closest('.mb-3, .col-md-6, .row').show();
        }

        // Mostrar sección de archivos en edición
        const fileManagementSection = document.getElementById('file-management-section');
        if (fileManagementSection) fileManagementSection.style.display = 'block';
        
        // Mostrar archivos asociados a la factura
        displayFacturaFiles(fNorm.id, 'edit');
        
        $('#facturaModal').modal('show');
    };

    const cached = tryGetFromCaches();
    if (cached) return proceedWithFactura(cached);

    // Fallback: cargar por AJAX
    $.ajax({
        url: buildApiUrl('facturas/' + encodeURIComponent(numericOrRawId)),
        type: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .done(function(res){
        const factura = res && res.factura ? res.factura : res;
        const fNorm = normalizeFacturaDates(factura);
        proceedWithFactura(fNorm);
    })
    .fail(function(){
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la factura.' });
    });
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
     // Validar que la fecha de emisión no sea superior al día actual
    const fechaInput = document.getElementById('date');
    if (fechaInput && fechaInput.value) {
        const fechaIngresada = new Date(fechaInput.value);
        const hoy = new Date();
        hoy.setHours(0,0,0,0);
        fechaIngresada.setHours(0,0,0,0);
        if (fechaIngresada > hoy) {
            Swal.fire({
                icon: 'warning',
                title: 'Fecha inválida',
                text: 'La fecha de emisión no puede ser posterior al día actual.'
            });
            return;
        }
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
            // Actualizar caches si el backend entrega la factura
            try {
                const f = response.factura || response.data || null;
                if (f && window.EntityDataManager) {
                    const exists = !!window.EntityDataManager.findById('facturas', f.id);
                    if (exists) {
                        window.EntityDataManager.updateItem('facturas', f.id, f);
                    } else {
                        window.EntityDataManager.addItem('facturas', f);
                    }
                    // Actualizar datasets del Home
                    (function(){
                        const mergeInto = (arr, item) => {
                            if (!Array.isArray(arr)) return;
                            const idx = arr.findIndex(x => String(x.id) === String(item.id));
                            if (idx !== -1) arr[idx] = { ...arr[idx], ...item };
                        };
                        mergeInto(window.__HOME_CLIENTES_VENCIDAS__, f);
                        mergeInto(window.__HOME_PROVEEDORES_VENCIDAS__, f);
                    })();
                }
            } catch(e) { /* noop */ }

            showSuccessMessage('Factura actualizada correctamente', function() {
                $('#facturaModal').modal('hide');
                reloadInvoiceTables();
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
 * Cargar datos de clientes, proveedores y métodos de pago para los selectores
 */
function loadSelectData() {
    // Cargar datos de referencia y poblar selects
    const $modal = $('#facturaModal');
    if (!window.ReferenceDataManager) return Promise.resolve();

    // Forzar refresh de clientes para asegurar que aparezcan los recién creados
    return ReferenceDataManager.refresh('clientes')
        .then(() => ReferenceDataManager.ensureLoaded(['proveedores', 'metodosPago']))
        .then(() => {
            if (typeof window.populateFacturaSelects === 'function') {
                window.populateFacturaSelects($modal);
            }
        });
}

// Función para abrir modal de creación
function openCreateFacturaModal(entity) {
    // Limpiar formulario
    $('#facturaForm')[0].reset();
    $('#factura_id').val('');
    // Marcar modo creación
    try { $('#facturaForm').attr('data-mode', 'create'); } catch(e) { /* noop */ }
    // Asegurar que el campo invoice esté editable en creación
    $('#invoice').prop('readonly', false).prop('disabled', false);

    // Cargar datos de los selectores desde referencias
    ReferenceDataManager.ensureLoaded(['clientes', 'proveedores', 'metodosPago']).then(() => {
        populateFacturaSelects($('#facturaModal'));
    });
    
    // Configurar modal según entidad
    if (entity === 'cliente') {
        $('#facturaModalLabel').text('Nueva Factura de Cliente');
        // Ocultar campo proveedor si existe
        const providerField = $('#provider_id');
        if (providerField.length) {
            providerField.closest('.row, .col-md-6, .col-md-12, .mb-3').hide();
        }
        // Mostrar campo cliente
        const clientField = $('#client_id');
        if (clientField.length) {
            clientField.closest('.row, .col-md-6, .col-md-12, .mb-3').show();
        }
    } else if (entity === 'proveedor') {
        $('#facturaModalLabel').text('Nueva Factura de Proveedor');
        // Ocultar campo cliente si existe
        const clientField = $('#client_id');
        if (clientField.length) {
            clientField.closest('.row, .col-md-6, .col-md-12, .mb-3').hide();
        }
        // Mostrar campo proveedor
        const providerField = $('#provider_id');
        if (providerField.length) {
            providerField.closest('.row, .col-md-6, .col-md-12, .mb-3').show();
        }
    } else {
        $('#facturaModalLabel').text('Nueva Factura');
        // En vista general, mostrar ambos campos
        $('#client_id, #provider_id').closest('.row, .col-md-6, .col-md-12, .mb-3').show();
    }
    
    // Ocultar sección de gestión de archivos en modo creación
    const fileManagementSection = document.getElementById('file-management-section');
    if (fileManagementSection) {
        fileManagementSection.style.display = 'none';
    }
    
    // Abrir modal
    $('#facturaModal').modal('show');
}


// ===== FUNCIONES DE GESTIÓN DE ARCHIVOS PARA FACTURAS =====

/**
 * Subir archivo - función llamada desde los botones en las vistas
 */
function uploadFile() {
    console.log('=== INICIO PROCESO UPLOAD FILE ===');
    
    const fileInput = document.getElementById('file-upload');
    if (!fileInput) {
        console.error('No se encontró el input file-upload');
        Swal.fire({
            title: 'Error',
            text: 'No se encontró el campo de archivo',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    const file = fileInput.files[0];
    if (!file) {
        console.warn('No hay archivo seleccionado');
        Swal.fire({
            title: 'Error',
            text: 'Por favor selecciona un archivo',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    console.log('Archivo seleccionado:', {
        name: file.name,
        size: file.size,
        type: file.type
    });
    
    // Obtener número de factura desde diferentes posibles fuentes
    let invoice = null;
    
    // 1. Desde formulario modal (si existe y está visible)
    const facturaModal = document.getElementById('facturaModal');
    if (facturaModal && (facturaModal.classList.contains('show') || facturaModal.style.display === 'block')) {
        const invoiceInput = document.querySelector('#facturaModal [name="invoice"]');
        if (invoiceInput && invoiceInput.value) {
            invoice = invoiceInput.value;
            console.log('Número de factura obtenido del modal:', invoice);
        }
    }
    
    // 2. Si no hay modal abierto, pedir al usuario que ingrese el número de factura
    if (!invoice) {
        console.log('No se encontró número de factura, solicitando al usuario');
        Swal.fire({
            title: 'Número de Factura',
            text: 'Ingresa el número de factura para asociar el archivo:',
            input: 'text',
            inputAttributes: {
                autocapitalize: 'off',
                placeholder: 'Ej: F-001'
            },
            showCancelButton: true,
            confirmButtonText: 'Subir Archivo',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value) {
                    return 'Debes ingresar un número de factura';
                }
                if (value.length < 1) {
                    return 'El número de factura es muy corto';
                }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                console.log('Usuario ingresó número de factura:', result.value);
                proceedWithUpload(result.value);
            } else {
                console.log('Usuario canceló la subida');
            }
        });
        return; // Salir aquí, la subida continuará en el callback
    } else {
        proceedWithUpload(invoice);
    }
    
    function proceedWithUpload(invoiceNumber) {
        console.log('=== PROCEDIENDO CON UPLOAD ===');
        console.log('Número de factura final:', invoiceNumber);
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('invoice', invoiceNumber);
        
        // Agregar información adicional según el contexto
        const clienteId = document.querySelector('meta[name="cliente-id"]')?.getAttribute('content');
        const proveedorId = document.querySelector('meta[name="proveedor-id"]')?.getAttribute('content');
        
        if (clienteId) {
            formData.append('client_id', clienteId);
            console.log('Agregado client_id:', clienteId);
        }
        if (proveedorId) {
            formData.append('provider_id', proveedorId);
            console.log('Agregado provider_id:', proveedorId);
        }
        
        console.log('Datos del FormData preparados:');
        for (let [key, value] of formData.entries()) {
            if (key === 'file') {
                console.log(`${key}:`, value.name, `(${value.size} bytes)`);
            } else {
                console.log(`${key}:`, value);
            }
        }
        
        uploadFileWithFormData(formData, () => {
            console.log('Upload completado exitosamente, limpiando input');
            // Limpiar input después de subir
            fileInput.value = '';
        });
    }
}

/**
 * Subir archivo para una factura - función interna que maneja el FormData
 */
function uploadFileWithFormData(formData, onSuccess = null, onError = null) {
    console.log('=== INICIANDO PETICIÓN AL SERVIDOR ===');
    
    // Debug: Mostrar lo que se está enviando
    console.log('Enviando archivo con datos:');
    for (let [key, value] of formData.entries()) {
        if (key === 'file') {
            console.log(`${key}:`, value.name, `(${value.size} bytes, ${value.type})`);
        } else {
            console.log(`${key}:`, value);
        }
    }
    
    const url = buildApiUrl('files/upload');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    console.log('URL de destino:', url);
    console.log('CSRF Token presente:', !!csrfToken);
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Respuesta del servidor recibida:', {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok,
            headers: Object.fromEntries(response.headers.entries())
        });
        
        if (response.status === 401) {
            console.error('Usuario no autenticado, redirigiendo a login');
            window.location.href = '/login';
            return;
        }
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('=== RESPUESTA JSON DEL SERVIDOR ===');
        console.log('Respuesta completa:', data);
        
        if (data && (data.status === 'success' || data.success === true)) {
            console.log('✅ Upload exitoso!');
            console.log('Detalles del archivo subido:', data.file);
            
            // Solo mostrar SweetAlert si no hay callback de éxito (modo manual)
            if (!onSuccess) {
                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Archivo subido correctamente',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            }
            
            if (onSuccess) {
                console.log('Ejecutando callback de éxito');
                onSuccess(data);
            }
            
            // Actualizar modal de edición si está abierto
            if ($('#facturaModal').hasClass('show')) {
                console.log('Modal de factura abierto, actualizando interfaz');
                const invoice = document.querySelector('[name="invoice"]')?.value;
                const facturaId = document.getElementById('factura_id')?.value;
                if (invoice && data.file && facturaId) {
                    console.log('Actualizando sección de archivos en modal');
                    
                    // Crear objeto factura temporal para mostrar el archivo recién subido
                    const tempFactura = {
                        id: facturaId,
                        has_file: true,
                        file_path: data.file.path,
                        file_name: data.file.name,
                        file_size: data.file.size || null
                    };
                    
                    // Actualizar la visualización usando las nuevas funciones
                    const archivoAsociadoDiv = document.getElementById('archivo-asociado');
                    if (archivoAsociadoDiv) {
                        archivoAsociadoDiv.innerHTML = createArchivoAsociadoHtml(tempFactura, 'edit');
                    }
                    
                    const filesListContainer = document.getElementById('files-list');
                    if (filesListContainer) {
                        filesListContainer.innerHTML = createFilesListHtml(tempFactura, 'edit');
                    }
                    
                    // Actualizar el indicador de archivo para mostrar que ya fue subido
                    const fileInput = document.getElementById('file-upload');
                    if (fileInput) {
                        fileInput.value = '';
                        updateFileInputIndicator(fileInput);
                    }
                }
            }
            
            // Recargar tablas solo si no hay callback (modo manual)
            if (!onSuccess) {
                console.log('Recargando tablas de facturas');
                reloadInvoiceTables();
            }
        } else {
            const errorMsg = data?.message || data?.error || 'Error al subir archivo';
            console.error('❌ Error en respuesta del servidor:', data);
            
            if (onError) {
                onError(errorMsg);
            } else {
                Swal.fire({
                    title: 'Error',
                    text: errorMsg,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        }
    })
    .catch(error => {
        console.error('❌ Error en fetch:', error);
        const errorMessage = 'Error al subir archivo: ' + error.message;
        
        if (onError) {
            onError(errorMessage);
        } else {
            Swal.fire({
                title: 'Error',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}

/**
 * Eliminar archivo desde el modal de edición de factura
 */
function eliminarArchivoFacturaModal() {
    // Obtener el ID de la factura desde el modal de edición
    const facturaId = document.getElementById('factura_id')?.value;
    
    if (!facturaId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo identificar la factura.'
        });
        return;
    }
    
    // Usar la función existente eliminarArchivoFactura
    eliminarArchivoFactura(facturaId);
}

// ===== EVENT LISTENERS PARA GESTIÓN DE ARCHIVOS =====

// Event listener para upload de archivos - REMOVIDO EL AUTO-UPLOAD
document.addEventListener('DOMContentLoaded', function() {
    // Ya no hay auto-upload, solo se sube cuando se presiona el botón uploadFile()
    console.log('Event listeners de archivos cargados - Auto-upload deshabilitado');
    
    // Agregar indicador visual para archivos seleccionados
    initializeFileInputs();
});

/**
 * Inicializar inputs de archivo en la página
 */
function initializeFileInputs() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(fileInput => {
        // Agregar contenedor visual si no existe
        if (!fileInput.closest('.file-input-container')) {
            const container = document.createElement('div');
            container.className = 'file-input-container';
            
            // Envolver el input en el contenedor
            fileInput.parentNode.insertBefore(container, fileInput);
            container.appendChild(fileInput);
            
            // Agregar texto instructivo
            const helpText = document.createElement('div');
            helpText.className = 'text-muted text-center mt-2';
            helpText.innerHTML = '<small><i class="fas fa-upload me-1"></i>Selecciona un archivo para subir</small>';
            container.appendChild(helpText);
        }
        
        // Agregar event listener
        fileInput.addEventListener('change', function(e) {
            updateFileInputIndicator(this);
        });
        
        // Inicializar indicador
        updateFileInputIndicator(fileInput);
    });
}

// Exponer función globalmente
window.initializeFileInputs = initializeFileInputs;

/**
 * Actualizar indicador visual del input de archivo
 */
function updateFileInputIndicator(fileInput) {
    if (!fileInput) return;
    
    const hasFile = fileInput.files && fileInput.files.length > 0;
    let indicator = fileInput.parentElement.querySelector('.file-indicator');
    
    // Crear indicador si no existe
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'file-indicator';
        fileInput.parentElement.appendChild(indicator);
    }
    
    // Actualizar contenedor del input
    const container = fileInput.closest('.file-input-container');
    if (container) {
        if (hasFile) {
            container.classList.add('has-file');
        } else {
            container.classList.remove('has-file');
        }
    }
    
    if (hasFile) {
        const fileName = fileInput.files[0].name;
        const fileSize = (fileInput.files[0].size / 1024 / 1024).toFixed(2); // MB
        
        indicator.innerHTML = `
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="fas fa-file-upload me-2" style="color: #0a58ca;"></i>
                <div class="flex-grow-1">
                    <strong>Archivo seleccionado:</strong><br>
                    <small style="color: #0a58ca;">${fileName} (${fileSize} MB)</small><br>
                    <small style="color: #856404;">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Presiona "Subir" para adjuntar el archivo
                    </small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearFileInput()" title="Quitar archivo">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Hacer más visible el botón de subir
        const uploadButton = document.querySelector('button[onclick="uploadFile()"]');
        if (uploadButton) {
            uploadButton.classList.add('btn-warning', 'fw-bold');
            uploadButton.classList.remove('btn-primary');
            if (!uploadButton.querySelector('.pulse-animation')) {
                uploadButton.innerHTML = `<i class="fas fa-upload pulse-animation"></i> Subir Archivo`;
            }
        }
    } else {
        indicator.innerHTML = `
            <div class="alert alert-light text-muted" role="alert">
                <i class="fas fa-file me-2"></i>
                <small>No hay archivo seleccionado</small>
            </div>
        `;
        
        // Restaurar botón de subir
        const uploadButton = document.querySelector('button[onclick="uploadFile()"]');
        if (uploadButton) {
            uploadButton.classList.remove('btn-warning', 'fw-bold');
            uploadButton.classList.add('btn-primary');
            uploadButton.innerHTML = `<i class="fas fa-upload"></i> Subir Archivo`;
        }
    }
}

/**
 * Limpiar input de archivo
 */
function clearFileInput() {
    const fileInput = document.getElementById('file-upload');
    if (fileInput) {
        fileInput.value = '';
        updateFileInputIndicator(fileInput);
        
        Swal.fire({
            title: 'Archivo eliminado',
            text: 'Se ha eliminado el archivo seleccionado',
            icon: 'info',
            timer: 1500,
            showConfirmButton: false
        });
    }
}

// Exponer la función globalmente
window.clearFileInput = clearFileInput;

// ===== COMPONENTE DE VISUALIZACIÓN DE ARCHIVOS DE FACTURAS =====

/**
 * Componente reutilizable para mostrar archivos de facturas
 * Proporciona una interfaz consistente para diferentes contextos
 */
class FacturaFilesComponent {
    constructor(facturaId, options = {}) {
        this.facturaId = facturaId;
        this.options = {
            context: 'edit', // 'edit', 'view', 'readonly'
            showTitle: true,
            showFileSize: true,
            showFileDate: true,
            containerClass: 'factura-files-component',
            ...options
        };
    }

    /**
     * Renderizar el componente en el contenedor especificado
     */
    render(containerSelector) {
        const container = document.querySelector(containerSelector);
        if (!container) {
            console.warn(`Contenedor ${containerSelector} no encontrado`);
            return;
        }

        const factura = EntityHelpers.getFactura(this.facturaId);
        const html = this.generateHTML(factura);
        
        container.innerHTML = html;
        container.className = `${this.options.containerClass} context-${this.options.context}`;
        
        // Agregar event listeners específicos del componente
        this.attachEventListeners(container);
    }

    /**
     * Generar HTML del componente
     */
    generateHTML(factura) {
        if (!factura || !factura.has_file || !factura.file_path) {
            return this.generateEmptyState();
        }

        return this.generateFileDisplay(factura);
    }

    /**
     * Generar HTML para estado vacío (sin archivos)
     */
    generateEmptyState() {
        return `
            <div class="files-empty-state">
                <div class="empty-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="empty-message">
                    <h6>No hay archivos adjuntos</h6>
                    <p class="text-muted mb-0">Esta factura no tiene archivos asociados</p>
                </div>
            </div>
        `;
    }

    /**
     * Generar HTML para mostrar archivo
     */
    generateFileDisplay(factura) {
        const fileInfo = this.extractFileInfo(factura);
        const actions = this.generateActions(factura);
        
        return `
            ${this.options.showTitle ? this.generateTitle() : ''}
            <div class="file-display-card">
                <div class="file-header">
                    <div class="file-icon">
                        ${this.getFileIcon(fileInfo.extension)}
                    </div>
                    <div class="file-info">
                        <div class="file-name" title="${fileInfo.fullName}">
                            ${fileInfo.displayName}
                        </div>
                        <div class="file-metadata">
                            ${this.generateMetadata(fileInfo)}
                        </div>
                    </div>
                </div>
                <div class="file-actions">
                    ${actions}
                </div>
            </div>
        `;
    }

    /**
     * Generar título del componente
     */
    generateTitle() {
        return `
            <div class="files-component-title">
                <h6 class="mb-3">
                    <i class="fas fa-paperclip me-2"></i>
                    Archivos Adjuntos
                </h6>
            </div>
        `;
    }

    /**
     * Extraer información del archivo
     */
    extractFileInfo(factura) {
        const fullName = factura.file_name || factura.file_path || 'Archivo adjunto';
        const extension = this.getFileExtension(fullName);
        const displayName = this.truncateFileName(fullName, 30);
        
        return {
            fullName,
            displayName,
            extension,
            size: factura.file_size || null,
            uploadDate: factura.file_uploaded_at || factura.updated_at || null,
            path: factura.file_path
        };
    }

    /**
     * Generar metadatos del archivo
     */
    generateMetadata(fileInfo) {
        const metadata = [];
        
        if (this.options.showFileSize && fileInfo.size) {
            metadata.push(`<span class="file-size">${this.formatFileSize(fileInfo.size)}</span>`);
        }
        
        if (this.options.showFileDate && fileInfo.uploadDate) {
            metadata.push(`<span class="file-date">${this.formatDate(fileInfo.uploadDate)}</span>`);
        }
        
        return metadata.length > 0 ? metadata.join(' • ') : '';
    }

    /**
     * Generar botones de acción
     */
    generateActions(factura) {
        const actions = [];
        
        // Botón de descarga (siempre disponible)
        actions.push(`
            <button type="button" 
                    class="btn btn-sm btn-file-action btn-file-download" 
                    onclick="descargarPDF(${factura.id}, event)"
                    title="Descargar archivo">
                <i class="fas fa-download"></i>
                <span class="btn-text">Descargar</span>
            </button>
        `);
        
        // Botón de eliminar (solo en contextos editables)
        if (this.options.context === 'edit') {
            actions.push(`
                <button type="button" 
                        class="btn btn-sm btn-file-action btn-file-delete" 
                        onclick="eliminarArchivoFacturaModal()" 
                        title="Eliminar archivo">
                    <i class="fas fa-trash"></i>
                    <span class="btn-text">Eliminar</span>
                </button>
            `);
        }
        
        return actions.join('');
    }

    /**
     * Obtener icono según extensión de archivo
     */
    getFileIcon(extension) {
        const iconMap = {
            'pdf': '<i class="fas fa-file-pdf text-danger"></i>',
            'doc': '<i class="fas fa-file-word text-primary"></i>',
            'docx': '<i class="fas fa-file-word text-primary"></i>',
            'xls': '<i class="fas fa-file-excel text-success"></i>',
            'xlsx': '<i class="fas fa-file-excel text-success"></i>',
            'jpg': '<i class="fas fa-file-image text-info"></i>',
            'jpeg': '<i class="fas fa-file-image text-info"></i>',
            'png': '<i class="fas fa-file-image text-info"></i>',
            'gif': '<i class="fas fa-file-image text-info"></i>',
            'txt': '<i class="fas fa-file-alt text-secondary"></i>',
            'zip': '<i class="fas fa-file-archive text-warning"></i>',
            'rar': '<i class="fas fa-file-archive text-warning"></i>'
        };
        
        return iconMap[extension.toLowerCase()] || '<i class="fas fa-file text-muted"></i>';
    }

    /**
     * Obtener extensión de archivo
     */
    getFileExtension(filename) {
        return filename.split('.').pop() || '';
    }

    /**
     * Truncar nombre de archivo para visualización
     */
    truncateFileName(filename, maxLength) {
        if (filename.length <= maxLength) return filename;
        
        const extension = this.getFileExtension(filename);
        const nameWithoutExt = filename.substring(0, filename.lastIndexOf('.')) || filename;
        const maxNameLength = maxLength - extension.length - 4; // 4 para "..." y punto
        
        if (nameWithoutExt.length > maxNameLength) {
            return nameWithoutExt.substring(0, maxNameLength) + '...' + (extension ? '.' + extension : '');
        }
        
        return filename;
    }

    /**
     * Formatear tamaño de archivo
     */
    formatFileSize(bytes) {
        if (!bytes) return '';
        
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        const size = (bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1);
        
        return `${size} ${sizes[i]}`;
    }

    /**
     * Formatear fecha
     */
    formatDate(dateString) {
        if (!dateString) return '';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch (e) {
            return '';
        }
    }

    /**
     * Adjuntar event listeners específicos del componente
     */
    attachEventListeners(container) {
        // Agregar tooltips para nombres de archivos largos
        const fileNameElements = container.querySelectorAll('.file-name');
        fileNameElements.forEach(element => {
            if (element.scrollWidth > element.clientWidth) {
                element.classList.add('text-truncated');
            }
        });
    }
}

/**
 * Mostrar archivos asociados a una factura usando el componente
 * Función de compatibilidad que mantiene la API existente
 */
function displayFacturaFiles(facturaId, modalContext = 'edit') {
    console.log('Mostrando archivos para factura ID:', facturaId, 'Contexto:', modalContext);
    
    if (!facturaId) {
        console.warn('No se proporcionó ID de factura');
        return;
    }
    
    // Crear instancia del componente
    const component = new FacturaFilesComponent(facturaId, {
        context: modalContext,
        showTitle: true,
        showFileSize: true,
        showFileDate: true
    });
    
    // Buscar contenedores existentes y renderizar
    const containers = [
        '#archivo-asociado',
        '#files-list',
        '.factura-files-container'
    ];
    
    containers.forEach(selector => {
        const container = document.querySelector(selector);
        if (container) {
            component.render(selector);
        }
    });
}

// Exponer funciones y clases globalmente
window.displayFacturaFiles = displayFacturaFiles;
window.FacturaFilesComponent = FacturaFilesComponent;

/**
 * Función de utilidad para crear rápidamente un componente de archivos
 * @param {number} facturaId - ID de la factura
 * @param {string} containerSelector - Selector del contenedor
 * @param {Object} options - Opciones del componente
 */
function createFacturaFilesComponent(facturaId, containerSelector, options = {}) {
    const component = new FacturaFilesComponent(facturaId, options);
    component.render(containerSelector);
    return component;
}

// Exponer función de utilidad
window.createFacturaFilesComponent = createFacturaFilesComponent;

// ==============================
// Referencias de datos (clientes, proveedores, métodos de pago)
// Carga perezosa + caché local opcional
// ==============================
(function(){
    if (window.ReferenceDataManager) return;

    const ENTITY_ENDPOINTS = {
        clientes: 'clientes/data',
        proveedores: 'proveedores/data',
        metodosPago: 'metodos-pago/data'
    };

    const ReferenceDataManager = {
        data: { clientes: null, proveedores: null, metodosPago: null },
        isLoading: { clientes: false, proveedores: false, metodosPago: false },

        getFromLocalCache(entity){
            try {
                if (window.searchCacheManager && typeof window.searchCacheManager.getEntityData === 'function') {
                    const d = window.searchCacheManager.getEntityData(entity);
                    if (Array.isArray(d) && d.length) return d;
                }
            } catch(e) { /* noop */ }
            return null;
        },
        set(entity, list){
            this.data[entity] = Array.isArray(list) ? list : [];
            try {
                if (window.searchCacheManager && typeof window.searchCacheManager.updateEntityCache === 'function') {
                    window.searchCacheManager.updateEntityCache(entity, this.data[entity]);
                }
            } catch(e) { /* noop */ }
            document.dispatchEvent(new CustomEvent(`${entity}:ready`, { detail: this.data[entity] }));
        },
        ensureLoaded(entities){
            const items = Array.isArray(entities) ? entities : [entities];
            const tasks = items.map((entity) => this.load(entity));
            return Promise.all(tasks);
        },
        load(entity){
            if (this.data[entity]) return Promise.resolve(this.data[entity]);
            if (this.isLoading[entity]) {
                // Esperar a que termine otra carga en curso
                return new Promise((resolve) => {
                    const handler = (e) => { document.removeEventListener(`${entity}:ready`, handler); resolve(e.detail || this.data[entity] || []); };
                    document.addEventListener(`${entity}:ready`, handler);
                });
            }

            this.isLoading[entity] = true;

            // 1) Intentar desde caché local
            const cached = this.getFromLocalCache(entity);
            if (cached) {
                this.set(entity, cached);
                this.isLoading[entity] = false;
                // 2) Opcionalmente refrescar en background si el caché venció
                try {
                    if (window.searchCacheManager && typeof window.searchCacheManager.needsUpdate === 'function' && window.searchCacheManager.needsUpdate(entity)) {
                        this.fetchFromServer(entity).catch(() => {});
                    }
                } catch(e) { /* noop */ }
                return Promise.resolve(cached);
            }

            // 3) Cargar desde servidor
            return this.fetchFromServer(entity).finally(() => {
                this.isLoading[entity] = false;
            });
        },
        fetchFromServer(entity){
            const endpoint = ENTITY_ENDPOINTS[entity];
            if (!endpoint) return Promise.resolve([]);
            return new Promise((resolve) => {
                $.get(buildApiUrl(endpoint))
                    .done((response) => {
                        const list = (response && response.data) ? response.data : [];
                        this.set(entity, list);
                        resolve(list);
                    })
                    .fail(() => {
                        // Fallback para métodos de pago
                        if (entity === 'metodosPago') {
                            const fallback = [
                                {id: 0, name: ''},
                                {id: 1, name: 'Transferencia bancaria'},
                                {id: 2, name: 'Efectivo'},
                                {id: 3, name: 'Red Compra'},
                                {id: 4, name: 'Cheque'}
                            ];
                            this.set(entity, fallback);
                            resolve(fallback);
                        } else {
                            console.error(`Error al cargar ${entity}`);
                            this.set(entity, []);
                            resolve([]);
                        }
                    });
            });
        },
        refresh(entity, options = {}){
            const { force = false } = options || {};
            // Si es forzado, evitar caché y pedir al servidor
            if (force) {
                if (this.isLoading[entity]) {
                    // Esperar a que termine la carga en curso
                    return new Promise((resolve) => {
                        const handler = (e) => { document.removeEventListener(`${entity}:ready`, handler); resolve(e.detail || this.data[entity] || []); };
                        document.addEventListener(`${entity}:ready`, handler);
                    });
                }
                this.isLoading[entity] = true;
                return this.fetchFromServer(entity).finally(() => {
                    this.isLoading[entity] = false;
                });
            }
            // Comportamiento original (puede usar caché)
            this.data[entity] = null;
            return this.load(entity);
        },
        // Inserta o actualiza uno o varios elementos en el caché sin ir al servidor
        upsert(entity, items){
            const list = Array.isArray(this.data[entity]) ? this.data[entity] : [];
            const byId = new Map(list.map(it => [String(it && it.id), it]).filter(([k]) => k !== 'undefined'));
            const arr = Array.isArray(items) ? items : [items];
            arr.forEach(it => {
                if (!it || it.id === undefined || it.id === null) return;
                const key = String(it.id);
                const prev = byId.get(key) || {};
                byId.set(key, Object.assign({}, prev, it));
            });
            const merged = Array.from(byId.values());
            this.set(entity, merged); // también actualiza caché local y emite `${entity}:ready`
            return merged;
        }
    };

    // Helper para poblar selects del modal de factura usando datos de referencia
    function populateFacturaSelects(dropdownParent){
        const $modal = dropdownParent && dropdownParent.length ? dropdownParent : $('#facturaModal');
        const select2Options = $modal.length ? { width: '100%', dropdownParent: $modal } : { width: '100%' };

        // Clientes
        const $client = $('#client_id');
        if ($client.length) {
            const current = $client.val();
            //console.log(current)
            $client.empty().append('<option value="">Seleccionar cliente...</option>');
            (ReferenceDataManager.data.clientes || []).forEach(c => {
                //console.log(c)
                const label = c && c.rut ? `${c.name} (${c.rut})` : (c && c.name ? c.name : '');
                $client.append(`<option value="${c.id}">${label}</option>`);
            });
            if ($client.hasClass('select2-hidden-accessible')) { $client.select2('destroy'); }
            $client.select2(select2Options);
            if (current) $client.val(current).trigger('change');
        }

        // Proveedores
        const $prov = $('#provider_id');
        if ($prov.length) {
            const current = $prov.val();
            $prov.empty().append('<option value="">Seleccionar proveedor...</option>');
            (ReferenceDataManager.data.proveedores || []).forEach(p => {
                const label = p && p.rut ? `${p.name} (${p.rut})` : (p && p.name ? p.name : '');
                $prov.append(`<option value="${p.id}">${label}</option>`);
            });
            if ($prov.hasClass('select2-hidden-accessible')) { $prov.select2('destroy'); }
            $prov.select2(select2Options);
            if (current) $prov.val(current).trigger('change');
        }

        // Métodos de pago
        const $pm = $('#payment_method_id');
        if ($pm.length) {
            const current = $pm.val();
            $pm.empty().append('<option value="">Seleccionar método...</option>');
            (ReferenceDataManager.data.metodosPago || []).forEach(m => {
                $pm.append(`<option value="${m.id}">${m.name}</option>`);
            });
            if ($pm.hasClass('select2-hidden-accessible')) { $pm.select2('destroy'); }
            $pm.select2(select2Options);
            if (current) $pm.val(current).trigger('change');
        }

        // Asegurar los botones de refresco tras reinit de Select2
        if (typeof ensureFacturaSelectRefreshButtons === 'function') {
            ensureFacturaSelectRefreshButtons($modal);
        }
    }

    // Inserta botones "Refrescar" junto a client_id y provider_id, sin duplicarlos
    function ensureFacturaSelectRefreshButtons(dropdownParent) {
        const $modal = dropdownParent && dropdownParent.length ? dropdownParent : $('#facturaModal');
        const pairs = [
            { selector: '#client_id', entity: 'clientes' },
            { selector: '#provider_id', entity: 'proveedores' }
        ];
        pairs.forEach(({ selector, entity }) => {
            const $sel = $modal.find(selector);
            if (!$sel.length) return;
            const id = $sel.attr('id');
            const $anchor = $sel.next('.select2').length ? $sel.next('.select2') : $sel;

            // Reutilizar si existe; eliminar duplicados
            let $btns = $modal.find(`.btn-refresh-select[data-for="${id}"]`);
            let $btn = $btns.first();
            if ($btns.length > 1) { $btns.slice(1).remove(); }
            if (!$btn.length) {
                $btn = $(`<button type="button" class="btn btn-outline-secondary btn-sm ml-2 ms-2 btn-refresh-select" title="Refrescar" data-for="${id}" data-entity="${entity}">↻ Refrescar</button>`);
            } else {
                $btn.attr('data-entity', entity);
            }
            // Colocar el botón justo después del contenedor visible del select
            $btn.insertAfter($anchor);
        });
    }

    // Exponer globalmente
    window.ReferenceDataManager = ReferenceDataManager;
    window.populateFacturaSelects = populateFacturaSelects;
    window.setSelect2Value = function(selector, value, label){
        const $el = $(selector);
        if (!$el.length) return;

        const valStr = (value === undefined || value === null) ? '' : String(value);

        // Asegurar que Select2 esté inicializado sobre el elemento
        if (!$el.hasClass('select2-hidden-accessible')) {
            const $modal = $('#facturaModal');
            const select2Options = $modal.length ? { width: '100%', dropdownParent: $modal } : { width: '100%' };
            try { $el.select2(select2Options); } catch(e) { /* noop */ }
        }

        const exists = $el.find(`option[value="${valStr}"]`).length > 0;
        if (exists || valStr === '') {
            $el.val(valStr).trigger('change');
            return;
        }

        // Si no existe la opción, intentar obtener el nombre desde los datos de referencia o usar label provisto
        let text = label;
        if (!text && window.ReferenceDataManager && ReferenceDataManager.data) {
            let entityKey = null;
            if (selector.includes('client_id')) entityKey = 'clientes';
            else if (selector.includes('provider_id')) entityKey = 'proveedores';
            else if (selector.includes('payment_method_id')) entityKey = 'metodosPago';

            if (entityKey && Array.isArray(ReferenceDataManager.data[entityKey])) {
                const match = ReferenceDataManager.data[entityKey].find(it => String(it.id) === valStr);
                if (match) text = match.rut ? `${match.name} (${match.rut})` : (match.name || text);
            }
        }

        const opt = new Option(text || valStr, valStr, true, true);
        $el.append(opt).trigger('change');
    };

    // Actualizar datos cuando se emitan eventos globales
    document.addEventListener('clientes:updated', () => ReferenceDataManager.refresh('clientes').then(() => populateFacturaSelects($('#facturaModal'))));
    document.addEventListener('proveedores:updated', () => ReferenceDataManager.refresh('proveedores').then(() => populateFacturaSelects($('#facturaModal'))));
    document.addEventListener('metodosPago:updated', () => ReferenceDataManager.refresh('metodosPago').then(() => populateFacturaSelects($('#facturaModal'))));

    // Exponer helper para asegurar botones desde fuera si es necesario
    window.ensureFacturaSelectRefreshButtons = ensureFacturaSelectRefreshButtons;
})();

// Iniciar y manejar botones de refresco en el modal de facturas
$(document).on('shown.bs.modal', '#facturaModal', function(){
    if (typeof window.ensureFacturaSelectRefreshButtons === 'function') {
        window.ensureFacturaSelectRefreshButtons($('#facturaModal'));
    }
});

$(document).on('click', '.btn-refresh-select', function(e){
    e.preventDefault();
    const $btn = $(this);
    const entity = $btn.attr('data-entity');
    const $modal = $('#facturaModal');
    if (!entity || !window.ReferenceDataManager) return;

    const originalText = $btn.text();
    $btn.prop('disabled', true).addClass('disabled').text('Actualizando...');

    window.ReferenceDataManager.refresh(entity, { force: true })
        .then(() => {
            if (typeof window.populateFacturaSelects === 'function') {
                window.populateFacturaSelects($modal);
            }
        })
        .finally(() => {
            $btn.prop('disabled', false).removeClass('disabled').text(originalText);
        });
});

function getDTLanguage(isLocal) {
    return {
        // Se mantiene el URL por si quieres cargar traducciones extendidas
        url: isLocal
            ? buildApiUrl('assets/js/comun/plugins/datatable/es-ES.json')
            : '//cdn.datatables.net/plug-ins/2.3.3/i18n/es-ES.json',
        decimal: ',',
        thousands: '.',
        processing: 'Procesando...',
        search: 'Buscar:',
        lengthMenu: 'Mostrar _MENU_ registros',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrado de _MAX_ registros)',
        loadingRecords: 'Cargando...',
        zeroRecords: 'No se encontraron resultados',
        emptyTable: 'No hay datos disponibles en la tabla',
        paginate: {
            first: 'Primero',
            previous: 'Anterior',
            next: 'Siguiente',
            last: 'Último'
        },
        aria: {
            sortAscending: ': activar para ordenar la columna ascendente',
            sortDescending: ': activar para ordenar la columna descendente'
        },
        buttons: {
            copy: 'Copiar',
            excel: 'Excel',
            pdf: 'PDF',
            print: 'Imprimir',
            colvis: 'Columnas'
        }
    };
}

// Manejador global para ver factura: abre la vista rápida del header
window.verFactura = function(id) {
    try {
        if (typeof openInvoiceQuickView === 'function') {
            openInvoiceQuickView(id);
            return;
        }
    } catch (e) { /* noop */ }
    // Fallback: navegar a la vista completa estándar
    window.location.href = buildApiUrl('facturas/' + id);
};

// Helper básico para generar URLs por nombre de ruta (sin Ziggy)
// Actualmente soporta los usos en cotizaciones; se puede extender según necesidad.
if (typeof window.routeUrl !== 'function') {
  window.routeUrl = function(name, param) {
    try {
      switch (name) {
        case 'cotizaciones.show':
          return buildApiUrl('cotizaciones/' + encodeURIComponent(param));
        case 'cotizaciones.edit':
          return buildApiUrl('cotizaciones/' + encodeURIComponent(param) + '/edit');
        case 'cotizaciones.pdf':
          return buildApiUrl('cotizaciones/' + encodeURIComponent(param) + '/pdf');
        case 'cotizaciones.send-email':
          return buildApiUrl('cotizaciones/' + encodeURIComponent(param) + '/send-email');
        case 'cotizaciones.export-upload':
          return buildApiUrl('cotizaciones/' + encodeURIComponent(param) + '/export-upload');
        // Rutas anidadas de clientes
        case 'clientes.files':
          return buildApiUrl('clientes/' + encodeURIComponent(param) + '/files');
        case 'clientes.cotizaciones.files':
          return buildApiUrl('clientes/' + encodeURIComponent(param) + '/cotizaciones/files');
        case 'proveedores.files':
          return buildApiUrl('proveedores/' + encodeURIComponent(param) + '/files');
        // Rutas de archivos
        case 'files.delete':
          return buildApiUrl('files/' + encodeURIComponent(param));
        default:
          // Fallback genérico: convertir puntos a slashes y anexar param si existe
          var base = String(name || '').replace(/\.+/g, '/');
          if (param !== undefined && param !== null && param !== '') {
            return buildApiUrl(base + '/' + encodeURIComponent(param));
          }
          return buildApiUrl(base);
      }
    } catch (e) {
      return '#';
    }
  };
}

/**
 * Recargar tablas de facturas
 */
function reloadInvoiceTables() {
    const ids = [
        '#facturas-table',
        '#cliente-facturas-table',
        '#facturas-clientes-table',
        '#proveedor-facturas-table',
        '#facturas-proveedores-table'
    ];
    ids.forEach(sel => {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(sel)) {
            try { $(sel).DataTable().ajax.reload(null, false); } catch(e) { /* noop */ }
        }
    });
    // Refrescar tablas del Home (datos locales)
    const refreshLocal = (sel, dataArr) => {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(sel)) {
            try {
                const api = $(sel).DataTable();
                api.clear();
                api.rows.add(dataArr || []);
                api.draw(false);
            } catch(e) { /* noop */ }
        }
    };
    refreshLocal('#home-clientes-vencidas', window.__HOME_CLIENTES_VENCIDAS__);
    refreshLocal('#home-proveedores-vencidas', window.__HOME_PROVEEDORES_VENCIDAS__);
    // Recargar filtros si existe la función attachInvoiceFilters
    if (typeof attachInvoiceFilters === 'function') {
        attachInvoiceFilters();
    }
}

// Agregar event listeners globales para el manejo de formularios
$(document).ready(function() {
    // Event listener para normalizar campos CLP antes de envío
    $(document).on('submit', 'form', function(e) {
        if (window.CLInputFormatter && typeof window.CLInputFormatter.normalizeOnSubmit === 'function') {
            window.CLInputFormatter.normalizeOnSubmit(e);
        }
    });
    
    // Event listeners para botones de acción globales
    $(document).on('click', '.btn-action.btn-delete', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const id = $btn.data('id');
        const entity = $btn.data('entity');
        
        if (!id || !entity) {
            console.warn('Missing data-id or data-entity on delete button');
            return;
        }
        
        // Construir la URL de eliminación según la entidad
        let deleteUrl = '';
        switch(entity) {
            case 'factura':
                deleteUrl = buildApiUrl(`facturas/${id}`);
                break;
            case 'cliente':
                deleteUrl = buildApiUrl(`clientes/${id}`);
                break;
            case 'proveedor':
                deleteUrl = buildApiUrl(`proveedores/${id}`);
                break;
            default:
                deleteUrl = buildApiUrl(`${entity}s/${id}`);
        }
        
        // Callback de éxito para recargar la tabla correspondiente
        const successCallback = function() {
            // Intentar recargar la tabla correspondiente
            const tableSelectors = [
                '#cliente-facturas-table',
                '#proveedor-facturas-table',
                '#facturas-table',
                '#clientes-table',
                '#proveedores-table'
            ];
            
            for (let selector of tableSelectors) {
                const $table = $(selector);
                if ($table.length && $.fn.DataTable.isDataTable(selector)) {
                    $table.DataTable().ajax.reload(null, false);
                    break;
                }
            }
        };
        
        // Llamar a la función de eliminación con SweetAlert2
        handleDelete(entity, id, deleteUrl, successCallback);
    });
    
    // Event listeners para otros botones de acción si es necesario
    $(document).on('click', '.btn-action.btn-view', function(e) {
        e.preventDefault();
        const onclick = $(this).attr('onclick');
        if (onclick) {
            eval(onclick);
        }
    });
    
    $(document).on('click', '.btn-action.btn-edit', function(e) {
        e.preventDefault();
        const onclick = $(this).attr('onclick');
        if (onclick) {
            eval(onclick);
        }
    });
    
    $(document).on('click', '.btn-action.btn-download', function(e) {
        e.preventDefault();
        const onclick = $(this).attr('onclick');
        if (onclick) {
            eval(onclick);
        }
    });
    
    // Auto-bind elementos con data-format al cargar la página
    $('[data-format]').each(function() {
        if (window.CLInputFormatter) {
            window.CLInputFormatter.bind(this);
        }
    });
    
    // Auto-bind elementos nuevos cuando se agregan al DOM
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    // Bind elementos con data-format en el nuevo contenido
                    if (node.hasAttribute && node.hasAttribute('data-format')) {
                        if (window.CLInputFormatter) {
                            window.CLInputFormatter.bind(node);
                        }
                    }
                    // También buscar dentro del elemento agregado
                    const formatElements = node.querySelectorAll ? node.querySelectorAll('[data-format]') : [];
                    formatElements.forEach(function(el) {
                        if (window.CLInputFormatter) {
                            window.CLInputFormatter.bind(el);
                        }
                    });
                }
            });
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});