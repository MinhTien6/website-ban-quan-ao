
<?php
/**
 * Trang chủ FashionStore
 * TV5 - Tuần 1: Luồng đặt hàng bằng Session Cart
 */

$pageTitle = 'Trang chủ';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/includes/functions.php';

// Khởi tạo Product Model
$productModel = new Product($pdo);

// Xử lý thêm sản phẩm vào Session Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    // Kiểm tra CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Yêu cầu không hợp lệ!');
        redirect('index.php');
    }

    // Kiểm tra dữ liệu đầu vào
    $variantId = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$variantId || !$quantity || $quantity < 1) {
        setFlash('error', 'Thông tin sản phẩm không hợp lệ!');
        redirect('index.php');
    }

    // Lấy thông tin biến thể từ Database
    $variant = $productModel->getVariantById($variantId);

    // Thêm vào Session Cart
    if (!$variant || !addToCart($variant, $quantity)) {
        setFlash('error', 'Không thể thêm sản phẩm. Vui lòng kiểm tra tồn kho!');
        redirect('index.php');
    }

    setFlash('success', 'Đã thêm sản phẩm vào giỏ hàng!');
    redirect('index.php');
}

// Lấy 8 sản phẩm mới nhất
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
                // Kiểm tra ảnh sản phẩm
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

                // Lấy các biến thể sản phẩm
                $variants = $productModel->getVariants((int)$product['id']);
                ?>

                <div class="product-card">

                    <div class="product-card-image">
                        <?php if ($hasImage): ?>
                            <img
                                src="<?= e($imageUrl) ?>"
                                alt="<?= e($product['name']) ?>"
                                loading="lazy">
                        <?php else: ?>
                            <div class="product-image-placeholder">
                                <span>Chưa có ảnh</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($product['category_name'])): ?>
                        <div class="category">
                            <?= e($product['category_name']) ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="product-title" title="<?= e($product['name']) ?>">
                        <?= e($product['name']) ?>
                    </h3>

                    <div class="product-price">
                        <?php if (
                            !empty($product['sale_price'])
                            && (float)$product['sale_price'] > 0
                            && (float)$product['sale_price'] < (float)$product['price']
                        ): ?>
                            <span class="price-sale">
                                <?= formatPrice($product['sale_price']) ?>
                            </span>

                            <span class="price-original">
                                <?= formatPrice($product['price']) ?>
                            </span>
                        <?php else: ?>
                            <span class="price-regular">
                                <?= formatPrice($product['price']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="product-actions">

                        <?php if (!empty($variants)): ?>

                            <form method="POST" action="index.php">

                                <?= csrfField() ?>

                                <label>Chọn size và màu:</label>

                                <select name="variant_id" required>
                                    <?php foreach ($variants as $variant): ?>
                                        <option
                                            value="<?= (int)$variant['id'] ?>"
                                            <?= (int)$variant['stock'] <= 0 ? 'disabled' : '' ?>>

                                            <?= e($variant['size']) ?>
                                            -
                                            <?= e($variant['color']) ?>

                                            (Còn <?= (int)$variant['stock'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <label>Số lượng:</label>

                                <input
                                    type="number"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    required>

                                <button
                                    type="submit"
                                    name="add_to_cart"
                                    value="1"
                                    class="button">

                                    Thêm vào giỏ hàng

                                </button>

                            </form>

                        <?php else: ?>

                            <p>Sản phẩm hiện chưa có biến thể.</p>

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
