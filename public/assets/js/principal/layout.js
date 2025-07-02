// Funciones principales del layout y sidebar
$(document).ready(function() {
    // Configuración del sidebar
    initSidebar();
    
    // Configuración de DataTables responsive
    initDataTablesResize();
    
    // Configuración de modales
    initModals();
    
    // Configuración de tooltips
    initTooltips();
});

/**
 * Inicializar funcionalidad del sidebar
 */
function initSidebar() {
    $('#sidebarToggle').on('click', function() {
        $('#sidebar').toggleClass('collapsed');
        $('#content').toggleClass('expanded');
        
        // Redimensionar DataTables después de la transición
        setTimeout(function() {
            resizeAllDataTables();
        }, 350); // Esperar a que termine la transición (300ms + buffer)
        
        // Guardar estado en localStorage para recordar preferencia
        if ($('#sidebar').hasClass('collapsed')) {
            localStorage.setItem('sidebarState', 'collapsed');
        } else {
            localStorage.setItem('sidebarState', 'expanded');
        }
    });
    
    // Restaurar estado del sidebar
    var sidebarState = localStorage.getItem('sidebarState');
    if (sidebarState === 'collapsed') {
        $('#sidebar').addClass('collapsed');
        $('#content').addClass('expanded');
        // Redimensionar DataTables después de restaurar el estado
        setTimeout(function() {
            resizeAllDataTables();
        }, 100);
    }
}

/**
 * Inicializar redimensionamiento de DataTables
 */
function initDataTablesResize() {
    // Redimensionar DataTables cuando cambie el tamaño de la ventana
    $(window).on('resize', function() {
        setTimeout(function() {
            resizeAllDataTables();
        }, 100);
    });
}

/**
 * Inicializar configuración de modales
 */
function initModals() {
    // Limpiar formularios cuando se cierre un modal
    $('.modal').on('hidden.bs.modal', function() {
        const form = $(this).find('form')[0];
        if (form) {
            form.reset();
            $(form).removeClass('was-validated');
            $(form).find('.is-invalid').removeClass('is-invalid');
            $(form).find('.is-valid').removeClass('is-valid');
        }
    });
    
    // Auto-focus en el primer input cuando se abra un modal
    $('.modal').on('shown.bs.modal', function() {
        $(this).find('input:not([type="hidden"]):first').focus();
    });
}

/**
 * Inicializar tooltips
 */
function initTooltips() {
    // Inicializar tooltips de Bootstrap
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

/**
 * Mostrar loading en un elemento
 * @param {string} elementId - ID del elemento
 */
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add('loading');
        element.style.pointerEvents = 'none';
    }
}

/**
 * Ocultar loading de un elemento
 * @param {string} elementId - ID del elemento
 */
function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove('loading');
        element.style.pointerEvents = 'auto';
    }
}

/**
 * Mostrar notificación toast
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de notificación (success, error, warning, info)
 */
function showToast(message, type = 'info') {
    // Implementar sistema de toast personalizado o usar SweetAlert2
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    const iconMap = {
        'success': 'success',
        'error': 'error',
        'warning': 'warning',
        'info': 'info'
    };

    Toast.fire({
        icon: iconMap[type] || 'info',
        title: message
    });
}

/**
 * Configurar validación en tiempo real para formularios
 * @param {string} formId - ID del formulario
 */
function setupFormValidation(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    // Validación en tiempo real
    form.addEventListener('input', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') {
            validateField(e.target);
        }
    });

    // Validación al enviar
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
}

/**
 * Validar un campo específico
 * @param {HTMLElement} field - Campo a validar
 */
function validateField(field) {
    if (field.checkValidity()) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
    }
}

/**
 * Scroll suave a un elemento
 * @param {string} elementId - ID del elemento
 */
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

/**
 * Detectar si el dispositivo es móvil
 * @returns {boolean}
 */
function isMobile() {
    return window.innerWidth <= 768;
}

/**
 * Configurar comportamiento responsivo
 */
function setupResponsiveBehavior() {
    // Adaptar sidebar en móviles
    if (isMobile()) {
        $('#sidebar').addClass('d-none d-md-block');
        // Agregar overlay para móviles si el sidebar está abierto
        if ($('#sidebar').hasClass('show')) {
            $('body').append('<div class="sidebar-overlay" onclick="closeMobileSidebar()"></div>');
        }
    }
}

/**
 * Cerrar sidebar en móviles
 */
function closeMobileSidebar() {
    $('#sidebar').removeClass('show');
    $('.sidebar-overlay').remove();
}

// Ejecutar configuración responsiva al redimensionar
$(window).on('resize', debounce(setupResponsiveBehavior, 250));

/**
 * Función debounce para optimizar eventos de resize
 * @param {Function} func - Función a ejecutar
 * @param {number} wait - Tiempo de espera en ms
 * @returns {Function}
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
