
<?php
/**
 * Trang thanh toán - FashionStore
 */

$pageTitle = 'Thanh toán';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Order.php';
require_once __DIR__ . '/models/User.php';

// Bắt buộc đăng nhập
requireLogin();

$currentUser = currentUser();

// Lấy hồ sơ người dùng
$userModel = new User($pdo);
$userProfile = $userModel->findById($currentUser['id']);

$successOrderId = filter_input(
    INPUT_GET,
    'success',
    FILTER_VALIDATE_INT
);

$cart = getCart();

// Giỏ hàng trống và không phải trang thông báo thành công
if (empty($cart) && !$successOrderId) {
    setFlash('error', 'Giỏ hàng của bạn đang trống!');
    redirect('cart.php');
}

$orderModel = new Order($pdo);

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Phiên gửi biểu mẫu không hợp lệ!');
        redirect('checkout.php');
    }

    $receiverName = trim($_POST['receiver_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cod';

    if (
        $receiverName === '' ||
        $phone === '' ||
        $address === ''
    ) {
        setFlash('error', 'Vui lòng nhập đầy đủ thông tin người nhận!');
        redirect('checkout.php');
    }

    if (!preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $phone)) {
        setFlash(
            'error',
            'Số điện thoại không đúng định dạng 10 số tại Việt Nam!'
        );
        redirect('checkout.php');
    }

    if (!in_array($paymentMethod, ['cod', 'bank'], true)) {
        setFlash('error', 'Phương thức thanh toán không hợp lệ!');
        redirect('checkout.php');
    }

    $orderData = [
        'user_id' => (int) $currentUser['id'],
        'receiver_name' => $receiverName,
        'phone' => $phone,
        'address' => $address,
        'payment_method' => $paymentMethod,
    ];

    try {
        $orderId = $orderModel->createOrder($orderData, $cart);

        clearCart();

        setFlash(
            'success',
            'Đặt hàng thành công! Mã đơn hàng của bạn: #' . $orderId
        );

        redirect('checkout.php?success=' . $orderId);

    } catch (Throwable $e) {
        error_log('ORDER ERROR: ' . $e->getMessage());

        setFlash('error', $e->getMessage());
        redirect('checkout.php');
    }
}

// Tính tổng tiền
$cartTotal = getCartTotal();
$cartCount = getCartCount();

// Lấy thông tin mặc định từ hồ sơ
$profile = is_array($userProfile) ? $userProfile : [];

$defaultName = $profile['full_name']
    ?? $profile['name']
    ?? $currentUser['full_name']
    ?? $currentUser['name']
    ?? '';

$defaultPhone = $profile['phone']
    ?? $currentUser['phone']
    ?? '';

$defaultAddress = $profile['address']
    ?? $currentUser['address']
    ?? '';

$pageCss = 'checkout.css';

require_once __DIR__ . '/includes/header.php';
?>

<style>
.checkout-page {
    max-width: 1180px;
    margin: 35px auto 60px;
    padding: 0 20px;
    color: #252525;
}

.checkout-heading {
    margin-bottom: 28px;
}

.checkout-heading h1 {
    font-size: 27px;
    margin: 0 0 8px;
}

.checkout-heading p {
    color: #777;
    margin: 0;
    font-size: 14px;
}

.checkout-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 25px;
    align-items: start;
}

.checkout-card,
.checkout-summary {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 14px;
    overflow: hidden;
}

.checkout-card {
    padding: 25px;
    margin-bottom: 20px;
}

.checkout-card h2,
.checkout-summary h2 {
    font-size: 19px;
    margin: 0 0 22px;
}

.checkout-form-group {
    margin-bottom: 19px;
}

.checkout-form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
}

.checkout-form-group input,
.checkout-form-group textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 12px 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font: inherit;
    font-size: 14px;
    outline: none;
    transition: border-color .2s;
}

.checkout-form-group input:focus,
.checkout-form-group textarea:focus {
    border-color: #a52a3a;
}

.checkout-form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.payment-option {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    border: 1px solid #e5e5e5;
    border-radius: 9px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: .2s;
}

