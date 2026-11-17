<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/products_data.php';

echo "========================================================\n";
echo " BẮT ĐẦU CẬP NHẬT 30 SẢN PHẨM MỚI VÀO DATABASE\n";
echo "========================================================\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    
    try {
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN image_path VARCHAR(255) DEFAULT NULL;");
        echo "✔ Đã kiểm tra/thêm cột image_path vào bảng product_variants.\n";
    } catch (Exception $e) {
        
    }

    
    $pdo->exec("TRUNCATE TABLE categories;");
    $stmtCat = $pdo->prepare("INSERT INTO categories (id, name, slug, description, parent_id, status) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $stmtCat->execute($cat);
    }
    echo "✔ Đã cập nhật " . count($categories) . " danh mục chuẩn theo Hình 1.\n";

    
    $pdo->exec("TRUNCATE TABLE products;");
    $stmtProd = $pdo->prepare("INSERT INTO products (id, name, slug, price, sale_price, description, category_id, status, views, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    foreach ($products as $p) {
        $stmtProd->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8]]);
    }
    echo "✔ Đã cập nhật " . count($products) . " sản phẩm chuẩn theo tên và nhóm.\n";

    
    $pdo->exec("TRUNCATE TABLE product_images;");
    $stmtImg = $pdo->prepare("INSERT INTO product_images (id, product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($product_images as $img) {
        $stmtImg->execute($img);
    }
    echo "✔ Đã cập nhật " . count($product_images) . " ảnh sản phẩm thực tế.\n";

    
    $pdo->exec("TRUNCATE TABLE product_variants;");
    $stmtVar = $pdo->prepare("INSERT INTO product_variants (id, product_id, size, color, stock, image_path) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($product_variants as $v) {
        $stmtVar->execute([$v[0], $v[1], $v[2], $v[3], $v[4], $v[5] ?? null]);
    }
    echo "✔ Đã cập nhật " . count($product_variants) . " biến thể kích cỡ/màu sắc có liên kết ảnh màu tương ứng.\n";

    
    $variantsByProduct = [];
    foreach ($product_variants as $variant) {
        $variantsByProduct[$variant[1]][] = $variant[0];
    }

    $pdo->exec("TRUNCATE TABLE order_items;");
    $orderItems = [
        [1, 1, 1, 2, 149000],
        [2, 1, 12, 1, 429000],
        [3, 2, 15, 1, 449000],
        [4, 2, 19, 1, 389000],
        [5, 3, 26, 1, 329000],
        [6, 3, 5, 1, 159000],
        [7, 4, 6, 1, 269000],
        [8, 5, 21, 1, 249000],
        [9, 5, 1, 2, 149000],
    ];
    $orderItemStmt = $pdo->prepare("INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, price) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($orderItems as $item) {
        $vId = $variantsByProduct[$item[2]][0] ?? 1;
        $orderItemStmt->execute([$item[0], $item[1], $item[2], $vId, $item[3], $item[4]]);
    }

    $pdo->exec("TRUNCATE TABLE wishlists;");
    $pdo->exec("INSERT INTO wishlists (id, user_id, product_id) VALUES
        (1, 2, 15),
        (2, 2, 21),
        (3, 3, 12),
        (4, 3, 26);");

    $pdo->exec("TRUNCATE TABLE reviews;");
    $pdo->exec("INSERT INTO reviews (id, product_id, user_id, rating, comment, status, created_at) VALUES
        (1, 1, 2, 5, 'Vải mềm mịn, mặc rất thoải mái, chuẩn ảnh mặt trước sau.', 1, '2026-10-04 20:00:00'),
        (2, 11, 2, 4, 'Áo flannel ấm áp, màu sắc đẹp.', 1, '2026-10-05 21:10:00'),
        (3, 15, 3, 5, 'Hoodie nỉ bông dày dặn ấm cực kỳ, form unisex rất ưng.', 1, '2026-10-18 09:00:00'),
        (4, 6, 3, 4, 'Áo polo đẹp, vải cá sấu mặc thoáng khí.', 1, '2026-10-10 15:30:00'),
        (5, 19, 3, 3, 'Quần jean rách cá tính, hơi dài một chút.', 1, '2026-10-19 12:45:00');");

    $pdo->exec("TRUNCATE TABLE cart_items;");
    $pdo->exec("INSERT INTO cart_items (id, cart_id, variant_id, quantity) VALUES
        (1, 1, 1, 1),
        (2, 1, 25, 2);");

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "\n========================================================\n";
    echo " CẬP NHẬT THÀNH CÔNG TOÀN BỘ DATABASE VỚI 30 SẢN PHẨM!\n";
    echo "========================================================\n";

} catch (Exception $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "❌ LỖI: " . $e->getMessage() . "\n";
}
