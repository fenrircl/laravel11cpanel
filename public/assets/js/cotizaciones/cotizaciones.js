$(document).ready(function() {
    console.log('Cotizaciones DataTable initialized');

    const columns = [
        { data: 'id', name: 'id', title: 'ID', width: '70px' },
        { data: 'cliente.name', name: 'cliente.name', title: 'Cliente', render: d => d || 'N/A' },
        { data: 'date', name: 'date', title: 'Fecha', render: (d) => formatTableDate(d, false) },
        { data: 'agent', name: 'agent', title: 'Agente' },
        { data: 'work', name: 'work', title: 'Trabajo', render: d => d || '-' },
        { data: 'total', name: 'total', title: 'Total', render: (d) => formatCurrency(d, 'CLP') },
        {
            data: 'action', name: 'action', orderable: false, searchable: false, title: 'Acciones',
            render: function(_data, _type, row) {
                const pageEditUrl = routeUrl('cotizaciones.edit', row.id);
                return `
                    <div class="btn-group">
                        <button class="btn btn-sm btn-action btn-view" title="Ver" onclick="verCotizacion(${row.id})"><i class="fas fa-eye"></i></button>
                        <a class="btn btn-sm btn-action btn-primary" title="Editar" href="${pageEditUrl}"><i class="fas fa-edit"></i></a>
                        <button class="btn btn-sm btn-action btn-secondary" title="PDF" onclick="pdfCotizacion(${row.id})"><i class="fas fa-file-pdf"></i></button>
                        <button class="btn btn-sm btn-action btn-danger" title="Eliminar" onclick="eliminarCotizacion(${row.id})"><i class="fas fa-trash"></i></button>
                    </div>`;
            }
        }
    ];

    const tableOptions = {
        ajax: {
            url: buildApiUrl('cotizaciones/data'),
            type: 'GET',
            dataSrc: function(json) {
                EntityDataManager.setEntityData('cotizaciones', json.data);
                return json.data;
            },
            error: function(xhr) {
                console.error('Error loading cotizaciones:', xhr.responseText);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar las cotizaciones' });
            }
        },
        order: [[0, 'desc']]
    };

    const table = initDataTable('cotizaciones-table', null, columns, tableOptions);

    window.verCotizacion = function(id) {
        // Obtener siempre desde API para asegurar items actualizados
        fetchRecord(buildApiUrl(`cotizaciones/${id}`), function(res) {
            const c = res.cotizacion || res;
            let itemsHtml = '';
            let netSubtotal = 0;
            if (c.items && c.items.length) {
                itemsHtml = `<table class="table table-sm"><thead><tr><th>Descripción</th><th class="text-end">Cant.</th><th class="text-end">Precio Neto</th><th class="text-end">Total Neto</th></tr></thead><tbody>` +
                    c.items.map(it => {
                        const lineTotal = (parseInt(it.amount||0) * parseInt(it.price||0));
                        netSubtotal += lineTotal;
                        return `
                        <tr>
                            <td>${it.description}</td>
                            <td class="text-end">${it.amount}</td>
                            <td class="text-end">${formatCurrency(it.price, 'CLP')}</td>
                            <td class="text-end">${formatCurrency(lineTotal, 'CLP')}</td>
                        </tr>`;}).join('') +
                    `</tbody></table>`;
            }
            const iva = Math.round(netSubtotal * 0.19);
            const total = netSubtotal + iva; // debería coincidir con c.total
            const summaryHtml = `
                <div class="mt-2">
                    <div class="d-flex justify-content-end">
                        <table class="table table-sm w-auto mb-0">
                            <tr><th class="text-end pe-3">Subtotal (Neto)</th><td class="text-end">${formatCurrency(netSubtotal, 'CLP')}</td></tr>
                            <tr><th class="text-end pe-3">IVA 19%</th><td class="text-end">${formatCurrency(iva, 'CLP')}</td></tr>
                            <tr><th class="text-end pe-3">Total</th><td class="text-end fw-bold">${formatCurrency(total, 'CLP')}</td></tr>
                        </table>
                    </div>
                </div>`;

            const html = `
                <div id="cotizacionPrintableModal">
                    <p><strong>Cliente:</strong> ${c.cliente ? c.cliente.name : ''}</p>
                    <p><strong>Fecha:</strong> ${formatTableDate(c.date, false)}</p>
                    <p><strong>Agente:</strong> ${c.agent || ''}</p>
                    <p><strong>Trabajo:</strong> ${c.work || ''}</p>
                    <hr>
                    ${itemsHtml}
                    ${summaryHtml}
                </div>`;
            $('#cotizacionDetailsContent').html(html);
            $('#cotizacionDetailsModal').modal('show');
        });
    };

    window.pdfCotizacion = function(id) {
        const url = routeUrl('cotizaciones.pdf', id);
        window.open(url, '_blank');
    };

    // Exportar y subir a R2 desde backend (preferido por el usuario)
    window.exportarYSubirCotizacion = function(id) {
        const url = routeUrl('cotizaciones.export-upload', id);
        Swal.fire({title: 'Generando...', text: 'Creando PDF y subiendo a R2', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
            .then(r => r.json())
            .then(res => {
                Swal.close();
                if (!res.success) throw new Error(res.message || 'Error');
                Swal.fire({ icon: 'success', title: 'Listo', html: `Archivo subido.<br><a href="${res.download_url}" target="_blank">Descargar</a>` });
            })
            .catch(err => {
                Swal.close();
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar/subir el PDF' });
            });
    };

    window.eliminarCotizacion = function(id) {
        const url = routeUrl('cotizaciones.destroy', id);
        Swal.fire({
            title: '¿Eliminar cotización?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            }).then(r => r.json())
            .then(res => {
                if (!res.success) throw new Error('Error al eliminar');
                Swal.fire({ icon: 'success', title: 'Eliminada', text: 'Cotización eliminada correctamente' });
                table.ajax.reload(null, false);
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar la cotización' });
            });
        });
    };
});
