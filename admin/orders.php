<?php
/**
 * Danh sách đơn hàng dành cho Admin - TV6 tuần 2.
 */
$pageTitle = 'Quản lý đơn hàng';
require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/models/Order.php';

$orderModel = new Order($pdo);
$allowedStatuses = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
$status = trim((string)($_GET['status'] ?? 'all'));
if ($status !== 'all' && !in_array($status, $allowedStatuses, true)) $status = 'all';
$keyword = trim((string)($_GET['keyword'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$pagination = paginate($orderModel->count($status, $keyword), $page, 10);
$orders = $orderModel->getList($status, $pagination['limit'], $pagination['offset'], $keyword);

$statusLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
];
$paymentLabels = ['cod' => 'Thanh toán khi nhận hàng', 'bank' => 'Chuyển khoản'];
$queryParams = [];
if ($status !== 'all') $queryParams['status'] = $status;
if ($keyword !== '') $queryParams['keyword'] = $keyword;
$baseUrl = BASE_URL . '/admin/orders.php' . ($queryParams ? '?' . http_build_query($queryParams) : '');
?>

<div class="admin-orders-page">
    <div class="orders-page-heading">
        <div><h1>Quản lý đơn hàng</h1><p>Xem danh sách và thông tin chi tiết từng đơn hàng.</p></div>
        <span class="orders-total"><?= number_format($pagination['total_items']) ?> đơn hàng</span>
    </div>

    <form method="get" class="order-filter-form">
        <label for="keyword">Tìm kiếm</label>
        <input type="search" id="keyword" name="keyword" class="form-control"
               value="<?= e($keyword) ?>" placeholder="Mã đơn, tên khách, số điện thoại hoặc email">
        <label for="status">Trạng thái</label>
        <select id="status" name="status" class="form-control">
            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
            <?php foreach ($statusLabels as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn-action" type="submit">Tìm / lọc đơn</button>
        <?php if ($status !== 'all' || $keyword !== ''): ?><a class="btn-action" href="<?= BASE_URL ?>/admin/orders.php">Xóa bộ lọc</a><?php endif; ?>
    </form>

    <div class="table-responsive order-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th>Mặt hàng</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th></th></tr></thead>
            <tbody>
            <?php if (!$orders): ?>
                <tr><td colspan="8" class="table-empty">Không có đơn hàng phù hợp.</td></tr>
            <?php else: foreach ($orders as $order): ?>
                <tr>
                    <td><strong>#<?= (int)$order['id'] ?></strong></td>
                    <td><strong><?= e($order['receiver_name']) ?></strong><small class="order-customer-email"><?= e($order['user_email'] ?? '') ?></small></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                    <td><?= (int)$order['total_items'] ?></td>
                    <td><?= formatPrice($order['total']) ?></td>
                    <td><?= e($paymentLabels[$order['payment_method']] ?? $order['payment_method']) ?></td>
                    <td><span class="order-status status-<?= e($order['status']) ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                    <td><a class="btn-action" href="<?= BASE_URL ?>/admin/order_detail.php?id=<?= (int)$order['id'] ?>">Chi tiết</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= renderPagination($pagination, $baseUrl) ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
