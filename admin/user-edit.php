
<?php
$pageTitle = 'Sửa người dùng';

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/models/User.php';

$userModel = new User($pdo);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$user = $userModel->findById($id);

if (!$user) {
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$errors = [];

$fullName = $user['full_name'];
$email = $user['email'];
$phone = $user['phone'] ?? '';
$address = $user['address'] ?? '';
$role = $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? '';

    if ($fullName === '') {
        $errors[] = 'Vui lòng nhập họ tên.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if (!in_array($role, ['user', 'admin'], true)) {
        $errors[] = 'Vai trò không hợp lệ.';
    }

    if (empty($errors)) {
        $existingUser = $userModel->findByEmail($email);

        if ($existingUser && (int)$existingUser['id'] !== $id) {
            $errors[] = 'Email đã được tài khoản khác sử dụng.';
        } else {
            try {
                $userModel->updateUser($id, [
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'role' => $role
                ]);

                header('Location: ' . BASE_URL . '/admin/users.php');
                exit;
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Không thể cập nhật tài khoản.';
            }
        }
    }
}
?>

<div class="admin-dashboard-wrapper">
    <h3>Sửa thông tin người dùng</h3>

    <?php foreach ($errors as $error): ?>
        <p style="color:red;"><?= e($error) ?></p>
    <?php endforeach; ?>

    <form method="POST">
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
                <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>
                    Khách hàng
                </option>
                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>
                    Quản trị viên
                </option>
            </select>
        </p>

        <button type="submit">Lưu thay đổi</button>
        <a href="<?= BASE_URL ?>/admin/users.php">Quay lại</a>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