.payment-option:has(input:checked) {
    border-color: #a52a3a;
    background: #fff9f9;
}

.payment-option input {
    margin-top: 4px;
    accent-color: #a52a3a;
}

.payment-option strong {
    display: block;
    font-size: 14px;
    margin-bottom: 5px;
}

.payment-option small {
    color: #777;
    font-size: 12px;
}

.checkout-summary {
    padding: 23px;
    position: sticky;
    top: 20px;
}

.checkout-product {
    display: flex;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid #eee;
}

.checkout-product-image {
    width: 65px;
    height: 78px;
    object-fit: cover;
    border-radius: 7px;
    background: #f5f5f5;
    flex-shrink: 0;
}

.checkout-product-info {
    flex: 1;
    min-width: 0;
}

.checkout-product-name {
    font-size: 13px;
    font-weight: 700;
    margin: 0 0 6px;
}

.checkout-product-meta {
    color: #777;
    font-size: 12px;
    margin: 4px 0;
}

.checkout-product-price {
    color: #a52a3a;
    font-size: 13px;
    font-weight: 700;
    margin-top: 6px;
}

.checkout-summary-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin: 16px 0;
    font-size: 14px;
    color: #666;
}

.checkout-summary-total {
    border-top: 1px solid #eee;
    padding-top: 18px;
    margin-top: 20px;
    color: #222;
    font-weight: 700;
}

.checkout-summary-total strong {
    color: #a52a3a;
    font-size: 20px;
}

.checkout-submit {
    width: 100%;
    border: none;
    background: #a52a3a;
    color: white;
    padding: 15px;
    border-radius: 9px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 15px;
    transition: background .2s;
}

.checkout-submit:hover {
    background: #84202e;
}

.checkout-back {
    display: block;
    text-align: center;
    color: #555;
    text-decoration: none;
    margin-top: 16px;
    font-size: 13px;
}

.checkout-success {
    max-width: 650px;
    margin: 55px auto;
    padding: 45px 25px;
    text-align: center;
    background: white;
    border: 1px solid #eee;
    border-radius: 15px;
}

.checkout-success-icon {
    font-size: 55px;
    color: #238b57;
    margin-bottom: 15px;
}

.checkout-success h1 {
    font-size: 25px;
    margin-bottom: 12px;
}

.checkout-success p {
    color: #666;
    line-height: 1.7;
}

.checkout-order-code {
    display: inline-block;
    background: #f8f1f2;
    color: #a52a3a;
    font-size: 18px;
    font-weight: 700;
    padding: 12px 22px;
    border-radius: 8px;
    margin: 15px 0;
}

.checkout-success-btn {
    display: inline-block;
    background: #a52a3a;
    color: white;
    text-decoration: none;
    padding: 13px 25px;
    border-radius: 8px;
    margin-top: 15px;
    font-weight: 600;
}

@media (max-width: 850px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }

    .checkout-summary {
        position: static;
    }
}

@media (max-width: 550px) {
    .checkout-page {
        padding: 0 14px;
    }

    .checkout-card {
        padding: 18px;
    }

    .checkout-heading h1 {
        font-size: 23px;
    }
}
</style>

