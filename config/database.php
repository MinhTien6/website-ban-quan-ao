<?php

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
    
    error_log("Database connection error: " . $e->getMessage());
    exit("Hệ thống đang gặp sự cố kết nối cơ sở dữ liệu. Vui lòng thử lại sau.");
}
