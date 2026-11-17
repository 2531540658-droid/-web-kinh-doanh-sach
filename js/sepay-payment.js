(() => {
  const card = document.getElementById('sepay-payment-card');
  if (!card) return;
  const status = card.querySelector('#payment-status');
  const pending = card.querySelector('.sepay-pending-content');
  const confirmation = card.querySelector('#payment-confirmation');
  const waiting = card.querySelector('.sepay-waiting');
  const errorBox = card.querySelector('.sepay-status-error');
  const errorMessage = card.querySelector('[data-payment-error-message]');
  const retry = card.querySelector('[data-payment-retry]');
  const errorLink = card.querySelector('[data-payment-error-link]');
  let timer;
  let inFlight = false;
  let stopped = false;
  let failures = 0;

  function showError(message, link = null) {
    status.textContent = 'Chưa xác nhận được trạng thái thanh toán';
    status.className = 'payment-status payment-status-error';
    waiting.hidden = true;
    errorMessage.textContent = message;
    errorBox.hidden = false;
    retry.hidden = stopped;
    errorLink.hidden = !link;
    if (link) {
      errorLink.href = link.href;
      errorLink.textContent = link.text;
    }
  }

  async function refresh() {
    if (inFlight || stopped) return;
    window.clearTimeout(timer);
    inFlight = true;
    retry.disabled = true;
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), 8000);
    try {
      const response = await fetch('payment_status.php?order_id=' + encodeURIComponent(card.dataset.orderId), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
        cache: 'no-store',
        signal: controller.signal,
      });
      if (response.status === 401 || response.status === 403) {
        stopped = true;
        showError('Phiên đăng nhập đã hết hạn. Đăng nhập lại để xem trạng thái đơn hàng.', {
          href: 'login.php', text: 'Đăng nhập lại',
        });
        return;
      }
      if (response.status === 404) {
        stopped = true;
        showError('Không tìm thấy đơn hàng để kiểm tra. Nếu đã chuyển tiền, vui lòng liên hệ cửa hàng.', {
          href: 'orders.php', text: 'Xem đơn hàng',
        });
        return;
      }
      if (!response.ok || !response.headers.get('Content-Type')?.includes('application/json')) {
        throw new Error('Payment status unavailable');
      }
      const result = await response.json();
      if (!result || typeof result.payment_status !== 'string') {
        throw new Error('Invalid payment status response');
      }
      if (result.payment_status === 'completed') {
        stopped = true;
        status.textContent = 'Đã thanh toán thành công';
        status.className = 'payment-status payment-status-success';
        card.classList.add('is-completed');
        pending.hidden = true;
        confirmation.hidden = false;
        errorBox.hidden = true;
        return;
      }
      if (result.payment_status !== 'pending') {
        stopped = true;
        showError('Trạng thái đơn hàng đã thay đổi. Vui lòng xem lại đơn hàng trước khi thanh toán.', {
          href: 'orders.php', text: 'Xem đơn hàng',
        });
        return;
      }
      failures = 0;
      errorBox.hidden = true;
      waiting.hidden = false;
      status.textContent = 'Đang chờ thanh toán';
      status.className = 'payment-status payment-status-pending';
      timer = window.setTimeout(refresh, 3000);
    } catch (error) {
      failures += 1;
      showError('Chưa lấy được trạng thái thanh toán. Nếu đã chuyển tiền, không chuyển lại; hãy kiểm tra lại hoặc liên hệ cửa hàng.');
      // Giới hạn số lần thử lại và không để các yêu cầu chồng nhau. Vẫn cho phép thử lại thủ công.
      if (failures < 3) timer = window.setTimeout(refresh, 5000 * failures);
    } finally {
      window.clearTimeout(timeout);
      inFlight = false;
      retry.disabled = false;
    }
  }

  retry.addEventListener('click', () => {
    if (inFlight) return;
    failures = 0;
    refresh();
  });
  refresh();
})();
