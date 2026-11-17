<?php
/**
 * Script cập nhật trực tiếp 30 sản phẩm mới vào MySQL
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/products_data.php';

echo "========================================================\n";
echo " BẮT ĐẦU CẬP NHẬT 30 SẢN PHẨM MỚI VÀO DATABASE\n";
echo "========================================================\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Cập nhật danh mục
    $pdo->exec("TRUNCATE TABLE categories;");
    $stmtCat = $pdo->prepare("INSERT INTO categories (id, name, slug, description, parent_id, status) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $stmtCat->execute($cat);
    }
    echo "✔ Đã cập nhật " . count($categories) . " danh mục chuẩn.\n";

    // 2. Cập nhật sản phẩm
    $pdo->exec("TRUNCATE TABLE products;");
    $stmtProd = $pdo->prepare("INSERT INTO products (id, name, slug, price, sale_price, description, category_id, status, views, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    foreach ($products as $p) {
        $stmtProd->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8]]);
    }
    echo "✔ Đã cập nhật " . count($products) . " sản phẩm.\n";

    // 3. Cập nhật ảnh sản phẩm
    $pdo->exec("TRUNCATE TABLE product_images;");
    $stmtImg = $pdo->prepare("INSERT INTO product_images (id, product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($product_images as $img) {
        $stmtImg->execute($img);
    }
    echo "✔ Đã cập nhật " . count($product_images) . " ảnh sản phẩm.\n";

    // 4. Cập nhật biến thể sản phẩm
    $pdo->exec("TRUNCATE TABLE product_variants;");
    $stmtVar = $pdo->prepare("INSERT INTO product_variants (id, product_id, size, color, stock) VALUES (?, ?, ?, ?, ?)");
    foreach ($product_variants as $v) {
        $stmtVar->execute($v);
    }
    echo "✔ Đã cập nhật " . count($product_variants) . " biến thể kích cỡ/màu sắc.\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "\n========================================================\n";
    echo " CẬP NHẬT THÀNH CÔNG TOÀN BỘ DATABASE!\n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
}
