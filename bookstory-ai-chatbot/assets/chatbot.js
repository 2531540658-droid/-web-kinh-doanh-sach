(() => {
  'use strict';
  const root = document.getElementById('bookbot-root');
  if (!root) return;
  const base = root.dataset.base || '';
  const csrf = root.dataset.csrf || '';
  const currency = root.dataset.currency || '₫';
  const launcher = root.querySelector('.bb-launcher');
  const panel = root.querySelector('.bb-panel');
  const closeBtn = root.querySelector('.bb-close');
  const chatView = root.querySelector('.bb-chat-view');
  const recommendView = root.querySelector('.bb-recommend-view');
  const messages = root.querySelector('.bb-messages');
  const form = root.querySelector('.bb-form');
  const input = root.querySelector('.bb-input');
  const sendBtn = root.querySelector('.bb-send');
  const recommendGrid = root.querySelector('.bb-recommend-grid');
  const tabs = [...root.querySelectorAll('.bb-tab')];
  let busy = false;

  const money = value => value === null || value === undefined ? 'Liên hệ' : new Intl.NumberFormat('vi-VN').format(Number(value)) + ' ' + currency;
  const element = (tag, cls, text) => {
    const el = document.createElement(tag);
    if (cls) el.className = cls;
    if (text !== undefined) el.textContent = text;
    return el;
  };

  function addMessage(text, type = 'bot') {
    const bubble = element('div', 'bb-message ' + (type === 'user' ? 'bb-user' : 'bb-bot'), text);
    messages.appendChild(bubble);
    messages.scrollTop = messages.scrollHeight;
    return bubble;
  }

  function productCard(product) {
    const card = element('article', 'bb-product');
    const link = element('a', 'bb-product-link');
    link.href = product.url || '#';
    link.target = '_self';
    const imageBox = element('div', 'bb-product-image');
    if (product.image) {
      const img = document.createElement('img');
      img.src = product.image;
      img.alt = product.name || 'Sách';
      img.loading = 'lazy';
      img.onerror = () => { img.remove(); imageBox.appendChild(element('span', 'bb-product-no-image', '📚')); };
      imageBox.appendChild(img);
    } else {
      imageBox.appendChild(element('span', 'bb-product-no-image', '📚'));
    }
    const info = element('div', 'bb-product-info');
    info.appendChild(element('div', 'bb-product-name', product.name || 'Sản phẩm'));
    info.appendChild(element('div', 'bb-product-price', money(product.price)));
    let meta = product.category || '';
    if (product.stock !== null && product.stock !== undefined) meta += (meta ? ' · ' : '') + (Number(product.stock) > 0 ? 'Còn ' + product.stock : 'Hết hàng');
    if (meta) info.appendChild(element('div', 'bb-product-meta', meta));
    link.appendChild(imageBox);
    link.appendChild(info);
    link.addEventListener('click', () => track('click', product.id));
    card.appendChild(link);
    if (product.reason) card.appendChild(element('div', 'bb-product-reason', product.reason));
    return card;
  }

  function renderProducts(container, products) {
    container.replaceChildren();
    if (!Array.isArray(products) || products.length === 0) {
      container.appendChild(element('div', 'bb-empty', 'Chưa có đủ dữ liệu để đưa ra gợi ý. Hãy xem hoặc tìm một vài cuốn sách trước nhé.'));
      return;
    }
    products.forEach(product => container.appendChild(productCard(product)));
  }

  async function request(path, body, keepalive = false) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 40000);
    try {
      const response = await fetch(base + path, {
        method: body === undefined ? 'GET' : 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: body === undefined ? undefined : JSON.stringify(body),
        signal: controller.signal,
        keepalive
      });
      const data = await response.json().catch(() => null);
      if (!data || typeof data !== 'object') throw new Error('Máy chủ chưa trả về dữ liệu hợp lệ. Bạn vui lòng thử lại.');
      if (!response.ok || data.ok === false) throw new Error(data.error || 'Có lỗi khi kết nối máy chủ.');
      return data;
    } catch (error) {
      if (error.name === 'AbortError') throw new Error('Chatbot phản hồi chậm. Bạn vui lòng thử lại.');
      throw error;
    } finally {
      clearTimeout(timeout);
    }
  }

  async function track(event, productId, query = '') {
    if (!productId) return;
    try { await request('/api/track.php', { event, product_id: Number(productId), query }, true); } catch (e) { /* Việc ghi nhận tương tác không được làm gián đoạn mua sắm. */ }
  }

  async function loadRecommendations() {
    recommendGrid.replaceChildren(element('div', 'bb-empty', 'Đang tải gợi ý...'));
    try {
      const data = await request('/api/recommend.php?limit=8');
      renderProducts(recommendGrid, data.products || []);
    } catch (error) {
      recommendGrid.replaceChildren(element('div', 'bb-empty', error.message));
    }
  }

  function setTab(name) {
    const isChat = name === 'chat';
    chatView.hidden = !isChat;
    recommendView.hidden = isChat;
    tabs.forEach(tab => {
      const active = tab.dataset.tab === name;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', String(active));
      tab.tabIndex = active ? 0 : -1;
    });
    if (!isChat) loadRecommendations();
  }

  function setOpen(open) {
    panel.hidden = !open;
    root.classList.toggle('is-open', open);
    launcher.setAttribute('aria-expanded', String(open));
    launcher.setAttribute('aria-label', open ? 'Đóng trợ lý tư vấn sách' : 'Mở trợ lý tư vấn sách');
    if (open) (chatView.hidden ? closeBtn : input).focus();
    else launcher.focus();
  }

  async function sendMessage(text) {
    const message = (text || '').trim();
    if (!message || busy) return;
    const welcome = root.querySelector('.bb-welcome');
    if (welcome) welcome.hidden = true;
    addMessage(message, 'user');
    input.value = '';
    busy = true;
    sendBtn.disabled = true;
    const waiting = addMessage('BookBot đang tìm sách phù hợp...', 'bot');
    waiting.classList.add('bb-thinking');
    try {
      const data = await request('/api/chat.php', { message });
      waiting.remove();
      addMessage(data.answer || 'Mình chưa có câu trả lời phù hợp.');
      if (Array.isArray(data.products) && data.products.length) {
        addMessage('Sản phẩm có thể phù hợp:', 'bot');
        const holder = element('div', 'bb-recommend-grid bb-chat-products');
        data.products.forEach(product => holder.appendChild(productCard(product)));
        messages.appendChild(holder);
        messages.scrollTop = messages.scrollHeight;
      }
    } catch (error) {
      waiting.remove();
      addMessage(error.message || 'Không thể kết nối chatbot. Vui lòng thử lại.');
    } finally {
      busy = false;
      sendBtn.disabled = false;
      input.focus();
    }
  }

  launcher.addEventListener('click', () => setOpen(panel.hidden));
  closeBtn.addEventListener('click', () => setOpen(false));
  root.addEventListener('keydown', event => { if (event.key === 'Escape') setOpen(false); });
  tabs.forEach(tab => tab.addEventListener('click', () => setTab(tab.dataset.tab)));
  tabs.forEach((tab, index) => tab.addEventListener('keydown', event => {
    let next;
    if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
    else if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
    else if (event.key === 'Home') next = 0;
    else if (event.key === 'End') next = tabs.length - 1;
    else return;
    event.preventDefault();
    setTab(tabs[next].dataset.tab);
    tabs[next].focus();
  }));
  form.addEventListener('submit', event => { event.preventDefault(); sendMessage(input.value); });
  root.querySelectorAll('[data-prompt]').forEach(button => button.addEventListener('click', () => sendMessage(button.dataset.prompt)));
  window.BookBot = window.BookBot || {};
  window.BookBot.track = track;
  window.BookBot.refreshRecommendations = loadRecommendations;
})();
