<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['quantity'] ?? 1);
    }
}
$currentPage = basename($_SERVER['PHP_SELF']);
$currentSort = $_GET['tab'] ?? $_GET['sort'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'VIBEZ.CLUB | Local Brand Streetwear GenZ' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/client.css">

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
            gap: 36px !important;
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
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06) !important;
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
            padding: 32px 20px 38px !important;
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 48px !important;
            box-sizing: border-box !important;
            text-align: left !important;
        }
        .site-header .mega-col {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        .site-header .mega-col h5 {
            font-size: 14px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            color: #111111 !important;
            margin: 0 0 18px 0 !important;
            padding: 0 !important;
            border: none !important;
        }
        .site-header .mega-col h5 a {
            color: #111111 !important;
            text-decoration: none !important;
            text-transform: uppercase !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            border: none !important;
        }
        .site-header .mega-col h5 a:hover {
            color: #e60023 !important;
        }
        .site-header .mega-col ul {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 14px !important;
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }
        .site-header .mega-col ul li {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            text-align: left !important;
        }
        .site-header .mega-col ul li a {
            display: block !important;
            text-decoration: none !important;
            color: #222222 !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            text-transform: none !important;
            line-height: 1.4 !important;
            padding: 0 !important;
            border: none !important;
            transition: color 0.15s ease !important;
        }
        .site-header .mega-col ul li a:hover {
            color: #e60023 !important;
        }
    </style>
</head>
<body>

    <div class="topbar" style="background:#111; color:#fff; text-align:center; padding:8px 15px; font-size:12px; font-weight:700; letter-spacing:1px; border-bottom:2px solid #e60023; font-family:'Inter', sans-serif;">
        🔥 FREESHIP ĐƠN TỪ 399K • BST STREETWEAR GENZ MỚI NHẤT ĐÃ LÊN KỆ 🔥
    </div>

    <header class="site-header">
        <div class="header-inner">
            
            <a href="index.php" class="logo" style="font-size:26px; font-weight:900; text-decoration:none; color:#111; letter-spacing:-1px;">
                VIBEZ<span style="color:#e60023;">.</span>CLUB
            </a>

            <nav class="main-nav">
                <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    Trang chủ
                </a>

                <div class="nav-item-dropdown">
                    <a href="products.php" class="nav-link <?= ($currentPage === 'products.php' && !in_array($currentSort, ['new', 'newest', 'bestseller', 'best'], true)) ? 'active' : '' ?>">
                        Sản phẩm <i class="fa-solid fa-chevron-down" style="font-size:11px;"></i>
                    </a>
                    
                    <div class="mega-dropdown">
                        <div class="mega-inner">
                            <div class="mega-col">
                                <h5><a href="products.php">TẤT CẢ SẢN PHẨM</a></h5>
                                <ul>
                                    <li><a href="products.php">Tất Cả Sản Phẩm</a></li>
                                    <li><a href="products.php?sort=newest">Hàng Mới Về</a></li>
                                    <li><a href="products.php?sort=bestseller">Hàng Bán Chạy</a></li>
                                </ul>
                            </div>

                            <div class="mega-col">
                                <h5><a href="products.php?category=ao-thun">ÁO UNISEX & KIỂU</a></h5>
                                <ul>
                                    <li><a href="products.php?category=ao-thun">Áo Thun & Croptop</a></li>
                                    <li><a href="products.php?category=ao-so-mi">Áo Sơ Mi & Polo</a></li>
                                    <li><a href="products.php?category=ao-khoac">Áo Khoác, Varsity & Hoodie</a></li>
                                </ul>
                            </div>

                            <div class="mega-col">
                                <h5><a href="products.php?category=quan-jean">QUẦN & VÁY ĐẦM</a></h5>
                                <ul>
                                    <li><a href="products.php?category=quan-jean">Quần Jeans, Tây & Quần Short</a></li>
                                    <li><a href="products.php?category=vay-dam">Váy, Đầm & Chân Váy</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="products.php?sort=newest" class="nav-link <?= in_array($currentSort, ['new', 'newest'], true) ? 'active' : '' ?>">
                    Hàng Mới
                </a>

                <a href="products.php?sort=bestseller" class="nav-link <?= in_array($currentSort, ['bestseller', 'best'], true) ? 'active' : '' ?>">
                    Hàng Bán Chạy
                </a>
            </nav>

            <div class="header-actions" style="display:flex; align-items:center; gap:18px;">
                <form action="products.php" method="GET" class="search-box" style="display:flex; align-items:center; border:1.5px solid #111; border-radius:4px; overflow:hidden;">
                    <input type="text" name="keyword" placeholder="Tìm áo thun, quần jean, váy..." value="<?= htmlspecialchars($_GET['keyword'] ?? $_GET['q'] ?? '') ?>" style="border:none; padding:7px 12px; font-size:13px; outline:none; width:165px; font-family:'Inter', sans-serif;">
                    <button type="submit" style="background:#111; color:#fff; border:none; padding:7px 12px; cursor:pointer;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>

                <a href="login.php" class="action-icon" style="color:#111; font-size:19px; text-decoration:none;" title="Tài khoản">
                    <i class="fa-regular fa-user"></i>
                </a>

                <a href="cart.php" class="action-icon cart-btn" style="color:#111; font-size:19px; text-decoration:none; position:relative;" title="Giỏ hàng">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span class="cart-badge" style="position:absolute; top:-8px; right:-10px; background:#e60023; color:#fff; font-size:11px; font-weight:800; padding:1px 6px; border-radius:50%;">
                        <?= $cartCount ?>
                    </span>
                </a>
            </div>

        </div>
    </header>

    <main class="main-content">