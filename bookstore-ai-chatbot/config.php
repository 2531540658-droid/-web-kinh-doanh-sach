<?php
declare(strict_types=1);

$localConfigPath = dirname(__DIR__) . '/web-kinh-doanh-s-ch/config/local.php';
$localConfig = [];
if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (is_array($loadedConfig)) {
        $localConfig = $loadedConfig;
    }
}

$dbSetting = static function (string $environmentVariable, string $key, string $default) use ($localConfig): string {
    $value = getenv($environmentVariable);
    return $value !== false ? $value : (string)($localConfig[$key] ?? $default);
};

$dbPort = getenv('DB_PORT');

return [
    'db' => [
        'host' => $dbSetting('DB_HOST', 'db_host', 'localhost'),
        'port' => $dbPort !== false ? (int)$dbPort : 3306,
        'name' => $dbSetting('DB_NAME', 'db_name', 'shop_db'),
        'user' => $dbSetting('DB_USER', 'db_user', 'root'),
        'pass' => $dbSetting('DB_PASSWORD', 'db_password', ''),
        'charset' => 'utf8mb4',
    ],
    'site_base_path' => '/web-kinh-doanh-s-ch',
    'chatbot_url_path' => '/bookstore-ai-chatbot',
    'product_table' => 'products',
    'product_columns' => [
        'id' => 'id',
        'name' => 'name',
        'description' => 'details',
        'category' => 'category',
        'price' => 'price',
        'image' => 'image',
        'stock' => null,
    ],
    'image_base_url' => '/web-kinh-doanh-s-ch/',
    'product_url_template' => '/pages/view_page.php?pid={id}',
    'currency_symbol' => '₫',
    'openai' => [
        'api_key' => getenv('OPENAI_API_KEY') ?: '',
        'model' => 'gpt-4.1-mini',
        'endpoint' => 'https://api.openai.com/v1/responses',
        'timeout_seconds' => 35,
        'max_output_tokens' => 500,
    ],
    'store_faq' => [
        'shipping' => 'Vui lòng xem mục Chính sách giao hàng trên website hoặc liên hệ cửa hàng để biết phí và thời gian giao hàng chính xác.',
        'payment' => 'Website hiển thị các phương thức thanh toán được hỗ trợ tại bước thanh toán. Vui lòng kiểm tra thông tin tại trang thanh toán.',
        'return' => 'Vui lòng xem mục Chính sách đổi trả trên website hoặc liên hệ bộ phận chăm sóc khách hàng để được xác nhận điều kiện áp dụng.',
        'contact' => 'Bạn có thể gửi yêu cầu qua trang Liên hệ của cửa hàng để được nhân viên hỗ trợ.',
    ],
    'user_session_key' => 'user_id',
];
