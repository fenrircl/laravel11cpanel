(function(){
  const $ = (sel, root) => (root || document).querySelector(sel);
  const NF = new Intl.NumberFormat('es-CL');
  const fmtCLP = (n) => '$' + NF.format(Number(n||0));

  function getBaseUrl(){
    const meta = document.querySelector('meta[name="base-url"]');
    if (meta) return meta.getAttribute('content');
    return window.APP_BASE_URL || '';
  }

  function parseId() {
    const u = new URL(window.location.href);
    const idParam = u.searchParams.get('id');
    if (idParam) return idParam;
    const m = u.pathname.match(/cotizaciones\/(\d+)\/pdf/);
    return m ? m[1] : null;
  }

  function setText(id, val, root) { const el = $("#"+id, root); if (el) el.textContent = (val ?? '—'); }

  // Exponer función reutilizable para poblar la plantilla PDF, con root opcional
  window.populateQuotationForPdf = function populateQuotationForPdf(q, root){
    if (!q) return;
    const cliente = q.cliente || q.client || {};
    setText('q-id', q.id, root);

    // Fecha
    const d = q.date ? new Date(q.date) : null;
    const dateStr = d && !isNaN(d) ? d.toLocaleDateString('es-CL') : (q.date || '—');
    setText('q-date', dateStr, root);
    setText('q-agent', q.agent || '', root);

    setText('c-name', cliente.name || '', root);
    setText('c-rut', cliente.rut || '', root);
    setText('c-address', cliente.address || '', root);
    setText('c-city', cliente.cityname || cliente.city || '', root);
    setText('c-phone', cliente.phone || '', root);
    setText('c-email', cliente.email || '', root);

    const work = q.work || '';
    const workSection = $('#work-section', root);
    const workBox = $('#q-work', root);
    if (work && workSection && workBox) { workSection.style.display = ''; workBox.textContent = work; }

    const tbody = $('#items-body', root);
    if (tbody) {
      tbody.innerHTML = '';
      let netSubtotal = 0;
      (q.items || []).forEach(it => {
        const amount = Number(it.amount||0);
        const price = Number(it.price||0);
        const line = amount * price;
        netSubtotal += line;
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${it.description||''}</td>
          <td class="num">${NF.format(amount)}</td>
          <td class="num">${fmtCLP(price)}</td>
          <td class="num">${fmtCLP(line)}</td>`;
        tbody.appendChild(tr);
      });

      const iva = Math.round(netSubtotal * 0.19);
      const total = (q.total != null) ? Number(q.total) : (netSubtotal + iva);

      setText('sum-net', fmtCLP(netSubtotal), root);
      setText('sum-iva', fmtCLP(iva), root);
      setText('sum-total', fmtCLP(total), root);
    }

    const container = $('#pdfPrintable', root) || $('#pdfPrintable');
    if (container) container.dataset.quoteId = q.id;
  }

  async function loadAndPopulate() {
    if (window.QUOTATION) { window.populateQuotationForPdf(window.QUOTATION); return; }
    const id = parseId();
    if (!id) { console.warn('No se pudo resolver ID de cotización'); return; }
    try {
      const url = `${getBaseUrl()}/cotizaciones/${id}`;
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }});
      const json = await res.json();
      const q = json.cotizacion || json || null;
      window.populateQuotationForPdf(q);
    } catch (e) {
      console.error('No se pudo cargar la cotización', e);
    }
  }

  document.addEventListener('DOMContentLoaded', loadAndPopulate);

  // Descargar PDF
  document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('btnDownloadPdf');
    if (!btn) return;
    btn.addEventListener('click', async function() {
      const element = document.getElementById('pdfPrintable');
      if (!element) { console.error('Elemento #pdfPrintable no existe'); return; }
      if (typeof html2pdf === 'undefined') { console.error('html2pdf no disponible'); alert('Generador PDF no disponible'); return; }

      try {
        // Asegurar un frame de render antes de capturar
        await new Promise(r => requestAnimationFrame(r));
        const opts = {
          margin: 0.5,
          filename: `cotizacion_${element.dataset.quoteId || 'sin_id'}.pdf`,
          image: { type: 'jpeg', quality: 1 },
          html2canvas: { scale: 2, useCORS: true, allowTaint: true, backgroundColor: '#ffffff', logging: false },
          jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait', compress: true }
        };
        // Usar forma corta evita cadenas internas que a veces disparan el logger error
        await html2pdf(element, opts);
      } catch (err) {
        console.error('Error al generar PDF', err);
        alert('Error al generar el PDF: ' + (err.message || ''));
      }
    });
  });
})();
