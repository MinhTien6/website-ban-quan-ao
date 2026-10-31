
<?php
$pageTitle = 'Quản lý người dùng';

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/models/User.php';

$userModel = new User($pdo);

// Tìm kiếm
$keyword = trim($_GET['keyword'] ?? '');

// Phân trang
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalUsers = $userModel->count($keyword);
$totalPages = max(1, (int)ceil($totalUsers / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// Lấy danh sách
// Xử lý khóa / mở khóa / xóa tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $currentUser = currentUser();

    if ($userId <= 0) {
        $_SESSION['flash_error'] = 'ID tài khoản không hợp lệ.';
    } elseif ($currentUser && (int)$currentUser['id'] === $userId) {
        $_SESSION['flash_error'] = 'Không thể thao tác trên tài khoản đang đăng nhập.';
    } elseif ($action === 'delete') {
        try {
            if ($userModel->delete($userId)) {
                $_SESSION['flash_success'] = 'Xóa tài khoản thành công.';
            } else {
                $_SESSION['flash_error'] = 'Không thể xóa tài khoản.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['flash_error'] = 'Không thể xóa tài khoản vì có dữ liệu liên quan.';
        }
    } elseif ($action === 'status') {
        $newStatus = (int)($_POST['status'] ?? -1);

        if (in_array($newStatus, [0, 1], true)) {
            $userModel->setStatus($userId, $newStatus);
            $_SESSION['flash_success'] = 'Cập nhật trạng thái thành công.';
        }
    }

    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

// Lấy danh sách
$users = $userModel->getList($keyword, $limit, $offset);
?>

<div class="admin-dashboard-wrapper">
    <h3>Danh sách người dùng</h3>

    <p>Tổng số tài khoản: <strong><?= $totalUsers ?></strong></p>

    <form method="GET" action="">
        <input
            type="text"
            name="keyword"
            placeholder="Tìm theo tên hoặc email..."
            value="<?= e($keyword) ?>"
        >
        <button type="submit">Tìm kiếm</button>
        <a href="<?= BASE_URL ?>/admin/users.php">Làm mới</a>
    </form>

    <p>
        <a href="<?= BASE_URL ?>/admin/user-create.php">
            + Thêm người dùng
        </a>
    </p>

    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Thao tác</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="8">Không tìm thấy người dùng.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= (int)$user['id'] ?></td>
                        <td><?= e($user['full_name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($user['phone'] ?? '') ?></td>
                        <td>
                            <?= $user['role'] === 'admin'
                                ? 'Quản trị viên'
                                : 'Khách hàng' ?>
                        </td>
                        <td>
                            <?= (int)$user['status'] === 1
                                ? 'Hoạt động'
                                : 'Đã khóa' ?>
                        </td>
                        <td><?= e($user['created_at']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/admin/user-edit.php?id=<?= (int)$user['id'] ?>">
                                Sửa
                            </a>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="status" value="<?= (int)$user['status'] === 1 ? 0 : 1 ?>">
                                <button type="submit">
                                    <?= (int)$user['status'] === 1 ? 'Khóa' : 'Mở khóa' ?>
                                </button>
                            </form>

                            <form method="POST" style="display:inline;"
                                onsubmit="return confirm('Bạn có chắc muốn xóa tài khoản này?');">
                                <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <?php if ($page > 1): ?>
            <a href="?keyword=<?= urlencode($keyword) ?>&page=<?= $page - 1 ?>">
                Trang trước
            </a>
        <?php endif; ?>

        Trang <?= $page ?> / <?= $totalPages ?>

        <?php if ($page < $totalPages): ?>
            <a href="?keyword=<?= urlencode($keyword) ?>&page=<?= $page + 1 ?>">
                Trang sau
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
