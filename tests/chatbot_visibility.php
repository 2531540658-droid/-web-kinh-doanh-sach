<?php
declare(strict_types=1);

// Không cần cơ sở dữ liệu: kiểm tra HTML mà tiện ích thực sự xuất ra.
session_start();
$checks = 0;
$originalServer = $_SERVER;
$originalQuery = $_GET;
function render_widget(string $page, string $prefix, array $query = []): string
{
    $_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/' . $page;
    $_SERVER['SCRIPT_NAME'] = $prefix . '/' . $page;
    $_GET = $query;
    ob_start();
    require __DIR__ . '/../bookstory-ai-chatbot/widget.php';
    return (string)ob_get_clean();
}
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}

foreach (['', '/bookstory', '/nested/renamed-store'] as $prefix) {
    foreach (['pages/cart.php', 'pages/checkout.php', 'pages/orders.php'] as $page) {
        check(trim(render_widget($page, $prefix)) === '', 'Widget/assets leaked onto ' . $prefix . '/' . $page);
    }
    foreach (['pending', 'completed', 'success'] as $status) {
        check(trim(render_widget('pages/checkout.php', $prefix, ['order_id' => 123, 'status' => $status])) === '', 'Widget leaked onto order/payment result');
    }
    foreach (['index.php', 'pages/shop.php', 'pages/view_page.php', 'pages/search_page.php', 'pages/contact.php'] as $page) {
        $html = render_widget($page, $prefix);
        check(str_contains($html, 'id="bookbot-root"'), 'Widget missing from ' . $page);
        check(str_contains($html, 'data-base="' . $prefix . '/bookstory-ai-chatbot"'), 'Wrong assets path on ' . $page);
    }
}
check(str_contains(render_widget('index.php', '/store', ['page' => 'cart.php']), 'id="bookbot-root"'), 'Query string incorrectly hides homepage widget');
$_SERVER = $originalServer;
$_GET = $originalQuery;
echo "BookBot visibility: {$checks} checks passed (cart, checkout/results, orders, browsing pages, root and nested URLs).\n";
