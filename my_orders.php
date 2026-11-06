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

<style>
.orders-page {
    max-width: 1100px;
    margin: 35px auto 60px;
    padding: 0 20px;
}

.orders-heading {
    margin-bottom: 25px;
}

.orders-heading h1 {
    margin: 0 0 8px;
    font-size: 27px;
}

.orders-heading p {
    color: #777;
    margin: 0;
}

.orders-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 12px;
    margin-bottom: 18px;
    overflow: hidden;
}

.orders-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 17px 20px;
    border-bottom: 1px solid #eee;
}

.order-code {
    font-weight: 700;
}

.order-date {
    color: #777;
    font-size: 13px;
    margin-top: 5px;
}

.order-status {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    background: #f2f2f2;
    color: #555;
}

.status-pending {
    background: #fff4d6;
    color: #946200;
}

.status-confirmed {
    background: #e7f0ff;
    color: #245da8;
}

.status-shipping {
    background: #e8f3ff;
    color: #176b9b;
}

.status-completed {
    background: #e4f6eb;
    color: #237443;
}

.status-cancelled {
    background: #fde8e8;
    color: #b02a37;
}

.orders-card-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    padding: 20px;
}

.order-info p {
    margin: 6px 0;
    color: #666;
    font-size: 14px;
}

.order-total {
    color: #a52a3a;
    font-size: 19px;
    font-weight: 700;
}

.order-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}

.order-btn {
    display: inline-block;
    padding: 9px 14px;
    border-radius: 7px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
}

.btn-detail {
    background: #252525;
    color: #fff;
}

.btn-cancel {
    background: #fff;
    color: #c03945;
    border: 1px solid #e2b4b8;
}

.btn-back {
    display: inline-block;
    margin-bottom: 20px;
    color: #555;
    text-decoration: none;
}

.order-detail {
    padding: 22px;
}

.order-detail h2 {
    font-size: 19px;
    margin: 0 0 15px;
}

.order-customer {
    background: #fafafa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 22px;
}

.order-customer p {
    margin: 7px 0;
    color: #555;
    font-size: 14px;
}

.order-product {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.order-product-info {
    min-width: 0;
}

.order-product-info strong {
    display: block;
    margin-bottom: 6px;
}

.order-product-info p {
    color: #777;
    font-size: 13px;
    margin: 4px 0;
}

.order-product-price {
    white-space: nowrap;
    font-weight: 600;
}

.order-detail-total {
    display: flex;
    justify-content: space-between;
    padding-top: 20px;
    font-size: 17px;
    font-weight: 700;
}

.order-detail-total strong {
    color: #a52a3a;
}

.orders-empty {
    text-align: center;
    padding: 55px 20px;
    background: #fff;
    border: 1px solid #eee;
    border-radius: 12px;
}

.orders-empty h2 {
    font-size: 20px;
}

.orders-empty p {
    color: #777;
    margin-bottom: 22px;
}

.orders-empty a {
    display: inline-block;
    padding: 11px 20px;
    background: #a52a3a;
    color: #fff;
    text-decoration: none;
    border-radius: 7px;
}

@media (max-width: 600px) {
    .orders-card-body {
        align-items: flex-start;
    }

    .order-actions {
        width: 100%;
    }
}
</style>

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
                        <input type="hidden" name="return_to" value="list">

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
