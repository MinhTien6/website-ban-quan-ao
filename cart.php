
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
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Yêu cầu không hợp lệ.');
        redirect('cart.php');
    }

    $variantId = (int)($_POST['variant_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (isset($cart[$variantId])) {
        if ($action === 'update') {
            $quantity = (int)($_POST['quantity'] ?? 1);

            $stmt = $pdo->prepare(
                "SELECT stock FROM product_variants WHERE id = :id"
            );
            $stmt->execute(['id' => $variantId]);
            $stock = (int)$stmt->fetchColumn();

            if ($quantity < 1) {
                setFlash('error', 'Số lượng phải lớn hơn 0.');
            } elseif ($quantity > $stock) {
                setFlash('error', 'Số lượng vượt quá tồn kho.');
            } else {
                $_SESSION['cart'][$variantId]['quantity'] = $quantity;
                setFlash('success', 'Đã cập nhật giỏ hàng.');
            }
        } elseif ($action === 'remove') {
            unset($_SESSION['cart'][$variantId]);
            setFlash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
        }
    }

    redirect('cart.php');
}

$cart = getCart();
$cartTotal = getCartTotal();
$cartCount = getCartCount();

$pageTitle = 'Giỏ hàng';
require_once __DIR__ . '/includes/header.php';
?>

<style>
.cart-page {
    max-width: 1180px;
    margin: 35px auto 60px;
    padding: 0 20px;
    color: #252525;
}

.cart-heading {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
}

.cart-heading .cart-heading-icon {
    width: 54px;
    height: 54px;
    background: #f4e9e9;
    color: #a52a3a;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cart-heading h1 {
    font-size: 27px;
    margin: 0 0 5px;
}

.cart-heading p {
    color: #777;
    margin: 0;
    font-size: 14px;
}

.cart-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 24px;
    align-items: start;
}

.cart-list, .cart-summary {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 14px;
    overflow: hidden;
}

.cart-list-heading {
    padding: 18px 22px;
    border-bottom: 1px solid #eee;
    font-weight: 700;
}

.cart-item {
    display: grid;
    grid-template-columns: 105px minmax(0, 1fr) 125px;
    gap: 18px;
    padding: 22px;
    border-bottom: 1px solid #eee;
    align-items: center;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-image {
    width: 105px;
    height: 125px;
    border-radius: 10px;
    object-fit: cover;
    background: #f5f5f5;
}

.cart-item-placeholder {
    width: 105px;
    height: 125px;
    border-radius: 10px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
}

.cart-item-name {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 10px;
}

.cart-item-meta {
    color: #777;
    font-size: 13px;
    margin: 5px 0;
}

.cart-item-price {
    color: #a52a3a;
    font-weight: 700;
    margin-top: 10px;
}

.cart-quantity {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 14px;
}

.cart-quantity input {
    width: 55px;
    height: 34px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.cart-quantity button {
    border: none;
    background: #252525;
    color: white;
    border-radius: 6px;
    padding: 8px 11px;
    cursor: pointer;
    font-size: 12px;
}

.cart-remove {
    margin-top: 9px;
    border: none;
    background: none;
    color: #c03945;
    cursor: pointer;
    padding: 0;
    font-size: 13px;
}

.cart-remove:hover {
    text-decoration: underline;
}

.cart-item-subtotal {
    text-align: right;
    font-weight: 700;
    color: #333;
}

.cart-summary {
    padding: 22px;
    position: sticky;
    top: 20px;
}

.cart-summary h2 {
    font-size: 19px;
    margin: 0 0 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin: 15px 0;
    color: #666;
    font-size: 14px;
}

.summary-total {
    border-top: 1px solid #eee;
    padding-top: 18px;
    margin-top: 20px;
    color: #222;
    font-size: 16px;
    font-weight: 700;
}

.summary-total strong {
    color: #a52a3a;
    font-size: 21px;
}

.cart-checkout-btn {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 9px;
    width: 100%;
    box-sizing: border-box;
    background: #a52a3a;
    color: white !important;
    text-decoration: none;
    padding: 14px;
    border-radius: 9px;
    font-weight: 700;
    margin-top: 22px;
    transition: .2s;
}

.cart-checkout-btn:hover {
    background: #84202e;
}

.cart-continue {
    display: flex;
    justify-content: center;
    gap: 7px;
    margin-top: 17px;
    color: #555;
    text-decoration: none;
    font-size: 13px;
}

.cart-continue:hover {
    color: #a52a3a;
}

.cart-empty {
    background: white;
    border: 1px solid #eee;
    border-radius: 14px;
    text-align: center;
    padding: 65px 20px;
}

.cart-empty-icon {
    color: #aaa;
    margin-bottom: 15px;
}

.cart-empty h2 {
    font-size: 21px;
}

.cart-empty p {
    color: #777;
    margin-bottom: 24px;
}

@media (max-width: 850px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }

    .cart-summary {
        position: static;
    }
}

@media (max-width: 550px) {
    .cart-item {
        grid-template-columns: 82px minmax(0, 1fr);
        gap: 13px;
        padding: 15px;
    }

    .cart-item-image, .cart-item-placeholder {
        width: 82px;
        height: 105px;
    }

    .cart-item-subtotal {
        grid-column: 2;
        text-align: left;
    }

    .cart-heading h1 {
        font-size: 22px;
    }
}
</style>

<section class="cart-page">

    <div class="cart-heading">
        <div class="cart-heading-icon">
            <svg width="28" height="28" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"/>
                <circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
        </div>

        <div>
            <h1>Giỏ hàng của bạn</h1>
            <p><?= $cartCount ?> sản phẩm đang có trong giỏ hàng</p>
        </div>
    </div>

    <?php if (empty($cart)): ?>

        <div class="cart-empty">
            <div class="cart-empty-icon">
                <svg width="65" height="65" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"/>
                    <circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
            </div>

            <h2>Giỏ hàng đang trống</h2>
            <p>Hãy khám phá các sản phẩm thời trang và chọn món đồ yêu thích nhé!</p>

            <a href="index.php" class="cart-checkout-btn"
               style="max-width:220px;margin:0 auto;">
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
                                src="<?= BASE_URL . '/' . ltrim(e($item['image']), '/') ?>"
                                alt="<?= e($item['name']) ?>">
                        <?php else: ?>
                            <div class="cart-item-placeholder">
                                <svg width="30" height="30" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor"
                                     stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <path d="m21 15-5-5L5 21"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <div>
                            <h3 class="cart-item-name">
                                <?= e($item['name']) ?>
                            </h3>

                            <p class="cart-item-meta">
                                <strong>Size:</strong> <?= e($item['size']) ?>
                            </p>

                            <p class="cart-item-meta">
                                <strong>Màu:</strong> <?= e($item['color']) ?>
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

                <a href="checkout.php" class="cart-checkout-btn">
                    <svg width="19" height="19" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"/>
                        <path d="m12 5 7 7-7 7"/>
                    </svg>
                    Tiến hành thanh toán
                </a>

                <a href="index.php" class="cart-continue">
                    ← Tiếp tục mua sắm
                </a>

            </aside>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
