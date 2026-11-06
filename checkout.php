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
             <a href="<?= BASE_URL ?>/my_orders.php" class="btn-my-orders">
    <i class="fas fa-receipt"></i>
    Đơn hàng của tôi
</a>

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
                                checked
                                onchange="toggleBankInfo()">

                            <span>
                                <strong>Thanh toán khi nhận hàng (COD)</strong>
                                <small>
                                    Thanh toán bằng tiền mặt trực tiếp cho nhân viên giao hàng khi nhận được sản phẩm.
                                </small>
                            </span>

                        </label>

                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="bank"
                                onchange="toggleBankInfo()">

                            <span>
                                <strong>Chuyển khoản ngân hàng</strong>
                                <small>
                                    Chuyển khoản qua tài khoản ngân hàng của FashionStore.
                                </small>
                            </span>

                        </label>

                        <!-- Khung thông tin chuyển khoản theo đúng Mục 1.2 Kế hoạch dự án -->
                        <div id="bank-transfer-info">
                            <strong style="color: #a52a3a; display: block; margin-bottom: 6px;">🏦 Thông tin tài khoản nhận thanh toán:</strong>
                            <p style="margin: 2px 0;">• <strong>Ngân hàng:</strong> Vietcombank - Chi nhánh TP. Hồ Chí Minh</p>
                            <p style="margin: 2px 0;">• <strong>Số tài khoản:</strong> <code>1029384756</code></p>
                            <p style="margin: 2px 0;">• <strong>Chủ tài khoản:</strong> CONG TY THOI TRANG FASHIONSTORE</p>
                            <p style="margin: 2px 0;">• <strong>Số tiền:</strong> <strong style="color: #a52a3a;"><?= formatPrice($cartTotal) ?></strong></p>
                            <p style="margin: 2px 0;">• <strong>Nội dung CK:</strong> <code>THANHTOAN <?= e($defaultPhone ?: 'SDT_CUA_BAN') ?></code></p>
                        </div>

                    </div>

                    <script>
                    function toggleBankInfo() {
                        const isBank = document.querySelector('input[name="payment_method"][value="bank"]').checked;
                        const infoBox = document.getElementById('bank-transfer-info');
                        if (infoBox) {
                            infoBox.style.display = isBank ? 'block' : 'none';
                        }
                    }
                    </script>

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
                    <a href="<?= BASE_URL ?>/my_orders.php" class="btn-my-orders">
    <i class="fas fa-receipt"></i>
    Đơn hàng của tôi
</a>

                    <a href="<?= BASE_URL ?>/cart.php"
                       class="checkout-back">
                        ← Quay lại giỏ hàng
                    </a>

                </aside>

            </div>

        </form>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
