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
        <p>Đây là giao diện quản trị nền tảng (Tuần 1 & 2 do TV1 thiết lập). Các module quản lý chuyên sâu sẽ do các thành viên TV2, TV3, TV5, TV6 tiếp tục phát triển ở các tuần tiếp theo theo kế hoạch.</p>
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

    <div class="admin-info-card">
        <h4>📋 Thông tin phân công kỹ thuật các tuần tiếp theo:</h4>
        <ul class="admin-task-list">
            <li><strong>TV2 (Tuần 3):</strong> Module Quản lý người dùng & phân quyền (`admin/users/`), Hồ sơ cá nhân.</li>
            <li><strong>TV3 (Tuần 3):</strong> Module CRUD danh mục (`admin/categories/`) & CRUD sản phẩm (`admin/products/`) có upload nhiều ảnh.</li>
            <li><strong>TV4 (Tuần 4):</strong> Trang danh sách sản phẩm, lọc, tìm kiếm, phân trang và trang chi tiết sản phẩm.</li>
            <li><strong>TV5 (Tuần 4, 5):</strong> Module Giỏ hàng, thanh toán đặt hàng và CRUD mã giảm giá.</li>
            <li><strong>TV6 (Tuần 4, 5):</strong> Module Quản lý đơn hàng, đổi trạng thái đơn, CRUD banner, duyệt đánh giá và biểu đồ Chart.js cho Dashboard.</li>
        </ul>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
