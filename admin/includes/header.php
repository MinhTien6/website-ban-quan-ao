<?php
/**
 * Header giao diện quản trị Admin dùng chung
 * Được bảo vệ tự động bằng requireAdmin()
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

// Kiểm tra quyền quản trị viên ngay tại header admin
requireAdmin();

$adminUser = currentUser();
$adminPageTitle = isset($pageTitle) && $pageTitle !== '' 
    ? e($pageTitle) . ' - Quản trị ' . e(SITE_NAME)
    : 'Quản trị - ' . e(SITE_NAME);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $adminPageTitle ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
</head>
<body class="admin-body">

<div class="admin-layout">
    <!-- Sidebar Menu Admin -->
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <a href="<?= BASE_URL ?>/admin/index.php">FASHION<span>ADMIN</span></a>
        </div>

        <nav class="admin-nav">
            <a href="<?= BASE_URL ?>/admin/index.php" class="nav-item active">
                📊 Bảng điều khiển (Dashboard)
            </a>
            <a href="<?= BASE_URL ?>/index.php" target="_blank" class="nav-item">
                🌐 Xem trang khách hàng
            </a>
            
            <div class="nav-heading">Quản lý bán hàng</div>
            <a href="#" class="nav-item nav-disabled" title="Chức năng Tuần 3 - TV3">
                📁 Quản lý danh mục
            </a>
            <a href="#" class="nav-item nav-disabled" title="Chức năng Tuần 3 - TV3">
                👕 Quản lý sản phẩm
            </a>
            <a href="#" class="nav-item nav-disabled" title="Chức năng Tuần 4, 5 - TV6">
                📦 Quản lý đơn hàng
            </a>
            <a href="#" class="nav-item nav-disabled" title="Chức năng Tuần 5 - TV5">
                🎟️ Quản lý mã giảm giá
            </a>

            <div class="nav-heading">Hệ thống & Tương tác</div>
            <a href="#" class="nav-item nav-disabled" title="Chức năng Tuần 3 - TV2">
                👥 Quản lý người dùng
            </a>
            <a href="#" class="nav-item nav-disabled" title="Chức năng Tuần 3 - TV6">
                🖼️ Quản lý banner
            </a>
            <a href="#" class="nav-item nav-disabled" title="Chức năng Tuần 5 - TV6">
                ⭐ Kiểm duyệt đánh giá
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <div class="admin-user-info">
                <span class="user-name"><?= e($adminUser['full_name']) ?></span>
                <span class="user-role">Quản trị viên</span>
            </div>
            <a href="<?= BASE_URL ?>/logout.php" class="button-logout" title="Đăng xuất">Đăng xuất</a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <h2><?= isset($pageTitle) ? e($pageTitle) : 'Trang quản trị' ?></h2>
            </div>
            <div class="topbar-right">
                <span>Xin chào, <strong><?= e($adminUser['full_name']) ?></strong></span>
            </div>
        </header>

        <main class="admin-content">
            <?php if ($flash !== null): ?>
                <div class="flash-message flash-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>
