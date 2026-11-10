(function () {
  if (!document.body || document.getElementById('chatbot-widget')) return;

  const chatBase = document.querySelector('meta[name="assistant-chat-url"]')?.content?.replace(/\/$/, '');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  if (!chatBase) return;

  const toggle = document.createElement('button');
  toggle.id = 'chatbot-toggle';
  toggle.type = 'button';
  toggle.setAttribute('aria-label', 'Abrir asistente');
  toggle.setAttribute('aria-controls', 'chatbot-widget');
  toggle.setAttribute('aria-expanded', 'false');
  toggle.innerHTML = '<span aria-hidden="true">💬</span><span>Abrir asistente</span>';

  const widget = document.createElement('section');
  widget.id = 'chatbot-widget';
  widget.setAttribute('aria-label', 'Asistente de AcerosEra');
  widget.innerHTML = `
    <header class="cb-header">
      <div class="cb-heading"><strong class="cb-title">Asistente de AcerosEra</strong><span class="cb-subtitle">Escriba su pregunta con sus propias palabras</span></div>
      <div class="cb-actions">
        <button class="cb-iconbtn" type="button" id="cb-history" aria-label="Ver conversaciones anteriores"><span aria-hidden="true">☷</span><span>Historial</span></button>
        <button class="cb-iconbtn" type="button" id="cb-new" aria-label="Empezar una conversación nueva"><span aria-hidden="true">＋</span><span>Nuevo</span></button>
        <button class="cb-iconbtn" type="button" id="cb-close" aria-label="Cerrar el asistente"><span aria-hidden="true">×</span><span>Cerrar</span></button>
      </div>
    </header>
    <div class="cb-history" id="cb-history-panel" hidden aria-label="Conversaciones anteriores"></div>
    <div class="cb-messages" id="cb-messages" role="log" aria-live="polite" aria-relevant="additions text"></div>
    <div class="cb-typing" id="cb-typing" hidden>Buscando en el sistema…</div>
    <form class="cb-input" id="cb-form">
      <label class="visually-hidden" for="cb-text">Escribe tu consulta</label>
      <textarea id="cb-text" rows="2" maxlength="2000" placeholder="Por ejemplo: ¿Qué facturas vencen este año?"></textarea>
      <div class="cb-submit-group"><span>Escriba aquí y presione Enviar</span><button id="cb-send" type="submit">Enviar</button></div>
    </form>
    <div class="cb-quota" id="cb-quota" hidden aria-live="polite"></div>
  `;

  const resizeHandle = document.createElement('div');
  resizeHandle.className = 'cb-resize-handle';
  resizeHandle.setAttribute('role', 'separator');
  resizeHandle.setAttribute('aria-orientation', 'vertical');
  resizeHandle.setAttribute('aria-label', 'Ajustar ancho del chat');
  resizeHandle.setAttribute('aria-valuemin', '420');
  resizeHandle.setAttribute('aria-valuemax', '900');
  resizeHandle.tabIndex = 0;
  widget.appendChild(resizeHandle);

  const heightHandle = document.createElement('div');
  heightHandle.className = 'cb-resize-height';
  heightHandle.setAttribute('role', 'separator');
  heightHandle.setAttribute('aria-orientation', 'horizontal');
  heightHandle.setAttribute('aria-label', 'Ajustar alto del chat');
  heightHandle.setAttribute('aria-valuemin', '440');
  heightHandle.setAttribute('aria-valuemax', String(Math.max(440, window.innerHeight - 128)));
  heightHandle.tabIndex = 0;
  widget.appendChild(heightHandle);

  const defaultChatWidth = 640;
  const defaultChatHeight = () => Math.min(820, Math.max(440, Math.round(window.innerHeight * 0.76)));
  const chatWidthKey = 'assistant-chat-width';
  const chatHeightKey = 'assistant-chat-height';
  const maxChatWidth = () => Math.max(420, Math.min(900, window.innerWidth - 48));
  const maxChatHeight = () => Math.max(440, Math.min(820, window.innerHeight - 128));
  let chatWidth = defaultChatWidth;
  let chatHeight = defaultChatHeight();
  try {
    const savedWidth = Number(window.localStorage.getItem(chatWidthKey));
    if (Number.isFinite(savedWidth) && savedWidth >= 420) chatWidth = savedWidth;
    const savedHeight = Number(window.localStorage.getItem(chatHeightKey));
    if (Number.isFinite(savedHeight) && savedHeight >= 440) chatHeight = savedHeight;
  } catch (_) {
    // The chat still works when local storage is unavailable.
  }

  function setChatWidth(width, persist = true) {
    chatWidth = Math.min(maxChatWidth(), Math.max(420, Math.round(width)));
    widget.style.setProperty('--cb-widget-width', `${chatWidth}px`);
    resizeHandle.setAttribute('aria-valuenow', String(chatWidth));
    if (persist) {
      try {
        window.localStorage.setItem(chatWidthKey, String(chatWidth));
      } catch (_) {
        // Width remains adjustable for the current page session.
      }
    }
  }

  function setChatHeight(height, persist = true) {
    chatHeight = Math.min(maxChatHeight(), Math.max(440, Math.round(height)));
    widget.style.setProperty('--cb-widget-height', `${chatHeight}px`);
    heightHandle.setAttribute('aria-valuenow', String(chatHeight));
    heightHandle.setAttribute('aria-valuemax', String(maxChatHeight()));
    if (persist) {
      try {
        window.localStorage.setItem(chatHeightKey, String(chatHeight));
      } catch (_) {
        // Height remains adjustable for the current page session.
      }
    }
  }

  setChatWidth(chatWidth, false);
  setChatHeight(chatHeight, false);
  let resizeStart = null;
  resizeHandle.addEventListener('pointerdown', (event) => {
    if (window.matchMedia('(max-width: 700px)').matches) return;
    event.preventDefault();
    resizeStart = { axis: 'width', x: event.clientX, width: chatWidth, pointerId: event.pointerId };
    resizeHandle.setPointerCapture(event.pointerId);
  });
  const finishResize = (event) => {
    if (!resizeStart || event.pointerId !== resizeStart.pointerId) return;
    if (resizeStart.axis === 'width') setChatWidth(chatWidth);
    if (resizeStart.axis === 'height') setChatHeight(chatHeight);
    resizeStart = null;
  };
  resizeHandle.addEventListener('pointermove', (event) => {
    if (!resizeStart || resizeStart.axis !== 'width' || event.pointerId !== resizeStart.pointerId) return;
    setChatWidth(resizeStart.width + resizeStart.x - event.clientX, false);
  });
  resizeHandle.addEventListener('pointerup', finishResize);
  resizeHandle.addEventListener('pointercancel', finishResize);
  heightHandle.addEventListener('pointerdown', (event) => {
    if (window.matchMedia('(max-width: 700px)').matches) return;
    event.preventDefault();
    resizeStart = { axis: 'height', y: event.clientY, height: chatHeight, pointerId: event.pointerId };
    heightHandle.setPointerCapture(event.pointerId);
  });
  heightHandle.addEventListener('pointermove', (event) => {
    if (!resizeStart || resizeStart.axis !== 'height' || event.pointerId !== resizeStart.pointerId) return;
    setChatHeight(resizeStart.height + resizeStart.y - event.clientY, false);
  });
  heightHandle.addEventListener('pointerup', finishResize);
  heightHandle.addEventListener('pointercancel', finishResize);
  resizeHandle.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowRight') {
      event.preventDefault();
      setChatWidth(chatWidth + 32);
    } else if (event.key === 'ArrowLeft') {
      event.preventDefault();
      setChatWidth(chatWidth - 32);
    } else if (event.key === 'Home') {
      event.preventDefault();
      setChatWidth(420);
    } else if (event.key === 'End') {
      event.preventDefault();
      setChatWidth(maxChatWidth());
    }
  });
  resizeHandle.addEventListener('dblclick', () => setChatWidth(defaultChatWidth));
  heightHandle.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowUp') {
      event.preventDefault();
      setChatHeight(chatHeight + 32);
    } else if (event.key === 'ArrowDown') {
      event.preventDefault();
      setChatHeight(chatHeight - 32);
    } else if (event.key === 'Home') {
      event.preventDefault();
      setChatHeight(440);
    } else if (event.key === 'End') {
      event.preventDefault();
      setChatHeight(maxChatHeight());
    }
  });
  heightHandle.addEventListener('dblclick', () => setChatHeight(defaultChatHeight()));
  window.addEventListener('resize', () => {
    setChatWidth(chatWidth, false);
    setChatHeight(chatHeight, false);
  });

  document.body.append(toggle, widget);

  const messages = widget.querySelector('#cb-messages');
  const historyPanel = widget.querySelector('#cb-history-panel');
  const input = widget.querySelector('#cb-text');
  const form = widget.querySelector('#cb-form');
  const sendButton = widget.querySelector('#cb-send');
  const typing = widget.querySelector('#cb-typing');
  const quotaCounter = widget.querySelector('#cb-quota');
  let currentQuota = null;
  let quotaRefreshTimer = null;
  const inlineIcons = {
    '📅': '<rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 11h18"></path>',
    '🧾': '<path d="M4 3h16v18l-3-2-3 2-3-2-3 2-4-2z"></path><path d="M8 8h8M8 12h8M8 16h4"></path>',
    '💰': '<rect x="2.5" y="6" width="19" height="12" rx="2"></rect><circle cx="12" cy="12" r="3"></circle><path d="M6 10h.01M18 14h.01"></path>',
    '✅': '<circle cx="12" cy="12" r="9"></circle><path d="m8 12 2.5 2.5L16 9"></path>',
    '⚠️': '<path d="M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path>',
  };

  function renderInlineIcons(container) {
    const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    while (walker.nextNode()) textNodes.push(walker.currentNode);

    textNodes.forEach((node) => {
      const matches = [...node.textContent.matchAll(/📅|🧾|💰|✅|⚠️/gu)];
      if (!matches.length) return;
      const fragment = document.createDocumentFragment();
      let offset = 0;
      matches.forEach((match) => {
        fragment.appendChild(document.createTextNode(node.textContent.slice(offset, match.index)));
        const icon = document.createElement('span');
        icon.className = 'cb-inline-icon';
        icon.setAttribute('aria-hidden', 'true');
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('focusable', 'false');
        svg.innerHTML = inlineIcons[match[0]];
        icon.appendChild(svg);
        fragment.appendChild(icon);
        offset = match.index + match[0].length;
      });
      fragment.appendChild(document.createTextNode(node.textContent.slice(offset)));
      node.replaceWith(fragment);
    });
  }

  let conversationId = null;
  let conversationsLoaded = false;
  let busy = false;

  async function request(path, options = {}) {
    const headers = {
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrf,
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    };
    const response = await fetch(`${chatBase}${path}`, {
      credentials: 'same-origin',
      ...options,
      headers,
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const error = new Error(payload.message || 'No se pudo completar la consulta.');
      error.status = response.status;
      error.payload = payload;
      throw error;
    }
    return payload;
  }

  function applyQuota(quota) {
    currentQuota = quota || null;
    window.clearTimeout(quotaRefreshTimer);
    if (!currentQuota || currentQuota.is_admin) {
      quotaCounter.hidden = true;
      input.disabled = busy;
      sendButton.disabled = busy;
      return;
    }

    quotaCounter.hidden = false;
    const resetTime = currentQuota.reset_time || '00:00';
    const resetTimestamp = Date.parse(currentQuota.reset_at || '');
    if (Number.isFinite(resetTimestamp)) {
      quotaRefreshTimer = window.setTimeout(
        () => refreshQuota().catch(() => {}),
        Math.max(1000, resetTimestamp - Date.now() + 1000),
      );
    }
    quotaCounter.textContent = currentQuota.remaining > 0
      ? `Consultas de hoy: ${currentQuota.used} de ${currentQuota.limit}. Se reinician a las ${resetTime}.`
      : `Ya utilizaste tus ${currentQuota.limit} consultas de hoy. Se reinician a las ${resetTime}.`;
    const reachedLimit = currentQuota.remaining < 1;
    input.disabled = busy || reachedLimit;
    sendButton.disabled = busy || reachedLimit;
    input.placeholder = reachedLimit
      ? 'El límite diario se reinicia a las ' + resetTime
      : 'Por ejemplo: ¿Qué facturas vencen este año?';
  }

  async function refreshQuota() {
    const result = await request('/quota');
    applyQuota(result.quota);
  }

  function addMessage(content, role = 'assistant', isSafeHtml = false, chart = null) {
    const row = document.createElement('div');
    row.className = `msg ${role === 'user' ? 'user' : 'bot'}`;
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    if (role === 'assistant' && isSafeHtml) {
      // The server converts model Markdown to sanitized HTML before returning it.
      bubble.innerHTML = content;
      bubble.querySelectorAll('table').forEach((table) => {
        if (table.parentElement?.classList.contains('cb-table-scroll')) return;
        const scroller = document.createElement('div');
        scroller.className = 'cb-table-scroll';
        scroller.tabIndex = 0;
        scroller.setAttribute('role', 'region');
        scroller.setAttribute('aria-label', 'Tabla; puede desplazarse horizontalmente');
        table.parentNode.insertBefore(scroller, table);
        scroller.appendChild(table);
      });
      renderInlineIcons(bubble);
    } else {
      bubble.textContent = content;
    }
    if (role === 'assistant' && chart) renderChart(bubble, chart);
    row.appendChild(bubble);
    messages.appendChild(row);
    messages.scrollTop = messages.scrollHeight;
  }

  function renderChart(bubble, chart) {
    const labels = Array.isArray(chart.labels) ? chart.labels.slice(0, 12) : [];
    const series = Array.isArray(chart.series) ? chart.series.slice(0, 4) : [];
    const colors = ['#176b87', '#df8b28', '#448b61', '#8b6caf'];
    if (!labels.length || !series.length || !series.every((item) => Array.isArray(item.data) && item.data.length === labels.length)) return;

    const card = document.createElement('section');
    card.className = 'cb-chart-card';
    const title = document.createElement('h3');
    title.className = 'cb-chart-title';
    title.textContent = chart.title || 'Gráfico';
    const canvas = document.createElement('canvas');
    canvas.className = 'cb-chart-canvas';
    canvas.width = 960;
    canvas.height = chart.type === 'doughnut' ? 540 : 560;
    canvas.setAttribute('role', 'img');
    canvas.setAttribute('aria-label', `${chart.title || 'Gráfico'}: ${labels.join(', ')}`);
    const download = document.createElement('button');
    download.className = 'cb-chart-download';
    download.type = 'button';
    download.textContent = 'Descargar imagen PNG';
    download.addEventListener('click', () => canvas.toBlob((blob) => {
      if (!blob) return;
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${(chart.title || 'grafico').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 60) || 'grafico'}.png`;
      link.click();
      window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }, 'image/png'));
    card.append(title, canvas, download);
    bubble.appendChild(card);

    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const width = canvas.width;
    const height = canvas.height;
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, width, height);
    ctx.fillStyle = '#173f52';
    ctx.font = 'bold 26px system-ui, sans-serif';
    ctx.fillText(chart.title || 'Gráfico', 42, 48);

    if (chart.type === 'doughnut') {
      const values = series[0].data.map((value) => Math.max(0, Number(value) || 0));
      const total = values.reduce((sum, value) => sum + value, 0);
      if (total <= 0) return;
      let angle = -Math.PI / 2;
      values.forEach((value, index) => {
        const next = angle + (value / total) * Math.PI * 2;
        ctx.beginPath();
        ctx.moveTo(285, 310);
        ctx.arc(285, 310, 185, angle, next);
        ctx.closePath();
        ctx.fillStyle = colors[index % colors.length];
        ctx.fill();
        angle = next;
      });
      ctx.beginPath();
      ctx.arc(285, 310, 92, 0, Math.PI * 2);
      ctx.fillStyle = '#fff';
      ctx.fill();
      ctx.textAlign = 'center';
      ctx.font = 'bold 28px system-ui, sans-serif';
      ctx.fillStyle = '#173f52';
      ctx.fillText(total.toLocaleString('es-CL'), 285, 320);
      ctx.textAlign = 'left';
      ctx.font = '19px system-ui, sans-serif';
      labels.forEach((label, index) => {
        const y = 145 + index * 32;
        ctx.fillStyle = colors[index % colors.length];
        ctx.fillRect(540, y - 15, 18, 18);
        ctx.fillStyle = '#263d47';
        ctx.fillText(`${label}: ${values[index].toLocaleString('es-CL')}`, 572, y, 350);
      });
      return;
    }

    const left = 100;
    const right = width - 30;
    const top = 92;
    const bottom = height - 135;
    const values = series.flatMap((item) => item.data.map((value) => Number(value) || 0));
    const min = Math.min(0, ...values);
    const max = Math.max(1, ...values);
    const yFor = (value) => bottom - ((value - min) / (max - min || 1)) * (bottom - top);
    const zeroY = yFor(0);
    ctx.font = '15px system-ui, sans-serif';
    ctx.textAlign = 'right';
    for (let step = 0; step <= 4; step++) {
      const value = max - (max - min) * step / 4;
      const y = top + (bottom - top) * step / 4;
      ctx.strokeStyle = '#e4ecef';
      ctx.beginPath();
      ctx.moveTo(left, y);
      ctx.lineTo(right, y);
      ctx.stroke();
      ctx.fillStyle = '#526872';
      ctx.fillText(value.toLocaleString('es-CL', { maximumFractionDigits: 1 }), left - 12, y + 5);
    }
    const slot = (right - left) / labels.length;
    if (chart.type === 'line') {
      series.forEach((item, seriesIndex) => {
        ctx.strokeStyle = colors[seriesIndex % colors.length];
        ctx.fillStyle = colors[seriesIndex % colors.length];
        ctx.lineWidth = 4;
        ctx.beginPath();
        item.data.forEach((raw, index) => {
          const x = left + slot * (index + .5);
          const y = yFor(Number(raw) || 0);
          if (index === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.stroke();
        item.data.forEach((raw, index) => {
          ctx.beginPath();
          ctx.arc(left + slot * (index + .5), yFor(Number(raw) || 0), 6, 0, Math.PI * 2);
          ctx.fill();
        });
      });
    } else {
      const groupWidth = slot * .68;
      const barWidth = groupWidth / series.length;
      series.forEach((item, seriesIndex) => item.data.forEach((raw, index) => {
        const value = Number(raw) || 0;
        const x = left + slot * index + (slot - groupWidth) / 2 + barWidth * seriesIndex;
        const y = yFor(value);
        ctx.fillStyle = colors[seriesIndex % colors.length];
        ctx.fillRect(x, Math.min(y, zeroY), Math.max(4, barWidth - 5), Math.max(1, Math.abs(zeroY - y)));
      }));
    }
    labels.forEach((label, index) => {
      ctx.save();
      ctx.translate(left + slot * (index + .5), bottom + 22);
      ctx.rotate(-Math.PI / 5);
      ctx.textAlign = 'right';
      ctx.fillStyle = '#354e58';
      ctx.font = '16px system-ui, sans-serif';
      ctx.fillText(label, 0, 0, 145);
      ctx.restore();
    });
    ctx.textAlign = 'left';
    ctx.font = '17px system-ui, sans-serif';
    series.forEach((item, index) => {
      const x = left + index * 220;
      ctx.fillStyle = colors[index % colors.length];
      ctx.fillRect(x, height - 25, 16, 16);
      ctx.fillStyle = '#263d47';
      ctx.fillText(item.name, x + 24, height - 11, 180);
    });
  }

  function showWelcome() {
    messages.replaceChildren();
    addMessage('Hola. Puedo ayudarle a buscar facturas, clientes, proveedores y cotizaciones. Por ejemplo: “¿Qué facturas están vencidas este año?”');
  }

  function setBusy(value) {
    busy = value;
    const reachedLimit = currentQuota && !currentQuota.is_admin && currentQuota.remaining < 1;
    input.disabled = value || reachedLimit;
    sendButton.disabled = value || reachedLimit;
    widget.setAttribute('aria-busy', value ? 'true' : 'false');
    typing.hidden = !value;
  }

  async function openConversation(id) {
    const result = await request(`/conversations/${encodeURIComponent(id)}`);
    conversationId = result.conversation.id;
    messages.replaceChildren();
    result.data.forEach((message) => {
      addMessage(message.content, message.role, message.role === 'assistant', message.chart);
    });
    historyPanel.hidden = true;
    input.focus();
  }

  async function refreshHistory(selectLatest = false) {
    const result = await request('/conversations');
    conversationsLoaded = true;
    historyPanel.replaceChildren();

    if (!result.data.length) {
      const empty = document.createElement('p');
      empty.className = 'cb-history-empty';
      empty.textContent = 'Tus conversaciones aparecerán aquí.';
      historyPanel.appendChild(empty);
      if (selectLatest) showWelcome();
      return;
    }

    result.data.forEach((conversation) => {
      const row = document.createElement('div');
      row.className = 'cb-history-row';
      const select = document.createElement('button');
      select.type = 'button';
      select.className = 'cb-history-select';
      select.textContent = conversation.title || 'Conversación';
      select.title = conversation.title || 'Conversación';
      select.addEventListener('click', () => openConversation(conversation.id).catch(showError));

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'cb-history-delete';
      remove.textContent = '×';
      remove.setAttribute('aria-label', `Eliminar ${conversation.title || 'conversación'}`);
      remove.addEventListener('click', async () => {
        try {
          await request(`/conversations/${encodeURIComponent(conversation.id)}`, { method: 'DELETE' });
          if (conversationId === conversation.id) {
            conversationId = null;
            showWelcome();
          }
          await refreshHistory(false);
        } catch (error) {
          showError(error);
        }
      });

      row.append(select, remove);
      historyPanel.appendChild(row);
    });

    if (selectLatest && !conversationId) {
      await openConversation(result.data[0].id);
    }
  }

  function showError(error) {
    addMessage(error?.message || 'No se pudo completar la consulta.', 'assistant');
  }

  async function sendMessage(event) {
    event.preventDefault();
    if (busy) return;
    const message = input.value.trim();
    if (!message) return;

    addMessage(message, 'user');
    input.value = '';
    setBusy(true);
    try {
      const result = await request('/messages', {
        method: 'POST',
        body: JSON.stringify({ conversation_id: conversationId, message }),
      });
      applyQuota(result.quota);
      conversationId = result.conversation_id;
      addMessage(result.message.content, 'assistant', true, result.message.chart);
      conversationsLoaded = false;
    } catch (error) {
      if (error?.payload?.quota) applyQuota(error.payload.quota);
      showError(error);
    } finally {
      setBusy(false);
      input.focus();
    }
  }

  toggle.addEventListener('click', async () => {
    const open = widget.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      try {
        await refreshQuota();
      } catch (error) {
        console.warn('No se pudo actualizar el contador diario del asistente.');
      }
      if (!conversationsLoaded) {
        try {
          await refreshHistory(!conversationId);
        } catch (error) {
          showError(error);
        }
      }
      input.focus();
    }
  });

  widget.querySelector('#cb-history').addEventListener('click', async () => {
    historyPanel.hidden = !historyPanel.hidden;
    if (!historyPanel.hidden) {
      try {
        await refreshHistory(false);
      } catch (error) {
        showError(error);
      }
    }
  });
  widget.querySelector('#cb-new').addEventListener('click', () => {
    conversationId = null;
    historyPanel.hidden = true;
    showWelcome();
    input.focus();
  });
  widget.querySelector('#cb-close').addEventListener('click', () => {
    widget.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
  });
  form.addEventListener('submit', sendMessage);
  input.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      form.requestSubmit();
    }
  });

  showWelcome();
})();
