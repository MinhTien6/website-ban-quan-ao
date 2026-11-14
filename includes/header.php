<?php
/**
 * Header giao diện người dùng FashionStore
 * TV1 + TV4 + TV5
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Đồng bộ thông tin mới nhất nếu người dùng đang đăng nhập
if (isLoggedIn()) {
    refreshCurrentUser();
}

$user = currentUser();
$siteTitle = isset($pageTitle) && $pageTitle !== '' 
    ? e($pageTitle) . ' - ' . e(SITE_NAME) 
    : e(SITE_NAME);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $siteTitle ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">

    <?php if (file_exists(ROOT_PATH . '/public/css/client.css')): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/client.css">
    <?php endif; ?>

    <?php if (file_exists(ROOT_PATH . '/public/css/cart.css')): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/cart.css">
    <?php endif; ?>

    <?php if (!empty($pageCss) && file_exists(ROOT_PATH . '/public/css/' . $pageCss) && $pageCss !== 'cart.css' && $pageCss !== 'style.css'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/<?= e($pageCss) ?>">
    <?php endif; ?>
</head>
<body>

<header class="header">
    <a href="<?= BASE_URL ?>/index.php" class="logo">
        FASHION<span>STORE</span>
    </a>

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
            <a href="<?= BASE_URL ?>/my_orders.php">Đơn hàng của tôi</a>
            <a href="<?= BASE_URL ?>/profile.php">Tài khoản của tôi</a>
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
