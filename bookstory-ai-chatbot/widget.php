<?php
/** Include this file near the end of your page, just before </body>. */
$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) return;
$bookbotConfig = require $configFile;

// Check the executing page, not query parameters or the previous page.
// No launcher, panel, assets or chatbot requests on purchase-related pages.
$pageFile = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
$projectDirectory = str_replace('\\', '/', dirname(__DIR__)) . '/';
$relativePage = str_starts_with($pageFile, $projectDirectory) ? substr($pageFile, strlen($projectDirectory)) : '';
if (!empty($bookbotConfig['hidden_pages'][$relativePage])) return;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
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
  <button class="bb-launcher" type="button" aria-label="Mở trợ lý tư vấn sách" aria-expanded="false" aria-controls="bb-panel">
    <svg class="bb-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 6.5C9.2 4.7 6.1 4.2 3 5v14c3.1-.8 6.2-.3 9 1.5 2.8-1.8 5.9-2.3 9-1.5V5c-3.1-.8-6.2-.3-9 1.5Zm0 0v14"/></svg>
    <span>Hỏi BookBot</span>
  </button>
  <section class="bb-panel" id="bb-panel" role="dialog" aria-labelledby="bb-title" hidden>
    <header class="bb-header">
      <div class="bb-avatar" aria-hidden="true"><svg class="bb-icon" viewBox="0 0 24 24"><path d="M12 6.5C9.2 4.7 6.1 4.2 3 5v14c3.1-.8 6.2-.3 9 1.5 2.8-1.8 5.9-2.3 9-1.5V5c-3.1-.8-6.2-.3-9 1.5Zm0 0v14"/></svg></div>
      <div class="bb-heading"><strong id="bb-title">BookBot<span>by BookStory</span></strong><small>Cùng bạn tìm cuốn sách tiếp theo</small></div>
      <button class="bb-close" type="button" aria-label="Đóng trợ lý"><svg class="bb-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
    </header>
    <nav class="bb-tabs" role="tablist" aria-label="Chức năng chatbot">
      <button type="button" class="bb-tab is-active" data-tab="chat" id="bb-tab-chat" role="tab" aria-selected="true" aria-controls="bb-chat-view">Tư vấn sách</button>
      <button type="button" class="bb-tab" data-tab="recommend" id="bb-tab-recommend" role="tab" aria-selected="false" aria-controls="bb-recommend-view" tabindex="-1">Dành cho bạn</button>
    </nav>
    <div class="bb-chat-view" id="bb-chat-view" role="tabpanel" aria-labelledby="bb-tab-chat">
      <div class="bb-messages" aria-live="polite">
        <div class="bb-welcome">
          <span class="bb-eyebrow">Góc nhỏ cho người yêu sách</span>
          <h3>Hôm nay bạn muốn đọc gì?</h3>
          <p>Kể mình nghe tên sách, chủ đề hoặc ngân sách. Mình sẽ cùng bạn chọn một cuốn phù hợp.</p>
        </div>
      </div>
      <div class="bb-quick-prompts">
        <button type="button" data-prompt="Gợi ý sách hay">Gợi ý sách</button>
        <button type="button" data-prompt="Gợi ý sách dưới 200.000 đồng">Dưới 200k</button>
        <button type="button" data-prompt="Tìm sách truyện tranh">Truyện tranh</button>
      </div>
      <form class="bb-form">
        <input class="bb-input" type="text" maxlength="1500" placeholder="Bạn đang tìm sách gì?" autocomplete="off" aria-label="Nhập câu hỏi">
        <button class="bb-send" type="submit" aria-label="Gửi câu hỏi"><svg class="bb-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 7-7 7 7M12 5v14"/></svg></button>
      </form>
      <div class="bb-disclaimer">Giá theo cửa hàng · Vui lòng xác nhận tình trạng sách.</div>
    </div>
    <div class="bb-recommend-view" id="bb-recommend-view" role="tabpanel" aria-labelledby="bb-tab-recommend" hidden>
      <p class="bb-recommend-intro">Một vài cuốn sách để bạn khám phá, dựa trên hoạt động của bạn và sự quan tâm của độc giả.</p>
      <div class="bb-recommend-grid"></div>
    </div>
  </section>
</div>
<script src="<?= htmlspecialchars($widgetBase, ENT_QUOTES, 'UTF-8') ?>/assets/chatbot.js?v=<?= filemtime(__DIR__ . '/assets/chatbot.js') ?>" defer></script>
