<?php
/**
 * Quản lý danh sách người dùng (admin/users.php - TV2)
 * Đã xóa dòng trống đầu file và đồng bộ CSS Admin chuẩn hệ thống
 */
$pageTitle = 'Quản lý người dùng';

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/User.php';

requireAdmin();

$userModel = new User($pdo);
$currentUser = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
        redirect('admin/users.php');
    }

    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $currentUser = currentUser();

    if ($userId <= 0) {
        setFlash('error', 'ID tài khoản không hợp lệ.');
    } elseif ($currentUser && (int)$currentUser['id'] === $userId) {
        setFlash('error', 'Không thể thao tác khóa hoặc xóa trên tài khoản đang đăng nhập.');
    } elseif ($action === 'delete') {
        try {
            if ($userModel->delete($userId)) {
                setFlash('success', 'Xóa tài khoản thành công.');
            } else {
                setFlash('error', 'Không thể xóa tài khoản.');
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            setFlash('error', 'Không thể xóa tài khoản vì đã có đơn hàng liên quan.');
        }
    } elseif ($action === 'status') {
        $newStatus = (int)($_POST['status'] ?? -1);
        if (in_array($newStatus, [0, 1], true)) {
            if ($userModel->setStatus($userId, $newStatus)) {
                setFlash('success', 'Cập nhật trạng thái tài khoản thành công.');
            } else {
                setFlash('error', 'Không thể cập nhật trạng thái tài khoản.');
            }
        } else {
            setFlash('error', 'Trạng thái tài khoản không hợp lệ.');
        }
    } else {
        setFlash('error', 'Thao tác không hợp lệ.');
    }

    redirect('admin/users.php');
}

$keyword = trim($_GET['keyword'] ?? '');
$limit = 10;
$totalUsers = $userModel->count($keyword);
$page = max(1, (int)($_GET['page'] ?? 1));
$pagination = paginate($totalUsers, $page, $limit);
$users = $userModel->getList($keyword, $limit, $pagination['offset']);

$paginationUrl = BASE_URL . '/admin/users.php';
if ($keyword !== '') {
    $paginationUrl .= '?keyword=' . urlencode($keyword);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <h3>Quản lý người dùng</h3>
        <p class="admin-subtitle">Quản lý tài khoản khách hàng, phân quyền quản trị và khóa/mở khóa (Nhiệm vụ TV2)</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/admin/user-create.php" class="button">
            ➕ Thêm người dùng mới
        </a>
    </div>
</div>

<div class="admin-card filter-card">
    <form method="GET" action="<?= BASE_URL ?>/admin/users.php" class="filter-form">
        <div class="filter-input-wrap">
            <input type="text" name="keyword" class="form-control"
                   placeholder="Tìm theo họ tên hoặc địa chỉ email..."
                   value="<?= e($keyword) ?>">
        </div>
        <button type="submit" class="button">🔍 Tìm kiếm</button>
        <?php if ($keyword !== ''): ?>
            <a href="<?= BASE_URL ?>/admin/users.php" class="button button-outline">Làm mới</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <div class="table-card-header">
        <h4>Tất cả tài khoản (<?= (int)$totalUsers ?>)</h4>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th style="text-align: center;">Vai trò</th>
                    <th style="text-align: center;">Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th style="width: 210px; text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="table-empty">Không tìm thấy người dùng nào phù hợp.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong>#<?= (int)$user['id'] ?></strong></td>
                            <td><strong><?= e($user['full_name']) ?></strong></td>
                            <td><?= e($user['email']) ?></td>
                            <td><?= e($user['phone'] ?? '—') ?></td>
                            <td style="text-align: center;">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge-count">Quản trị viên</span>
                                <?php else: ?>
                                    <span class="badge-category">Khách hàng</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ((int)$user['status'] === 1): ?>
                                    <span class="badge-status badge-success">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge-status badge-inactive">Đã khóa</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($user['created_at']) ?></td>
                            <td style="text-align: center;">
                                <div class="action-buttons">
                                    <a href="<?= BASE_URL ?>/admin/user-edit.php?id=<?= (int)$user['id'] ?>"
                                       class="btn-action btn-edit" title="Sửa">✏️ Sửa</a>

                                    <form method="POST" action="" style="display:inline-block;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="status" value="<?= (int)$user['status'] === 1 ? 0 : 1 ?>">
                                        <button type="submit" class="btn-action">
                                            <?= (int)$user['status'] === 1 ? '🔒 Khóa' : '🔓 Mở' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="" style="display:inline-block;"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa tài khoản này?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
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

    <?= renderPagination($pagination, $paginationUrl) ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
