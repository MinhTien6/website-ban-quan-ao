<?php
/**
 * Trang xem chi tiết một đơn hàng - TV6 tuần 2.
 * Kiểm tra quyền và lấy dữ liệu trước khi bắt đầu xuất HTML để trả đúng mã 404.
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();
require_once dirname(__DIR__) . '/models/Order.php';

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$order = $orderId > 0 ? (new Order($pdo))->getDetails($orderId) : false;
if (!$order) http_response_code(404);

$pageTitle = 'Chi tiết đơn hàng';
require_once __DIR__ . '/includes/header.php';

$statusLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
];
$paymentLabels = ['cod' => 'Thanh toán khi nhận hàng', 'bank' => 'Chuyển khoản'];
?>

<div class="order-detail-page">
    <a class="btn-action order-back-link" href="<?= BASE_URL ?>/admin/orders.php">← Quay lại danh sách</a>
    <?php if (!$order): ?>
        <section class="admin-info-card"><h1>Không tìm thấy đơn hàng</h1><p>Đơn hàng có thể đã bị xóa hoặc mã đơn không hợp lệ.</p></section>
    <?php else: ?>
        <div class="orders-page-heading"><div><p class="order-detail-kicker">THÔNG TIN GIAO DỊCH</p><h1>Đơn hàng #<?= (int)$order['id'] ?></h1></div><span class="order-status status-<?= e($order['status']) ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></div>
        <div class="order-detail-grid">
            <section class="admin-info-card"><h2>Thông tin khách hàng</h2><dl class="order-info-list">
                <div><dt>Người nhận</dt><dd><?= e($order['receiver_name']) ?></dd></div>
                <div><dt>Tài khoản</dt><dd><?= e($order['customer_name'] ?? '') ?> · <?= e($order['customer_email'] ?? '') ?></dd></div>
                <div><dt>Số điện thoại</dt><dd><?= e($order['phone']) ?></dd></div>
                <div><dt>Địa chỉ giao hàng</dt><dd><?= e($order['address']) ?></dd></div>
            </dl></section>
            <section class="admin-info-card"><h2>Thông tin đơn hàng</h2><dl class="order-info-list">
                <div><dt>Ngày tạo</dt><dd><?= e(date('d/m/Y H:i:s', strtotime($order['created_at']))) ?></dd></div>
                <div><dt>Thanh toán</dt><dd><?= e($paymentLabels[$order['payment_method']] ?? $order['payment_method']) ?></dd></div>
                <div><dt>Mã giảm giá</dt><dd><?= !empty($order['coupon_id']) ? '#' . (int)$order['coupon_id'] : 'Không sử dụng' ?></dd></div>
                <div><dt>Trạng thái</dt><dd><?= e($statusLabels[$order['status']] ?? $order['status']) ?></dd></div>
            </dl></section>
        </div>

        <section class="admin-info-card order-items-card"><h2>Sản phẩm trong đơn</h2>
            <div class="table-responsive"><table class="admin-table"><thead><tr><th>Sản phẩm</th><th>Biến thể</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead><tbody>
            <?php if (empty($order['items'])): ?><tr><td colspan="5" class="table-empty">Đơn hàng chưa có sản phẩm.</td></tr>
            <?php else: foreach ($order['items'] as $item): ?>
                <tr>
                    <td><div class="order-product-cell">
                        <?php if (!empty($item['image_path'])): ?><img src="<?= BASE_URL ?>/public/<?= e(ltrim($item['image_path'], '/')) ?>" alt="<?= e($item['product_name'] ?? 'Sản phẩm') ?>">
                        <?php else: ?><span class="order-product-placeholder">👕</span><?php endif; ?>
                        <span><?= e($item['product_name'] ?? 'Sản phẩm đã ẩn') ?></span>
                    </div></td>
                    <td><?= e(trim(($item['size'] ?? '') . ' / ' . ($item['color'] ?? ''), ' /')) ?: 'Mặc định' ?></td>
                    <td><?= formatPrice($item['price']) ?></td>
                    <td><?= (int)$item['quantity'] ?></td>
                    <td><?= formatPrice((float)$item['price'] * (int)$item['quantity']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody></table></div>
            <div class="order-grand-total"><span>Tổng thanh toán</span><strong><?= formatPrice($order['total']) ?></strong></div>
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
