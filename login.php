<?php
/**
 * Trang đăng nhập
 */

$pageTitle = 'Đăng nhập';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Nếu đã đăng nhập thì chuyển về trang chủ
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/index.php');
    } else {
        redirect('index.php');
    }
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ.';
        } else {
            try {
                // Tìm tài khoản theo email
                $stmt = $pdo->prepare(
                    "SELECT id, full_name, email, password_hash, role, status
                     FROM users
                     WHERE email = :email
                     LIMIT 1"
                );
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();

                // Kiểm tra mật khẩu (thông báo lỗi chung tránh lộ thông tin người dùng)
                if (!$user || !password_verify($password, $user['password_hash'])) {
                    $error = 'Email hoặc mật khẩu không đúng.';
                } elseif ((int)$user['status'] !== 1) {
                    // Kiểm tra trạng thái tài khoản
                    $error = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
                } else {
                    // Đăng nhập thành công, tái tạo ID phiên để chống tấn công Session Fixation
                    session_regenerate_id(true);

                    // Chuẩn hóa mảng phiên đăng nhập duy nhất
                    $_SESSION['user'] = [
                        'id'        => (int)$user['id'],
                        'full_name' => $user['full_name'],
                        'email'     => $user['email'],
                        'role'      => $user['role'],
                    ];

                    // Phân luồng chuyển hướng
                    if ($user['role'] === 'admin') {
                        redirect('admin/index.php');
                    } else {
                        redirect('index.php');
                    }
                }
            } catch (PDOException $e) {
                error_log("Lỗi đăng nhập: " . $e->getMessage());
                $error = 'Đã có lỗi xảy ra trong quá trình đăng nhập. Vui lòng thử lại sau.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Đăng nhập</h1>
        <p class="auth-subtitle">Đăng nhập vào tài khoản của bạn để trải nghiệm mua sắm.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/login.php" method="post" class="auth-form" novalidate>
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
                    placeholder="ví dụ: ban@example.com"
                >
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Nhập mật khẩu"
                >
            </div>

            <button type="submit" class="button button-block">Đăng nhập</button>
        </form>

        <div class="auth-footer">
            <p>Chưa có tài khoản? <a href="<?= BASE_URL ?>/register.php">Đăng ký ngay</a></p>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