<section class="checkout-page">

    <?php if ($successOrderId): ?>

        <!-- ĐẶT HÀNG THÀNH CÔNG -->
        <div class="checkout-success">

            <div class="checkout-success-icon">✓</div>

            <h1>Đặt hàng thành công!</h1>

            <p>
                Cảm ơn bạn đã mua sắm tại FashionStore.
                Đơn hàng của bạn đã được ghi nhận.
            </p>

            <div class="checkout-order-code">
                Mã đơn hàng: #<?= (int) $successOrderId ?>
            </div>

            <p>
                Bạn có thể tiếp tục mua sắm hoặc kiểm tra
                trạng thái đơn hàng trong tài khoản.
            </p>

            <a href="<?= BASE_URL ?>/index.php"
               class="checkout-success-btn">
                Tiếp tục mua sắm
            </a>

        </div>

    <?php else: ?>

        <div class="checkout-heading">
            <h1>Thanh toán</h1>
            <p>Kiểm tra đơn hàng và điền thông tin nhận hàng.</p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/checkout.php">

            <?= csrfField() ?>

            <div class="checkout-layout">

                <!-- THÔNG TIN THANH TOÁN -->
                <div>

                    <div class="checkout-card">

                        <h2>Thông tin người nhận</h2>

                        <div class="checkout-form-group">
                            <label for="receiver_name">
                                Họ và tên người nhận *
                            </label>

                            <input
                                type="text"
                                id="receiver_name"
                                name="receiver_name"
                                value="<?= e($defaultName) ?>"
                                maxlength="150"
                                required
                                placeholder="Nhập họ và tên người nhận">
                        </div>

                        <div class="checkout-form-group">
                            <label for="phone">
                                Số điện thoại *
                            </label>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= e($defaultPhone) ?>"
                                pattern="(03|05|07|08|09)[0-9]{8}"
                                maxlength="10"
                                required
                                placeholder="Nhập số điện thoại">
                        </div>

                        <div class="checkout-form-group">
                            <label for="address">
                                Địa chỉ nhận hàng *
                            </label>

                            <textarea
                                id="address"
                                name="address"
                                maxlength="500"
                                required
                                placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố"><?= e($defaultAddress) ?></textarea>
                        </div>

                    </div>

                    <div class="checkout-card">

                        <h2>Phương thức thanh toán</h2>

                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="cod"
                                checked>

                            <span>
                                <strong>Thanh toán khi nhận hàng (COD)</strong>
                                <small>
                                    Thanh toán trực tiếp khi nhận được sản phẩm.
                                </small>
                            </span>

                        </label>

                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="bank">

                            <span>
                                <strong>Chuyển khoản ngân hàng</strong>
                                <small>
                                    Chọn phương thức chuyển khoản cho đơn hàng.
                                </small>
                            </span>

                        </label>

                    </div>

                </div>

                <!-- TÓM TẮT ĐƠN HÀNG -->
                <aside class="checkout-summary">

                    <h2>Đơn hàng của bạn</h2>

                    <?php foreach ($cart as $variantId => $item): ?>

                        <div class="checkout-product">

                            <?php if (!empty($item['image'])): ?>

                                <img
                                    class="checkout-product-image"
                                    src="<?= BASE_URL . '/public/' . ltrim(e($item['image']), '/') ?>"
                                    alt="<?= e($item['name']) ?>">

                            <?php else: ?>

                                <div class="checkout-product-image"></div>

                            <?php endif; ?>

                            <div class="checkout-product-info">

                                <p class="checkout-product-name">
                                    <?= e($item['name']) ?>
                                </p>

                                <p class="checkout-product-meta">
                                    Size: <?= e($item['size']) ?>
                                    | Màu: <?= e($item['color']) ?>
                                </p>

                                <p class="checkout-product-meta">
                                    Số lượng: <?= (int) $item['quantity'] ?>
                                </p>

                                <div class="checkout-product-price">
                                    <?= formatPrice(
                                        $item['price'] * $item['quantity']
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                    <div class="checkout-summary-row">
                        <span>Số lượng sản phẩm</span>
                        <strong><?= (int) $cartCount ?></strong>
                    </div>

                    <div class="checkout-summary-row">
                        <span>Tạm tính</span>
                        <strong><?= formatPrice($cartTotal) ?></strong>
                    </div>

                    <div class="checkout-summary-row">
                        <span>Phí vận chuyển</span>
                        <span>Chưa tính</span>
                    </div>

                    <div class="checkout-summary-row checkout-summary-total">
                        <span>Tổng tiền hàng</span>
                        <strong><?= formatPrice($cartTotal) ?></strong>
                    </div>

                    <button type="submit" class="checkout-submit">
                        Xác nhận đặt hàng
                    </button>

                    <a href="<?= BASE_URL ?>/cart.php"
                       class="checkout-back">
                        ← Quay lại giỏ hàng
                    </a>
                    <a href="<?= BASE_URL ?>/order-history.php"
   class="order-history-btn">
    <i class="fa-solid fa-box"></i>
    <span>Đơn hàng của tôi</span>
</a>

                </aside>

            </div>

        </form>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
