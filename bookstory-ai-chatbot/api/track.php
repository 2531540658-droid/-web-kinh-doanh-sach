<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
bb_require_post();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) bb_json(['error' => 'Dữ liệu gửi lên không hợp lệ.'], 400);
$productId = filter_var($input['product_id'] ?? null, FILTER_VALIDATE_INT);
$event = (string)($input['event'] ?? 'view');
$allowed = ['view','click','cart','purchase','favorite','search'];
if (!$productId || $productId < 1 || !in_array($event, $allowed, true)) {
    bb_json(['error' => 'Mã sản phẩm hoặc loại tương tác không hợp lệ.'], 422);
}
$queryText = isset($input['query']) ? mb_substr(trim((string)$input['query']), 0, 500) : null;
try {
    $stmt = bb_db()->prepare('INSERT INTO ai_book_interactions (visitor_key, user_id, product_id, event_type, query_text) VALUES (:visitor, :user_id, :product_id, :event, :query_text)');
    $stmt->execute([
        ':visitor' => bb_visitor_key(),
        ':user_id' => bb_user_id(),
        ':product_id' => $productId,
        ':event' => $event,
        ':query_text' => $queryText,
    ]);
    bb_json(['ok' => true]);
} catch (Throwable $e) {
    error_log('[BookBot track] ' . $e->getMessage());
    bb_json(['error' => 'Không lưu được lịch sử tương tác.'], 500);
}
