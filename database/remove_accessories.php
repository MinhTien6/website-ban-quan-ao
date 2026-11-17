<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

echo "Bắt đầu xóa bỏ danh mục và sản phẩm Phụ kiện...\n";

try {
    $pdo->beginTransaction();
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    
    $stmt = $pdo->query("SELECT id FROM products WHERE category_id = 6 OR slug IN ('mu-luoi-trai-theu-logo', 'that-lung-da-bo', 'tui-tote-vai-canvas', 'khan-choang-len')");
    $prodIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($prodIds)) {
        $inClause = implode(',', array_map('intval', $prodIds));
        
        
        $pdo->exec("DELETE FROM product_images WHERE product_id IN ($inClause)");
        
        
        $pdo->exec("DELETE FROM product_variants WHERE product_id IN ($inClause)");
        
        
        $pdo->exec("DELETE FROM order_items WHERE product_id IN ($inClause)");
        
        
        $pdo->exec("DELETE FROM products WHERE id IN ($inClause)");
        
        echo "- Đã xóa " . count($prodIds) . " sản phẩm phụ kiện (Mũ, Thắt lưng, Túi tote, Khăn choàng).\n";
    } else {
        echo "- Không tìm thấy sản phẩm phụ kiện nào trong bảng products.\n";
    }

    
    $delCat = $pdo->exec("DELETE FROM categories WHERE id = 6 OR slug = 'phu-kien'");
    if ($delCat > 0) {
        echo "- Đã xóa danh mục 'Phụ kiện' khỏi CSDL.\n";
    } else {
        echo "- Danh mục 'Phụ kiện' đã được loại bỏ từ trước.\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    $pdo->commit();

    echo "==> ĐÃ DỌN DẸP SẠCH TOÀN BỘ PHỤ KIỆN THÀNH CÔNG!\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "LỖI: " . $e->getMessage() . "\n";
}
