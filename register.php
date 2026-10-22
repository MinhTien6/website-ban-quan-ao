<?php
/**
 * Trang đăng ký tài khoản
 */

$pageTitle = 'Đăng ký tài khoản';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Nếu đã đăng nhập thì chuyển về trang chủ
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin vào các trường bắt buộc.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Định dạng email không hợp lệ.';
    } elseif (mb_strlen($password) < 8) {
        $error = 'Mật khẩu phải có ít nhất 8 ký tự.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        try {
            // Kiểm tra trước email đã tồn tại hay chưa
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $checkStmt->execute(['email' => $email]);

            if ($checkStmt->fetch()) {
                $error = 'Địa chỉ email này đã được sử dụng. Vui lòng chọn email khác hoặc đăng nhập.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $insertStmt = $pdo->prepare(
                    "INSERT INTO users (full_name, email, password_hash)
                     VALUES (:full_name, :email, :password_hash)"
                );

                $insertStmt->execute([
                    'full_name'     => $fullName,
                    'email'         => $email,
                    'password_hash' => $passwordHash,
                ]);

                // Đăng ký thành công, tạo thông báo flash và chuyển sang trang đăng nhập
                setFlash('success', 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay bây giờ.');
                redirect('login.php');
            }
        } catch (PDOException $e) {
            // Bắt trường hợp race condition (mã lỗi trùng lặp dữ liệu unique 23000)
            if ($e->getCode() === '23000') {
                $error = 'Địa chỉ email này đã được đăng ký. Vui lòng thử đăng nhập.';
            } else {
                error_log("Lỗi đăng ký tài khoản: " . $e->getMessage());
                $error = 'Không thể đăng ký lúc này do sự cố hệ thống. Vui lòng thử lại sau.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Đăng ký tài khoản</h1>
        <p class="auth-subtitle">Tạo tài khoản mới để nhận nhiều ưu đãi mua sắm.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/register.php" method="post" class="auth-form" novalidate>
            <div class="form-group">
                <label for="full_name">Họ và tên</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    required
                    maxlength="100"
                    value="<?= e($fullName) ?>"
                    placeholder="ví dụ: Nguyễn Văn A"
                >
            </div>

            <div class="form-group">
                <label for="email">Địa chỉ Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    maxlength="150"
                    autocomplete="email"
                    value="<?= e($email) ?>"
                    placeholder="ví dụ: nguyenvana@example.com"
                >
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Tối thiểu 8 ký tự"
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Nhập lại mật khẩu"
                >
            </div>

            <button type="submit" class="button button-block">Đăng ký</button>
        </form>

        <div class="auth-footer">
            <p>Đã có tài khoản? <a href="<?= BASE_URL ?>/login.php">Đăng nhập</a></p>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
