<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Product.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart = getCart();
$productModel = new Product($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
        redirect('cart.php');
    }

    $action = $_POST['action'] ?? '';
    if (isset($_POST['add_to_cart']) && empty($action)) {
        $action = 'add';
    }

    $variantId = filter_input(
        INPUT_POST,
        'variant_id',
        FILTER_VALIDATE_INT
    );

    
    if ($action === 'add') {
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT)
            ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;
        $size = trim($_POST['size'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $isBuyNow = !empty($_POST['buy_now']);

        if ($quantity < 1) {
            $quantity = 1;
        } elseif ($quantity > 100) {
            $quantity = 100;
        }

        
        if ((!$variantId || $variantId <= 0) && $productId) {
            if ($size !== '' || $color !== '') {
                $vSql = "SELECT id FROM product_variants 
                         WHERE product_id = :pid";
                $vParams = ['pid' => $productId];
                if ($size !== '') {
                    $vSql .= " AND size = :size";
                    $vParams['size'] = $size;
                }
                if ($color !== '') {
                    $vSql .= " AND color = :color";
                    $vParams['color'] = $color;
                }
                $vSql .= " AND stock > 0 ORDER BY id ASC LIMIT 1";
                $vStmt = $pdo->prepare($vSql);
                $vStmt->execute($vParams);
                $foundVId = $vStmt->fetchColumn();
                if ($foundVId) {
                    $variantId = (int)$foundVId;
                }
            }

            
            if (!$variantId || $variantId <= 0) {
                $fStmt = $pdo->prepare("SELECT id FROM product_variants WHERE product_id = :pid ORDER BY id ASC LIMIT 1");
                $fStmt->execute(['pid' => $productId]);
                $variantId = (int)$fStmt->fetchColumn();
            }
        }

        if (!$variantId || $variantId <= 0) {
            setFlash('error', 'Vui lòng chọn phân loại sản phẩm (kích cỡ, màu sắc).');
            redirect($_SERVER['HTTP_REFERER'] ?? 'cart.php');
        }

        
        $variantData = $productModel->getVariantById($variantId);
        if (!$variantData || (int)$variantData['status'] !== 1) {
            setFlash('error', 'Sản phẩm hoặc phân loại không còn tồn tại trên hệ thống.');
            redirect($_SERVER['HTTP_REFERER'] ?? 'cart.php');
        }

        $stock = (int)$variantData['stock'];
        if ($stock <= 0) {
            setFlash('error', 'Phân loại sản phẩm này hiện đã hết hàng.');
            redirect($_SERVER['HTTP_REFERER'] ?? 'cart.php');
        }

        
        $added = addToCart($variantData, $quantity);
        if (!$added) {
            setFlash('error', "Không thể thêm vào giỏ. Số lượng bạn chọn vượt quá số lượng tồn kho (còn {$stock} sản phẩm).");
            redirect($_SERVER['HTTP_REFERER'] ?? 'cart.php');
        }

        if ($isBuyNow) {
            redirect('checkout.php');
        } else {
            setFlash('success', 'Đã thêm sản phẩm vào giỏ hàng thành công!');
            redirect($_SERVER['HTTP_REFERER'] ?? 'cart.php');
        }
    }

    
    if (!$variantId || !isset($cart[$variantId])) {
        setFlash('error', 'Sản phẩm không tồn tại trong giỏ hàng.');
        redirect('cart.php');
    }

    
    if ($action === 'remove') {

        unset($_SESSION['cart'][$variantId]);

        setFlash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
        redirect('cart.php');
    }

    
    if ($action === 'update') {

        $quantity = filter_input(
            INPUT_POST,
            'quantity',
            FILTER_VALIDATE_INT
        );

        if ($quantity === false || $quantity < 1 || $quantity > 100) {
            setFlash(
                'error',
                'Số lượng sản phẩm phải từ 1 đến 100.'
            );
            redirect('cart.php');
        }

        
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

        
        $_SESSION['cart'][$variantId]['quantity'] = $quantity;

        setFlash('success', 'Đã cập nhật giỏ hàng.');
        redirect('cart.php');
    }

    
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

    
    <div class="cart-layout">

        <div class="cart-list">

            <div class="cart-list-heading">
                Sản phẩm đã chọn (<?= $cartCount ?>)
            </div>

            <?php foreach ($cart as $variantId => $item): ?>

                <div class="cart-item">

                    
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

                    
                    <div class="cart-item-subtotal">
                        <?= formatPrice(
                            $item['price'] * $item['quantity']
                        ) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        
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
