<?php
/**
 * Chỉnh sửa người dùng (admin/user-edit.php - TV2)
 * Đã di chuyển require header xuống sau khối xử lý POST/redirect, chặn tự hạ quyền Admin và đồng bộ CSS Admin
 */
$pageTitle = 'Sửa người dùng';

// Nạp các file cần thiết trước khi xử lý POST
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/User.php';

// Kiểm tra quyền Admin
requireAdmin();

$userModel = new User($pdo);
$currentUser = currentUser();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    setFlash('error', 'ID người dùng không hợp lệ.');
    redirect('admin/users.php');
}

$user = $userModel->findById($id);

if (!$user) {
    setFlash('error', 'Không tìm thấy người dùng.');
    redirect('admin/users.php');
}

$errors = [];

$fullName = $user['full_name'];
$email = $user['email'];
$phone = $user['phone'] ?? '';
$address = $user['address'] ?? '';
$role = $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF
    if (!verifyCsrfToken()) {
        setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
        redirect('admin/users.php');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? '';

    if ($fullName === '') {
        $errors[] = 'Vui lòng nhập họ và tên.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if (!in_array($role, ['user', 'admin'], true)) {
        $errors[] = 'Vai trò không hợp lệ.';
    }

    // Ngăn Admin tự hạ quyền của chính mình (TV2-04)
    $currentUser = currentUser();
    if (
        $currentUser &&
        (int)$currentUser['id'] === (int)$id &&
        $user['role'] === 'admin' &&
        $role === 'user'
    ) {
        $errors[] = 'Bạn không thể tự hạ quyền Admin của chính mình.';
    }

    if (empty($errors)) {
        $existingUser = $userModel->findByEmail($email);

        if ($existingUser && (int)$existingUser['id'] !== (int)$id) {
            $errors[] = 'Địa chỉ email đã được tài khoản khác sử dụng.';
        } else {
            try {
                $userModel->updateUser($id, [
                    'full_name' => $fullName,
                    'email'     => $email,
                    'phone'     => $phone !== '' ? $phone : null,
                    'address'   => $address !== '' ? $address : null,
                    'role'      => $role,
                ]);

                // Nếu vừa sửa chính tài khoản đang đăng nhập, đồng bộ lại session
                if ($currentUser && (int)$currentUser['id'] === (int)$id) {
                    refreshCurrentUser();
                }

                setFlash('success', 'Cập nhật thông tin người dùng thành công.');
                redirect('admin/users.php');
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Không thể cập nhật tài khoản. Vui lòng kiểm tra lại dữ liệu.';
            }
        }
    }
}

// Chỉ nạp header sau khi đã xử lý POST và redirect
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <h3>Sửa thông tin người dùng #<?= (int)$id ?></h3>
        <p class="admin-subtitle">Cập nhật hồ sơ tài khoản và phân quyền quản trị (Nhiệm vụ TV2)</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/admin/users.php" class="button button-outline">
            ⬅️ Quay lại danh sách
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="flash-message flash-error">
        <strong>Vui lòng kiểm tra lại thông tin:</strong>
        <ul style="margin: 6px 0 0 18px;">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-card" style="max-width: 650px;">
    <form method="POST" action="<?= BASE_URL ?>/admin/user-edit.php?id=<?= (int)$id ?>">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="full_name">Họ và tên <span class="required" style="color:red;">*</span></label>
            <input type="text" id="full_name" name="full_name" class="form-control"
                   value="<?= e($fullName) ?>" required>
        </div>

        <div class="form-row-2">
            <div class="form-group">
                <label for="email">Địa chỉ Email <span class="required" style="color:red;">*</span></label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= e($email) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="text" id="phone" name="phone" class="form-control"
                       value="<?= e($phone) ?>" placeholder="Ví dụ: 0912345678">
            </div>
        </div>

        <div class="form-row-2">
            <div class="form-group">
                <label for="role">Vai trò người dùng</label>
                <select id="role" name="role" class="form-control">
                    <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>
                        Khách hàng (User)
                    </option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>
                        Quản trị viên (Admin)
                    </option>
                </select>
                <?php if ($currentUser && (int)$currentUser['id'] === (int)$id): ?>
                    <small style="color: #666; font-size: 12px; margin-top: 4px; display: block;">
                        ℹ️ Đây là tài khoản bạn đang đăng nhập (không thể tự hạ quyền).
                    </small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="address">Địa chỉ liên hệ</label>
                <input type="text" id="address" name="address" class="form-control"
                       value="<?= e($address) ?>" placeholder="Ví dụ: 123 Đường Cầu Giấy, Hà Nội">
            </div>
        </div>

        <div class="form-actions" style="margin-top: 20px;">
            <button type="submit" class="button">
                💾 Lưu thay đổi
            </button>
            <a href="<?= BASE_URL ?>/admin/users.php" class="button button-outline">
                Hủy bỏ
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
