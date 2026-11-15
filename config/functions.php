<?php
// Kiểm tra page chân trang
function getBaseUrl() {
    $current_path = $_SERVER['PHP_SELF'];
    if (strpos($current_path, '/pages/') !== false) {
        return '../';
    }
    return '';
}
?>
