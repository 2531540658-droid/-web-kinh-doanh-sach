<?php
require_once __DIR__ . '/../config/sepay.php';

function check_sepay(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$variables = ['SEPAY_BANK_CODE', 'SEPAY_ACCOUNT_NUMBER', 'SEPAY_WEBHOOK_BANK', 'SEPAY_WEBHOOK_API_KEY'];
$original = [];
foreach ($variables as $variable) {
    $original[$variable] = getenv($variable);
    putenv($variable);
}
try {
    $local = [
        'sepay_bank_code' => 'MB',
        'sepay_account_number' => '0012345678',
        'sepay_webhook_api_key' => 'test-only-secret',
    ];
    $configuration = sepay_configuration($local);
    check_sepay($configuration['configured'], 'Private local configuration should work');
    foreach (['MBBank', 'MB', '970422'] as $gateway) {
        check_sepay(sepay_recipient_matches($configuration, '0012345678', $gateway), 'Known bank alias rejected');
    }
    check_sepay(!sepay_recipient_matches($configuration, '12345678', 'MBBank'), 'Leading zeros must be preserved');
    check_sepay(!sepay_recipient_matches($configuration, '9999999999', 'MBBank'), 'Wrong recipient accepted');
    check_sepay(!sepay_recipient_matches($configuration, '0012345678', 'OtherBank'), 'Wrong gateway accepted');
    check_sepay(!sepay_recipient_matches($configuration, null, 'MBBank'), 'Missing account accepted');
    check_sepay(!sepay_recipient_matches($configuration, ['0012345678'], 'MBBank'), 'Array account accepted');
    check_sepay(!sepay_configuration([])['configured'], 'Missing configuration must disable payment');

    putenv('SEPAY_WEBHOOK_API_KEY=environment-test-key');
    check_sepay(sepay_configuration($local)['key'] === 'environment-test-key', 'Environment must override local key');
    putenv('SEPAY_ACCOUNT_NUMBER=0099999999');
    $configuration = sepay_configuration($local);
    check_sepay(sepay_recipient_matches($configuration, '0099999999', 'MBBank'), 'Environment account ignored');
    check_sepay(!sepay_recipient_matches($configuration, '0012345678', 'MBBank'), 'Old local account accepted');
    putenv('SEPAY_WEBHOOK_API_KEY=');
    check_sepay(!sepay_configuration($local)['configured'], 'Empty environment key must disable payment');
    putenv('SEPAY_WEBHOOK_API_KEY=environment-test-key');
    putenv('SEPAY_WEBHOOK_BANK=CustomGateway');
    check_sepay(sepay_recipient_matches(sepay_configuration($local), '0099999999', 'CustomGateway'), 'Gateway override ignored');
    check_sepay(!sepay_recipient_matches(sepay_configuration($local), '0099999999', 'MBBank'), 'Previous gateway accepted after override');
    echo "SePay configuration and recipient checks passed\n";
} finally {
    foreach ($original as $variable => $value) {
        putenv($value === false ? $variable : $variable . '=' . $value);
    }
}
