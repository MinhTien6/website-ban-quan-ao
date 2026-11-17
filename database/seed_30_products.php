<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/products_data.php';

echo "========================================================\n";
echo " BẮT ĐẦU CẬP NHẬT 30 SẢN PHẨM THỜI TRANG VÀO DATABASE\n";
echo "========================================================\n";

try {
    $pdo->beginTransaction();

    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    
    $pdo->exec("TRUNCATE TABLE categories;");
    $catStmt = $pdo->prepare("INSERT INTO categories (id, name, slug, description, parent_id, status) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
    }
    echo "1. [OK] Đã cập nhật " . count($categories) . " danh mục (5 nhóm chính chuẩn theo hình).\n";

    
    $pdo->exec("TRUNCATE TABLE products;");
    $prodStmt = $pdo->prepare("INSERT INTO products (id, name, slug, price, sale_price, description, category_id, status, views, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    foreach ($products as $p) {
        $prodStmt->execute($p);
    }
    echo "2. [OK] Đã cập nhật " . count($products) . " sản phẩm thời trang (6 sản phẩm mỗi nhóm).\n";

    
    $pdo->exec("TRUNCATE TABLE product_images;");
    $imgStmt = $pdo->prepare("INSERT INTO product_images (id, product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($product_images as $img) {
        $imgStmt->execute($img);
    }
    echo "3. [OK] Đã cập nhật " . count($product_images) . " ảnh sản phẩm (đủ ảnh mặt trước và mặt sau thực tế).\n";

    
    $pdo->exec("TRUNCATE TABLE product_variants;");
    $varStmt = $pdo->prepare("INSERT INTO product_variants (id, product_id, size, color, stock) VALUES (?, ?, ?, ?, ?)");
    foreach ($product_variants as $variant) {
        $varStmt->execute($variant);
    }
    echo "4. [OK] Đã cập nhật " . count($product_variants) . " biến thể (đảm bảo 2-3 màu khác nhau + các size cho mỗi sản phẩm).\n";

    
    $variantsByProduct = [];
    foreach ($product_variants as $variant) {
        $variantsByProduct[$variant[1]][] = $variant[0];
    }

    $pdo->exec("TRUNCATE TABLE order_items;");
    $orderItems = [
        [1, 1, 1, 2, 149000],
        [2, 1, 12, 1, 399000],
        [3, 2, 17, 1, 449000],
        [4, 2, 21, 1, 579000],
        [5, 3, 25, 1, 459000],
        [6, 3, 5, 1, 129000],
        [7, 4, 7, 1, 259000],
        [8, 5, 23, 1, 349000],
        [9, 5, 1, 2, 149000],
    ];
    $orderItemStmt = $pdo->prepare("INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, price) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($orderItems as $item) {
        if (empty($variantsByProduct[$item[2]])) {
            throw new RuntimeException("Không tìm thấy biến thể cho sản phẩm ID " . $item[2]);
        }
        $orderItemStmt->execute([$item[0], $item[1], $item[2], $variantsByProduct[$item[2]][0], $item[3], $item[4]]);
    }

    $pdo->exec("TRUNCATE TABLE wishlists;");
    $pdo->exec("INSERT INTO wishlists (id, user_id, product_id) VALUES
        (1, 2, 17),
        (2, 2, 23),
        (3, 3, 12),
        (4, 3, 25);");

    $pdo->exec("TRUNCATE TABLE reviews;");
    $pdo->exec("INSERT INTO reviews (id, product_id, user_id, rating, comment, status, created_at) VALUES
        (1, 1, 2, 5, 'Vải mềm mịn, mặc rất thoải mái, chuẩn ảnh mặt trước sau.', 1, '2026-10-04 20:00:00'),
        (2, 12, 2, 4, 'Áo flannel ấm áp, màu sắc đẹp.', 1, '2026-10-05 21:10:00'),
        (3, 17, 3, 5, 'Hoodie nỉ bông dày dặn ấm cực kỳ, form unisex rất ưng.', 1, '2026-10-18 09:00:00'),
        (4, 7, 3, 4, 'Áo polo đẹp, vải cá sấu mặc thoáng khí.', 1, '2026-10-10 15:30:00'),
        (5, 21, 3, 3, 'Quần jean rách cá tính, hơi dài một chút.', 1, '2026-10-19 12:45:00');");

    $pdo->exec("TRUNCATE TABLE cart_items;");
    $pdo->exec("INSERT INTO cart_items (id, cart_id, variant_id, quantity) VALUES
        (1, 1, 1, 1),
        (2, 1, 25, 2);");

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    $pdo->commit();

    echo "========================================================\n";
    echo " CẬP NHẬT 30 SẢN PHẨM HOÀN TẤT THÀNH CÔNG RỰC RỠ!\n";
    echo "========================================================\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "LỖI KHI CẬP NHẬT: " . $e->getMessage() . "\n";
}
