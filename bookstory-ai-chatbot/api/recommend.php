<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    $rows = bb_recommend_rows($limit);
    $products = array_map(fn($row) => bb_format_product($row, (string)($row['_reason'] ?? '')), $rows);
    $personalized = count(array_filter($rows, fn($row) => $row['_personalized'] ?? false)) > 0;
    bb_json(['ok' => true, 'personalized' => $personalized, 'products' => $products]);
} catch (Throwable $e) {
    error_log('[BookBot recommend] ' . $e->getMessage());
    bb_json(['ok' => false, 'error' => 'Chưa tải được gợi ý lúc này. Bạn vui lòng thử lại sau.'], 503);
}
