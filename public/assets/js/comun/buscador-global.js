/**
 * ============================================
 * BUSCADOR GLOBAL INTELIGENTE
 * ============================================
 * 
 * Sistema de búsqueda global que combina caché local
 * con búsquedas en servidor siguiendo principios SOLID
 */

// Verificar si ya está inicializado para evitar redeclaraciones
if (typeof window.globalSearchManager === 'undefined') {

/**
 * Configuración del buscador
 */
const SEARCH_CONFIG = {
    // Delay para debounce en milisegundos
    DEBOUNCE_DELAY: 300,
    
    // Mínimo de caracteres para activar búsqueda
    MIN_QUERY_LENGTH: 2,
    
    // Máximo de resultados a mostrar
    MAX_RESULTS: 50,
    
    // Tiempo máximo de espera para respuesta del servidor
    TIMEOUT: 5000
};

/**
 * Clase principal del buscador global
 * Siguiendo el principio de Responsabilidad Única (SOLID)
 */
class GlobalSearchManager {
    constructor() {
        this.searchInput = document.getElementById('search-input');
        this.searchButton = document.getElementById('search-button');
        this.searchEntity = document.getElementById('search-entity');
        this.searchResults = document.getElementById('search-results');
        
        this.currentQuery = '';
        this.currentEntity = 'all';
        this.isSearching = false;
        this.searchAbortController = null;
        this.debounceTimer = null;
        
        this.initializeEventListeners();
    }

    /**
     * Inicializar event listeners
     */
    initializeEventListeners() {
        // Búsqueda manual con botón
        this.searchButton?.addEventListener('click', () => {
            this.performSearch();
        });

        // Búsqueda al presionar Enter
        this.searchInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch();
            }
        });

        // Búsqueda automática con debounce (opcional)
        this.searchInput?.addEventListener('input', (e) => {
            this.currentQuery = e.target.value.trim();
            
            // Limpiar resultados si query es muy corto
            if (this.currentQuery.length < SEARCH_CONFIG.MIN_QUERY_LENGTH) {
                this.hideResults();
                return;
            }

            // Búsqueda con debounce solo en caché local
            this.debouncedCacheSearch();
        });

        // Cambio de entidad
        this.searchEntity?.addEventListener('change', (e) => {
            this.currentEntity = e.target.value;
            if (this.currentQuery.length >= SEARCH_CONFIG.MIN_QUERY_LENGTH) {
                this.performSearch();
            }
        });

        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.header-search-container')) {
                this.hideResults();
            }
        });

        // Manejar ESC para cerrar
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideResults();
                this.searchInput?.blur();
            }
        });
    }

    /**
     * Búsqueda con debounce solo en caché local
     */
    debouncedCacheSearch() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.searchInCacheOnly();
        }, SEARCH_CONFIG.DEBOUNCE_DELAY);
    }

    /**
     * Buscar solo en caché local (sin servidor)
     */
    searchInCacheOnly() {
        if (!window.searchCacheManager) return;

        const results = window.searchCacheManager.searchInCache(this.currentQuery, this.currentEntity);
        
        if (results.length > 0) {
            this.displayResults(results, true); // true = solo caché
        } else {
            this.showNoResults('No se encontraron resultados en caché. Presiona Buscar para búsqueda completa.');
        }
    }

    /**
     * Realizar búsqueda completa (caché + servidor)
     */
    async performSearch() {
        const query = this.searchInput?.value.trim() || '';
        
        if (query.length < SEARCH_CONFIG.MIN_QUERY_LENGTH) {
            this.showError('Ingresa al menos 2 caracteres para buscar');
            return;
        }

        this.currentQuery = query;
        this.currentEntity = this.searchEntity?.value || 'all';

        // Cancelar búsqueda anterior si existe
        if (this.searchAbortController) {
            this.searchAbortController.abort();
        }

        this.isSearching = true;
        this.showLoading();

        try {
            // Primero mostrar resultados de caché si existen
            const cacheResults = window.searchCacheManager?.searchInCache(query, this.currentEntity) || [];
            
            if (cacheResults.length > 0) {
                this.displayResults(cacheResults, true);
            }

            // Luego buscar en servidor para resultados más completos
            const serverResults = await this.searchInServer(query, this.currentEntity);
            
            // Combinar y deduplicar resultados
            const combinedResults = this.combineResults(cacheResults, serverResults);
            this.displayResults(combinedResults, false);

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Search error:', error);
                this.showError('Error en la búsqueda. Inténtalo nuevamente.');
            }
        } finally {
            this.isSearching = false;
        }
    }

    /**
     * Buscar en el servidor
     */
    async searchInServer(query, entity) {
        this.searchAbortController = new AbortController();
        
        const url = buildApiUrl('buscar');
        const params = new URLSearchParams({
            q: query,
            entity: entity,
            limit: SEARCH_CONFIG.MAX_RESULTS
        });

        const response = await fetch(`${url}?${params}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: this.searchAbortController.signal,
            timeout: SEARCH_CONFIG.TIMEOUT
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        return this.formatServerResults(data.results || data);
    }

    /**
     * Formatear resultados del servidor
     */
    formatServerResults(serverData) {
        const results = [];
        
        Object.entries(serverData).forEach(([entity, items]) => {
            if (Array.isArray(items)) {
                items.forEach(item => {
                    results.push({
                        id: item.id,
                        entity: entity,
                        title: this.getItemTitle(item, entity),
                        subtitle: this.getItemSubtitle(item, entity),
                        meta: this.getItemMeta(item, entity),
                        data: item,
                        relevance: item.relevance || 0
                    });
                });
            }
        });

        return results;
    }

    /**
     * Combinar resultados de caché y servidor, eliminando duplicados
     */
    combineResults(cacheResults, serverResults) {
        const combined = [...cacheResults];
        const existingIds = new Set(cacheResults.map(r => `${r.entity}-${r.id}`));

        serverResults.forEach(serverResult => {
            const key = `${serverResult.entity}-${serverResult.id}`;
            if (!existingIds.has(key)) {
                combined.push(serverResult);
            }
        });

        // Ordenar por relevancia y limitar resultados
        return combined
            .sort((a, b) => (b.relevance || 0) - (a.relevance || 0))
            .slice(0, SEARCH_CONFIG.MAX_RESULTS);
    }

    /**
     * Mostrar resultados en el dropdown
     */
    displayResults(results, isCacheOnly = false) {
        if (!this.searchResults) return;

        if (results.length === 0) {
            this.showNoResults(isCacheOnly ? 
                'No se encontraron resultados en caché local. Presiona "Buscar" para búsqueda completa en servidor.' : 
                'No se encontraron resultados para tu búsqueda.'
            );
            return;
        }

        // Usar DocumentFragment para mejor rendimiento
        const fragment = document.createDocumentFragment();
        const container = document.createElement('div');
        
        // Agrupar resultados por entidad
        const groupedResults = this.groupResultsByEntity(results);
        
        let html = '';
        
        // Indicador de fuente de datos
        if (isCacheOnly) {
            html += '<div class="search-cache-indicator" style="padding: 8px 16px; background: #e8f5e8; color: #2d5a2d; font-size: 0.85rem; border-bottom: 1px solid #d4edda;"><i class="fas fa-memory me-2"></i>Resultados desde caché local</div>';
        }

        // Renderizar grupos
        Object.entries(groupedResults).forEach(([entity, items]) => {
            html += this.renderEntityGroup(entity, items);
        });

        // Actualizar contenido de una vez para evitar reflow múltiple
        container.innerHTML = html;
        
        // Limpiar y actualizar en un solo paso
        this.searchResults.innerHTML = '';
        this.searchResults.appendChild(container);
        
        this.showResults();
        
        // Agregar event listeners a los resultados
        requestAnimationFrame(() => {
            this.attachResultEventListeners();
        });
    }

    /**
     * Agrupar resultados por entidad
     */
    groupResultsByEntity(results) {
        const grouped = {};
        
        results.forEach(result => {
            if (!grouped[result.entity]) {
                grouped[result.entity] = [];
            }
            grouped[result.entity].push(result);
        });

        return grouped;
    }

    /**
     * Renderizar grupo de entidad
     */
    renderEntityGroup(entity, items) {
        const entityNames = {
            clientes: 'Clientes',
            proveedores: 'Proveedores',
            facturas: 'Facturas'
        };

        const entityName = entityNames[entity] || entity;
        let html = `<div class="search-section-header">${entityName} (${items.length})</div>`;

        items.forEach(item => {
            html += this.renderResultItem(item);
        });

        return html;
    }

    /**
     * Renderizar item de resultado
     */
    renderResultItem(result) {
        const iconClass = this.getEntityIcon(result.entity);
        
        return `
            <div class="search-result-item" 
                 data-entity="${result.entity}" 
                 data-id="${result.id}" 
                 data-action="view">
                <div class="search-result-icon ${result.entity}">
                    <i class="${iconClass}"></i>
                </div>
                <div class="search-result-content">
                    <div class="search-result-title">${result.title}</div>
                    <div class="search-result-subtitle">${result.subtitle}</div>
                    ${result.meta ? `<div class="search-result-meta">${result.meta}</div>` : ''}
                </div>
            </div>
        `;
    }

    /**
     * Obtener icono de entidad
     */
    getEntityIcon(entity) {
        const icons = {
            clientes: 'fas fa-user',
            proveedores: 'fas fa-truck',
            facturas: 'fas fa-file-invoice'
        };
        
        return icons[entity] || 'fas fa-circle';
    }

    /**
     * Obtener título del item
     */
    getItemTitle(item, entity) {
        switch (entity) {
            case 'clientes':
            case 'proveedores':
                return item.name || `${entity.slice(0, -1)} #${item.id}`;
            case 'facturas':
                return `Factura #${item.numero || item.id}`;
            default:
                return `Registro #${item.id}`;
        }
    }

    /**
     * Obtener subtítulo del item
     */
    getItemSubtitle(item, entity) {
        switch (entity) {
            case 'clientes':
            case 'proveedores':
                const contactInfo = [];
                if (item.email) contactInfo.push(`📧 ${item.email}`);
                if (item.phone) contactInfo.push(`📞 ${item.phone}`);
                return contactInfo.length > 0 ? contactInfo.join(' • ') : 'Sin información de contacto';
            case 'facturas':
                const infoItems = [];
                if (item.cliente_name) infoItems.push(`👤 ${item.cliente_name}`);
                if (item.proveedor_name) infoItems.push(`🏢 ${item.proveedor_name}`);
                if (item.fecha_emision) {
                    const fecha = new Date(item.fecha_emision).toLocaleDateString('es-ES');
                    infoItems.push(`📅 ${fecha}`);
                }
                return infoItems.length > 0 ? infoItems.join(' • ') : 'Sin información adicional';
            default:
                return '';
        }
    }

    /**
     * Obtener metadatos del item
     */
    getItemMeta(item, entity) {
        switch (entity) {
            case 'facturas':
                return item.total ? formatCurrency(item.total) : '';
            default:
                return '';
        }
    }

    /**
     * Agregar event listeners a los resultados
     */
    attachResultEventListeners() {
        const resultItems = this.searchResults?.querySelectorAll('.search-result-item');
        
        resultItems?.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                
                const entity = item.dataset.entity;
                const id = item.dataset.id;
                const action = item.dataset.action || 'view';
                
                this.handleResultClick(entity, id, action);
                this.hideResults();
            });
        });
    }

    /**
     * Manejar clic en resultado
     */
    handleResultClick(entity, id, action) {
        switch (action) {
            case 'view':
                this.viewEntity(entity, id);
                break;
            case 'edit':
                this.editEntity(entity, id);
                break;
            default:
                console.log(`Action not implemented: ${action}`);
        }
    }

    /**
     * Ver entidad
     */
    viewEntity(entity, id) {
        const functionMap = {
            clientes: 'verCliente',
            proveedores: 'verProveedor',
            facturas: 'verFactura'
        };

        const functionName = functionMap[entity];
        if (functionName && typeof window[functionName] === 'function') {
            window[functionName](id);
        } else {
            // Fallback: redirigir a la página de la entidad
            const urls = {
                clientes: `clientes`,
                proveedores: `proveedores`,
                facturas: `facturas`
            };
            
            if (urls[entity]) {
                window.location.href = buildApiUrl(urls[entity]);
            }
        }
    }

    /**
     * Editar entidad
     */
    editEntity(entity, id) {
        const functionMap = {
            clientes: 'editarCliente',
            proveedores: 'editarProveedor',
            facturas: 'editarFactura'
        };

        const functionName = functionMap[entity];
        if (functionName && typeof window[functionName] === 'function') {
            window[functionName](id);
        }
    }

    /**
     * Mostrar estado de carga
     */
    showLoading() {
        if (!this.searchResults) return;
        
        this.searchResults.innerHTML = `
            <div class="search-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <span class="ms-2">Buscando...</span>
            </div>
        `;
        this.showResults();
    }

    /**
     * Mostrar mensaje de sin resultados
     */
    showNoResults(message = 'No se encontraron resultados') {
        if (!this.searchResults) return;
        
        this.searchResults.innerHTML = `
            <div class="search-no-results">
                <i class="fas fa-search mb-2"></i>
                <div>${message}</div>
            </div>
        `;
        this.showResults();
    }

    /**
     * Mostrar error
     */
    showError(message) {
        if (!this.searchResults) return;
        
        this.searchResults.innerHTML = `
            <div class="search-error">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        `;
        this.showResults();
    }

    /**
     * Mostrar dropdown de resultados
     */
    showResults() {
        this.searchResults?.classList.add('show');
    }

    /**
     * Ocultar dropdown de resultados
     */
    hideResults() {
        this.searchResults?.classList.remove('show');
    }

    /**
     * Limpiar búsqueda
     */
    clearSearch() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        this.currentQuery = '';
        this.hideResults();
    }
}

/**
 * Inicializar buscador global cuando el DOM esté listo
 */
document.addEventListener('DOMContentLoaded', async function() {
    // Esperar a que el cache manager esté disponible
    if (window.searchCacheManager) {
        await window.searchCacheManager.initialize();
    }
    
    // Inicializar buscador global
    window.globalSearchManager = new GlobalSearchManager();
    
    console.log('✓ Buscador global inicializado');
});

/**
 * Funciones de utilidad globales
 */
window.GlobalSearchHelpers = {
    search: (query, entity = 'all') => {
        if (window.globalSearchManager) {
            window.globalSearchManager.searchInput.value = query;
            window.globalSearchManager.searchEntity.value = entity;
            window.globalSearchManager.performSearch();
        }
    },
    
    clearSearch: () => {
        if (window.globalSearchManager) {
            window.globalSearchManager.clearSearch();
        }
    },
    
    isSearching: () => {
        return window.globalSearchManager?.isSearching || false;
    }
};

} // Fin de la verificación de inicialización
