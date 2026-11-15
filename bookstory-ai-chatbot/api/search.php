<?php
declare(strict_types=1);

/** Fold Vietnamese accents without requiring the intl extension. */
function bb_fold_text(string $text): string
{
    return preg_replace(
        ['/[àáạảãâầấậẩẫăằắặẳẵ]/u', '/[èéẹẻẽêềếệểễ]/u', '/[ìíịỉĩ]/u',
         '/[òóọỏõôồốộổỗơờớợởỡ]/u', '/[ùúụủũưừứựửữ]/u', '/[ỳýỵỷỹ]/u', '/đ/u'],
        ['a', 'e', 'i', 'o', 'u', 'y', 'd'],
        mb_strtolower($text)
    ) ?? '';
}

function bb_normalize_text(string $text): string
{
    return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', bb_fold_text($text)) ?? '');
}

/** Shared by price parsing and keyword extraction so prices never become topics. */
function bb_budget_pattern(): string
{
    return '/(?:\b(?:duoi|toi da|khong qua|khong vuot qua)|<=?)\s*([0-9]+(?:[.,][0-9]+)*)\s*(trieu|tr|k|nghin|ngan)?(?:\s*(?:dong|vnd|₫))?/u';
}

function bb_parse_budget(string $query): ?float
{
    if (!preg_match(bb_budget_pattern(), bb_fold_text($query), $matches)) return null;
    $number = $matches[1];
    $unit = $matches[2] ?? '';
    if ($unit !== '') {
        $value = (float)str_replace(',', '.', $number);
        $value *= in_array($unit, ['trieu', 'tr'], true) ? 1000000 : 1000;
    } else {
        $value = (float)str_replace(['.', ','], '', $number);
        // Common shorthand: "dưới 200" means 200.000 đồng.
        if ($value > 0 && $value < 1000) $value *= 1000;
    }
    return $value;
}

function bb_search_terms(string $query): array
{
    global $config;
    $text = preg_replace(bb_budget_pattern(), ' ', bb_fold_text($query)) ?? '';
    $text = ' ' . bb_normalize_text($text) . ' ';
    foreach ($config['search']['ignore_phrases'] ?? [] as $phrase) {
        $text = str_replace(' ' . bb_normalize_text($phrase) . ' ', ' ', $text);
    }
    $stopWords = array_map('bb_normalize_text', $config['search']['stop_words'] ?? []);
    $terms = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $terms = array_filter($terms, fn($term) => mb_strlen($term) >= 2 && !in_array($term, $stopWords, true));
    return array_slice(array_values(array_unique($terms)), 0, 12);
}

/** Match every requested keyword AND the budget; never fill gaps with unrelated books. */
function bb_search_products(string $query, int $limit = 5, ?float $maxPrice = null): array
{
    $terms = bb_search_terms($query);
    if (!$terms && $maxPrice === null) return [];

    $name = bb_col('name', 'p');
    $id = bb_col('id', 'p');
    if ($name === null || $id === null) throw new RuntimeException('Cần cấu hình cột id và name của products.');
    $fields = array_filter([$name, bb_col('description', 'p'), bb_col('category', 'p')]);
    $where = [];
    $ranking = [];
    $params = [];
    foreach ($terms as $i => $term) {
        $matches = [];
        $pattern = '%' . strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        foreach ($fields as $j => $field) {
            $key = ':term_' . $i . '_' . $j;
            $matches[] = $field . " LIKE " . $key . " ESCAPE '!'";
            $params[$key] = $pattern;
        }
        $where[] = '(' . implode(' OR ', $matches) . ')';
        $ranking[] = 'CASE WHEN ' . $name . ' LIKE :rank_' . $i . " ESCAPE '!' THEN 1 ELSE 0 END";
        $params[':rank_' . $i] = $pattern;
    }
    if ($maxPrice !== null) {
        $price = bb_col('price', 'p');
        if ($price === null) return [];
        $where[] = $price . ' <= :max_price';
        $params[':max_price'] = $maxPrice;
    }
    $stock = bb_col('stock', 'p');
    if ($stock !== null) $where[] = '(' . $stock . ' > 0 OR ' . $stock . ' IS NULL)';

    $sql = 'SELECT ' . bb_product_select('p') . ', ' . ($ranking ? implode(' + ', $ranking) : '0') . ' AS _score'
        . ' FROM ' . bb_table('product_table') . ' p WHERE ' . implode(' AND ', $where)
        . ' ORDER BY _score DESC, ' . $id . ' DESC LIMIT ' . max(1, min(10, $limit));
    $statement = bb_db()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}
