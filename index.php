
<?php
$products = [
    [
        "id" => 1,
        "name" => "Áo thun",
        "price" => 150000
    ],
    [
        "id" => 2,
        "name" => "Áo sơ mi",
        "price" => 220000
    ],
    [
        "id" => 3,
        "name" => "Quần jean",
        "price" => 300000
    ],
    [
        "id" => 4,
        "name" => "Áo khoác",
        "price" => 350000
    ]
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website bán quần áo</title>
    <link rel="stylesheet" href="public/css/c.css">
</head>
<body>

    <!-- Menu -->
    <header>
        <h2>FashionStore</h2>

        <nav>
            <a href="index.php">Trang chủ</a>
            <a href="#products">Sản phẩm</a>
            <a href="#about">Giới thiệu</a>
            <a href="#contact">Liên hệ</a>
            <a href="login.php">Đăng nhập</a>
        </nav>
    </header>

    <!-- Nội dung chính -->
    <main>
        <section class="banner">
            <h1>Chào mừng đến với FashionStore</h1>
            <p>Cửa hàng quần áo dành cho bạn.</p>
            <a href="#products">Xem sản phẩm</a>
        </section>

        <!-- Danh sách sản phẩm -->
        
<section id="products">
    <h2>Sản phẩm</h2>

    <div class="products">
        <?php foreach ($products as $product): ?>
            <div class="product">
                <h3>
                    <?= htmlspecialchars($product["name"]) ?>
                </h3>

                <p>
                    Giá:
                    <?= number_format($product["price"], 0, ",", ".") ?>đ
                </p>

                <a href="product.php?id=<?= $product["id"] ?>">
                    Xem chi tiết
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>


        <!-- Giới thiệu -->
        <section id="about">
            <h2>Giới thiệu</h2>
            <p>
                FashionStore cung cấp các sản phẩm quần áo
                với nhiều lựa chọn phù hợp cho nhu cầu hằng ngày.
            </p>
        </section>

        <!-- Liên hệ -->
        <section id="contact">
            <h2>Liên hệ</h2>
            <p>Email: fashionstore@example.com</p>
            <p>Điện thoại: 0123 456 789</p>
        </section>
    </main>

    <!-- Chân trang -->
    <footer>
        <p>&copy; 2026 FashionStore</p>
    </footer>

</body>
</html>
