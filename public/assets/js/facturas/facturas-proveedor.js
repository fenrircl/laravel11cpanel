$(document).ready(function() {
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
        {data: 'invoice', name: 'invoice', title: 'Factura'},
        {
            data: 'proveedor.name', 
            name: 'proveedor.name',
            title: 'Proveedor',
            render: function(data) {
                const name = data || 'N/A';
                return `<span class="text-truncate d-inline-block" style="max-width:240px" title="${name}">${name}</span>`;
            }
        },
        { data: 'date', name: 'date', title: 'Fecha', render: (d)=> formatTableDate(d, false) },
        { data: 'expiry', name: 'expiry', title: 'Vencimiento', render: (d)=> d ? formatTableDate(d, false) : 'N/A' },
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
                return renderDaysCounter(expiryDate, row.status);
            }
        },
        { data: 'pay_date', name: 'pay_date', title: 'Fecha Pago', render: (d)=> d ? formatTableDate(d, false) : 'N/A' },
        { data: 'amount', name: 'amount', title: 'Monto', render: (d)=> formatCurrency(d) },
        {
            data: null,
            name: 'status_badge',
            title: 'Estado',
            orderable: false,
            searchable: false,
            render: function(_data, _type, row){
                return (typeof renderInvoiceStatusBadge === 'function') ? renderInvoiceStatusBadge(row.status, row.expiry) : '';
            }
        },
        {
            data: 'action', name: 'action', orderable: false, searchable: false, title: 'Acciones',
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
        order: [[3, 'asc']], // Ordenar por días: más urgentes primero cuando hay filtro pendiente
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
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="descargarPDF(${factura.id})">
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
            $('#facturaDetailsContent').html(detailsHtml);
            new bootstrap.Modal(document.getElementById('facturaDetailsModal')).show();
        } else {
            Swal.fire('Error', 'No se pudieron encontrar los detalles de la factura.', 'error');
        }
    };
});
