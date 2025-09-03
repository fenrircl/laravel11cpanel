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
                return `
                    <div class="btn-group">
                        <button class="btn btn-sm btn-action btn-view" title="Ver" onclick="verCotizacion(${row.id})"><i class="fas fa-eye"></i></button>
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

    initDataTable('cotizaciones-table', null, columns, tableOptions);

    window.verCotizacion = function(id) {
        const cot = EntityDataManager.findById('cotizaciones', id);
        if (!cot) return;
        fetchRecord(buildApiUrl(`cotizaciones/${id}`), function(res) {
            const c = res.cotizacion || res;
            let itemsHtml = '';
            if (c.items && c.items.length) {
                itemsHtml = `<table class="table table-sm"><thead><tr><th>Descripción</th><th class="text-end">Cant.</th><th class="text-end">Precio</th><th class="text-end">Total</th></tr></thead><tbody>` +
                    c.items.map(it => `
                        <tr>
                            <td>${it.description}</td>
                            <td class="text-end">${it.amount}</td>
                            <td class="text-end">${formatCurrency(it.price, 'CLP')}</td>
                            <td class="text-end">${formatCurrency(it.total, 'CLP')}</td>
                        </tr>`).join('') +
                    `</tbody></table>`;
            }
            const html = `
                <div>
                    <p><strong>Cliente:</strong> ${c.cliente ? c.cliente.name : ''}</p>
                    <p><strong>Fecha:</strong> ${formatTableDate(c.date, false)}</p>
                    <p><strong>Agente:</strong> ${c.agent || ''}</p>
                    <p><strong>Trabajo:</strong> ${c.work || ''}</p>
                    <hr>
                    ${itemsHtml}
                    <div class="d-flex justify-content-end">
                        <div class="text-end">
                            <div><strong>Total:</strong> ${formatCurrency(c.total, 'CLP')}</div>
                        </div>
                    </div>
                </div>`;
            $('#cotizacionDetailsContent').html(html);
            $('#cotizacionDetailsModal').modal('show');
        });
    };
});
