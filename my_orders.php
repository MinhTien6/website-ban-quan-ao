<?php
/**
 * Lịch sử đơn hàng của khách hàng
 * TV5 - Xem danh sách, chi tiết và hủy đơn hàng
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Order.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bắt buộc đăng nhập
requireLogin();

// Lấy thông tin người dùng từ Session
$user = currentUser();
$userId = (int)$user['id'];

// Khởi tạo Order Model
$orderModel = new Order($pdo);

/// Xử lý yêu cầu hủy đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Yêu cầu không hợp lệ. Vui lòng thử lại.');
        redirect('my_orders.php');
    }

    $action = $_POST['action'] ?? '';
    $orderId = filter_var(
        $_POST['order_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $returnTo = $_POST['return_to'] ?? 'list';

    if ($action === 'cancel' && $orderId !== false && $orderId > 0) {
        try {
            $result = $orderModel->cancelByUser($orderId, $userId);

            if ($result) {
                setFlash('success', 'Đã hủy đơn hàng thành công.');
            } else {
                setFlash(
                    'error',
                    'Không thể hủy đơn hàng. Đơn hàng có thể đã được xử lý.'
                );
            }

        } catch (Throwable $e) {
            setFlash('error', $e->getMessage());
        }

    } else {
        setFlash('error', 'Thao tác không hợp lệ.');
    }

    // Giữ nguyên vị trí sau khi hủy
    if ($returnTo === 'detail' && $orderId !== false && $orderId > 0) {
        redirect('my_orders.php?id=' . $orderId);
    }

    redirect('my_orders.php');
}

// Lấy danh sách đơn hàng của người dùng
$orders = $orderModel->getByUserId($userId);

// Xem chi tiết đơn hàng nếu có ID trên URL
$selectedOrder = null;

if (isset($_GET['id'])) {
    $orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($orderId !== false && $orderId !== null && $orderId > 0) {
        $selectedOrder = $orderModel->getDetails($orderId, $userId);

        if ($selectedOrder === false) {
            setFlash('error', 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem.');
            redirect('my_orders.php');
        }
    } else {
    setFlash('error', 'Mã đơn hàng không hợp lệ.');
    redirect('my_orders.php');
}
}

// Ánh xạ trạng thái đơn hàng
$statusLabels = [
    'pending'   => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping'  => 'Đang giao hàng',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];

$pageTitle = 'Lịch sử đơn hàng';

require_once __DIR__ . '/includes/header.php';
?>

<section class="orders-page">

    <div class="orders-heading">
        <h1>
            <?= $selectedOrder
                ? 'Chi tiết đơn hàng'
                : 'Lịch sử đơn hàng' ?>
        </h1>

        <p>
            Theo dõi và quản lý các đơn hàng bạn đã đặt.
        </p>
    </div>

    <?php if ($selectedOrder): ?>

        <a href="<?= BASE_URL ?>/my_orders.php" class="btn-back">
            &larr; Quay lại lịch sử đơn hàng
        </a>

        <div class="orders-card">

            <div class="orders-card-header">
                <div>
                    <div class="order-code">
                        Đơn hàng #<?= (int)$selectedOrder['id'] ?>
                    </div>

                    <div class="order-date">
                        Ngày đặt:
                        <?= e(date(
                            'd/m/Y H:i',
                            strtotime($selectedOrder['created_at'])
                        )) ?>
                    </div>
                </div>

                <?php
                    $status = $selectedOrder['status'];
                    $statusText = $statusLabels[$status] ?? $status;
                ?>

                <span class="order-status status-<?= e($status) ?>">
                    <?= e($statusText) ?>
                </span>
            </div>

            <div class="order-detail">

                <h2>Thông tin nhận hàng</h2>

                <div class="order-customer">
                    <p>
                        <strong>Người nhận:</strong>
                        <?= e($selectedOrder['receiver_name']) ?>
                    </p>

                    <p>
                        <strong>Số điện thoại:</strong>
                        <?= e($selectedOrder['phone']) ?>
                    </p>

                    <p>
                        <strong>Địa chỉ:</strong>
                        <?= e($selectedOrder['address']) ?>
                    </p>

                    <p>
                        <strong>Thanh toán:</strong>
                        <?= $selectedOrder['payment_method'] === 'cod'
                            ? 'Thanh toán khi nhận hàng'
                            : 'Chuyển khoản' ?>
                    </p>
                </div>

                <h2>Sản phẩm trong đơn hàng</h2>

                <?php foreach ($selectedOrder['items'] as $item): ?>
                    <div class="order-product">

                        <div class="order-product-info">
                            <strong>
                                <?= e($item['product_name'] ?? 'Sản phẩm không còn tồn tại') ?>
                            </strong>

                            <p>
                                Size: <?= e($item['size'] ?? 'N/A') ?>
                                &nbsp;|&nbsp;
                                Màu: <?= e($item['color'] ?? 'N/A') ?>
                            </p>

                            <p>
                                Số lượng: <?= (int)$item['quantity'] ?>
                            </p>
                        </div>

                        <div class="order-product-price">
                            <?= formatPrice(
                                (float)$item['price'] * (int)$item['quantity']
                            ) ?>
                        </div>

                    </div>

                <?php endforeach; ?>

                <div class="order-detail-total">
                    <span>Tổng thanh toán</span>

                    <strong>
                        <?= formatPrice($selectedOrder['total']) ?>
                    </strong>
                </div>

                <?php if ($selectedOrder['status'] === 'pending'): ?>

                    <form method="POST"
                          action="<?= BASE_URL ?>/my_orders.php"
                          style="margin-top: 20px;"
                          onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?');">

                        <?= csrfField() ?>

                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="return_to" value="detail">

                        <input type="hidden"
                               name="order_id"
                               value="<?= (int)$selectedOrder['id'] ?>">

                        <button type="submit" class="order-btn btn-cancel">
                            Hủy đơn hàng
                        </button>
                    </form>

                <?php endif; ?>

            </div>
        </div>

    <?php elseif (empty($orders)): ?>

        <div class="orders-empty">
            <h2>Bạn chưa có đơn hàng nào</h2>

            <p>
                Hãy khám phá sản phẩm và đặt đơn hàng đầu tiên của bạn.
            </p>

            <a href="<?= BASE_URL ?>/index.php">
                Tiếp tục mua sắm
            </a>
        </div>

    <?php else: ?>

        <?php foreach ($orders as $order): ?>

            <?php
                $status = $order['status'];
                $statusText = $statusLabels[$status] ?? $status;
            ?>

            <div class="orders-card">

                <div class="orders-card-header">

                    <div>
                        <div class="order-code">
                            Đơn hàng #<?= (int)$order['id'] ?>
                        </div>

                        <div class="order-date">
                            <?= e(date(
                                'd/m/Y H:i',
                                strtotime($order['created_at'])
                            )) ?>
                        </div>
                    </div>

                    <span class="order-status status-<?= e($status) ?>">
                        <?= e($statusText) ?>
                    </span>

                </div>

                <div class="orders-card-body">

                    <div class="order-info">

                        <p>
                            Số sản phẩm:
                            <strong><?= (int)$order['total_items'] ?></strong>
                        </p>

                        <p>Tổng thanh toán:</p>

                        <div class="order-total">
                            <?= formatPrice($order['total']) ?>
                        </div>

                    </div>

                    <div class="order-actions">

                        <a
                            href="<?= BASE_URL ?>/my_orders.php?id=<?= (int)$order['id'] ?>"
                            class="order-btn btn-detail">
                            Xem chi tiết
                        </a>

                        <?php if ($status === 'pending'): ?>

                            <form method="POST"
                                  action="<?= BASE_URL ?>/my_orders.php"
                                  onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?');">

                                <?= csrfField() ?>

                                <input type="hidden"
                                       name="action"
                                       value="cancel">

                                <input type="hidden"
                                       name="order_id"
                                       value="<?= (int)$order['id'] ?>">

                                <button type="submit"
                                        class="order-btn btn-cancel">
                                    Hủy đơn hàng
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
