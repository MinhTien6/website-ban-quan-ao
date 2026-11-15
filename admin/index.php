<?php
/**
 * Dashboard quản trị - tổng quan trạng thái và doanh thu đơn hàng (TV6).
 */
$pageTitle = 'Bảng điều khiển';
require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/models/Order.php';

$orderModel = new Order($pdo);
$stats = $orderModel->getDashboardStats();
$revenueChart = $orderModel->getRevenueChartData(7);
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
            <div class="stat-icon icon-users">✔️</div>
            <div class="stat-info"><span class="stat-label">Đã xác nhận</span><span class="stat-value"><?= (int)$stats['confirmed_orders'] ?></span><span class="stat-desc">Đang chuẩn bị hàng</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-products">🚚</div>
            <div class="stat-info"><span class="stat-label">Đang giao</span><span class="stat-value"><?= (int)$stats['shipping_orders'] ?></span><span class="stat-desc">Đơn đã bàn giao vận chuyển</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-users">✅</div>
            <div class="stat-info"><span class="stat-label">Đã hoàn thành</span><span class="stat-value"><?= (int)$stats['completed_orders'] ?></span><span class="stat-desc">Đơn đã giao thành công</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-categories">✖️</div>
            <div class="stat-info"><span class="stat-label">Đã hủy</span><span class="stat-value"><?= (int)$stats['cancelled_orders'] ?></span><span class="stat-desc">Đơn không tiếp tục xử lý</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-products">💰</div>
            <div class="stat-info"><span class="stat-label">Doanh thu hoàn thành</span><span class="stat-value"><?= formatPrice($stats['revenue']) ?></span><span class="stat-desc">Chỉ tính đơn hoàn thành</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-users">👥</div>
            <div class="stat-info"><span class="stat-label">Khách hàng</span><span class="stat-value"><?= (int)$stats['total_customers'] ?></span><span class="stat-desc">Tài khoản thành viên</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-categories">⚠️</div>
            <div class="stat-info"><span class="stat-label">Sắp hết hàng</span><span class="stat-value"><?= (int)$stats['low_stock_products'] ?></span><span class="stat-desc"><a href="<?= BASE_URL ?>/admin/products.php">Sản phẩm còn tối đa 10 chiếc</a></span></div>
        </div>
    </div>

    <section class="admin-info-card revenue-chart-card">
        <div class="revenue-chart-heading">
            <div>
                <h4>Biểu đồ doanh thu</h4>
                <p>Doanh thu từ đơn hoàn thành trong 7 ngày gần nhất.</p>
            </div>
            <span class="revenue-chart-note">Đơn vị: VNĐ</span>
        </div>
        <div class="revenue-chart-container">
            <canvas id="revenue-chart" role="img" aria-label="Biểu đồ doanh thu 7 ngày gần nhất"></canvas>
        </div>
    </section>

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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script>
    const revenueCanvas = document.getElementById('revenue-chart');
    if (revenueCanvas && typeof Chart !== 'undefined') {
        const revenueLabels = <?= json_encode($revenueChart['labels'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const revenueValues = <?= json_encode($revenueChart['values'], JSON_NUMERIC_CHECK) ?>;
        const currencyFormatter = new Intl.NumberFormat('vi-VN');

        new Chart(revenueCanvas, {
            type: 'line',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Doanh thu',
                    data: revenueValues,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#2563eb',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return 'Doanh thu: ' + currencyFormatter.format(context.parsed.y) + 'đ';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return currencyFormatter.format(value) + 'đ';
                            }
                        }
                    }
                }
            }
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
