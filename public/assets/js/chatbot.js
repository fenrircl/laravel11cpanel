(function(){
  if(!document.body) return;

  // Elements
  const toggleBtn = document.createElement('button');
  toggleBtn.id = 'chatbot-toggle';
  toggleBtn.title = 'Abrir chat';
  toggleBtn.innerHTML = '💬';

  const widget = document.createElement('div');
  widget.id = 'chatbot-widget';
  widget.innerHTML = `
    <div class="cb-header">
      <div class="cb-title">Asistente</div>
      <div class="cb-actions">
        <button class="cb-iconbtn" id="cb-min">_</button>
        <button class="cb-iconbtn" id="cb-close">×</button>
      </div>
    </div>
    <div class="cb-messages" id="cb-messages"></div>
    <div class="cb-typing" id="cb-typing" style="display:none;">Escribiendo…</div>
    <div class="cb-input">
      <input type="text" id="cb-text" placeholder="Escribe tu mensaje..." />
      <button id="cb-send">Enviar</button>
    </div>
  `;

  document.body.appendChild(toggleBtn);
  document.body.appendChild(widget);

  const messages = widget.querySelector('#cb-messages');
  const typing = widget.querySelector('#cb-typing');
  const input = widget.querySelector('#cb-text');
  const sendBtn = widget.querySelector('#cb-send');
  const minBtn = widget.querySelector('#cb-min');
  const closeBtn = widget.querySelector('#cb-close');

  // Base URL desde meta o fallback al origen actual
  const BASE = (document.querySelector('meta[name="base-url"]')?.content || window.location.origin || '').replace(/\/$/, '');
  // Proxy local que usa N8N_WEBHOOK_URL del .env en el servidor
  const LOCAL_PROXY = BASE + '/api/mcp/webhook';

  // Ajustes para esperar más la respuesta y reintentar si falla
  const MAX_RETRIES = 2; // número de reintentos adicionales
  const RETRY_DELAY_MS = 3000; // espera entre reintentos

  function sleep(ms){ return new Promise(r => setTimeout(r, ms)); }

  function addMessage(text, from='bot'){
    const wrap = document.createElement('div');
    wrap.className = `msg ${from}`;
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.textContent = text;
    wrap.appendChild(bubble);
    messages.appendChild(wrap);
    messages.scrollTop = messages.scrollHeight;
  }

  function setTyping(v){ typing.style.display = v ? 'block' : 'none'; }

  function toggleWidget(){
    widget.style.display = widget.style.display === 'none' || !widget.style.display ? 'flex' : 'none';
  }

  function minimizeWidget(){
    if(widget.style.display === 'none') return;
    const msgArea = widget.querySelector('.cb-messages');
    const inputArea = widget.querySelector('.cb-input');
    const typingEl = widget.querySelector('#cb-typing');
    const isMin = widget.dataset.min === '1';
    if(isMin){
      msgArea.style.display = '';
      inputArea.style.display = '';
      typingEl.style.display = 'none';
      widget.dataset.min = '0';
    } else {
      msgArea.style.display = 'none';
      inputArea.style.display = 'none';
      typingEl.style.display = 'none';
      widget.dataset.min = '1';
    }
  }

  async function send(){
    const text = input.value.trim();
    if(!text) return;
    addMessage(text, 'user');
    input.value = '';
    setTyping(true);

    // Mantener al usuario informado si la respuesta tarda
    const start = Date.now();
    const typingMsgBase = 'Escribiendo…';
    typing.textContent = typingMsgBase;
    const typingUpdater = setInterval(() => {
      const elapsed = Math.floor((Date.now() - start) / 1000);
      if(elapsed >= 10){
        typing.textContent = `Procesando (${elapsed}s)…`;
      }
    }, 1000);

    try{
      let lastError;
      for(let attempt = 0; attempt <= MAX_RETRIES; attempt++){
        try{
          const res = await fetch(LOCAL_PROXY, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: text }) // El servidor usará N8N_WEBHOOK_URL del .env
          });

          // Leer contenido con tolerancia (JSON o texto plano)
          const contentType = res.headers.get('content-type') || '';
          let data;
          if(contentType.includes('application/json')){
            data = await res.json().catch(() => ({}));
          } else {
            const raw = await res.text().catch(() => '');
            data = { message: raw };
          }

          if(!res.ok){
            // Para errores 5xx/429 reintentamos, otros se muestran directo
            const msg = (data && (data.error || data.message)) || `HTTP ${res.status}`;
            if([429].includes(res.status) || (res.status >= 500 && res.status <= 599)){
              throw new Error(msg || 'Error temporal del servidor');
            } else {
              addMessage(msg || 'Error en la respuesta del servidor', 'bot');
              return;
            }
          }

          const reply = (data && (data.respuesta || data.message || data.error)) || 'Sin respuesta del servidor';
          addMessage(reply, 'bot');
          return; // éxito, salir de la función
        }catch(err){
          lastError = err;
          if(attempt < MAX_RETRIES){
            // Informar que seguimos esperando y reintentaremos
            typing.textContent = `El servidor tarda, reintentando… (${attempt + 1}/${MAX_RETRIES})`;
            await sleep(RETRY_DELAY_MS * (attempt + 1));
            continue;
          }
        }
      }
      // Si agotamos reintentos
      addMessage('El servidor está tardando más de lo esperado o no responde. Inténtalo nuevamente.', 'bot');
      if(lastError) console.error(lastError);
    }catch(err){
      addMessage('Error al conectar con el servidor', 'bot');
      console.error(err);
    }finally{
      clearInterval(typingUpdater);
      setTyping(false);
    }
  }

  toggleBtn.addEventListener('click', toggleWidget);
  minBtn.addEventListener('click', minimizeWidget);
  closeBtn.addEventListener('click', () => { widget.style.display = 'none'; });
  sendBtn.addEventListener('click', send);
  input.addEventListener('keypress', e => { if(e.key === 'Enter') send(); });
})();
