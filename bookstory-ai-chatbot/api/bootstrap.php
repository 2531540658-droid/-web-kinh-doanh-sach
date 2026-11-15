<?php
declare(strict_types=1);

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Chatbot tạm thời chưa sẵn sàng. Vui lòng liên hệ cửa hàng.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$config = require $configFile;
require_once __DIR__ . '/search.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function bb_json(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bb_db(): PDO
{
    global $config;
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $db = $config['db'];
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], (int)$db['port'], $db['name'], $db['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function bb_identifier(string $name): string
{
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
        throw new RuntimeException('Tên bảng/cột trong config.php không hợp lệ.');
    }
    return '`' . $name . '`';
}

function bb_table(string $key): string
{
    global $config;
    return bb_identifier((string)$config[$key]);
}

function bb_col(string $key, string $tableAlias = ''): ?string
{
    global $config;
    $column = $config['product_columns'][$key] ?? null;
    if ($column === null || $column === '') return null;
    return ($tableAlias !== '' ? $tableAlias . '.' : '') . bb_identifier((string)$column);
}

function bb_user_id(): ?int
{
    global $config;
    $key = $config['user_session_key'] ?? 'user_id';
    $value = $_SESSION[$key] ?? null;
    return (is_numeric($value) && (int)$value > 0) ? (int)$value : null;
}

function bb_visitor_key(): string
{
    return hash('sha256', session_id());
}

function bb_csrf_token(): string
{
    if (empty($_SESSION['bookbot_csrf'])) {
        $_SESSION['bookbot_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['bookbot_csrf'];
}

function bb_require_csrf(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals(bb_csrf_token(), $sent)) {
        bb_json(['error' => 'Phiên làm việc hết hạn. Hãy tải lại trang và thử lại.'], 403);
    }
}

function bb_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        bb_json(['error' => 'Phương thức không được hỗ trợ.'], 405);
    }
    bb_require_csrf();
}

function bb_product_select(string $alias = 'p'): string
{
    $map = [
        'id' => bb_col('id', $alias),
        'name' => bb_col('name', $alias),
        'description' => bb_col('description', $alias),
        'category' => bb_col('category', $alias),
        'price' => bb_col('price', $alias),
        'image' => bb_col('image', $alias),
        'stock' => bb_col('stock', $alias),
    ];
    $parts = [];
    foreach ($map as $key => $expr) {
        $parts[] = ($expr ?? 'NULL') . ' AS `' . $key . '`';
    }
    return implode(', ', $parts);
}

function bb_product_url(int $id): string
{
    global $config;
    $url = str_replace('{id}', (string)$id, (string)$config['product_url_template']);
    if (preg_match('#^https?://#i', $url)) return $url;
    $base = rtrim((string)$config['site_base_path'], '/');
    return $base . '/' . ltrim($url, '/');
}

function bb_image_url(?string $image): ?string
{
    global $config;
    if ($image === null || trim($image) === '') return null;
    $image = trim($image);
    if (preg_match('#^https?://#i', $image) || str_starts_with($image, '/')) return $image;
    if (str_starts_with($image, 'uploaded_img/')) {
        return rtrim((string)$config['site_base_path'], '/') . '/' . $image;
    }
    return rtrim((string)$config['image_base_url'], '/') . '/' . ltrim($image, '/');
}

function bb_format_product(array $row, ?string $reason = null): array
{
    return [
        'id' => (int)$row['id'],
        'name' => (string)($row['name'] ?? 'Sản phẩm'),
        'description' => mb_substr(trim(strip_tags((string)($row['description'] ?? ''))), 0, 240),
        'category' => (string)($row['category'] ?? ''),
        'price' => is_numeric($row['price'] ?? null) ? (float)$row['price'] : null,
        'stock' => is_numeric($row['stock'] ?? null) ? (int)$row['stock'] : null,
        'image' => bb_image_url(isset($row['image']) ? (string)$row['image'] : null),
        'url' => bb_product_url((int)$row['id']),
        'reason' => $reason,
    ];
}

