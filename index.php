<?php
/**
 * Trang chủ FashionStore
 */

$pageTitle = 'Trang chủ';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/product.php';

// Khởi tạo model và lấy 8 sản phẩm mới nhất
$productModel = new Product($pdo);
$latestProducts = $productModel->getLatest(8);

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-content">
        <p class="eyebrow">BỘ SƯU TẬP MỚI 2026</p>
        <h1>Phong cách thời trang hiện đại & thanh lịch</h1>
        <p>Khám phá các mẫu trang phục mới nhất với chất liệu cao cấp và kiểu dáng dẫn đầu xu hướng.</p>
        <a href="#products" class="button">Xem sản phẩm ngay</a>
    </div>
</section>

<section id="products" class="section">
    <div class="section-heading">
        <h2>Sản phẩm mới nhất</h2>
    </div>

    <?php if (empty($latestProducts)): ?>
        <p class="empty-state">Hiện chưa có sản phẩm nào được hiển thị.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($latestProducts as $product): ?>
                <?php
                // Kiểm tra sự tồn tại của file ảnh trên máy chủ
                $imagePath = $product['main_image'] ?? '';
                $hasImage = false;
                $imageUrl = '';

                if ($imagePath !== '') {
                    $localPath = ROOT_PATH . '/public/' . ltrim($imagePath, '/');
                    if (file_exists($localPath) && !is_dir($localPath)) {
                        $hasImage = true;
                        $imageUrl = BASE_URL . '/public/' . ltrim($imagePath, '/');
                    }
                }
                ?>
                <div class="product-card">
                    <div class="product-card-image">
                        <?php if ($hasImage): ?>
                            <img src="<?= e($imageUrl) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="product-image-placeholder">
                                <span>Chưa có ảnh</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($product['category_name'])): ?>
                        <div class="category"><?= e($product['category_name']) ?></div>
                    <?php endif; ?>

                    <h3 class="product-title" title="<?= e($product['name']) ?>">
                        <?= e($product['name']) ?>
                    </h3>

                    <div class="product-price">
                        <?php if (!empty($product['sale_price']) && (float)$product['sale_price'] > 0 && (float)$product['sale_price'] < (float)$product['price']): ?>
                            <span class="price-sale"><?= formatPrice($product['sale_price']) ?></span>
                            <span class="price-original"><?= formatPrice($product['price']) ?></span>
                        <?php else: ?>
                            <span class="price-regular"><?= formatPrice($product['price']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="product-actions">
                        <button type="button" class="button button-disabled" disabled>Xem chi tiết</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
