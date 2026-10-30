
<?php
/**
 * Checkout - TV5
 * Xử lý luồng đặt hàng từ Session Cart
 */

$pageTitle = 'Thanh toán';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Order.php';

// Không cho thanh toán khi giỏ hàng trống
$successOrderId = filter_input(
    INPUT_GET,
    'success',
    FILTER_VALIDATE_INT
);

$cart = getCart();

if (empty($cart) && !$successOrderId) {
    setFlash('error', 'Giỏ hàng đang trống!');
    redirect('index.php');
}
// Khởi tạo Order Model
$orderModel = new Order($pdo);

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Kiểm tra CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Yêu cầu không hợp lệ!');
        redirect('checkout.php');
    }

    // Lấy thông tin người nhận
    $receiverName = trim($_POST['receiver_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? '';

    // Kiểm tra thông tin bắt buộc
    if ($receiverName === '' || $phone === '' || $address === '') {
        setFlash('error', 'Vui lòng nhập đầy đủ thông tin nhận hàng!');
        redirect('checkout.php');
    }

    if (!preg_match('/^[0-9]{9,11}$/', $phone)) {
        setFlash('error', 'Số điện thoại không hợp lệ!');
        redirect('checkout.php');
    }

    if (!in_array($paymentMethod, ['cod', 'bank'], true)) {
        setFlash('error', 'Phương thức thanh toán không hợp lệ!');
        redirect('checkout.php');
    }

    // Chuẩn bị dữ liệu đơn hàng
    $orderData = [
        'user_id' => isLoggedIn() ? currentUser()['id'] : null,
        'receiver_name' => $receiverName,
        'phone' => $phone,
        'address' => $address,
        'payment_method' => $paymentMethod
    ];

    try {
        // Tạo đơn hàng và trừ tồn kho trong Transaction
        $orderId = $orderModel->createOrder($orderData, $cart);

        // Chỉ xóa Session sau khi tạo đơn thành công
        clearCart();

        setFlash(
            'success',
            'Đặt hàng thành công! Mã đơn hàng: #' . $orderId
        );

        redirect('checkout.php?success=' . $orderId);

    } catch (Throwable $e) {
    error_log('ORDER ERROR: ' . $e->getMessage());

    echo '<pre>';
    echo 'LỖI ĐẶT HÀNG: ';
    echo htmlspecialchars($e->getMessage());
    echo '</pre>';
    exit;
}
}

$cart = getCart();
$cartTotal = getCartTotal();

require_once __DIR__ . '/includes/header.php';

?>
<?php if ($successOrderId): ?>
    <div class="alert alert-success">
        <h2>Đặt hàng thành công!</h2>
        <p>Mã đơn hàng của bạn: #<?= (int)$successOrderId ?></p>
        <p>Cảm ơn bạn đã mua hàng tại Fashion Store.</p>
        <a href="<?= BASE_URL ?>/index.php">Tiếp tục mua sắm</a>
    </div>
<?php endif; ?>

<section class="section">
    <div class="section-heading">
        <h2>Thông tin đặt hàng</h2>
    </div>

    <?php if (isset($_GET['success'])): ?>

        <div class="empty-state">
            <h3>Đặt hàng thành công!</h3>
            <p>Mã đơn hàng: #<?= (int)$_GET['success'] ?></p>
            <a href="index.php" class="button">
                Tiếp tục mua sắm
            </a>
        </div>

    <?php else: ?>

        <h3>Tóm tắt đơn hàng</h3>

        <?php foreach ($cart as $item): ?>
            <p>
                <?= e($item['name']) ?>
                (<?= e($item['size']) ?> - <?= e($item['color']) ?>)
                x <?= (int)$item['quantity'] ?>
            </p>
        <?php endforeach; ?>

        <p>
            <strong>Tổng tiền:</strong>
            <?= formatPrice($cartTotal) ?>
        </p>

        <hr>

        <form method="POST" action="checkout.php">

            <?= csrfField() ?>

            <div>
                <label>Họ tên người nhận</label>
                <input
                    type="text"
                    name="receiver_name"
                    required
                    maxlength="150">
            </div>

            <div>
                <label>Số điện thoại</label>
                <input
                    type="tel"
                    name="phone"
                    required
                    maxlength="11">
            </div>

            <div>
                <label>Địa chỉ giao hàng</label>
                <textarea
                    name="address"
                    required
                    rows="3"></textarea>
            </div>

            <div>
                <label>Phương thức thanh toán</label>

                <select name="payment_method" required>
                    <option value="cod">
                        Thanh toán khi nhận hàng (COD)
                    </option>

                    <option value="bank">
                        Chuyển khoản ngân hàng
                    </option>
                </select>
            </div>

            <button type="submit" class="button">
                Đặt hàng
            </button>

        </form>

    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
