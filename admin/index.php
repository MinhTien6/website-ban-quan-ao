<?php
/**
 * Trang tổng quan quản trị (Admin Dashboard)
 */

$pageTitle = 'Bảng điều khiển';
require_once __DIR__ . '/includes/header.php';

// Truy vấn nhanh số liệu thống kê cơ bản để kiểm tra thông suốt CSDL
try {
    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $totalProducts = 0;
    $totalCategories = 0;
    $totalUsers = 0;
    $totalOrders = 0;
}
?>

<div class="admin-dashboard-wrapper">
    <div class="admin-welcome-box">
        <h3>Chào mừng quay trở lại, <?= e($adminUser['full_name']) ?>!</h3>
        <p>Hệ thống quản lý website bán quần áo FashionStore. Theo dõi nhanh các chỉ số hoạt động kinh doanh và quản trị bán hàng.</p>
    </div>

    <!-- Khối thẻ thống kê nhanh -->
    <div class="admin-stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-products">👕</div>
            <div class="stat-info">
                <span class="stat-label">Sản phẩm</span>
                <span class="stat-value"><?= $totalProducts ?></span>
                <span class="stat-desc">Đang quản lý</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-categories">📁</div>
            <div class="stat-info">
                <span class="stat-label">Danh mục</span>
                <span class="stat-value"><?= $totalCategories ?></span>
                <span class="stat-desc">Phân loại hàng</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-users">👥</div>
            <div class="stat-info">
                <span class="stat-label">Người dùng</span>
                <span class="stat-value"><?= $totalUsers ?></span>
                <span class="stat-desc">Tài khoản hệ thống</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-orders">📦</div>
            <div class="stat-info">
                <span class="stat-label">Đơn hàng</span>
                <span class="stat-value"><?= $totalOrders ?></span>
                <span class="stat-desc">Đơn mẫu ban đầu</span>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
