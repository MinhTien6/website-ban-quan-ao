<?php
/**
 * Xử lý đăng xuất tài khoản và hủy phiên làm việc
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xóa tất cả các biến trong session
$_SESSION = [];

// Xóa cookie phiên nếu có
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hủy toàn bộ phiên
session_destroy();

// Khởi tạo phiên mới để gửi thông báo flash đăng xuất thành công
session_start();
setFlash('success', 'Bạn đã đăng xuất tài khoản thành công.');

redirect('index.php');
