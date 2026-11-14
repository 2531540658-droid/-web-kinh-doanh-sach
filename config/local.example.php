<?php
// Copy to local.php on the server, or use environment variables instead.
// Keep local.php private: it is ignored by Git.
return [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_password' => '',
    'db_name' => 'shop_db',
    'sepay_bank_code' => '',
    'sepay_account_number' => '',
    // Optional: exact gateway name from SePay if the QR bank alias differs.
    'sepay_webhook_bank' => '',
    'sepay_webhook_api_key' => '',
];
