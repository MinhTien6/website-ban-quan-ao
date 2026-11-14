
<?php
/**
 * Trang yêu cầu đặt lại mật khẩu - chế độ demo localhost
 */

$pageTitle = 'Quên mật khẩu';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$email = '';
$demoLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Yêu cầu không hợp lệ. Vui lòng tải lại trang.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if ($email === '') {
            $error = 'Vui lòng nhập email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ.';
        } else {
            try {
                // Tìm tài khoản theo email
                $stmt = $pdo->prepare(
                    "SELECT email, status FROM users WHERE email = :email LIMIT 1"
                );
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Chỉ tạo mã cho tài khoản đang hoạt động
                if ($user && (int)$user['status'] === 1) {
                    // Xóa các mã cũ của email này
                    $deleteStmt = $pdo->prepare(
                        "DELETE FROM password_resets WHERE email = :email"
                    );
                    $deleteStmt->execute(['email' => $email]);

                    // Tạo token ngẫu nhiên và chỉ lưu bản băm
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);

                    $expiresAt = date('Y-m-d H:i:s', time() + 15 * 60);

                    $insertStmt = $pdo->prepare(
                        "INSERT INTO password_resets (email, token, expires_at)
                         VALUES (:email, :token, :expires_at)"
                    );
                    $insertStmt->execute([
                        'email' => $email,
                        'token' => $tokenHash,
                        'expires_at' => $expiresAt,
                    ]);

                    // Demo localhost: hiển thị liên kết thay vì gửi email
                    $demoLink = BASE_URL . '/reset-password.php?token=' . urlencode($token);
                }

                // Thông báo chung, không tiết lộ email có tồn tại hay không
                $success = 'Nếu email thuộc tài khoản đang hoạt động, liên kết đặt lại mật khẩu sẽ được tạo.';

            } catch (PDOException $e) {
                error_log('Forgot password error: ' . $e->getMessage());
                $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            } catch (Throwable $e) {
                error_log('Forgot password error: ' . $e->getMessage());
                $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Quên mật khẩu</h1>
        <p class="auth-subtitle">
            Nhập email tài khoản để tạo liên kết đặt lại mật khẩu.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($demoLink !== ''): ?>
            <div class="alert alert-info">
                <strong>Liên kết demo (chỉ dùng trên localhost):</strong>
                <p>
                    <a href="<?= e($demoLink) ?>">Đặt lại mật khẩu tại đây</a>
                </p>
                <small>Liên kết hết hạn sau 15 phút và chỉ sử dụng được một lần.</small>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/forgot-password.php" method="post" class="auth-form">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="email">Địa chỉ Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    autocomplete="email"
                    value="<?= e($email) ?>"
                    placeholder="Nhập email tài khoản"
                >
            </div>

            <button type="submit" class="button button-block">
                Tạo liên kết đặt lại mật khẩu
            </button>
        </form>

        <div class="auth-footer">
            <a href="<?= BASE_URL ?>/login.php">Quay lại đăng nhập</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
