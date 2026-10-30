<?php
/**
 * Header giao diện chung của hệ thống (FashionStore)
 * Xử lý khởi tạo phiên làm việc và cấu hình trước khi render HTML
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Nạp các tệp cấu hình và hàm nền tảng cốt lõi
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// 2. Thiết lập dữ liệu người dùng và tiêu đề trang
$user = currentUser();
$siteTitle = isset($pageTitle) && $pageTitle !== '' 
    ? e($pageTitle) . ' - ' . e(SITE_NAME) 
    : e(SITE_NAME);
$flash = getFlash();
?>
<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $siteTitle ?></title>

    <!-- 1. CSS CỐT LÕI CỦA HỆ THỐNG (Bắt buộc giữ nguyên cho toàn bộ website) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">

    <!-- 2. CSS RIÊNG CỦA TV4 (Giao diện hiển thị sản phẩm, banner streetwear khách hàng) -->
    <?php if (file_exists(ROOT_PATH . '/public/css/client.css')): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/client.css">
    <?php endif; ?>

    <!-- 3. CSS RIÊNG CỦA TV5 (Giao diện giỏ hàng, bảng thanh toán checkout, đơn hàng) -->
    <?php if (file_exists(ROOT_PATH . '/public/css/cart.css')): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/cart.css">
    <?php endif; ?>
</head>
<body>

<header class="header">
    <a href="<?= BASE_URL ?>/index.php" class="logo">
        FASHION<span>STORE</span>
    </a>

    <!-- Biểu tượng giỏ hàng hiển thị số lượng tức thời (Đã bọc kiểm tra an toàn) -->
    <a href="<?= BASE_URL ?>/cart.php" class="nav-cart">
        <svg width="20" height="20" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"/>
            <circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <span>GIỎ HÀNG</span>
        <span class="nav-cart-count">
            <?= function_exists('getCartCount') ? getCartCount() : 0 ?>
        </span>
    </a>

    <nav>
        <a href="<?= BASE_URL ?>/index.php">Trang chủ</a>

        <?php if ($user !== null): ?>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a href="<?= BASE_URL ?>/admin/index.php" class="nav-admin">Quản trị</a>
            <?php endif; ?>
            <span class="user-greeting">Xin chào, <?= e($user['full_name']) ?></span>
            <a href="<?= BASE_URL ?>/logout.php" class="nav-logout">Đăng xuất</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php">Đăng nhập</a>
            <a href="<?= BASE_URL ?>/register.php">Đăng ký</a>
        <?php endif; ?>
    </nav>
</header>

<main class="main-content">
    <?php if ($flash !== null): ?>
        <div class="flash-message flash-<?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>