<?php

function verify_account_password(string $password, string $stored): bool
{
    if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
        // Tài khoản cũ dùng MD5 sau khi áp dụng bộ lọc chuỗi. Lấy mã bộ lọc mà không
        // tham chiếu đến hằng số đã lỗi thời; chỉ giữ cách này để phục vụ di trú.
        $legacyPassword = filter_var($password, filter_id('string'));
        return hash_equals(strtolower($stored), md5($password))
            || hash_equals(strtolower($stored), md5((string) $legacyPassword));
    }
    if (str_contains($password, "\0") || (str_starts_with($stored, '$2y$') && strlen($password) > 72)) {
        return false;
    }
    return password_verify($password, $stored);
}
