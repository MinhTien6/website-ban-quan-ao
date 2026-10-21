<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FashionStore</title>
    <link rel="stylesheet" href="/FashionStore/public/css/style.css">
</head>
<body>

<header class="header">
    <a href="/FashionStore/index.php" class="logo">
        FASHION<span>STORE</span>
    </a>

    <nav>
        <a href="/FashionStore/index.php">Trang chủ</a>
        <a href="/FashionStore/public/views/products.php">Sản phẩm</a>
        <a href="/FashionStore/public/views/cart.php">Giỏ hàng</a>

        <?php if (isset($_SESSION['user'])): ?>
            <span>Xin chào,
                <?= htmlspecialchars($_SESSION['user']['name']) ?>
            </span>
            <a href="/FashionStore/controllers/AuthController.php?action=logout">
                Đăng xuất
            </a>
        <?php else: ?>
            <a href="/FashionStore/public/views/login.php">Đăng nhập</a>
            <a href="/FashionStore/public/views/register.php">Đăng ký</a>
        <?php endif; ?>
    </nav>
</header>
