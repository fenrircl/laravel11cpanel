$(document).ready(function() {
    console.log('Facturas Proveedores DataTable initialized');

    // Precargar datos necesarios para esta vista
    if (window.ReferenceDataManager) {
        ReferenceDataManager.ensureLoaded(['proveedores', 'metodosPago']);
    }

    // Refrescar datasets si hay cambios en proveedores o métodos de pago
    document.addEventListener('proveedores:updated', () => ReferenceDataManager.refresh('proveedores'));
    document.addEventListener('metodosPago:updated', () => ReferenceDataManager.refresh('metodosPago'));
    
    // Inicializar eventos del modal de detalles
    initModalEvents();
    
    // Detectar si viene desde Home con filtro de pendientes o desde URLeady(function() {
    console.log('Facturas Proveedor DataTable initialized');

    // Precargar datos necesarios para esta vista
    if (window.ReferenceDataManager) {
        ReferenceDataManager.ensureLoaded(['proveedores', 'metodosPago']);
    }

    // Refrescar datasets si hay cambios en proveedores o métodos de pago
    document.addEventListener('proveedores:updated', () => ReferenceDataManager.refresh('proveedores'));
    document.addEventListener('metodosPago:updated', () => ReferenceDataManager.refresh('metodosPago'));
    
    // Detectar si viene desde Home con filtro de pendientes o desde URL
    const urlParams = new URLSearchParams(window.location.search);
    const filterParam = urlParams.get('filter');
    const estadoParam = urlParams.get('estado');
    
    // También verificar estado temporal
    const tempFilters = sessionStorage.getItem(`temp-filters-facturas-proveedores-table`);
    let shouldFilterPending = filterParam === 'pending';
    let shouldFilterByStatus = estadoParam === '0';
    
    if (tempFilters) {
        try {
            const parsed = JSON.parse(tempFilters);
            shouldFilterPending = shouldFilterPending || parsed.filter === 'pending';
            shouldFilterByStatus = shouldFilterByStatus || parsed.estado === '0';
        } catch (e) {
            console.warn('Error parsing temp filters:', e);
        }
    }
    
    // Funciones para calcular y renderizar días hasta/desde vencimiento
    function calculateDaysToExpiry(expiryDate) {
        if (!expiryDate) return null;
        const today = new Date();
        const expiry = new Date(expiryDate);
        today.setHours(0, 0, 0, 0);
        expiry.setHours(0, 0, 0, 0);
        const diffTime = expiry - today;
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }

    function renderDaysCounter(expiryDate, status) {
        // Solo mostrar días si la factura está pendiente (status = 0)
        if (status !== 0 && status !== '0') {
            return '—'; // Factura pagada, no mostrar días
        }
        
        const days = calculateDaysToExpiry(expiryDate);
        if (days === null) return '—';
        
        if (days > 0) {
            // Próximo a vencer
            if (days <= 7) {
                return `<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>${days} días para vencer</span>`;
            } else if (days <= 30) {
                return `<span class="badge bg-info"><i class="fas fa-clock me-1"></i>${days} días para vencer</span>`;
            } else {
                return `<span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>${days} días para vencer</span>`;
            }
        } else if (days === 0) {
            return `<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Hoy</span>`;
        } else {
            // Vencida
            const daysOverdue = Math.abs(days);
            return `<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>${daysOverdue} días vencida</span>`;
        }
    }
    
    // Configuración de columnas para la tabla de facturas de proveedores
    const columns = [
        {
            data: 'invoice', 
            name: 'invoice', 
            title: 'Factura',
            render: function(data, type, row) {
                if (type === 'sort' || type === 'type') {
                    // Para ordenamiento, extraer el número de la factura y convertirlo a entero
                    const match = String(data || '').match(/\d+/);
                    return match ? parseInt(match[0], 10) : 0;
                }
                return data || '';
            }
        },
        {
            data: 'proveedor.name', 
            name: 'proveedor.name',
            title: 'Proveedor',
            render: function(data, type, row) {
                 if (type === 'export') {
                    // Si data ya viene como HTML, extraer solo el texto
                    if (typeof data === 'string') {
                        // Usar DOMParser para extraer el texto del span
                        const div = document.createElement('div');
                        div.innerHTML = data;
                        return div.textContent || div.innerText || 'N/A';
                    }
                    return data || 'N/A';
                }
                const name = data || 'N/A';
                return `<span class="text-truncate d-inline-block" style="max-width:240px" title="${name}">${name}</span>`;
            }
        },
        { 
            data: 'date', 
            name: 'date', 
            title: 'Fecha', 
            render: function(data, type, row) {
                if (type === 'sort' || type === 'type') {
                    // Para ordenamiento, retornar timestamp
                    return data ? new Date(data).getTime() : 0;
                }
                if (type === 'export') {
                    // Para exportación, formato ISO (YYYY-MM-DD) que Excel entiende mejor
                    if (!data) return '';
                    try {
                        const date = new Date(data);
                        if (isNaN(date.getTime())) return '';
                        return date.toISOString().split('T')[0];
                    } catch(e) {
                        return '';
                    }
                }
                return formatTableDate(data, false);
            }
        },
        { 
            data: 'expiry', 
            name: 'expiry', 
            title: 'Vencimiento', 
            render: function(data, type, row) {
                if (type === 'sort' || type === 'type') {
                    // Para ordenamiento, retornar timestamp (fechas vacías al final)
                    return data ? new Date(data).getTime() : 9999999999999;
                }
                if (type === 'export') {
                    // Para exportación, formato ISO que Excel entiende
                    if (!data) return '';
                    try {
                        const date = new Date(data);
                        if (isNaN(date.getTime())) return '';
                        return date.toISOString().split('T')[0];
                    } catch(e) {
                        return '';
                    }
                }
                return data ? formatTableDate(data, false) : 'N/A';
            }
        },
        {
            data: null,
            name: 'days_counter',
            title: 'Días',
            orderable: true,
            searchable: false,
            render: function(data, type, row) {
                const expiryDate = row.expiry || row.date || '';
                if (type === 'sort' || type === 'type') {
                    // Para ordenamiento, retornar los días calculados solo si está pendiente
                    if (row.status !== 0 && row.status !== '0') {
                        return 999999; // Facturas pagadas van al final
                    }
                    return calculateDaysToExpiry(expiryDate) || 999999;
                }
                if (type === 'export') {
                    // Para exportación, texto plano sin badges HTML
                    if (row.status !== 0 && row.status !== '0') {
                        return '—'; // Factura pagada
                    }
                    const days = calculateDaysToExpiry(expiryDate);
                    if (days === null) return '—';
                    
                    if (days > 0) {
                        return `${days} días para vencer`;
                    } else if (days === 0) {
                        return 'Hoy';
                    } else {
                        return `${Math.abs(days)} días vencida`;
                    }
                }
                return renderDaysCounter(expiryDate, row.status);
            }
        },
        { data: 'pay_date', name: 'pay_date', title: 'Fecha Pago', render: function(data, type, row) {
            if (type === 'sort' || type === 'type') {
                // Para ordenamiento, retornar timestamp (fechas vacías al final)
                return data ? new Date(data).getTime() : 9999999999999;
            }
            if (type === 'export') {
                    // Para exportación, formato ISO que Excel entiende
                    if (!data) return '';
                    try {
                        const date = new Date(data);
                        if (isNaN(date.getTime())) return '';
                        return date.toISOString().split('T')[0];
                    } catch(e) {
                        return '';
                    }
                }
            return data ? formatTableDate(data, false) : 'N/A';
        }},
        { data: 'amount', name: 'amount', title: 'Monto', render: function(data, type, row) {
            if (type === 'export') {
                    // Eliminar cualquier símbolo de moneda y separadores de miles
                    // Ejemplo: "$ 107.100" -> "107100"
                    if (typeof data === 'string') {
                        // Quitar símbolo de moneda y espacios
                        let cleaned = data.replace(/[^0-9,.-]+/g, '');
                        // Reemplazar punto como separador de miles por nada, y coma decimal por punto
                        // Si el formato es "107.100,50" (europeo), convertir a "107100.50"
                        if (cleaned.indexOf(',') > -1 && cleaned.indexOf('.') > -1) {
                            cleaned = cleaned.replace(/\./g, '').replace(',', '.');
                        } else {
                            cleaned = cleaned.replace(/\./g, '');
                        }
                        const amount = parseFloat(cleaned);
                        return isNaN(amount) ? 0 : amount;
                    }
                    // Si ya es número
                    return data || 0;
                }
            return formatCurrency(data);
        }},
        {
            data: null,
            name: 'status_badge',
            title: 'Estado',
            orderable: false,
            searchable: false,
            render: function(_data, type, row){
                if (type === 'export') {
                    // Para exportación, texto plano sin HTML
                    return (row.status === 1 || row.status === '1') ? 'Pagado' : 'Pendiente';
                }
                return (typeof renderInvoiceStatusBadge === 'function') ? renderInvoiceStatusBadge(row.status, row.expiry) : '';
            }
        },
        {
            data: null, name: 'action', orderable: false, searchable: false, title: 'Acciones',
            render: function(data, type, row) {
                const options = {};
                if (!row.has_file || !row.file_path) options.exclude = ['download'];
                return generateActionButtons(row.id, 'facturas', options);
            }
        }
    ];
    
    // Configuración específica para obtener datos de la API
    const tableOptions = {
        ajax: {
            url: buildApiUrl('facturas/proveedores/data'),
            type: 'GET',
            dataSrc: function(json) {
                let data = json.data || [];
                
                // Aplicar filtro de facturas pendientes si viene desde Home o por parámetros
                if (shouldFilterPending || shouldFilterByStatus) {
                    const today = new Date(); today.setHours(0,0,0,0);
                    const thirtyDaysFromNow = new Date(today);
                    thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
                    
                    data = data.filter(r => {
                        const status = parseInt(r.status, 10) || 0;
                        
                        // Si solo se filtra por status pendiente
                        if (shouldFilterByStatus && !shouldFilterPending) {
                            return status === 0;
                        }
                        
                        // Si viene desde Home (pending = vencidas + próximas a vencer)
                        if (shouldFilterPending) {
                            if (status !== 0) return false; // Solo facturas pendientes
                            
                            const dStr = r.expiry || r.date;
                            if (!dStr) return false;
                            
                            const d = new Date(dStr); d.setHours(0,0,0,0);
                            
                            // Incluir facturas vencidas O próximas a vencer (dentro de 30 días)
                            const isOverdue = d < today;
                            const isDueSoon = d >= today && d <= thirtyDaysFromNow;
                            
                            return isOverdue || isDueSoon;
                        }
                        
                        return true;
                    });
                    
                    console.log(`Filtro aplicado (proveedores): ${data.length} facturas mostradas`);
                }
                
                // Almacenar los datos en el sistema global
                EntityDataManager.setEntityData('facturas', data);
                return data;
            },
            error: function(xhr, error, code) {
                console.error('Error loading facturas proveedores data:', error);
                console.log('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los datos de facturas de proveedores. Verifique la conexión.'
                });
            }
        },
        order: shouldFilterPending ? [[4, 'asc']] : [[2, 'desc']], // Si hay filtro pending: ordenar por días (urgentes primero), sino por fecha (más reciente primero)
        columnDefs: [
            { targets: 0, width: '140px', responsivePriority: 2 }, // Número Factura
            { targets: 1, width: '260px', className: 'text-start', responsivePriority: 3 }, // Proveedor
            { targets: 2, width: '120px', responsivePriority: 6 }, // Fecha
            { targets: 3, width: '120px', responsivePriority: 7 }, // Vencimiento  
            { targets: 4, width: '140px', responsivePriority: shouldFilterPending ? 2 : 5 }, // Días (prioritario si filtro activo)
            { targets: 5, width: '120px', responsivePriority: 8 }, // Fecha Pago
            { targets: 6, width: '120px', responsivePriority: 5 }, // Monto
            { targets: 7, width: '110px', responsivePriority: 4 }, // Estado 
            { targets: -1, width: '160px', className: 'text-end nowrap', responsivePriority: 1 } // Acciones siempre visible
        ]
    };
    
    // Inicializar DataTable usando la función reutilizable
    initDataTable('facturas-proveedores-table', null, columns, tableOptions);

    // Adjuntar filtros para proveedores
    if (typeof window.attachInvoiceFilters === 'function') {
        window.attachInvoiceFilters({ tableId: 'facturas-proveedores-table', mode: 'proveedor' });
    }

    // Manejo de eliminación usando la función reutilizable
    $(document).on('click', '.delete-factura', function() {
        const id = $(this).data('id');
        handleDelete('factura', id, buildApiUrl(`facturas/${id}`), function() {
            // Recargar la tabla después de eliminar
            $('#facturas-proveedores-table').DataTable().ajax.reload();
        });
    });

    // Inicializar eventos del modal de detalles
    function initModalEvents() {
        const modalElement = document.getElementById('facturaDetailsModal');
        if (!modalElement) return;
        
        // Limpiar eventos previos
        modalElement.removeEventListener('hidden.bs.modal', handleModalHidden);
        
        // Agregar evento de cierre
        modalElement.addEventListener('hidden.bs.modal', handleModalHidden);
        
        // Manejar botones de cierre específicamente
        const closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
        closeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        });
    }
    
    // Función para manejar el cierre del modal
    function handleModalHidden() {
        // Limpiar backdrop residual
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Restaurar scroll del body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // Limpiar atributos del HTML
        const htmlElement = document.documentElement;
        htmlElement.style.overflow = '';
        htmlElement.style.paddingRight = '';
    }

    // Ver detalles de la factura en un modal
    window.verFactura = function(id) {
        const factura = EntityHelpers.getFactura(id);
        if (factura) {
            console.log(factura)
            
            // Generar enlace de archivo si existe
            const archivoSection = factura.has_file && factura.file_path ? `
                <hr>
                <div class="archivo-asociado mt-3">
                    <p><strong><i class="fas fa-file-download me-2"></i>Archivo Asociado:</strong></p>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="descargarPDF(${factura.id}, event)">>
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
                <p><strong>Proveedor:</strong> ${factura.proveedor.name}</p>
                <p><strong>Fecha:</strong> ${formatTableDate(factura.date, false)}</p>
                <p><strong>Vencimiento:</strong> ${factura.expiry ? formatTableDate(factura.expiry, false) : 'N/A'}</p>
                <p><strong>Fecha de Pago:</strong> ${factura.pay_date ? formatTableDate(factura.pay_date, false) : 'N/A'}</p>
                <p><strong>Monto:</strong> ${formatCurrency(factura.amount)}</p>
                <p><strong>Método de Pago:</strong> ${factura.metodo_pago.name}</p>
                <p><strong>Estado:</strong> <span class="badge ${factura.status === 1 ? 'bg-success' : 'bg-warning'}">${factura.status === 1 ? 'Pagado' : 'Pendiente'}</span></p>
                <p><strong>Detalle:</strong> ${factura.detail || 'Sin detalles'}</p>
                ${archivoSection}
            `;
            
            // Actualizar contenido del modal
            $('#facturaDetailsContent').html(detailsHtml);
            
            // Obtener o crear instancia del modal de manera segura
            const modalElement = document.getElementById('facturaDetailsModal');
            let modalInstance = bootstrap.Modal.getInstance(modalElement);
            
            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
            }
            
            modalInstance.show();
        } else {
            Swal.fire('Error', 'No se pudieron encontrar los detalles de la factura.', 'error');
        }
    };
});
