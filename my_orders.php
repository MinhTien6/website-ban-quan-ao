<?php
/**
 * Lịch sử đơn hàng của khách hàng
 * TV5 - Xem danh sách, chi tiết, bộ lọc trạng thái, phân trang và hủy đơn hàng
 */

$pageTitle = 'Lịch sử đơn hàng';

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

// Ánh xạ nhãn trạng thái đơn hàng
$statusLabels = [
    'pending'   => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping'  => 'Đang giao hàng',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];

// Xử lý yêu cầu hủy đơn hàng (chỉ cho phép khi status = 'pending')
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

    $returnTo = ($_POST['return_to'] ?? 'list') === 'detail'
        ? 'detail'
        : 'list';

    $statusFromPost = isset($_POST['status']) ? trim((string)$_POST['status']) : null;
    $pageFromPost = isset($_POST['page']) ? filter_var($_POST['page'], FILTER_VALIDATE_INT) : null;
    $redirectQuery = [];

    if ($statusFromPost !== '' && $statusFromPost !== null && array_key_exists($statusFromPost, $statusLabels)) {
        $redirectQuery[] = 'status=' . urlencode($statusFromPost);
    }

    if ($pageFromPost !== false && $pageFromPost !== null && $pageFromPost > 1) {
        $redirectQuery[] = 'page=' . (int)$pageFromPost;
    }

    if ($action === 'cancel' && $orderId !== false && $orderId > 0) {
        try {
            $result = $orderModel->cancelByUser($orderId, $userId);

            if ($result) {
                setFlash('success', 'Đã hủy đơn hàng #' . $orderId . ' thành công. Tồn kho sản phẩm đã được hoàn lại.');
            } else {
                setFlash('error', 'Không thể hủy đơn hàng. Đơn hàng có thể đã được xác nhận hoặc giao đi.');
            }
        } catch (Throwable $e) {
            error_log('CANCEL ORDER ERROR: ' . $e->getMessage());
            setFlash('error', 'Không thể hủy đơn: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Thao tác không hợp lệ.');
    }

    if ($returnTo === 'detail' && $orderId !== false && $orderId > 0) {
        redirect('my_orders.php?id=' . $orderId);
    }

    if (!empty($redirectQuery)) {
        redirect('my_orders.php?' . implode('&', $redirectQuery));
    }

    redirect('my_orders.php');
}

// Xem chi tiết đơn hàng nếu có tham số id trên URL
$selectedOrder = null;
if (isset($_GET['id'])) {
    $orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($orderId !== false && $orderId !== null && $orderId > 0) {
        $selectedOrder = $orderModel->getDetails($orderId, $userId);

        if ($selectedOrder === false) {
            setFlash('error', 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này.');
            redirect('my_orders.php');
        }
    } else {
        setFlash('error', 'Mã đơn hàng không hợp lệ.');
        redirect('my_orders.php');
    }
}

// Bộ lọc trạng thái và phân trang (khi không xem chi tiết)
$statusFilter = null;
$totalFiltered = 0;
$totalAll = 0;
$orders = [];
$pagination = null;

if (!$selectedOrder) {
    $statusParam = filter_input(INPUT_GET, 'status', FILTER_DEFAULT);
    if ($statusParam !== null && array_key_exists($statusParam, $statusLabels)) {
        $statusFilter = $statusParam;
    }

    $totalAll = $orderModel->countByUserId($userId);
    $totalFiltered = $orderModel->countByUserId($userId, $statusFilter);

    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $page = max(1, $page);
    $perPage = 10;

    $pagination = paginate($totalFiltered, $page, $perPage);
    $orders = $orderModel->getByUserId($userId, $statusFilter, $pagination['limit'], $pagination['offset']);
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="orders-page">

    <div class="orders-heading">
        <h1>
            <?= $selectedOrder
                ? 'Chi tiết đơn hàng #' . (int)$selectedOrder['id']
                : 'Lịch sử đơn hàng của tôi' ?>
        </h1>
        <p>
            <?= $selectedOrder
                ? 'Xem chi tiết sản phẩm, trạng thái và tiến độ xử lý đơn hàng.'
                : 'Theo dõi, tra cứu trạng thái và quản lý các đơn hàng bạn đã đặt.' ?>
        </p>
    </div>

    <?php if ($selectedOrder): ?>

        <!-- ============================================================== -->
        <!-- GIAO DIỆN XEM CHI TIẾT ĐƠN HÀNG                               -->
        <!-- ============================================================== -->
        <a href="<?= BASE_URL ?>/my_orders.php" class="btn-back">
            &larr; Quay lại danh sách đơn hàng
        </a>

        <div class="orders-card">
            <div class="orders-card-header">
                <div>
                    <div class="order-code">
                        Đơn hàng #<?= (int)$selectedOrder['id'] ?>
                    </div>
                    <div class="order-date">
                        Ngày đặt: <?= e(date('d/m/Y H:i', strtotime($selectedOrder['created_at']))) ?>
                    </div>
                </div>

                <?php
                    $curStatus = $selectedOrder['status'];
                    $curStatusText = $statusLabels[$curStatus] ?? $curStatus;
                ?>
                <span class="order-status status-<?= e($curStatus) ?>">
                    <?= e($curStatusText) ?>
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
                        <strong>Phương thức thanh toán:</strong>
                        <?= $selectedOrder['payment_method'] === 'bank'
                            ? 'Chuyển khoản ngân hàng'
                            : 'Thanh toán khi nhận hàng (COD)' ?>
                    </p>
                </div>

                <h2>Sản phẩm trong đơn hàng</h2>
                <?php
                    $itemsSubtotal = 0;
                    foreach ($selectedOrder['items'] as $item):
                        $itemLineTotal = (float)$item['price'] * (int)$item['quantity'];
                        $itemsSubtotal += $itemLineTotal;
                ?>
                    <div class="order-product">
                        <?php if (!empty($item['image_path'])): ?>
                            <img src="<?= BASE_URL . '/public/' . ltrim(e($item['image_path']), '/') ?>"
                                 alt="<?= e($item['product_name'] ?? '') ?>"
                                 style="width: 55px; height: 65px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; margin-right: 12px;">
                        <?php endif; ?>

                        <div class="order-product-info">
                            <strong><?= e($item['product_name'] ?? 'Sản phẩm') ?></strong>
                            <p>
                                Size: <?= e($item['size'] ?? 'N/A') ?>
                                &nbsp;|&nbsp;
                                Màu: <?= e($item['color'] ?? 'N/A') ?>
                            </p>
                            <p>
                                Số lượng: <?= (int)$item['quantity'] ?> × <?= formatPrice($item['price']) ?>
                            </p>
                        </div>

                        <div class="order-product-price">
                            <?= formatPrice($itemLineTotal) ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php
                    $orderDiscount = max(0, $itemsSubtotal - (float)$selectedOrder['total']);
                ?>

                <!-- Hiển thị phân rã Tạm tính và Giảm giá -->
                <?php if ($orderDiscount > 0): ?>
                    <div class="order-detail-subtotal">
                        <span>Tạm tính</span>
                        <strong><?= formatPrice($itemsSubtotal) ?></strong>
                    </div>

                    <div class="order-detail-discount">
                        <span>
                            Giảm giá <?= !empty($selectedOrder['coupon_code']) ? '(' . e($selectedOrder['coupon_code']) . ')' : '' ?>
                        </span>
                        <strong>-<?= formatPrice($orderDiscount) ?></strong>
                    </div>
                <?php endif; ?>

                <div class="order-detail-total">
                    <span>Tổng thanh toán</span>
                    <strong><?= formatPrice($selectedOrder['total']) ?></strong>
                </div>

                <!-- Nút hủy đơn hàng khi trạng thái là pending -->
                <?php if ($selectedOrder['status'] === 'pending'): ?>
                    <form method="POST"
                          action="<?= BASE_URL ?>/my_orders.php"
                          style="margin-top: 24px;"
                          onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này? Số lượng sản phẩm sẽ được hoàn lại vào kho.');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="return_to" value="detail">
                        <input type="hidden" name="order_id" value="<?= (int)$selectedOrder['id'] ?>">

                        <button type="submit" class="order-btn btn-cancel">
                            ✕ Hủy đơn hàng này
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>

        <!-- ============================================================== -->
        <!-- GIAO DIỆN DANH SÁCH ĐƠN HÀNG KÈM BỘ LỌC VÀ PHÂN TRANG         -->
        <!-- ============================================================== -->

        <!-- Thanh tab lọc trạng thái -->
        <div class="order-filter-tabs">
            <a href="<?= BASE_URL ?>/my_orders.php"
               class="order-tab <?= $statusFilter === null ? 'active' : '' ?>">
                Tất cả <span class="tab-count"><?= (int)$totalAll ?></span>
            </a>
            <a href="<?= BASE_URL ?>/my_orders.php?status=pending"
               class="order-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">
                Chờ xác nhận
            </a>
            <a href="<?= BASE_URL ?>/my_orders.php?status=confirmed"
               class="order-tab <?= $statusFilter === 'confirmed' ? 'active' : '' ?>">
                Đã xác nhận
            </a>
            <a href="<?= BASE_URL ?>/my_orders.php?status=shipping"
               class="order-tab <?= $statusFilter === 'shipping' ? 'active' : '' ?>">
                Đang giao hàng
            </a>
            <a href="<?= BASE_URL ?>/my_orders.php?status=completed"
               class="order-tab <?= $statusFilter === 'completed' ? 'active' : '' ?>">
                Hoàn thành
            </a>
            <a href="<?= BASE_URL ?>/my_orders.php?status=cancelled"
               class="order-tab <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">
                Đã hủy
            </a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="orders-empty">
                <?php if ($statusFilter !== null): ?>
                    <h2>Không có đơn hàng nào ở trạng thái "<?= e($statusLabels[$statusFilter] ?? $statusFilter) ?>"</h2>
                    <p>Thử chọn tab trạng thái khác hoặc xem tất cả đơn hàng.</p>
                    <a href="<?= BASE_URL ?>/my_orders.php">Xem tất cả đơn hàng</a>
                <?php else: ?>
                    <h2>Bạn chưa có đơn hàng nào</h2>
                    <p>Hãy khám phá các sản phẩm thời trang mới nhất và đặt đơn hàng đầu tiên của bạn!</p>
                    <a href="<?= BASE_URL ?>/index.php">Tiếp tục mua sắm</a>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <?php foreach ($orders as $order): ?>
                <?php
                    $curStatus = $order['status'];
                    $curStatusText = $statusLabels[$curStatus] ?? $curStatus;
                ?>
                <div class="orders-card">
                    <div class="orders-card-header">
                        <div>
                            <div class="order-code">
                                Đơn hàng #<?= (int)$order['id'] ?>
                                <?php if (!empty($order['coupon_code'])): ?>
                                    <span style="font-size: 11px; background: #e0f2fe; color: #0369a1; padding: 2px 7px; border-radius: 4px; font-weight: 600; margin-left: 8px;">
                                        Mã: <?= e($order['coupon_code']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="order-date">
                                Ngày đặt: <?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?>
                            </div>
                        </div>

                        <span class="order-status status-<?= e($curStatus) ?>">
                            <?= e($curStatusText) ?>
                        </span>
                    </div>

                    <div class="orders-card-body">
                        <div class="order-info">
                            <p>
                                Số sản phẩm: <strong><?= (int)$order['total_items'] ?></strong>
                            </p>
                            <p>
                                Thanh toán:
                                <strong>
                                    <?= $order['payment_method'] === 'bank' ? 'Chuyển khoản' : 'COD' ?>
                                </strong>
                            </p>
                            <p>Tổng thanh toán:</p>
                            <div class="order-total">
                                <?= formatPrice($order['total']) ?>
                            </div>
                        </div>

                        <div class="order-actions">
                            <a href="<?= BASE_URL ?>/my_orders.php?id=<?= (int)$order['id'] ?>"
                               class="order-btn btn-detail">
                                Xem chi tiết
                            </a>

                            <?php if ($curStatus === 'pending'): ?>
                                <form method="POST"
                                      action="<?= BASE_URL ?>/my_orders.php"
                                      onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng #<?= (int)$order['id'] ?>? Tồn kho sẽ được hoàn lại.');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                    <?php if ($statusFilter !== null): ?>
                                        <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                                    <?php endif; ?>
                                    <?php if ($pagination): ?>
                                        <input type="hidden" name="page" value="<?= (int)$pagination['current_page'] ?>">
                                    <?php endif; ?>

                                    <button type="submit" class="order-btn btn-cancel">
                                        Hủy đơn
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Phân trang danh sách đơn hàng -->
            <?php if ($pagination && $pagination['total_pages'] > 1): ?>
                <div style="margin-top: 25px; display: flex; justify-content: center;">
                    <?php
                        $pageBaseUrl = BASE_URL . '/my_orders.php';
                        if ($statusFilter !== null) {
                            $pageBaseUrl .= '?status=' . urlencode($statusFilter);
                        }
                        echo renderPagination($pagination, $pageBaseUrl, 'page');
                    ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
