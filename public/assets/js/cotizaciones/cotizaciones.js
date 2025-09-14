$(document).ready(function() {
    console.log('Cotizaciones DataTable initialized');
    
    const columns = [
        { data: 'id', name: 'id', title: 'ID', width: '70px', responsivePriority: 2 },
        { data: 'cliente.name', name: 'cliente.name', title: 'Cliente', width: '200px', responsivePriority: 4, render: d => d || 'N/A' },
        { data: 'date', name: 'date', title: 'Fecha', width: '100px', responsivePriority: 3, render: (d) => formatTableDate(d, false) },
        { data: 'agent', name: 'agent', title: 'Agente', width: '150px', responsivePriority: 5 },
        { data: 'work', name: 'work', title: 'Trabajo', width: '200px', responsivePriority: 6, render: d => d || '-' },
        { data: 'total', name: 'total', title: 'Total', width: '100px', responsivePriority: 2, render: (d) => formatCurrency(d, 'CLP') },
        {
            data: null,
            defaultContent: '',
            name: 'action',
            orderable: false,
            searchable: false,
            title: 'Acciones',
            width: '220px',
            className: 'dt-actions text-nowrap all',
            responsivePriority: 1,
            render: function(_data, _type, row) {
                const pageEditUrl = routeUrl('cotizaciones.edit', row.id);
                return `
                    <div class="btn-group">
                        <button class="btn btn-sm btn-action btn-view" title="Ver" onclick="verCotizacion(${row.id})"><i class="fas fa-eye"></i></button>
                        <a class="btn btn-sm btn-action btn-primary" title="Editar" href="${pageEditUrl}"><i class="fas fa-edit"></i></a>
                        <button class="btn btn-sm btn-action btn-secondary" title="PDF" onclick="pdfCotizacion(${row.id})"><i class="fas fa-file-pdf"></i></button>
                        <button class="btn btn-sm btn-action btn-success" title="Enviar por correo" onclick="abrirEnviarCotizacion(${row.id})"><i class="fas fa-envelope"></i></button>
                        <button class="btn btn-sm btn-action btn-danger" title="Eliminar" onclick="eliminarCotizacion(${row.id})"><i class="fas fa-trash"></i></button>
                    </div>`;
            }
        }
    ];

    const tableOptions = {
        responsive: true,
        autoWidth: false,
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

    // Prevent DataTable reinitialization error
    if ($.fn.DataTable.isDataTable('#cotizaciones-table')) {
        $('#cotizaciones-table').DataTable().destroy();
    }
    $('#cotizaciones-table').empty();

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
        const url = buildApiUrl(`cotizaciones/${id}`); // DELETE /cotizaciones/{id}
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

    // Modal envío
    const sendModalHtml = `
    <div class="modal fade" id="sendQuoteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Enviar cotización</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label">Para</label>
              <input type="email" class="form-control" id="sendToEmail" placeholder="correo@cliente.com">
            </div>
            <div>
              <label class="form-label">Mensaje</label>
              <textarea class="form-control" id="sendMessage" rows="4" placeholder="Mensaje opcional"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnConfirmSend">Enviar</button>
          </div>
        </div>
      </div>
    </div>`;

    if (!document.getElementById('sendQuoteModal')) {
        document.body.insertAdjacentHTML('beforeend', sendModalHtml);
    }

    let sendContext = { id: null, to: null };

    window.abrirEnviarCotizacion = function(id) {
        // Prefill email: prioriza cotizacion.email, sino cliente.email
        fetchRecord(buildApiUrl(`cotizaciones/${id}`), function(res){
            const c = res.cotizacion || res;
            sendContext.id = id;
            const to = c.email || (c.cliente ? c.cliente.email : '');
            document.getElementById('sendToEmail').value = to || '';
            document.getElementById('sendMessage').value = '';
            const modal = new bootstrap.Modal(document.getElementById('sendQuoteModal'));
            modal.show();
        });
    };

    document.getElementById('btnConfirmSend').addEventListener('click', async function(){
        const to = document.getElementById('sendToEmail').value.trim();
        const msg = document.getElementById('sendMessage').value.trim();
        if (!to) {
            Swal.fire({ icon:'warning', title:'Correo requerido', text:'Ingrese un correo de destino' });
            return;
        }
        const id = sendContext.id;
        let iframe = null;
        try {
            Swal.fire({ title:'Generando PDF...', allowOutsideClick:false, didOpen:()=>Swal.showLoading() });

            // Usar el host oculto ya presente en el DOM
            let host = document.getElementById('pdfHiddenHost');
            if (!host) {
                // Fallback: crear contenedor oculto si no existe
                host = document.createElement('div');
                host.id = 'pdfHiddenHost';
                host.style.position = 'fixed';
                host.style.left = '-200vw';
                host.style.top = '0';
                host.style.width = '1000px';
                host.style.background = '#fff';
                host.style.pointerEvents = 'none';
                host.style.zIndex = '-1';
                document.body.appendChild(host);
            }

            // Cargar datos y pintar dentro del contenedor oculto
            const res = await fetch(buildApiUrl(`cotizaciones/${id}`), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const json = await res.json();
            const c = json.cotizacion || json;

            // Llenar plantilla existente en host oculto
            // Asumimos que dentro del host está el div #pdfPrintable
            const printable = host.querySelector('#pdfPrintable');
            if (!printable) throw new Error('Plantilla PDF no disponible');

            // Usar el mismo poblar que en la vista PDF si está disponible
            if (typeof window.populateQuotationForPdf === 'function') {
                window.populateQuotationForPdf(c, host);
            } else {
                // Fallback: poblar campos mínimos
                const set = (id, v) => { const el = host.querySelector('#' + id); if (el) el.textContent = v || '—'; };
                set('q-id', c.id);
                set('q-date', (c.date || '').split('T')[0]);
                set('q-agent', c.agent || '');
                const cliente = c.cliente || {};
                set('c-name', cliente.name || '');
                set('c-rut', cliente.rut || '');
                set('c-address', cliente.address || '');
                set('c-city', cliente.cityname || cliente.city || '');
                set('c-phone', cliente.phone || '');
                set('c-email', cliente.email || '');
                const tbody = host.querySelector('#items-body');
                if (tbody) {
                    tbody.innerHTML = '';
                    let net = 0;
                    (c.items||[]).forEach(it => { const a = +it.amount||0, p = +it.price||0; net += a*p; tbody.insertAdjacentHTML('beforeend', `<tr><td>${it.description||''}</td><td class="num">${a}</td><td class="num">$${p.toLocaleString('es-CL')}</td><td class="num">$${(a*p).toLocaleString('es-CL')}</td></tr>`); });
                    const iva = Math.round(net*0.19);
                    host.querySelector('#sum-net') && (host.querySelector('#sum-net').textContent = `$${net.toLocaleString('es-CL')}`);
                    host.querySelector('#sum-iva') && (host.querySelector('#sum-iva').textContent = `$${iva.toLocaleString('es-CL')}`);
                    host.querySelector('#sum-total') && (host.querySelector('#sum-total').textContent = `$${(net+iva).toLocaleString('es-CL')}`);
                }
            }

            if (typeof html2pdf === 'undefined') throw new Error('html2pdf no está disponible');

            const opt = {
                margin: 0.5,
                filename: `cotizacion_${id}.pdf`,
                image: { type: 'jpeg', quality: 1 },
                html2canvas: { scale: 2, useCORS:true, allowTaint:true, backgroundColor:'#ffffff', logging:false },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait', compress:true }
            };

            // Subir el PDF como archivo temporal en vez de enviarlo en base64
            // Obtener Blob de forma compatible
            const pdfWorker = html2pdf().from(printable).set(opt).toPdf();
            const jsPdfInstance = await pdfWorker.get('pdf');
            const blob = jsPdfInstance.output('blob');

            const tmpForm = new FormData();
            tmpForm.append('file', blob, `cotizacion_${id}.pdf`);
            // Campos requeridos por /files/upload
            tmpForm.append('model_type', 'App\\Cotizacion');
            tmpForm.append('model_id', String(id));
            // Opcional: id real o metadata
            // tmpForm.append('real_id', String(id));

            // Subir al endpoint existente de archivos
            const uploadResp = await fetch(buildApiUrl('files/upload'), {
                method: 'POST',
                body: tmpForm,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            if (!uploadResp.ok) throw new Error('Fallo subiendo PDF temporal');
            const uploadJson = await uploadResp.json();
            const ok = (uploadJson.status === 'success') || (uploadJson.success === true);
            if (!ok) throw new Error(uploadJson.message || 'Fallo al subir PDF');

            const fileUrl = uploadJson.url || (uploadJson.file && (uploadJson.file.download_url || uploadJson.file.url || uploadJson.file.path));
            const filePath = uploadJson.file && uploadJson.file.path;
            const fileId = uploadJson.file && uploadJson.file.id;
            if (!fileUrl && !filePath && !fileId) throw new Error('Respuesta de subida sin URL/Path/ID');

            Swal.close();
            Swal.fire({ title:'Enviando...', allowOutsideClick:false, didOpen:()=>Swal.showLoading() });

            const payload = new FormData();
            payload.append('to', to);
            if (msg) payload.append('message', msg);
            if (fileId) payload.append('file_id', String(fileId));
            if (filePath) payload.append('file_path', String(filePath));
            if (fileUrl) payload.append('file_url', String(fileUrl));
            payload.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            const sendUrl = routeUrl('cotizaciones.send-email', id);
            const sendRes = await fetch(sendUrl, { method:'POST', body: payload, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const jsonSend = await sendRes.json();
            Swal.close();
            if (!jsonSend.success) throw new Error(jsonSend.message || 'Error');
            Swal.fire({ icon:'success', title:'Enviado', text:'Correo enviado correctamente' });
            bootstrap.Modal.getInstance(document.getElementById('sendQuoteModal')).hide();
        } catch (e) {
            console.error(e);
            Swal.close();
            Swal.fire({ icon:'error', title:'Error', text: e.message || 'No se pudo enviar el correo' });
        }
    });
});
