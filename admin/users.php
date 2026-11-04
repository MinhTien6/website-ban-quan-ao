
<?php
$pageTitle = 'Quản lý người dùng';

// Nạp các file cần thiết trước khi xử lý POST
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/User.php';

// Kiểm tra quyền Admin
requireAdmin();

$userModel = new User($pdo);

// Xử lý khóa / mở khóa / xóa tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Kiểm tra CSRF
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
        setFlash('error', 'Không thể thao tác trên tài khoản đang đăng nhập.');

    } elseif ($action === 'delete') {
        try {
            if ($userModel->delete($userId)) {
                setFlash('success', 'Xóa tài khoản thành công.');
            } else {
                setFlash('error', 'Không thể xóa tài khoản.');
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            setFlash('error', 'Không thể xóa tài khoản vì có dữ liệu liên quan.');
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

// Tìm kiếm
$keyword = trim($_GET['keyword'] ?? '');

// Phân trang bằng hàm dùng chung
$limit = 10;
$totalUsers = $userModel->count($keyword);
$page = max(1, (int)($_GET['page'] ?? 1));

$pagination = paginate($totalUsers, $page, $limit);

$page = $pagination['current_page'];
$offset = $pagination['offset'];
$totalPages = $pagination['total_pages'];

// Lấy danh sách người dùng
$users = $userModel->getList($keyword, $limit, $offset);

// Tạo URL phân trang và giữ lại từ khóa tìm kiếm
$paginationUrl = BASE_URL . '/admin/users.php';

if ($keyword !== '') {
    $paginationUrl .= '?keyword=' . urlencode($keyword);
}

// Chỉ nạp header sau khi xử lý POST và dữ liệu
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-dashboard-wrapper">
    <h3>Danh sách người dùng</h3>

    <p>
        Tổng số tài khoản:
        <strong><?= (int)$totalUsers ?></strong>
    </p>

    <form method="GET" action="">
        <input
            type="text"
            name="keyword"
            placeholder="Tìm theo tên hoặc email..."
            value="<?= e($keyword) ?>"
        >

        <button type="submit">Tìm kiếm</button>

        <a href="<?= BASE_URL ?>/admin/users.php">
            Làm mới
        </a>
    </form>

    <p>
        <a href="<?= BASE_URL ?>/admin/user-create.php">
            + Thêm người dùng
        </a>
    </p>

    <table>
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

                            <form method="POST" action="" style="display:inline-block;">
                                <?= csrfField() ?>

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?= (int)$user['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="status"
                                >

                                <input
                                    type="hidden"
                                    name="status"
                                    value="<?= (int)$user['status'] === 1 ? 0 : 1 ?>"
                                >

                                <button type="submit">
                                    <?= (int)$user['status'] === 1
                                        ? 'Khóa'
                                        : 'Mở khóa' ?>
                                </button>
                            </form>

                            <form
                                method="POST"
                                action=""
                                style="display:inline-block;"
                                onsubmit="return confirm('Bạn có chắc muốn xóa tài khoản này?');"
                            >
                                <?= csrfField() ?>

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?= (int)$user['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete"
                                >

                                <button type="submit">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <?= renderPagination($pagination, $paginationUrl) ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
