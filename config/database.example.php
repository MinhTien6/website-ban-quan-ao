<?php
/**
 * Cấu hình kết nối cơ sở dữ liệu (Mẫu)
 * 
 * HƯỚNG DẪN:
 * Sao chép file này thành config/database.php rồi sửa thông tin máy của bạn.
 * File config/database.php đã được đưa vào .gitignore và sẽ không bị commit lên Git.
 */

$host = 'localhost';
$dbname = 'shop_quanao';
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Ghi lỗi kỹ thuật vào error_log của server, không in thông báo lỗi gốc ra màn hình
    error_log("Database connection error: " . $e->getMessage());
    exit("Hệ thống đang gặp sự cố kết nối cơ sở dữ liệu. Vui lòng thử lại sau.");
}
