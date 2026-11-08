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
  toggle.textContent = '💬';

  const widget = document.createElement('section');
  widget.id = 'chatbot-widget';
  widget.setAttribute('aria-label', 'Asistente de AcerosEra');
  widget.innerHTML = `
    <header class="cb-header">
      <div class="cb-heading"><strong class="cb-title">Asistente</strong><span class="cb-subtitle">Consultas del sistema</span></div>
      <div class="cb-actions">
        <button class="cb-iconbtn" type="button" id="cb-history" aria-label="Ver conversaciones anteriores" title="Conversaciones">☷</button>
        <button class="cb-iconbtn" type="button" id="cb-new" aria-label="Nueva conversación" title="Nueva conversación">＋</button>
        <button class="cb-iconbtn" type="button" id="cb-min" aria-label="Minimizar chat" title="Minimizar">−</button>
        <button class="cb-iconbtn" type="button" id="cb-close" aria-label="Cerrar chat" title="Cerrar">×</button>
      </div>
    </header>
    <div class="cb-history" id="cb-history-panel" hidden aria-label="Conversaciones anteriores"></div>
    <div class="cb-messages" id="cb-messages" role="log" aria-live="polite" aria-relevant="additions text"></div>
    <div class="cb-typing" id="cb-typing" hidden>Buscando en el sistema…</div>
    <form class="cb-input" id="cb-form">
      <label class="visually-hidden" for="cb-text">Escribe tu consulta</label>
      <textarea id="cb-text" rows="1" maxlength="2000" placeholder="Pregunta por clientes, facturas o montos…"></textarea>
      <button id="cb-send" type="submit">Enviar</button>
    </form>
  `;

  document.body.append(toggle, widget);

  const messages = widget.querySelector('#cb-messages');
  const historyPanel = widget.querySelector('#cb-history-panel');
  const input = widget.querySelector('#cb-text');
  const form = widget.querySelector('#cb-form');
  const sendButton = widget.querySelector('#cb-send');
  const typing = widget.querySelector('#cb-typing');
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
      throw new Error(payload.message || 'No se pudo completar la consulta.');
    }
    return payload;
  }

  function addMessage(content, role = 'assistant', isSafeHtml = false) {
    const row = document.createElement('div');
    row.className = `msg ${role === 'user' ? 'user' : 'bot'}`;
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    if (role === 'assistant' && isSafeHtml) {
      // The server converts model Markdown to sanitized HTML before returning it.
      bubble.innerHTML = content;
    } else {
      bubble.textContent = content;
    }
    row.appendChild(bubble);
    messages.appendChild(row);
    messages.scrollTop = messages.scrollHeight;
  }

  function showWelcome() {
    messages.replaceChildren();
    addMessage('Hola. Puedo buscar clientes, proveedores, facturas, montos y texto de PDFs procesados.');
  }

  function setBusy(value) {
    busy = value;
    input.disabled = value;
    sendButton.disabled = value;
    widget.setAttribute('aria-busy', value ? 'true' : 'false');
    typing.hidden = !value;
  }

  async function openConversation(id) {
    const result = await request(`/conversations/${encodeURIComponent(id)}`);
    conversationId = result.conversation.id;
    messages.replaceChildren();
    result.data.forEach((message) => {
      addMessage(message.content, message.role, message.role === 'assistant');
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
      conversationId = result.conversation_id;
      addMessage(result.message.content, 'assistant', true);
      conversationsLoaded = false;
    } catch (error) {
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
  widget.querySelector('#cb-min').addEventListener('click', () => widget.classList.toggle('is-minimized'));
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
