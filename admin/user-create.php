<?php
/**
 * Thêm người dùng mới (admin/user-create.php - TV2)
 * Đã di chuyển require header xuống sau khối xử lý POST/redirect và đồng bộ CSS Admin
 */
$pageTitle = 'Thêm người dùng';

// Nạp các file cần thiết trước khi xử lý POST
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/User.php';

// Kiểm tra quyền Admin
requireAdmin();

$userModel = new User($pdo);
$errors = [];

$fullName = '';
$email = '';
$phone = '';
$address = '';
$role = 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF
    if (!verifyCsrfToken()) {
        setFlash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
        redirect('admin/user-create.php');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($fullName === '') {
        $errors[] = 'Vui lòng nhập họ và tên.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if (mb_strlen($password) < 8) {
        $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự.';
    }

    if (!in_array($role, ['user', 'admin'], true)) {
        $errors[] = 'Vai trò không hợp lệ.';
    }

    if (empty($errors)) {
        if ($userModel->findByEmail($email)) {
            $errors[] = 'Địa chỉ email này đã tồn tại trong hệ thống.';
        } else {
            try {
                $userModel->create([
                    'full_name'     => $fullName,
                    'email'         => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'phone'         => $phone !== '' ? $phone : null,
                    'address'       => $address !== '' ? $address : null,
                    'role'          => $role,
                    'status'        => 1,
                ]);

                setFlash('success', 'Thêm mới tài khoản người dùng thành công.');
                redirect('admin/users.php');
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Không thể tạo tài khoản. Vui lòng kiểm tra lại dữ liệu.';
            }
        }
    }
}

// Chỉ nạp header sau khi đã xử lý POST và redirect
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <h3>Thêm người dùng mới</h3>
        <p class="admin-subtitle">Tạo tài khoản người dùng hoặc phân quyền quản trị viên (Nhiệm vụ TV2)</p>
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
    <form method="POST" action="<?= BASE_URL ?>/admin/user-create.php">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="full_name">Họ và tên <span class="required" style="color:red;">*</span></label>
            <input type="text" id="full_name" name="full_name" class="form-control"
                   value="<?= e($fullName) ?>" required placeholder="Ví dụ: Nguyễn Văn A">
        </div>

        <div class="form-row-2">
            <div class="form-group">
                <label for="email">Địa chỉ Email <span class="required" style="color:red;">*</span></label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= e($email) ?>" required placeholder="email@example.com">
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu khởi tạo <span class="required" style="color:red;">*</span></label>
                <input type="password" id="password" name="password" class="form-control"
                       minlength="8" required placeholder="Tối thiểu 8 ký tự">
            </div>
        </div>

        <div class="form-row-2">
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="text" id="phone" name="phone" class="form-control"
                       value="<?= e($phone) ?>" placeholder="Ví dụ: 0912345678">
            </div>

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
            </div>
        </div>

        <div class="form-group">
            <label for="address">Địa chỉ liên hệ</label>
            <input type="text" id="address" name="address" class="form-control"
                   value="<?= e($address) ?>" placeholder="Ví dụ: 123 Đường Cầu Giấy, Hà Nội">
        </div>

        <div class="form-actions" style="margin-top: 20px;">
            <button type="submit" class="button">
                ➕ Thêm tài khoản
            </button>
            <a href="<?= BASE_URL ?>/admin/users.php" class="button button-outline">
                Hủy bỏ
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
