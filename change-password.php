
<?php
/**
 * Trang đổi mật khẩu
 */

$pageTitle = 'Đổi mật khẩu';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';

// Bắt buộc đăng nhập
requireLogin();

$userModel = new User($pdo);
$userId = (int) currentUser()['id'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $error = 'Vui lòng nhập đầy đủ thông tin.';
        } elseif (mb_strlen($newPassword) < 8) {
            $error = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Mật khẩu xác nhận không khớp.';
        } elseif ($currentPassword === $newPassword) {
            $error = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
        } else {
            try {
                // Lấy mật khẩu hiện tại trong database
                $stmt = $pdo->prepare(
                    "SELECT password_hash FROM users WHERE id = :id LIMIT 1"
                );
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                    $error = 'Mật khẩu hiện tại không chính xác.';
                } else {
                    // Mã hóa mật khẩu mới
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                    $userModel->changePassword($userId, $newPasswordHash);

                    setFlash('success', 'Đổi mật khẩu thành công.');
                    redirect('profile.php');
                }
            } catch (PDOException $e) {
                error_log('Change password error: ' . $e->getMessage());
                $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Đổi mật khẩu</h1>
        <p class="auth-subtitle">
            Nhập mật khẩu hiện tại và mật khẩu mới của bạn.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post"
              action="<?= BASE_URL ?>/change-password.php"
              class="auth-form">

            <?= csrfField() ?>

            <div class="form-group">
                <label for="current_password">Mật khẩu hiện tại</label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="form-group">
                <label for="new_password">Mật khẩu mới</label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="button button-block">
                Xác nhận đổi mật khẩu
            </button>
        </form>

        <div class="auth-footer">
            <a href="<?= BASE_URL ?>/profile.php">Quay lại hồ sơ</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
