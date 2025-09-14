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

  const WEBHOOK = document.querySelector('meta[name="mcp-webhook"]')?.content || '';
  const LOCAL_PROXY = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '') + '/api/mcp/webhook';

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
    try{
      const res = await fetch(LOCAL_PROXY, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query: text, webhook: WEBHOOK })
      });
      const data = await res.json().catch(() => ({}));
      addMessage(data.respuesta || data.message || 'Sin respuesta del servidor', 'bot');
    }catch(err){
      addMessage('Error al conectar con el servidor', 'bot');
      console.error(err);
    }finally{
      setTyping(false);
    }
  }

  toggleBtn.addEventListener('click', toggleWidget);
  minBtn.addEventListener('click', minimizeWidget);
  closeBtn.addEventListener('click', () => { widget.style.display = 'none'; });
  sendBtn.addEventListener('click', send);
  input.addEventListener('keypress', e => { if(e.key === 'Enter') send(); });
})();
