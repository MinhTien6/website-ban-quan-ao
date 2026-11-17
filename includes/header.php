<?php
/**
 * Header giao diện người dùng Streetwear FashionStore (TV1 + TV4 + TV5)
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
    : e(SITE_NAME) . ' | Local Brand Streetwear GenZ';

$cartCount = function_exists('getCartCount') ? getCartCount() : 0;
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$currentSort = $_GET['sort'] ?? $_GET['tab'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $siteTitle ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">

    <?php if (file_exists(ROOT_PATH . '/public/css/client.css')): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/client.css">
    <?php endif; ?>

    <?php if (file_exists(ROOT_PATH . '/public/css/cart.css')): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/cart.css">
    <?php endif; ?>

    <?php if (!empty($pageCss) && file_exists(ROOT_PATH . '/public/css/' . $pageCss) && !in_array($pageCss, ['client.css', 'cart.css', 'style.css'], true)): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/<?= e($pageCss) ?>">
    <?php endif; ?>

    <style>
        .site-header {
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
            background: #ffffff !important;
            border-bottom: 1px solid #eaeaea !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            width: 100% !important;
        }
        .site-header .header-inner {
            max-width: 1200px !important;
            margin: 0 auto !important;
            padding: 0 20px !important;
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            height: 70px !important;
            box-sizing: border-box !important;
        }
        .site-header .main-nav {
            display: flex !important;
            flex-direction: row !important;
            gap: 28px !important;
            align-items: center !important;
            height: 100% !important;
        }
        .site-header .nav-item-dropdown {
            position: static !important;
            height: 100% !important;
            display: flex !important;
            align-items: center !important;
        }
        .site-header .nav-link {
            text-decoration: none !important;
            font-weight: 600 !important;
            font-size: 15px !important;
            text-transform: none !important;
            color: #111111 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            height: 100% !important;
            border-bottom: 2px solid transparent !important;
            transition: all 0.2s ease !important;
            white-space: nowrap !important;
        }
        .site-header .nav-link:hover,
        .site-header .nav-link.active {
            color: #e60023 !important;
            border-bottom: 2px solid #111111 !important;
        }
        .site-header .mega-dropdown {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            width: 100% !important;
            background: #ffffff !important;
            border-top: 1px solid #eeeeee !important;
            border-bottom: 1px solid #e0e0e0 !important;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08) !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transform: translateY(6px) !important;
            transition: all 0.2s ease !important;
            z-index: 999 !important;
        }
        .site-header .nav-item-dropdown:hover .mega-dropdown {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) !important;
        }
        .site-header .mega-inner {
            max-width: 1000px !important;
            margin: 0 auto !important;
            padding: 28px 20px 32px !important;
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 40px !important;
            box-sizing: border-box !important;
            text-align: left !important;
        }
        .site-header .mega-col {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        .site-header .mega-col h5 {
            font-size: 13.5px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            color: #111111 !important;
            margin: 0 0 16px 0 !important;
            padding: 0 !important;
            letter-spacing: 0.3px !important;
        }
        .site-header .mega-col h5 a {
            color: #111111 !important;
            text-decoration: none !important;
            text-transform: uppercase !important;
            font-weight: 800 !important;
            font-size: 13.5px !important;
            letter-spacing: 0.3px !important;
        }
        .site-header .mega-col h5 a:hover {
            color: #e60023 !important;
        }
        .site-header .mega-col ul {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 12px !important;
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }
        .site-header .mega-col ul li a {
            display: block !important;
            text-decoration: none !important;
            color: #111111 !important;
            font-size: 13.5px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.2px !important;
            transition: color 0.15s ease !important;
        }
        .site-header .mega-col ul li a:hover {
            color: #e60023 !important;
        }
        .user-menu-wrap {
            position: relative;
            display: inline-block;
        }
        .user-menu-toggle {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            font-size: 14px;
            color: #111;
            padding: 6px 8px;
            border-radius: 4px;
        }
        .user-menu-toggle:hover {
            background: #f5f5f5;
        }
        .user-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 6px;
            background: #ffffff;
            border: 1px solid #eaeaea;
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
            border-radius: 8px;
            min-width: 200px;
            padding: 8px 0;
            display: none;
            z-index: 1002;
        }
        .user-menu-wrap:hover .user-dropdown-menu,
        .user-menu-wrap:focus-within .user-dropdown-menu {
            display: block;
        }
        .user-dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 18px;
            font-size: 13.5px;
            color: #222222;
            text-decoration: none;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .user-dropdown-menu a:hover {
            background: #f8f8f8;
            color: #e60023;
        }
    </style>
</head>
<body>

    <div class="topbar" style="background:#111; color:#fff; text-align:center; padding:8px 15px; font-size:12px; font-weight:700; letter-spacing:1px; border-bottom:2px solid #e60023; font-family:'Inter', sans-serif;">
        🔥 FREESHIP ĐƠN TỪ 399K • BST STREETWEAR GENZ MỚI NHẤT ĐÃ LÊN KỆ 🔥
    </div>

    <header class="site-header">
        <div class="header-inner">
            
            <a href="<?= BASE_URL ?>/index.php" class="logo" style="font-size:26px; font-weight:900; text-decoration:none; color:#111; letter-spacing:-1px;">
                VIBEZ<span style="color:#e60023;">.</span>CLUB
            </a>

            <nav class="main-nav">
                <a href="<?= BASE_URL ?>/index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    Trang chủ
                </a>

                <div class="nav-item-dropdown">
                    <a href="<?= BASE_URL ?>/products.php" class="nav-link <?= ($currentPage === 'products.php' && !in_array($currentSort, ['new', 'newest', 'bestseller', 'best'], true)) ? 'active' : '' ?>">
                        Sản phẩm <i class="fa-solid fa-chevron-down" style="font-size:11px;"></i>
                    </a>
                    
                    <div class="mega-dropdown">
                        <div class="mega-inner">
                            <div class="mega-col">
                                <h5><a href="<?= BASE_URL ?>/products.php">TẤT CẢ SẢN PHẨM</a></h5>
                                <ul>
                                    <li><a href="<?= BASE_URL ?>/products.php">TẤT CẢ SẢN PHẨM</a></li>
                                    <li><a href="<?= BASE_URL ?>/products.php?tab=new">HÀNG MỚI VỀ</a></li>
                                    <li><a href="<?= BASE_URL ?>/products.php?tab=bestseller">HÀNG BÁN CHẠY</a></li>
                                </ul>
                            </div>

                            <div class="mega-col">
                                <h5><a href="<?= BASE_URL ?>/products.php?category=1">ÁO UNISEX & KIỂU</a></h5>
                                <ul>
                                    <li><a href="<?= BASE_URL ?>/products.php?category=1">ÁO THUN STREETWEAR</a></li>
                                    <li><a href="<?= BASE_URL ?>/products.php?category=2">ÁO SƠ MI & POLO</a></li>
                                    <li><a href="<?= BASE_URL ?>/products.php?category=5">ÁO KHOÁC & HOODIE</a></li>
                                </ul>
                            </div>

                            <div class="mega-col">
                                <h5><a href="<?= BASE_URL ?>/products.php?category=3">QUẦN & VÁY ĐẦM</a></h5>
                                <ul>
                                    <li><a href="<?= BASE_URL ?>/products.php?category=3">QUẦN JEANS & SHORT</a></li>
                                    <li><a href="<?= BASE_URL ?>/products.php?category=4">VÁY, ĐẦM & CHÂN VÁY</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>/products.php?sort=newest" class="nav-link <?= in_array($currentSort, ['new', 'newest'], true) ? 'active' : '' ?>">
                    Hàng Mới
                </a>

                <a href="<?= BASE_URL ?>/products.php?sort=bestseller" class="nav-link <?= in_array($currentSort, ['bestseller', 'best'], true) ? 'active' : '' ?>">
                    Hàng Bán Chạy
                </a>
            </nav>

            <div class="header-actions" style="display:flex; align-items:center; gap:16px;">
                <form action="<?= BASE_URL ?>/products.php" method="GET" class="search-box" style="display:flex; align-items:center; border:1.5px solid #111; border-radius:4px; overflow:hidden;">
                    <input type="text" name="keyword" placeholder="Tìm áo thun, quần jean, váy..." value="<?= htmlspecialchars($_GET['keyword'] ?? $_GET['q'] ?? '') ?>" style="border:none; padding:7px 12px; font-size:13px; outline:none; width:165px; font-family:'Inter', sans-serif;">
                    <button type="submit" style="background:#111; color:#fff; border:none; padding:7px 12px; cursor:pointer;" title="Tìm kiếm">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>

                <?php if ($user !== null): ?>
                    <div class="user-menu-wrap">
                        <button type="button" class="user-menu-toggle" title="Tài khoản">
                            <i class="fa-regular fa-user" style="font-size:17px;"></i>
                            <span class="user-name" style="max-width:110px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e($user['full_name']) ?></span>
                            <i class="fa-solid fa-chevron-down" style="font-size:10px;"></i>
                        </button>
                        <div class="user-dropdown-menu">
                            <a href="<?= BASE_URL ?>/profile.php">
                                <i class="fa-regular fa-id-card"></i> Tài khoản của tôi
                            </a>
                            <a href="<?= BASE_URL ?>/change-password.php">
                                <i class="fa-solid fa-key"></i> Đổi mật khẩu
                            </a>
                            <a href="<?= BASE_URL ?>/my_orders.php">
                                <i class="fa-solid fa-clock-rotate-left"></i> Đơn hàng của tôi
                            </a>
                            <?php if (($user['role'] ?? '') === 'admin'): ?>
                                <a href="<?= BASE_URL ?>/admin/index.php" style="color:#d9534f; font-weight:700;">
                                    <i class="fa-solid fa-shield-halved"></i> Trang Quản Trị
                                </a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/logout.php" style="border-top:1px solid #f0f0f0; color:#e60023;">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <a href="<?= BASE_URL ?>/login.php" class="action-icon" style="color:#111; font-size:13.5px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px;" title="Đăng nhập">
                            <i class="fa-regular fa-user" style="font-size:16px;"></i> Đăng nhập
                        </a>
                        <span style="color:#ccc;">|</span>
                        <a href="<?= BASE_URL ?>/register.php" style="color:#555; font-size:13px; text-decoration:none;">
                            Đăng ký
                        </a>
                    </div>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>/cart.php" class="action-icon cart-btn" style="color:#111; font-size:19px; text-decoration:none; position:relative;" title="Giỏ hàng">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span class="cart-badge" style="position:absolute; top:-8px; right:-10px; background:#e60023; color:#fff; font-size:11px; font-weight:800; padding:1px 6px; border-radius:50%; min-width:18px; text-align:center;">
                        <?= $cartCount ?>
                    </span>
                </a>
            </div>

        </div>
    </header>

    <main class="main-content">
        <?php if ($flash !== null): ?>
            <div class="flash-container" style="max-width:1200px; margin:16px auto; padding:0 20px;">
                <div class="flash-message flash-<?= e($flash['type']) ?>" style="padding:12px 18px; border-radius:6px; font-weight:600; font-size:14px; background:<?= ($flash['type'] === 'success' ? '#e6f7ed; color:#0e6245; border:1px solid #a3e0c4;' : '#fdeded; color:#9c1c1c; border:1px solid #f5c2c2;') ?>">
                    <?= e($flash['message']) ?>
                </div>
            </div>
        <?php endif; ?>
