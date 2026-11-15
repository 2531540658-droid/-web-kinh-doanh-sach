<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    $rows = bb_recommend_rows($limit);
    $products = array_map(fn($row) => bb_format_product($row, (string)($row['_reason'] ?? '')), $rows);
    bb_json(['ok' => true, 'personalized' => bb_user_id() !== null || count($products) > 0, 'products' => $products]);
} catch (Throwable $e) {
    error_log('[BookBot recommend] ' . $e->getMessage());
    bb_json(['ok' => false, 'error' => 'Không thể tải gợi ý lúc này. Hãy kiểm tra cấu hình bảng sản phẩm và bảng tương tác.'], 500);
}
