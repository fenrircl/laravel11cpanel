/**
 * ============================================
 * SISTEMA DE CACHÉ LOCAL INTELIGENTE
 * ============================================
 * 
 * Sistema de caché versionado para búsquedas rápidas
 * que minimiza las consultas al servidor y mantiene
 * sincronizados los datos esenciales.
 */

// Verificar si ya está inicializado para evitar redeclaraciones
if (typeof window.searchCacheManager === 'undefined') {

/**
 * Configuración del caché local
 */
const CACHE_CONFIG = {
    // Tiempo de vida del caché en milisegundos (30 minutos)
    TTL: 30 * 60 * 1000,
    
    // Clave base para localStorage
    STORAGE_KEY: 'search_cache',
    
    // Versión del caché (cambiar cuando cambie la estructura)
    VERSION: '1.0.0',
    
    // Número máximo de registros por entidad en caché
    MAX_RECORDS_PER_ENTITY: 1000,
    
    // Campos esenciales por entidad para reducir peso
    ENTITY_FIELDS: {
        clientes: ['id', 'name', 'rut', 'email', 'phone', 'created_at'],
        proveedores: ['id', 'name', 'rut', 'email', 'phone', 'created_at'],
        facturas: ['id', 'numero', 'client_id', 'provider_id', 'total', 'fecha_emision', 'fecha_vencimiento']
    }
};

/**
 * Manager del caché local para búsquedas
 */
class SearchCacheManager {
    constructor() {
        this.cache = this.loadCache();
        this.searchTimers = new Map();
        this.isOnline = navigator.onLine;
        
        // Escuchar cambios de conectividad
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.syncCacheFromServer();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
        });
    }

    /**
     * Cargar caché desde localStorage
     */
    loadCache() {
        try {
            const stored = localStorage.getItem(CACHE_CONFIG.STORAGE_KEY);
            if (!stored) return this.createEmptyCache();
            
            const cache = JSON.parse(stored);
            
            // Verificar versión y TTL
            if (cache.version !== CACHE_CONFIG.VERSION || this.isCacheExpired(cache)) {
                return this.createEmptyCache();
            }
            
            return cache;
        } catch (error) {
            console.warn('Error loading search cache:', error);
            return this.createEmptyCache();
        }
    }

    /**
     * Crear estructura de caché vacía
     */
    createEmptyCache() {
        return {
            version: CACHE_CONFIG.VERSION,
            timestamp: Date.now(),
            lastSync: null,
            entities: {
                clientes: { data: [], hash: null, lastUpdate: null },
                proveedores: { data: [], hash: null, lastUpdate: null },
                facturas: { data: [], hash: null, lastUpdate: null }
            }
        };
    }

    /**
     * Verificar si el caché ha expirado
     */
    isCacheExpired(cache) {
        return (Date.now() - cache.timestamp) > CACHE_CONFIG.TTL;
    }

    /**
     * Guardar caché en localStorage
     */
    saveCache() {
        try {
            this.cache.timestamp = Date.now();
            localStorage.setItem(CACHE_CONFIG.STORAGE_KEY, JSON.stringify(this.cache));
        } catch (error) {
            console.warn('Error saving search cache:', error);
            // Si localStorage está lleno, limpiar caché antiguo
            this.clearCache();
        }
    }

    /**
     * Limpiar caché
     */
    clearCache() {
        this.cache = this.createEmptyCache();
        localStorage.removeItem(CACHE_CONFIG.STORAGE_KEY);
    }

    /**
     * Actualizar datos de una entidad en el caché
     */
    updateEntityCache(entity, data, hash = null) {
        if (!this.cache.entities[entity]) return;
        
        // Filtrar solo campos esenciales y limitar cantidad
        const fields = CACHE_CONFIG.ENTITY_FIELDS[entity];
        const filteredData = data
            .slice(0, CACHE_CONFIG.MAX_RECORDS_PER_ENTITY)
            .map(item => {
                const filtered = {};
                fields.forEach(field => {
                    if (item[field] !== undefined) {
                        filtered[field] = item[field];
                    }
                });
                return filtered;
            });
        
        this.cache.entities[entity] = {
            data: filteredData,
            hash: hash || this.generateDataHash(filteredData),
            lastUpdate: Date.now()
        };
        
        this.saveCache();
        console.log(`✓ Caché actualizado para ${entity}: ${filteredData.length} registros`);
    }

    /**
     * Generar hash simple para detectar cambios
     */
    generateDataHash(data) {
        const str = JSON.stringify(data);
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convertir a 32-bit integer
        }
        return hash.toString();
    }

    /**
     * Obtener datos de una entidad desde el caché
     */
    getEntityData(entity) {
        return this.cache.entities[entity]?.data || [];
    }

    /**
     * Verificar si una entidad necesita actualización
     */
    needsUpdate(entity) {
        const entityCache = this.cache.entities[entity];
        if (!entityCache || !entityCache.lastUpdate) return true;
        
        // Verificar TTL por entidad
        const age = Date.now() - entityCache.lastUpdate;
        return age > (CACHE_CONFIG.TTL / 2); // Actualizar a la mitad del TTL
    }

    /**
     * Sincronizar caché con el servidor
     */
    async syncCacheFromServer() {
        if (!this.isOnline) return;
        
        const entities = ['clientes', 'proveedores', 'facturas'];
        const promises = entities.map(entity => this.syncEntityFromServer(entity));
        
        try {
            await Promise.allSettled(promises);
            this.cache.lastSync = Date.now();
            this.saveCache();
        } catch (error) {
            console.warn('Error syncing cache:', error);
        }
    }

    /**
     * Sincronizar una entidad específica desde el servidor
     */
    async syncEntityFromServer(entity) {
        try {
            const response = await fetch(buildApiUrl(`${entity}/search-data`), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const result = await response.json();
            const data = result.data || result;
            const hash = result.hash || null;
            
            // Solo actualizar si hay cambios
            if (hash && this.cache.entities[entity]?.hash !== hash) {
                this.updateEntityCache(entity, data, hash);
            } else if (!hash) {
                this.updateEntityCache(entity, data);
            }
            
        } catch (error) {
            console.warn(`Error syncing ${entity}:`, error);
        }
    }

    /**
     * Buscar en el caché local
     */
    searchInCache(query, entity = 'all') {
        const normalizedQuery = query.toLowerCase().trim();
        if (normalizedQuery.length < 2) return [];
        
        let results = [];
        const entities = entity === 'all' ? ['clientes', 'proveedores', 'facturas'] : [entity];
        
        entities.forEach(entityName => {
            const data = this.getEntityData(entityName);
            const entityResults = this.searchInEntityData(data, normalizedQuery, entityName);
            results.push(...entityResults);
        });
        
        // Ordenar por relevancia
        return this.sortResultsByRelevance(results, normalizedQuery);
    }

    /**
     * Buscar dentro de los datos de una entidad
     */
    searchInEntityData(data, query, entityName) {
        return data
            .filter(item => this.itemMatchesQuery(item, query, entityName))
            .map(item => this.formatSearchResult(item, entityName, query))
            .slice(0, 10); // Limitar resultados por entidad
    }

    /**
     * Verificar si un item coincide con la búsqueda
     */
    itemMatchesQuery(item, query, entityName) {
        const searchFields = this.getSearchFields(entityName);
        
        return searchFields.some(field => {
            const value = item[field];
            if (!value) return false;
            
            const stringValue = value.toString().toLowerCase();
            
            // Búsqueda por coincidencia exacta o parcial
            if (stringValue.includes(query)) return true;
            
            // Búsqueda por fecha (si el campo es fecha)
            if (field.includes('fecha') || field.includes('_at')) {
                return this.matchesDateSearch(value, query);
            }
            
            // Búsqueda numérica (para montos, números, etc.)
            if (field === 'total' || field === 'numero') {
                return this.matchesNumericSearch(value, query);
            }
            
            return false;
        });
    }

    /**
     * Obtener campos de búsqueda por entidad
     */
    getSearchFields(entityName) {
        const fieldsMap = {
            clientes: ['name', 'email', 'phone'],
            proveedores: ['name', 'email', 'phone'],
            facturas: ['numero', 'total', 'fecha_emision', 'fecha_vencimiento']
        };
        
        return fieldsMap[entityName] || [];
    }

    /**
     * Verificar coincidencia en fechas
     */
    matchesDateSearch(dateValue, query) {
        if (!dateValue) return false;
        
        const date = new Date(dateValue);
        const dateStr = date.toLocaleDateString('es-ES');
        const dateStrShort = date.toLocaleDateString('es-ES', { 
            year: '2-digit', 
            month: '2-digit', 
            day: '2-digit' 
        });
        
        return dateStr.includes(query) || dateStrShort.includes(query);
    }

    /**
     * Verificar coincidencia numérica
     */
    matchesNumericSearch(value, query) {
        if (!value) return false;
        
        const numStr = value.toString();
        return numStr.includes(query);
    }

    /**
     * Formatear resultado de búsqueda
     */
    formatSearchResult(item, entityName, query) {
        const result = {
            id: item.id,
            entity: entityName,
            title: this.getResultTitle(item, entityName),
            subtitle: this.getResultSubtitle(item, entityName),
            meta: this.getResultMeta(item, entityName),
            data: item,
            relevance: this.calculateRelevance(item, query, entityName)
        };
        
        // Destacar texto coincidente
        result.title = this.highlightMatches(result.title, query);
        result.subtitle = this.highlightMatches(result.subtitle, query);
        
        return result;
    }

    /**
     * Obtener título del resultado
     */
    getResultTitle(item, entityName) {
        switch (entityName) {
            case 'clientes':
            case 'proveedores':
                return item.name || `${entityName.slice(0, -1)} #${item.id}`;
            case 'facturas':
                return `Factura #${item.numero || item.id}`;
            default:
                return `Registro #${item.id}`;
        }
    }

    /**
     * Obtener subtítulo del resultado
     */
    getResultSubtitle(item, entityName) {
        switch (entityName) {
            case 'clientes':
            case 'proveedores':
                return item.email || item.phone || 'Sin información de contacto';
            case 'facturas':
                const fecha = item.fecha_emision ? new Date(item.fecha_emision).toLocaleDateString('es-ES') : 'Sin fecha';
                return `Fecha: ${fecha}`;
            default:
                return '';
        }
    }

    /**
     * Obtener metadatos del resultado
     */
    getResultMeta(item, entityName) {
        switch (entityName) {
            case 'facturas':
                return item.total ? formatCurrency(item.total) : '';
            default:
                return '';
        }
    }

    /**
     * Calcular relevancia del resultado
     */
    calculateRelevance(item, query, entityName) {
        let score = 0;
        const searchFields = this.getSearchFields(entityName);
        
        searchFields.forEach(field => {
            const value = item[field];
            if (!value) return;
            
            const stringValue = value.toString().toLowerCase();
            const index = stringValue.indexOf(query);
            
            if (index === 0) {
                score += 10; // Coincidencia al inicio
            } else if (index > 0) {
                score += 5; // Coincidencia en el medio
            }
            
            // Bonus por campo principal
            if (field === 'name' || field === 'numero') {
                score += 3;
            }
        });
        
        return score;
    }

    /**
     * Ordenar resultados por relevancia
     */
    sortResultsByRelevance(results, query) {
        return results.sort((a, b) => {
            // Primero por relevancia
            if (b.relevance !== a.relevance) {
                return b.relevance - a.relevance;
            }
            
            // Luego alfabéticamente
            return a.title.localeCompare(b.title);
        });
    }

    /**
     * Destacar coincidencias en el texto
     */
    highlightMatches(text, query) {
        if (!text || !query) return text;
        
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<span class="search-highlight">$1</span>');
    }

    /**
     * Obtener estadísticas del caché
     */
    getCacheStats() {
        const stats = {
            version: this.cache.version,
            lastSync: this.cache.lastSync,
            timestamp: this.cache.timestamp,
            isExpired: this.isCacheExpired(this.cache),
            entities: {}
        };
        
        Object.entries(this.cache.entities).forEach(([entity, data]) => {
            stats.entities[entity] = {
                records: data.data?.length || 0,
                lastUpdate: data.lastUpdate,
                needsUpdate: this.needsUpdate(entity)
            };
        });
        
        return stats;
    }

    /**
     * Inicialización del caché
     */
    async initialize() {
        // Sincronizar si es necesario
        if (!this.cache.lastSync || this.isCacheExpired(this.cache)) {
            await this.syncCacheFromServer();
        }
        
        // Verificar entidades que necesitan actualización
        const entities = Object.keys(this.cache.entities);
        for (const entity of entities) {
            if (this.needsUpdate(entity)) {
                this.syncEntityFromServer(entity); // Async sin await para no bloquear
            }
        }
        
        console.log('✓ SearchCacheManager inicializado', this.getCacheStats());
    }
}

// Instancia global del cache manager
window.searchCacheManager = new SearchCacheManager();

// Exponer funciones útiles globalmente
window.SearchCacheHelpers = {
    getStats: () => window.searchCacheManager.getCacheStats(),
    clearCache: () => window.searchCacheManager.clearCache(),
    syncCache: () => window.searchCacheManager.syncCacheFromServer(),
    search: (query, entity) => window.searchCacheManager.searchInCache(query, entity)
};

} // Fin de la verificación de inicialización
