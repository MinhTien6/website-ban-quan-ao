
<?php
/**
 * Trang đặt lại mật khẩu bằng token
 */

$pageTitle = 'Đặt lại mật khẩu';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/User.php';

$error = '';
$success = '';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$userModel = new User($pdo);

// Kiểm tra token
if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'Liên kết đặt lại mật khẩu không hợp lệ.';
} else {
    try {
        $tokenHash = hash('sha256', $token);

        $stmt = $pdo->prepare(
            "SELECT id, email
             FROM password_resets
             WHERE token = :token
               AND expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute(['token' => $tokenHash]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            $error = 'Liên kết đã hết hạn hoặc không hợp lệ. Vui lòng yêu cầu liên kết mới.';
        }
    } catch (PDOException $e) {
        error_log('Reset password lookup error: ' . $e->getMessage());
        $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
    }
}

// Xử lý đặt mật khẩu mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    if (!verifyCsrfToken()) {
        $error = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword === '' || $confirmPassword === '') {
            $error = 'Vui lòng nhập đầy đủ mật khẩu.';
        } elseif (mb_strlen($newPassword) < 8) {
            $error = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Mật khẩu xác nhận không khớp.';
        } else {
            try {
                // Kiểm tra lại token trước khi cập nhật
                $pdo->beginTransaction();

                $checkStmt = $pdo->prepare(
                    "SELECT id, email
                     FROM password_resets
                     WHERE token = :token
                       AND expires_at > NOW()
                     LIMIT 1
                     FOR UPDATE"
                );
                $checkStmt->execute([
                    'token' => hash('sha256', $token)
                ]);
                $validReset = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$validReset) {
                    $pdo->rollBack();
                    $error = 'Liên kết đã hết hạn hoặc đã được sử dụng.';
                } else {
                    $userStmt = $pdo->prepare(
                        "SELECT id, status
                         FROM users
                         WHERE email = :email
                         LIMIT 1"
                    );
                    $userStmt->execute(['email' => $validReset['email']]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user || (int)$user['status'] !== 1) {
                        $pdo->rollBack();
                        $error = 'Tài khoản không tồn tại hoặc đã bị khóa.';
                    } else {
                        $passwordHash = password_hash(
                            $newPassword,
                            PASSWORD_DEFAULT
                        );

                        $updateStmt = $pdo->prepare(
                            "UPDATE users
                             SET password_hash = :password_hash
                             WHERE id = :id"
                        );
                        $updateStmt->execute([
                            'password_hash' => $passwordHash,
                            'id' => (int)$user['id']
                        ]);

                        // Xóa token sau khi sử dụng thành công
                        $deleteStmt = $pdo->prepare(
                            "DELETE FROM password_resets WHERE email = :email"
                        );
                        $deleteStmt->execute([
                            'email' => $validReset['email']
                        ]);

                        $pdo->commit();

                        setFlash(
                            'success',
                            'Đặt lại mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.'
                        );
                        redirect('login.php');
                    }
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Reset password error: ' . $e->getMessage());
                $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Đặt lại mật khẩu</h1>
        <p class="auth-subtitle">
            Nhập mật khẩu mới cho tài khoản của bạn.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (isset($reset) && $reset && $error === ''): ?>
            <form action="<?= BASE_URL ?>/reset-password.php" method="post" class="auth-form">
                <?= csrfField() ?>

                <input type="hidden" name="token" value="<?= e($token) ?>">

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
                    Xác nhận đặt lại mật khẩu
                </button>
            </form>
        <?php endif; ?>

        <div class="auth-footer">
            <a href="<?= BASE_URL ?>/forgot-password.php">Yêu cầu liên kết mới</a>
            <p><a href="<?= BASE_URL ?>/login.php">Quay lại đăng nhập</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