function bb_recommend_rows(int $limit = 6): array
{
    global $config;
    $limit = max(1, min(10, $limit));
    $pdo = bb_db();
    $visitor = bb_visitor_key();
    $interactionTable = '`ai_book_interactions`';
    $productTable = bb_table('product_table');
    $productId = bb_col('id', 'p');
    $categoryCol = bb_col('category', 'p');
    $stockCol = bb_col('stock', 'p');

    $categoryWeights = [];
    $popularity = [];
    $sqlHistory = 'SELECT i.event_type, i.product_id, ' . ($categoryCol ?? 'NULL') . ' AS category, i.created_at FROM ' . $interactionTable . ' i LEFT JOIN ' . $productTable . ' p ON ' . $productId . ' = i.product_id WHERE (i.visitor_key = :visitor OR (:user_id_check IS NOT NULL AND i.user_id = :user_id)) ORDER BY i.created_at DESC LIMIT 250';
    $historyStmt = $pdo->prepare($sqlHistory);
    $historyStmt->execute([':visitor' => $visitor, ':user_id_check' => bb_user_id(), ':user_id' => bb_user_id()]);
    $history = $historyStmt->fetchAll();
    $weights = ['view' => 1.0, 'click' => 1.2, 'cart' => 3.0, 'purchase' => 2.4, 'favorite' => 3.5, 'search' => 0.8];
    $recentlyPurchased = [];
    foreach ($history as $event) {
        $days = max(0, (time() - strtotime((string)$event['created_at'])) / 86400);
        $decay = max(0.25, 1 - ($days / 120));
        $w = ($weights[$event['event_type']] ?? 1.0) * $decay;
        $cat = trim((string)($event['category'] ?? ''));
        if ($cat !== '') $categoryWeights[$cat] = ($categoryWeights[$cat] ?? 0) + $w;
        $pid = (int)$event['product_id'];
        if ($event['event_type'] === 'purchase') $recentlyPurchased[$pid] = true;
    }

    $popularStmt = $pdo->query('SELECT product_id, COUNT(*) AS total FROM ' . $interactionTable . ' WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY product_id ORDER BY total DESC LIMIT 300');
    foreach ($popularStmt->fetchAll() as $pop) $popularity[(int)$pop['product_id']] = (int)$pop['total'];
    $maxPop = max(1, $popularity ? max($popularity) : 1);

    $sql = 'SELECT ' . bb_product_select('p') . ' FROM ' . $productTable . ' p';
    if ($stockCol !== null) $sql .= ' WHERE (' . $stockCol . ' > 0 OR ' . $stockCol . ' IS NULL)';
    $sql .= ' ORDER BY ' . $productId . ' DESC LIMIT 300';
    $rows = $pdo->query($sql)->fetchAll();

    foreach ($rows as &$row) {
        $cat = trim((string)($row['category'] ?? ''));
        $affinity = $cat !== '' ? ($categoryWeights[$cat] ?? 0) : 0;
        $popScore = ($popularity[(int)$row['id']] ?? 0) / $maxPop;
        $alreadyPurchased = isset($recentlyPurchased[(int)$row['id']]);
        $row['_personalized'] = $affinity > 0;
        // Blend personal category affinity and store-wide popularity. The weights are a simple baseline,
        // not a trained ranking model; they can later be replaced with XGBoost/reranking.
        $row['_score'] = ($affinity * 1.8) + ($popScore * 1.2) - ($alreadyPurchased ? 2.0 : 0.0);
        if ($affinity > 0) $row['_reason'] = 'Phù hợp với thể loại bạn thường quan tâm';
        elseif ($popScore > 0) $row['_reason'] = 'Được nhiều khách hàng quan tâm';
        else $row['_reason'] = 'Gợi ý khám phá từ cửa hàng';
    }
    unset($row);
    usort($rows, fn($a, $b) => ($b['_score'] <=> $a['_score']) ?: ((int)$b['id'] <=> (int)$a['id']));
    return array_slice($rows, 0, $limit);
}


function bb_save_message(string $role, string $message): void
{
    $stmt = bb_db()->prepare('INSERT INTO ai_book_chat_messages (visitor_key, user_id, role, message) VALUES (:visitor, :user_id, :role, :message)');
    $stmt->execute([
        ':visitor' => bb_visitor_key(),
        ':user_id' => bb_user_id(),
        ':role' => $role,
        ':message' => mb_substr($message, 0, 6000),
    ]);
}

function bb_rate_limit(): void
{
    $stmt = bb_db()->prepare("SELECT COUNT(*) FROM ai_book_chat_messages WHERE visitor_key = :visitor AND role = 'user' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([':visitor' => bb_visitor_key()]);
    if ((int)$stmt->fetchColumn() >= 40) bb_json(['error' => 'Bạn gửi quá nhiều tin nhắn trong thời gian ngắn. Vui lòng thử lại sau.'], 429);
}
