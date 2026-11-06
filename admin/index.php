<?php
/**
 * Dashboard quản trị - nhiệm vụ TV6 tuần 2.
 * Hiển thị thống kê đơn hàng và danh sách giao dịch gần đây.
 */
$pageTitle = 'Bảng điều khiển';
require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/models/Order.php';

$orderModel = new Order($pdo);
$stats = $orderModel->getDashboardStats();
$recentOrders = $orderModel->getRecentOrders(5);

$statusLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
];
?>

<div class="admin-dashboard-wrapper">
    <div class="admin-welcome-box">
        <h3>Chào mừng quay trở lại, <?= e($adminUser['full_name']) ?>!</h3>
        <p>Đây là tổng quan hoạt động đơn hàng của FashionStore.</p>
    </div>

    <div class="admin-stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-orders">📦</div>
            <div class="stat-info"><span class="stat-label">Tổng đơn hàng</span><span class="stat-value"><?= (int)$stats['total_orders'] ?></span><span class="stat-desc">Tất cả trạng thái</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-categories">⏳</div>
            <div class="stat-info"><span class="stat-label">Đang chờ</span><span class="stat-value"><?= (int)$stats['pending_orders'] ?></span><span class="stat-desc">Chờ xác nhận</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-users">✅</div>
            <div class="stat-info"><span class="stat-label">Đã hoàn thành</span><span class="stat-value"><?= (int)$stats['completed_orders'] ?></span><span class="stat-desc">Đơn đã giao thành công</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-products">💰</div>
            <div class="stat-info"><span class="stat-label">Doanh thu hoàn thành</span><span class="stat-value"><?= formatPrice($stats['revenue']) ?></span><span class="stat-desc">Chỉ tính đơn hoàn thành</span></div>
        </div>
    </div>

    <section class="admin-info-card recent-orders-card">
        <div class="table-card-header recent-orders-heading">
            <div><h4>Đơn hàng mới nhất</h4><p>Theo dõi nhanh 5 đơn gần đây.</p></div>
            <a class="btn-action" href="<?= BASE_URL ?>/admin/orders.php">Xem tất cả đơn hàng →</a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th>Số mặt hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                <?php if (!$recentOrders): ?>
                    <tr><td colspan="7" class="table-empty">Chưa có đơn hàng nào.</td></tr>
                <?php else: foreach ($recentOrders as $order): ?>
                    <tr>
                        <td>#<?= (int)$order['id'] ?></td>
                        <td><strong><?= e($order['receiver_name']) ?></strong><small class="order-customer-email"><?= e($order['user_email'] ?? '') ?></small></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                        <td><?= (int)$order['total_items'] ?></td>
                        <td><?= formatPrice($order['total']) ?></td>
                        <td><span class="order-status status-<?= e($order['status']) ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                        <td><a class="btn-action" href="<?= BASE_URL ?>/admin/order_detail.php?id=<?= (int)$order['id'] ?>">Chi tiết</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
