<?php
declare(strict_types=1);

// Chạy kiểm tra: TEST_DB_NAME=bookbot_test php tests/chatbot.php
// Mọi bảng kiểm tra đều là bảng TEMPORARY và tự mất khi đóng kết nối PDO này.
$database = getenv('TEST_DB_NAME');
if (!$database) throw new RuntimeException('Set TEST_DB_NAME to an existing disposable database.');
putenv('DB_NAME=' . $database);
require __DIR__ . '/../bookstory-ai-chatbot/api/bootstrap.php';
require __DIR__ . '/../bookstory-ai-chatbot/api/answers.php';
$config['openai']['api_key'] = ''; // Không tiêu tốn hạn mức API thật trong bộ kiểm tra này.
$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}

$server = $_SERVER;
$oldPath = getenv('BOOKBOT_SITE_PATH');
putenv('BOOKBOT_SITE_PATH');
foreach ([
    ['/index.php', '/index.php', ''],
    ['/index.php', '/renamed-shop/index.php', '/renamed-shop'],
    ['/pages/shop.php', '/nested/store/pages/shop.php', '/nested/store'],
    ['/bookstory-ai-chatbot/api/chat.php', '/store/bookstory-ai-chatbot/api/chat.php', '/store'],
] as [$file, $url, $prefix]) {
    $_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . $file;
    $_SERVER['SCRIPT_NAME'] = $url;
    $settings = require __DIR__ . '/../bookstory-ai-chatbot/config.php';
    check($settings['site_base_path'] === $prefix, 'Wrong base path for ' . $url);
    check($settings['chatbot_url_path'] === $prefix . '/bookstory-ai-chatbot', 'Wrong API/assets path');
    check($settings['image_base_url'] === $prefix . '/uploaded_img/', 'Wrong image directory');
}
putenv('BOOKBOT_SITE_PATH=/public-shop/');
$settings = require __DIR__ . '/../bookstory-ai-chatbot/config.php';
check($settings['site_base_path'] === '/public-shop', 'Public URL override ignored');
putenv($oldPath === false ? 'BOOKBOT_SITE_PATH' : 'BOOKBOT_SITE_PATH=' . $oldPath);
$_SERVER = $server;

foreach ([
    ['dưới 200.000 đồng', 200000.0], ['dưới 200,000 VND', 200000.0],
    ['không quá 200k', 200000.0], ['duoi 200', 200000.0],
    ['tối đa 1,5 triệu', 1500000.0], ['dưới 1.5tr', 1500000.0],
    ['<=200000', 200000.0], ['dưới 0k', 0.0], ['Python', null],
] as [$text, $expected]) {
    check(bb_parse_budget($text) === $expected, 'Wrong budget: ' . $text);
}
check(bb_search_terms('Gợi ý sách hay cho người mới bắt đầu') === [], 'Generic recommendation became a topic');
check(bb_search_terms('Tìm sách lập trình cho người mới học dưới 200k') === ['lap', 'trinh'], 'Budget/filler leaked into keywords');
check(bb_search_terms('Tìm sách Python dưới 200.000 đồng') === ['python'], 'Price became a keyword');

