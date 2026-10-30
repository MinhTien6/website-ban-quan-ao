<?php
/**
 * Header giao diện chung
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

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
</head>
<body>

<header class="header">
    <a href="<?= BASE_URL ?>/index.php" class="logo">
        FASHION<span>STORE</span>
    </a>

    <nav>
        <a href="<?= BASE_URL ?>/index.php">Trang chủ</a>

        <?php if ($user !== null): ?>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a href="<?= BASE_URL ?>/admin/index.php" class="nav-admin">Quản trị</a>
            <?php endif; ?>
            <span class="user-greeting">
    		Xin chào, <?= e($user['full_name']) ?>
		</span>

		<a href="<?= BASE_URL ?>/profile.php">Tài khoản của tôi</a>

		<a href="<?= BASE_URL ?>/logout.php" class="nav-logout"> Đăng xuất </a>
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
