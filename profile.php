
<?php
/**
 * Trang hồ sơ cá nhân
 */

$pageTitle = 'Tài khoản của tôi';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';

// Chỉ người đã đăng nhập mới được truy cập
requireLogin();

$userModel = new User($pdo);
$userId = (int) currentUser()['id'];

$error = '';
$success = '';

// Xử lý cập nhật hồ sơ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($fullName === '') {
            $error = 'Vui lòng nhập họ và tên.';
        } elseif (mb_strlen($fullName) > 100) {
            $error = 'Họ và tên không được vượt quá 100 ký tự.';
        } elseif (strlen($phone) > 20) {
            $error = 'Số điện thoại không được vượt quá 20 ký tự.';
        } elseif (mb_strlen($address) > 255) {
            $error = 'Địa chỉ không được vượt quá 255 ký tự.';
        } else {
            try {
                $updated = $userModel->updateProfile($userId, [
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'address' => $address
                ]);

                if ($updated) {
                    // Cập nhật tên trong session
                    refreshCurrentUser();

                    setFlash('success', 'Cập nhật hồ sơ thành công.');
                    redirect('profile.php');
                } else {
                    $error = 'Không thể cập nhật hồ sơ.';
                }
            } catch (PDOException $e) {
                error_log('Profile update error: ' . $e->getMessage());
                $error = 'Đã xảy ra lỗi. Vui lòng thử lại sau.';
            }
        }
    }
}

// Lấy thông tin người dùng
$user = $userModel->findById($userId);

if (!$user) {
    setFlash('error', 'Không tìm thấy tài khoản.');
    redirect('logout.php');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Tài khoản của tôi</h1>
        <p class="auth-subtitle">
            Xem và cập nhật thông tin cá nhân.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post"
              action="<?= BASE_URL ?>/profile.php"
              class="auth-form">

            <?= csrfField() ?>

            <div class="form-group">
                <label for="full_name">Họ và tên</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="100"
                    required
                    value="<?= e($_POST['full_name'] ?? $user['full_name']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    value="<?= e($user['email']) ?>"
                    readonly
                >
            </div>

            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    maxlength="20"
                    value="<?= e($_POST['phone'] ?? $user['phone'] ?? '') ?>"
                >
            </div>

            <div class="form-group">
                <label for="address">Địa chỉ</label>
                <input
                    type="text"
                    id="address"
                    name="address"
                    maxlength="255"
                    value="<?= e($_POST['address'] ?? $user['address'] ?? '') ?>"
                >
            </div>

            <button type="submit" class="button button-block">
                Cập nhật hồ sơ
            </button>
        </form>

        <div class="auth-footer">
            <a href="<?= BASE_URL ?>/change-password.php">
                Đổi mật khẩu
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
