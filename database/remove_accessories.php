<?php
/**
 * Script xóa sạch danh mục và sản phẩm Phụ kiện khỏi CSDL MySQL
 * Chạy bằng: D:\xampp\php\php.exe database/remove_accessories.php
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

echo "Bắt đầu xóa bỏ danh mục và sản phẩm Phụ kiện...\n";

try {
    $pdo->beginTransaction();
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Tìm ID các sản phẩm thuộc phụ kiện
    $stmt = $pdo->query("SELECT id FROM products WHERE category_id = 6 OR slug IN ('mu-luoi-trai-theu-logo', 'that-lung-da-bo', 'tui-tote-vai-canvas', 'khan-choang-len')");
    $prodIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($prodIds)) {
        $inClause = implode(',', array_map('intval', $prodIds));
        
        // Xóa ảnh phụ kiện
        $pdo->exec("DELETE FROM product_images WHERE product_id IN ($inClause)");
        
        // Xóa biến thể phụ kiện
        $pdo->exec("DELETE FROM product_variants WHERE product_id IN ($inClause)");
        
        // Xóa chi tiết đơn hàng nếu có
        $pdo->exec("DELETE FROM order_items WHERE product_id IN ($inClause)");
        
        // Xóa sản phẩm phụ kiện
        $pdo->exec("DELETE FROM products WHERE id IN ($inClause)");
        
        echo "- Đã xóa " . count($prodIds) . " sản phẩm phụ kiện (Mũ, Thắt lưng, Túi tote, Khăn choàng).\n";
    } else {
        echo "- Không tìm thấy sản phẩm phụ kiện nào trong bảng products.\n";
    }

    // 2. Xóa danh mục Phụ kiện (ID = 6 hoặc slug = 'phu-kien')
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
