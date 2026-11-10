<?php
/**
 * Trang chủ FashionStore
 */

$pageTitle = 'Trang chủ';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Product.php';

// Khởi tạo model và lấy 8 sản phẩm mới nhất
$productModel = new Product($pdo);
   
// Xử lý thêm sản phẩm vào Session Cart (TV5)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Yêu cầu không hợp lệ!');
        redirect('index.php');
    }

    $variantId = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$variantId || !$quantity || $quantity < 1) {
        setFlash('error', 'Thông tin sản phẩm không hợp lệ!');
        redirect('index.php');
    }

    $variant = $productModel->getVariantById($variantId);
    if (!$variant || !addToCart($variant, $quantity)) {
        setFlash('error', 'Không thể thêm sản phẩm. Vui lòng kiểm tra tồn kho!');
        redirect('index.php');
    }

    setFlash('success', 'Đã thêm sản phẩm vào giỏ hàng thành công!');
    redirect('index.php');
}

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

                    <?php $variants = $productModel->getVariants((int)$product['id']); ?>
                    <div class="product-actions">
                        <?php if (!empty($variants)): ?>
                            <form method="POST" action="<?= BASE_URL ?>/index.php" class="home-add-cart-form">
                                <?= csrfField() ?>
                                <div class="form-group-sm">
                                    <select name="variant_id" class="form-control-sm" required>
                                        <?php foreach ($variants as $variant): ?>
                                            <option value="<?= (int)$variant['id'] ?>" <?= (int)$variant['stock'] <= 0 ? 'disabled' : '' ?>>
                                                <?= e($variant['size']) ?> - <?= e($variant['color']) ?> (Còn <?= (int)$variant['stock'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="home-cart-actions">
                                    <input type="number" name="quantity" value="1" min="1" max="99" class="cart-qty-input" required>
                                    <button type="submit" name="add_to_cart" value="1" class="button button-sm">
                                        🛒 Thêm vào giỏ
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <p class="empty-state-sm">Hết hàng</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
