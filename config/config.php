<?php
/**
 * Cấu hình chung cho toàn bộ website
 * File này được commit lên Git (không lưu thông tin bí mật)
 */

// Đặt múi giờ chuẩn cho ứng dụng
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Tên trang web
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'FashionStore');
}

// Đường dẫn tuyệt đối tới thư mục gốc dự án trên ổ đĩa
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)));
}

// Tự động xác định BASE_URL dựa trên DOCUMENT_ROOT và ROOT_PATH
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: '') : '';
    $realRootPath = str_replace('\\', '/', realpath(ROOT_PATH) ?: ROOT_PATH);

    $baseUrl = '';
    if ($docRoot !== '' && str_starts_with($realRootPath, $docRoot)) {
        $relative = substr($realRootPath, strlen($docRoot));
        $baseUrl = '/' . trim(str_replace('\\', '/', $relative), '/');
        if ($baseUrl === '/') {
            $baseUrl = '';
        }
    } else {
        // Fallback khi chạy CLI hoặc server proxy đặc biệt
        $baseUrl = '';
    }

    define('BASE_URL', $baseUrl);
}

// Đường dẫn thư mục upload và URL truy cập ảnh
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', ROOT_PATH . '/public/uploads');
}

if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', BASE_URL . '/public/uploads');
}
