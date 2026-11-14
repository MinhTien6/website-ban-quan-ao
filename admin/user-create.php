<?php
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
        $errors[] = 'Vui lòng nhập họ tên.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự.';
    }

    if (!in_array($role, ['user', 'admin'], true)) {
        $errors[] = 'Vai trò không hợp lệ.';
    }

    if (
        $phone !== '' &&
        !preg_match('/^(0|\+84|84)[0-9]{9}$/', $phone)
    ) {
        $errors[] = 'Số điện thoại không đúng định dạng Việt Nam (ví dụ: 0912345678).';
    }

    if (empty($errors)) {
        if ($userModel->findByEmail($email)) {
            $errors[] = 'Email này đã tồn tại.';
        } else {
            try {
                $userModel->create([
                    'full_name' => $fullName,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'phone' => $phone,
                    'address' => $address,
                    'role' => $role,
                    'status' => 1
                ]);

                setFlash('success', 'Thêm tài khoản thành công.');
                redirect('admin/users.php');

            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Không thể tạo tài khoản. Vui lòng kiểm tra lại dữ liệu.';
            }
        }
    }
}

// Chỉ nạp header sau khi xử lý POST
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-dashboard-wrapper">
    <h3>Thêm người dùng</h3>

    <?php foreach ($errors as $error): ?>
        <p style="color:red;">
            <?= e($error) ?>
        </p>
    <?php endforeach; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= e(csrfToken()) ?>">

        <p>
            <label>Họ và tên</label><br>
            <input type="text" name="full_name"
                   value="<?= e($fullName) ?>" required>
        </p>

        <p>
            <label>Email</label><br>
            <input type="email" name="email"
                   value="<?= e($email) ?>" required>
        </p>

        <p>
            <label>Mật khẩu</label><br>
            <input type="password" name="password"
                   minlength="8" required>
        </p>

        <p>
            <label>Số điện thoại</label><br>
            <input type="text" name="phone"
                   value="<?= e($phone) ?>">
        </p>

        <p>
            <label>Địa chỉ</label><br>
            <input type="text" name="address"
                   value="<?= e($address) ?>">
        </p>

        <p>
            <label>Vai trò</label><br>
            <select name="role">
                <option value="user"
                    <?= $role === 'user' ? 'selected' : '' ?>>
                    Khách hàng
                </option>

                <option value="admin"
                    <?= $role === 'admin' ? 'selected' : '' ?>>
                    Quản trị viên
                </option>
            </select>
        </p>

        <button type="submit">Thêm tài khoản</button>
        <a href="<?= BASE_URL ?>/admin/users.php">Quay lại</a>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
