<?php
/** Include this file near the end of your page, just before </body>. */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) return;
$bookbotConfig = require $configFile;
if (empty($_SESSION['bookbot_csrf'])) $_SESSION['bookbot_csrf'] = bin2hex(random_bytes(32));
$basePath = rtrim((string)($bookbotConfig['site_base_path'] ?? ''), '/');
$widgetBase = rtrim((string)($bookbotConfig['chatbot_url_path'] ?? ($basePath . '/bookstory-ai-chatbot')), '/');
$csrf = (string)$_SESSION['bookbot_csrf'];
?>
<link rel="stylesheet" href="<?= htmlspecialchars($widgetBase, ENT_QUOTES, 'UTF-8') ?>/assets/chatbot.css?v=<?= filemtime(__DIR__ . '/assets/chatbot.css') ?>">
<div id="bookbot-root"
     data-base="<?= htmlspecialchars($widgetBase, ENT_QUOTES, 'UTF-8') ?>"
     data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"
     data-currency="<?= htmlspecialchars((string)($bookbotConfig['currency_symbol'] ?? '₫'), ENT_QUOTES, 'UTF-8') ?>">
  <button class="bb-launcher" type="button" aria-label="Mở trợ lý tư vấn sách" aria-expanded="false">
    <span class="bb-launcher-icon">✦</span><span>Hỏi BookBot</span>
  </button>
  <section class="bb-panel" aria-label="Chatbot tư vấn sách" hidden>
    <header class="bb-header">
      <div class="bb-avatar">B</div>
      <div class="bb-heading"><strong>BookBot</strong><small>Trợ lý tư vấn sách</small></div>
      <button class="bb-close" type="button" aria-label="Đóng">×</button>
    </header>
    <nav class="bb-tabs" aria-label="Chức năng chatbot">
      <button type="button" class="bb-tab is-active" data-tab="chat">Tư vấn</button>
      <button type="button" class="bb-tab" data-tab="recommend">Dành cho bạn</button>
    </nav>
    <div class="bb-chat-view">
      <div class="bb-messages" aria-live="polite">
        <div class="bb-message bb-bot">Xin chào! Mình là BookBot. Bạn đang muốn tìm sách về chủ đề nào?</div>
      </div>
      <div class="bb-quick-prompts">
        <button type="button" data-prompt="Gợi ý sách hay cho người mới bắt đầu">Sách cho người mới</button>
        <button type="button" data-prompt="Gợi ý sách dưới 200.000 đồng">Sách dưới 200.000₫</button>
        <button type="button" data-prompt="Tìm sách lập trình cho người mới học">Sách lập trình</button>
      </div>
      <form class="bb-form">
        <input class="bb-input" type="text" maxlength="1500" placeholder="Nhập câu hỏi hoặc tên sách..." autocomplete="off" aria-label="Nhập câu hỏi">
        <button class="bb-send" type="submit" aria-label="Gửi">➤</button>
      </form>
      <div class="bb-disclaimer">Giá theo dữ liệu cửa hàng. Liên hệ cửa hàng để xác nhận còn sách.</div>
    </div>
    <div class="bb-recommend-view" hidden>
      <p class="bb-recommend-intro">Gợi ý dựa trên hoạt động của bạn và những sản phẩm được quan tâm.</p>
      <div class="bb-recommend-grid"></div>
    </div>
  </section>
</div>
<script src="<?= htmlspecialchars($widgetBase, ENT_QUOTES, 'UTF-8') ?>/assets/chatbot.js?v=<?= filemtime(__DIR__ . '/assets/chatbot.js') ?>" defer></script>
