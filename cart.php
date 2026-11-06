<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart = getCart();

// Xử lý cập nhật và xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Kiểm tra CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Yêu cầu không hợp lệ.');
        redirect('cart.php');
    }

    // 2. Lấy dữ liệu gửi lên
    $variantId = filter_input(
        INPUT_POST,
        'variant_id',
        FILTER_VALIDATE_INT
    );

    $action = $_POST['action'] ?? '';

    // 3. Kiểm tra dữ liệu
    if (!$variantId || !isset($cart[$variantId])) {
        setFlash('error', 'Sản phẩm không tồn tại trong giỏ hàng.');
        redirect('cart.php');
    }

    // 4. Xử lý xóa sản phẩm
    if ($action === 'remove') {

        unset($_SESSION['cart'][$variantId]);

        setFlash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
        redirect('cart.php');
    }

    // 5. Xử lý cập nhật số lượng
    if ($action === 'update') {

        $quantity = filter_input(
            INPUT_POST,
            'quantity',
            FILTER_VALIDATE_INT
        );

        if (!$quantity || $quantity < 1) {
            setFlash('error', 'Số lượng phải lớn hơn 0.');
            redirect('cart.php');
        }

        // Kiểm tra biến thể và sản phẩm còn hoạt động
        $stmt = $pdo->prepare("
            SELECT
                pv.id,
                pv.stock,
                p.status
            FROM product_variants pv
            INNER JOIN products p ON p.id = pv.product_id
            WHERE pv.id = :variant_id
              AND p.status = 1
            LIMIT 1
        ");

        $stmt->execute([
            'variant_id' => $variantId
        ]);

        $variant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$variant) {
            unset($_SESSION['cart'][$variantId]);

            setFlash(
                'error',
                'Sản phẩm không còn khả dụng và đã được xóa khỏi giỏ hàng.'
            );

            redirect('cart.php');
        }

        $stock = (int)$variant['stock'];

        if ($stock <= 0) {
            unset($_SESSION['cart'][$variantId]);

            setFlash(
                'error',
                'Sản phẩm đã hết hàng và được xóa khỏi giỏ hàng.'
            );

            redirect('cart.php');
        }

        if ($quantity > $stock) {
            setFlash(
                'error',
                "Số lượng vượt quá tồn kho. Hiện chỉ còn {$stock} sản phẩm."
            );

            redirect('cart.php');
        }

        // Cập nhật số lượng
        $_SESSION['cart'][$variantId]['quantity'] = $quantity;

        setFlash('success', 'Đã cập nhật giỏ hàng.');
        redirect('cart.php');
    }

    // 6. Action không hợp lệ
    setFlash('error', 'Thao tác không hợp lệ.');
    redirect('cart.php');
}

$cart = getCart();
$cartTotal = getCartTotal();
$cartCount = getCartCount();

$pageTitle = 'Giỏ hàng';
$pageCss = 'cart.css';

require_once __DIR__ . '/includes/header.php';
?>

<section class="cart-page">

    <?php if (empty($cart)): ?>

    <!-- GIAO DIỆN KHI GIỎ HÀNG TRỐNG -->
    <div class="cart-empty">
        <div class="cart-empty-icon">
            <svg width="60" height="60" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="1.5"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"/>
                <circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
        </div>

        <h2>Giỏ hàng đang trống</h2>

        <p>Bạn chưa có sản phẩm nào trong giỏ hàng.</p>

        <a href="<?= BASE_URL ?>/index.php" class="cart-checkout-btn">
            Tiếp tục mua sắm
        </a>
    </div>

<?php else: ?>

    <!-- GIAO DIỆN KHI CÓ SẢN PHẨM -->
    <div class="cart-layout">

        <div class="cart-list">

            <div class="cart-list-heading">
                Sản phẩm đã chọn (<?= $cartCount ?>)
            </div>

            <?php foreach ($cart as $variantId => $item): ?>

                <div class="cart-item">

                    <!-- Ảnh sản phẩm -->
                    <?php if (!empty($item['image'])): ?>
                        <img
                            class="cart-item-image"
                            src="<?= BASE_URL . '/public/' . ltrim(e($item['image']), '/') ?>"
                            alt="<?= e($item['name']) ?>">
                    <?php else: ?>
                        <div class="cart-item-placeholder">
                            Chưa có ảnh
                        </div>
                    <?php endif; ?>

                    <!-- Thông tin sản phẩm -->
                    <div>
                        <h3 class="cart-item-name">
                            <?= e($item['name']) ?>
                        </h3>

                        <p class="cart-item-meta">
                            <strong>Size:</strong>
                            <?= e($item['size']) ?>
                        </p>

                        <p class="cart-item-meta">
                            <strong>Màu:</strong>
                            <?= e($item['color']) ?>
                        </p>

                        <div class="cart-item-price">
                            <?= formatPrice($item['price']) ?>
                        </div>

                        <!-- Cập nhật số lượng -->
                        <form method="POST" action="cart.php"
                              class="cart-quantity">

                            <?= csrfField() ?>

                            <input type="hidden" name="variant_id"
                                   value="<?= (int)$variantId ?>">

                            <input type="hidden" name="action"
                                   value="update">

                            <input type="number" name="quantity"
                                   value="<?= (int)$item['quantity'] ?>"
                                   min="1" required>

                            <button type="submit">Cập nhật</button>
                        </form>

                        <!-- Xóa sản phẩm -->
                        <form method="POST" action="cart.php"
                              onsubmit="return confirm('Bạn muốn xóa sản phẩm này khỏi giỏ hàng?');">

                            <?= csrfField() ?>

                            <input type="hidden" name="variant_id"
                                   value="<?= (int)$variantId ?>">

                            <input type="hidden" name="action"
                                   value="remove">

                            <button type="submit" class="cart-remove">
                                ✕ Xóa sản phẩm
                            </button>
                        </form>
                    </div>

                    <!-- Thành tiền -->
                    <div class="cart-item-subtotal">
                        <?= formatPrice(
                            $item['price'] * $item['quantity']
                        ) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <!-- TỔNG KẾT ĐƠN HÀNG -->
        <aside class="cart-summary">

            <h2>Đơn hàng của bạn</h2>

            <div class="summary-row">
                <span>Số lượng sản phẩm</span>
                <strong><?= $cartCount ?></strong>
            </div>

            <div class="summary-row">
                <span>Tạm tính</span>
                <strong><?= formatPrice($cartTotal) ?></strong>
            </div>

            <div class="summary-row">
                <span>Phí vận chuyển</span>
                <span>Được xác nhận khi giao hàng</span>
            </div>

            <div class="summary-row summary-total">
                <span>Tổng tiền hàng</span>
                <strong><?= formatPrice($cartTotal) ?></strong>
            </div>

            <a href="<?= BASE_URL ?>/checkout.php"
               class="cart-checkout-btn">
                Tiến hành thanh toán
            </a>

            <a href="<?= BASE_URL ?>/index.php" class="cart-continue">
                ← Tiếp tục mua sắm
            </a>

        </aside>

    </div>

<?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
