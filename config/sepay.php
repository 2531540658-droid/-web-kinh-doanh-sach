<?php

function sepay_bank_identity(string $bank): string
{
    $bank = strtoupper(trim($bank));
    $aliases = [
        'MB' => 'MBBANK', '970422' => 'MBBANK',
        'VCB' => 'VIETCOMBANK', '970436' => 'VIETCOMBANK',
        'ICB' => 'VIETINBANK', 'CTG' => 'VIETINBANK', '970415' => 'VIETINBANK',
        '970418' => 'BIDV', '970416' => 'ACB',
        'TCB' => 'TECHCOMBANK', '970407' => 'TECHCOMBANK',
        'VPB' => 'VPBANK', '970432' => 'VPBANK',
        'TPB' => 'TPBANK', '970423' => 'TPBANK',
        'STB' => 'SACOMBANK', '970403' => 'SACOMBANK',
        'VBA' => 'AGRIBANK', '970405' => 'AGRIBANK',
    ];
    return $aliases[$bank] ?? $bank;
}

function sepay_configuration(array $localConfig = []): array
{
    $setting = static function (string $environment, string $key) use ($localConfig): string {
        $value = getenv($environment);
        $value = $value !== false ? $value : ($localConfig[$key] ?? '');
        return is_string($value) ? trim($value) : '';
    };
    $bank = $setting('SEPAY_BANK_CODE', 'sepay_bank_code');
    $webhookBank = $setting('SEPAY_WEBHOOK_BANK', 'sepay_webhook_bank');
    $key = $setting('SEPAY_WEBHOOK_API_KEY', 'sepay_webhook_api_key');
    // Revoke the key previously committed to Git. Only its digest is retained.
    $revokedDigest = 'e55e4458ad2f03db52f1c0117caa6494048637c0238c40c79ee62096fc2e6925';
    if (hash_equals($revokedDigest, hash('sha256', $key))) {
        $key = '';
    }
    $account = $setting('SEPAY_ACCOUNT_NUMBER', 'sepay_account_number');
    return [
        'bank' => $bank,
        'account' => $account,
        'webhook_bank' => sepay_bank_identity($webhookBank !== '' ? $webhookBank : $bank),
        'key' => $key,
        'configured' => $bank !== '' && preg_match('/^[A-Za-z0-9]+$/D', $account) === 1 && $key !== '',
    ];
}

function sepay_recipient_matches(array $configuration, mixed $account, mixed $gateway): bool
{
    return $configuration['configured']
        && is_string($account) && is_string($gateway)
        && hash_equals($configuration['account'], trim($account))
        && hash_equals($configuration['webhook_bank'], sepay_bank_identity($gateway));
}
