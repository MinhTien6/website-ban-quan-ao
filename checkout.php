<?php
/**
 * Trang thanh toán - FashionStore
 * TV5 - Xử lý thông tin người nhận, phương thức thanh toán, mã giảm giá và tạo đơn hàng
 */

$pageTitle = 'Thanh toán';
$pageCss = 'checkout.css';

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

// Kiểm tra quyền truy cập thông báo đặt hàng thành công (chống IDOR)
if (
    $successOrderId &&
    !$orderModel->belongsToUser(
        $successOrderId,
        (int)$currentUser['id']
    )
) {
    setFlash('error', 'Không tìm thấy đơn hàng.');
    redirect('my_orders.php');
}

// Xử lý gửi biểu mẫu (Áp dụng / Hủy mã giảm giá / Đặt hàng)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Kiểm tra CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Phiên gửi biểu mẫu không hợp lệ!');
        redirect('checkout.php');
    }

    $action = $_POST['action'] ?? 'place_order';

    // 2. Xử lý áp dụng mã giảm giá
    if ($action === 'apply_coupon') {
        $cartTotal = (float)getCartTotal();

        // Lưu tạm thông tin người nhận để không bị mất khi reload
        $_SESSION['checkout_draft'] = [
            'receiver_name'  => trim($_POST['receiver_name'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'address'        => trim($_POST['address'] ?? ''),
            'payment_method' => $_POST['payment_method'] ?? 'cod',
        ];

        if ($cartTotal <= 0) {
            unset($_SESSION['applied_coupon']);
            setFlash('error', 'Giỏ hàng của bạn đang trống nên không thể áp dụng mã giảm giá.');
            redirect('checkout.php');
        }

        $couponCode = trim($_POST['coupon_code'] ?? '');

        try {
            $couponResult = $orderModel->getCouponDiscount(
                $couponCode,
                $cartTotal
            );

            $_SESSION['applied_coupon'] = $couponResult['code'];

            setFlash(
                'success',
                'Áp dụng mã giảm giá thành công! Bạn được giảm '
                . formatPrice($couponResult['discount']) . '.'
            );
        } catch (Throwable $e) {
            unset($_SESSION['applied_coupon']);
            setFlash('error', $e->getMessage());
        }

        redirect('checkout.php');
    }

    // 3. Xử lý hủy mã giảm giá
    if ($action === 'remove_coupon') {
        // Lưu tạm thông tin người nhận để không bị mất khi reload
        $_SESSION['checkout_draft'] = [
            'receiver_name'  => trim($_POST['receiver_name'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'address'        => trim($_POST['address'] ?? ''),
            'payment_method' => $_POST['payment_method'] ?? 'cod',
        ];

        unset($_SESSION['applied_coupon']);
        setFlash('success', 'Đã hủy mã giảm giá.');
        redirect('checkout.php');
    }

    // 4. Xử lý đặt hàng (place_order)
    $receiverName  = trim($_POST['receiver_name'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cod';

    if ($receiverName === '' || $phone === '' || $address === '') {
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

    $appliedCouponCode = $_SESSION['applied_coupon'] ?? null;

    try {
        $orderId = $orderModel->createOrder(
            [
                'user_id'        => (int)$currentUser['id'],
                'receiver_name'  => $receiverName,
                'phone'          => $phone,
                'address'        => $address,
                'payment_method' => $paymentMethod,
            ],
            $cart,
            $appliedCouponCode
        );

        // Xóa giỏ hàng và dữ liệu nháp sau khi đặt hàng thành công
        clearCart();
        unset($_SESSION['applied_coupon']);
        unset($_SESSION['checkout_draft']);

        setFlash('success', 'Đặt hàng thành công! Đơn hàng của bạn đã được ghi nhận.');
        redirect('checkout.php?success=' . $orderId);

    } catch (Throwable $e) {
        setFlash('error', 'Đặt hàng thất bại: ' . $e->getMessage());
        redirect('checkout.php');
    }
}

// Tính toán giảm giá từ Session nếu có mã được áp dụng
$cartTotal = getCartTotal();
$cartCount = getCartCount();
$discount = 0;
$finalTotal = $cartTotal;
$appliedCoupon = null;

if (!empty($_SESSION['applied_coupon'])) {
    try {
        $appliedCoupon = $orderModel->getCouponDiscount(
            $_SESSION['applied_coupon'],
            (float)$cartTotal
        );

        $discount = (float)$appliedCoupon['discount'];
        $finalTotal = (float)$appliedCoupon['final_total'];
    } catch (Throwable $e) {
        unset($_SESSION['applied_coupon']);
        setFlash(
            'error',
            'Mã giảm giá không còn hợp lệ: ' . $e->getMessage()
        );
    }
}

// Lấy thông tin mặc định từ hồ sơ người dùng hoặc nháp đã lưu
$profile = is_array($userProfile) ? $userProfile : [];
$draft   = $_SESSION['checkout_draft'] ?? [];

$defaultName = $draft['receiver_name']
    ?? $profile['full_name']
    ?? $profile['name']
    ?? $currentUser['full_name']
    ?? $currentUser['name']
    ?? '';

$defaultPhone = $draft['phone']
    ?? $profile['phone']
    ?? $currentUser['phone']
    ?? '';

$defaultAddress = $draft['address']
    ?? $profile['address']
    ?? $currentUser['address']
    ?? '';

$defaultPayment = $draft['payment_method'] ?? 'cod';

require_once __DIR__ . '/includes/header.php';
?>

<section class="checkout-page">

    <?php if ($successOrderId): ?>

        <!-- ĐẶT HÀNG THÀNH CÔNG -->
        <div class="checkout-success">
            <div class="checkout-success-icon">✓</div>

            <h1>Đặt hàng thành công!</h1>

            <p>
                Cảm ơn bạn đã mua sắm tại FashionStore.<br>
                Đơn hàng của bạn đã được ghi nhận và đang chờ xử lý.
            </p>

            <div class="checkout-order-code">
                Mã đơn hàng: #<?= (int)$successOrderId ?>
            </div>

            <p style="margin-bottom: 20px;">
                Bạn có thể theo dõi tiến độ giao hàng và trạng thái đơn hàng trong tài khoản của mình.
            </p>

            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>/my_orders.php?id=<?= (int)$successOrderId ?>" class="btn-my-orders">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    Xem chi tiết đơn hàng
                </a>

                <a href="<?= BASE_URL ?>/index.php" class="checkout-success-btn" style="margin-top: 0;">
                    Tiếp tục mua sắm
                </a>
            </div>
        </div>

    <?php else: ?>

        <div class="checkout-heading">
            <h1>Thanh toán</h1>
            <p>Kiểm tra đơn hàng và điền thông tin nhận hàng.</p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/checkout.php" id="checkout-form">
            <?= csrfField() ?>

            <div class="checkout-layout">

                <!-- THÔNG TIN THANH TOÁN -->
                <div>
                    <div class="checkout-card">
                        <h2>Thông tin người nhận</h2>

                        <div class="checkout-form-group">
                            <label for="receiver_name">
                                Họ và tên người nhận <span style="color: #a52a3a;">*</span>
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
                                Số điện thoại <span style="color: #a52a3a;">*</span>
                            </label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= e($defaultPhone) ?>"
                                pattern="(03|05|07|08|09)[0-9]{8}"
                                maxlength="10"
                                required
                                placeholder="Nhập số điện thoại 10 số (VD: 0912345678)">
                        </div>

                        <div class="checkout-form-group">
                            <label for="address">
                                Địa chỉ nhận hàng <span style="color: #a52a3a;">*</span>
                            </label>
                            <textarea
                                id="address"
                                name="address"
                                maxlength="500"
                                required
                                placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"><?= e($defaultAddress) ?></textarea>
                        </div>
                    </div>

                    <div class="checkout-card">
                        <h2>Phương thức thanh toán</h2>

                        <label class="payment-option">
                            <input
                                type="radio"
                                name="payment_method"
                                value="cod"
                                <?= ($defaultPayment === 'cod') ? 'checked' : '' ?>>
                            <span>
                                <strong>Thanh toán khi nhận hàng (COD)</strong>
                                <small>Thanh toán bằng tiền mặt trực tiếp cho nhân viên giao hàng khi nhận được kiện hàng.</small>
                            </span>
                        </label>

                        <label class="payment-option">
                            <input
                                type="radio"
                                name="payment_method"
                                value="bank"
                                <?= ($defaultPayment === 'bank') ? 'checked' : '' ?>>
                            <span>
                                <strong>Chuyển khoản ngân hàng</strong>
                                <small>Chuyển khoản qua số tài khoản ngân hàng của shop trước khi giao hàng.</small>
                            </span>
                        </label>

                        <!-- Khung thông tin chuyển khoản ngân hàng (Yêu cầu đề bài Mục 1.2) -->
                        <div id="bank-info-box" style="display: <?= ($defaultPayment === 'bank') ? 'block' : 'none' ?>;">
                            <strong class="bank-title">Thông tin tài khoản nhận thanh toán:</strong>
                            <p>• Ngân hàng: <strong>Vietcombank - CN TP.HCM</strong></p>
                            <p>• Số tài khoản: <strong>0123456789</strong></p>
                            <p>• Chủ tài khoản: <strong>FASHION STORE</strong></p>
                            <p>• Nội dung CK: <strong>[Họ tên] + [Số điện thoại đặt hàng]</strong></p>
                            <small style="display: block; margin-top: 6px; color: #64748b;">
                                <em>* Vui lòng chuyển khoản đúng nội dung để đơn hàng được xác nhận nhanh nhất.</em>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- TÓM TẮT ĐƠN HÀNG -->
                <aside class="checkout-summary">
                    <h2>Đơn hàng của bạn</h2>

                    <!-- Mục nhập mã giảm giá -->
                    <div class="coupon-section">
                        <h3>Mã giảm giá</h3>

                        <?php if ($appliedCoupon): ?>
                            <div class="coupon-applied">
                                <div class="coupon-applied-info">
                                    <span class="coupon-badge"><?= e($appliedCoupon['code']) ?></span>
                                    <span class="coupon-saving">
                                        Đã giảm <?= formatPrice($discount) ?>
                                    </span>
                                </div>

                                <button
                                    type="submit"
                                    name="action"
                                    value="remove_coupon"
                                    formnovalidate
                                    class="btn-remove-coupon">
                                    ✕ Hủy mã
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="coupon-input-group">
                                <input
                                    type="text"
                                    name="coupon_code"
                                    maxlength="30"
                                    placeholder="Nhập mã (VD: WELCOME10)"
                                    autocomplete="off">

                                <button
                                    type="submit"
                                    name="action"
                                    value="apply_coupon"
                                    formnovalidate>
                                    Áp dụng
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Danh sách sản phẩm trong đơn -->
                    <div class="checkout-product-list">
                        <?php foreach ($cart as $variantId => $item): ?>
                            <div class="checkout-product">
                                <?php if (!empty($item['image'])): ?>
                                    <img
                                        class="checkout-product-image"
                                        src="<?= BASE_URL . '/public/' . ltrim(e($item['image']), '/') ?>"
                                        alt="<?= e($item['name']) ?>">
                                <?php else: ?>
                                    <div class="checkout-product-image-empty">Ảnh</div>
                                <?php endif; ?>

                                <div class="checkout-product-info">
                                    <p class="checkout-product-name"><?= e($item['name']) ?></p>
                                    <p class="checkout-product-meta">
                                        Size: <?= e($item['size']) ?> | Màu: <?= e($item['color']) ?>
                                    </p>
                                    <p class="checkout-product-meta">
                                        SL: <?= (int)$item['quantity'] ?> × <?= formatPrice($item['price']) ?>
                                    </p>
                                    <div class="checkout-product-price">
                                        <?= formatPrice($item['price'] * $item['quantity']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Tổng kết giá tiền -->
                    <div class="checkout-summary-row">
                        <span>Số lượng sản phẩm</span>
                        <strong><?= (int)$cartCount ?></strong>
                    </div>

                    <div class="checkout-summary-row">
                        <span>Tạm tính</span>
                        <strong><?= formatPrice($cartTotal) ?></strong>
                    </div>

                    <div class="checkout-summary-row">
                        <span>Phí vận chuyển</span>
                        <span style="color: #238b57; font-weight: 500;">Miễn phí</span>
                    </div>

                    <?php if ($discount > 0): ?>
                        <div class="checkout-summary-row">
                            <span>Giảm giá <?= !empty($appliedCoupon['code']) ? '(' . e($appliedCoupon['code']) . ')' : '' ?></span>
                            <strong class="summary-discount">-<?= formatPrice($discount) ?></strong>
                        </div>
                    <?php endif; ?>

                    <div class="checkout-summary-total">
                        <span>Tổng thanh toán</span>
                        <strong><?= formatPrice($finalTotal) ?></strong>
                    </div>

                    <button type="submit" name="action" value="place_order" class="checkout-submit">
                        Xác nhận đặt hàng
                    </button>

                    <a href="<?= BASE_URL ?>/cart.php" class="checkout-back">
                        ← Quay lại giỏ hàng
                    </a>
                </aside>

            </div>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var radios = document.querySelectorAll('input[name="payment_method"]');
            var bankBox = document.getElementById('bank-info-box');

            radios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    if (bankBox) {
                        bankBox.style.display = (this.value === 'bank') ? 'block' : 'none';
                    }
                });
            });
        });
        </script>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
