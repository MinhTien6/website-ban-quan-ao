<?php
/**
 * Quản lý danh sách sản phẩm (Admin Products - TV3)
 */

$pageTitle = 'Quản lý sản phẩm';
require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/models/Product.php';
require_once dirname(__DIR__) . '/models/Category.php';

$productModel = new Product($pdo);
$categoryModel = new Category($pdo);

// Xử lý hành động Xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCsrfToken()) {
        setFlash('error', 'Mã xác thực CSRF không hợp lệ hoặc đã hết hạn.');
        redirect('admin/products.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $productModel->delete($id);
                setFlash('success', 'Đã xóa sản phẩm thành công!');
            } catch (Exception $e) {
                setFlash('error', 'Lỗi khi xóa sản phẩm: ' . $e->getMessage());
            }
        }
        redirect('admin/products.php');
    }
}

// Lọc và tìm kiếm
$keyword = trim($_GET['keyword'] ?? '');
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;

// Đếm tổng số sản phẩm và tính phân trang
$totalProducts = $productModel->countAdminList($keyword);
$pagination = paginate($totalProducts, $currentPage, $limit);

// Lấy danh sách sản phẩm theo trang
$products = $productModel->getAdminList($keyword, $limit, $pagination['offset']);

// Lấy danh sách sản phẩm sắp hết hàng để hiển thị cảnh báo (Nhiệm vụ Tuần 3 TV3)
$lowStockAlerts = $productModel->getLowStockProducts(10, 5);
?>

<div class="admin-page-header">
    <div>
        <h3>Quản lý sản phẩm</h3>
        <p class="admin-subtitle">Danh sách sản phẩm, quản lý kho hàng, biến thể size/màu và hình ảnh (Nhiệm vụ TV3)</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/admin/product-add.php" class="button">
            ➕ Thêm sản phẩm mới
        </a>
    </div>
</div>

<?php if (!empty($lowStockAlerts)): ?>
    <div class="admin-alert-banner">
        <div class="alert-banner-icon">⚠️</div>
        <div class="alert-banner-content">
            <strong>Cảnh báo tồn kho thấp (Tuần 3):</strong>
            <span>Có <strong><?= count($lowStockAlerts) ?></strong> sản phẩm có tổng tồn kho dưới hoặc bằng 10 chiếc. Vui lòng nhập thêm hàng!</span>
        </div>
    </div>
<?php endif; ?>

<!-- Khung tìm kiếm và bộ lọc -->
<div class="admin-card filter-card">
    <form action="<?= BASE_URL ?>/admin/products.php" method="GET" class="filter-form">
        <div class="filter-input-wrap">
            <input type="text" name="keyword" class="form-control" 
                   value="<?= e($keyword) ?>" placeholder="Tìm theo tên sản phẩm hoặc tên danh mục...">
        </div>
        <button type="submit" class="button">🔍 Tìm kiếm</button>
        <?php if ($keyword !== ''): ?>
            <a href="<?= BASE_URL ?>/admin/products.php" class="button button-outline">Làm mới</a>
        <?php endif; ?>
    </form>
</div>

<!-- Bảng danh sách sản phẩm -->
<div class="admin-card">
    <div class="table-card-header">
        <h4>Tất cả sản phẩm (<?= $totalProducts ?>)</h4>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 70px;">Hình ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá bán</th>
                    <th style="text-align: center;">Tồn kho</th>
                    <th style="text-align: center;">Trạng thái</th>
                    <th style="width: 140px; text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="table-empty">
                            <?= $keyword !== '' ? 'Không tìm thấy sản phẩm nào khớp với từ khóa tìm kiếm.' : 'Chưa có sản phẩm nào trong hệ thống.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><strong>#<?= $p['id'] ?></strong></td>
                            <td>
                                <div class="product-thumb-wrap">
                                    <?php if (!empty($p['main_image'])): ?>
                                        <img src="<?= BASE_URL ?>/public/<?= e(ltrim($p['main_image'], '/')) ?>" 
                                             alt="<?= e($p['name']) ?>" class="product-thumb">
                                    <?php else: ?>
                                        <div class="thumb-placeholder">👕</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="product-info-cell">
                                    <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $p['id'] ?>" class="product-name-link">
                                        <?= e($p['name']) ?>
                                    </a>
                                    <small class="product-slug">slug: <?= e($p['slug']) ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="badge-category">
                                    📁 <?= e($p['category_name'] ?? 'Chưa phân loại') ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                                    <div class="price-cell">
                                        <span class="price-sale-tag"><?= formatPrice($p['sale_price']) ?></span>
                                        <del class="price-old-tag"><?= formatPrice($p['price']) ?></del>
                                    </div>
                                <?php else: ?>
                                    <span class="price-normal-tag"><?= formatPrice($p['price']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php 
                                $stock = (int)($p['total_stock'] ?? 0);
                                if ($stock <= 0): ?>
                                    <span class="badge-stock stock-out">Hết hàng (0)</span>
                                <?php elseif ($stock <= 10): ?>
                                    <span class="badge-stock stock-low">Sắp hết (<?= $stock ?>)</span>
                                <?php else: ?>
                                    <span class="badge-stock stock-in"><?= $stock ?> cái</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($p['status'] == 1): ?>
                                    <span class="badge-status badge-success">Hiển thị</span>
                                <?php else: ?>
                                    <span class="badge-status badge-inactive">Đang ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-buttons">
                                    <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $p['id'] ?>" 
                                       class="btn-action btn-edit" title="Chỉnh sửa sản phẩm">✏️ Sửa</a>
                                    
                                    <form action="<?= BASE_URL ?>/admin/products.php" method="POST" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm \'<?= e(addslashes($p['name'])) ?>\'? Thao tác này sẽ xóa toàn bộ ảnh và biến thể.');" 
                                          style="display: inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn-action btn-delete" title="Xóa">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <?php
    $baseUrl = BASE_URL . '/admin/products.php';
    if ($keyword !== '') {
        $baseUrl .= '?keyword=' . urlencode($keyword);
    }
    echo renderPagination($pagination, $baseUrl);
    ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