$pdo = bb_db();
$pdo->exec('CREATE TEMPORARY TABLE products (
    id INT PRIMARY KEY, name VARCHAR(255), details TEXT, category VARCHAR(100),
    price DECIMAL(12,2), image VARCHAR(255), stock INT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec("INSERT INTO products VALUES
    (1, 'Python cơ bản', 'Dành cho người bắt đầu', 'Lập trình', 150000, 'python.jpg', 8),
    (2, 'Python nâng cao', 'Kiến thức chuyên sâu', 'Lập trình', 250000, 'advanced.jpg', 5),
    (3, 'Python thực hành', 'Bài tập lập trình', 'Lập trình', 180000, 'practice.jpg', 0),
    (4, 'Jujutsu Kaisen - Tập 23', 'Truyện tranh Nhật Bản', 'Truyện tranh', 27000, 'jujustukaisen23.png', 6),
    (5, 'Lập nghiệp', 'Câu chuyện kinh doanh', 'Tiểu thuyết', 45000, 'story.jpg', 4),
    (6, 'Hai vạn dặm dưới biển', 'Tiểu thuyết phiêu lưu', 'Tiểu thuyết', 89000, 'sea.jpg', 4),
    (7, 'SQL cơ bản', 'Học cơ sở dữ liệu', 'Lập trình', 100000, 'sql.jpg', 5)");
$pdo->exec("CREATE TEMPORARY TABLE ai_book_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY, visitor_key CHAR(64), user_id INT NULL,
    product_id INT, event_type VARCHAR(20), query_text VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TEMPORARY TABLE ai_book_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY, visitor_key CHAR(64), user_id INT NULL,
    role VARCHAR(20), message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$rows = bb_search_products('Tìm sách Python dưới 200k', 5, bb_parse_budget('dưới 200k'));
check(array_column($rows, 'id') === [3, 1], 'Topic and budget were not both enforced');
check(bb_search_products('Tìm sách Rust') === [], 'Unknown topic returned unrelated books');
$rows = bb_search_products('Tìm sách lập trình cho người mới học');
check(count($rows) === 4, 'Accented category search failed or matched Lập nghiệp');
check(count(bb_search_products('tim sach lap trinh')) === 4, 'Unaccented search failed');
check(bb_search_products('Gợi ý sách hay') === [], 'Empty search returned arbitrary products');
$config['product_columns']['stock'] = 'stock';
$rows = bb_search_products('Python', 5, 200000);
check(array_column($rows, 'id') === [1], 'Out-of-stock book was returned');
$config['product_columns']['stock'] = null;

foreach (['Phí giao hàng bao nhiêu?', 'phi ship bao nhieu', 'Chính sách đổi trả thế nào?', 'Thanh toán bằng cách nào?'] as $question) {
    $reply = bb_chat_reply($question);
    check($reply['products'] === [], 'FAQ was replaced by products: ' . $question);
    check($reply['ai_enabled'] === false, 'FAQ must use configured policy text');
}
check(str_contains(bb_chat_reply('Phí giao hàng bao nhiêu?')['answer'], $config['store_faq']['shipping']), 'Shipping answer changed');
check(count(bb_faq_context('Giao hàng và đổi trả thế nào?')) === 2, 'Multiple FAQ topics were lost');
check(bb_chat_reply('Xin chào!')['products'] === [], 'Greeting searched product titles');
check(bb_chat_reply('Tìm sách Rust')['products'] === [], 'Missing topic fell back to recommendations');
$reply = bb_chat_reply('Tìm sách Python dưới 100k');
check($reply['products'] === [] && str_contains($reply['answer'], '100.000'), 'No-match budget answer is misleading');
check(count(bb_chat_reply('Gợi ý sách hay cho người mới bắt đầu')['products']) === 5, 'Generic recommendations failed');

$config['site_base_path'] = '/store';
$config['image_base_url'] = '/store/uploaded_img/';
check(bb_image_url('book.jpg') === '/store/uploaded_img/book.jpg', 'Bare image filename is wrong');
check(bb_image_url('uploaded_img/book.jpg') === '/store/uploaded_img/book.jpg', 'Image prefix duplicated');
check(bb_image_url('https://example.test/book.jpg') === 'https://example.test/book.jpg', 'Absolute image URL changed');
check(bb_product_url(4) === '/store/pages/view_page.php?pid=4', 'Wrong product link');

$rows = bb_recommend_rows(5);
check(count(array_filter($rows, fn($row) => $row['_personalized'])) === 0, 'New visitor marked personalized');
$statement = $pdo->prepare("INSERT INTO ai_book_interactions (visitor_key, product_id, event_type) VALUES (?, 1, 'favorite')");
$statement->execute([bb_visitor_key()]);
$rows = bb_recommend_rows(5);
check($rows[0]['category'] === 'Lập trình' && $rows[0]['_personalized'], 'Interest did not affect recommendations');
bb_save_message('user', 'Python');
bb_save_message('assistant', 'Sách phù hợp');
check((int)$pdo->query('SELECT COUNT(*) FROM ai_book_chat_messages')->fetchColumn() === 2, 'Conversation not saved');

// Giả lập dịch vụ AI bị gián đoạn mà không gọi bất kỳ dịch vụ bên ngoài nào.
$config['openai'] = ['api_key' => 'local-test-only', 'endpoint' => 'http://127.0.0.1:1', 'timeout_seconds' => 1];
$reply = bb_chat_reply('Jujutsu Kaisen');
check($reply['ai_enabled'] === false && $reply['products'][0]['id'] === 4, 'AI outage broke catalog fallback');
echo "BookBot: {$checks} checks passed (paths, search, budgets, FAQ, recommendations, logging, AI fallback).\n";
