
<?php
session_start();

require_once __DIR__ . "/config/database.php";

$error = "";
$success = "";
$fullname = "";
$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST["fullname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (
        $fullname === "" ||
        $username === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,50}$/', $username)) {
        $error = "Tên đăng nhập phải từ 4-50 ký tự, chỉ gồm chữ, số và dấu gạch dưới.";
    } elseif (strlen($password) < 8) {
        $error = "Mật khẩu phải có ít nhất 8 ký tự.";
    } elseif ($password !== $confirmPassword) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        try {
            $check = $conn->prepare(
                "SELECT id FROM users WHERE username = ? OR email = ?"
            );
            $check->execute([$username, $email]);

            if ($check->fetch()) {
                $error = "Tên đăng nhập hoặc email đã được sử dụng.";
            } else {
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (fullname, username, email, password_hash)
                    VALUES (?, ?, ?, ?)"
                );

                $stmt->execute([
                    $fullname,
                    $username,
                    $email,
                    $passwordHash
                ]);

                $success = "Đăng ký thành công! Bạn có thể đăng nhập.";
                $fullname = "";
                $username = "";
                $email = "";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Không thể đăng ký lúc này. Hãy kiểm tra database hoặc thông tin đã nhập.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - FashionStore</title>
    <link rel="stylesheet" href="public/css/c.css">

    <style>
        .auth-box {
            max-width: 450px;
            margin: 40px auto;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .auth-box h1 {
            margin-bottom: 20px;
            text-align: center;
        }

        .auth-box label {
            display: block;
            margin-top: 12px;
        }

        .auth-box input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .auth-box button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            border: none;
            border-radius: 4px;
            background: #2563eb;
            color: white;
            cursor: pointer;
        }

        .message-error {
            color: #dc2626;
            margin-bottom: 10px;
        }

        .message-success {
            color: #15803d;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<header>
    <h2>FashionStore</h2>
    <nav>
        <a href="index.php">Trang chủ</a>
        <a href="login.php">Đăng nhập</a>
    </nav>
</header>

<main>
    <div class="auth-box">
        <h1>Đăng ký tài khoản</h1>

        <?php if ($error !== ""): ?>
            <p class="message-error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <p class="message-success">
                <?= htmlspecialchars($success) ?>
            </p>
            <p>
                <a href="login.php">Đi đến trang đăng nhập</a>
            </p>
        <?php endif; ?>

        <form method="post" action="register.php">
            <label for="fullname">Họ và tên</label>
            <input type="text" id="fullname" name="fullname"
                maxlength="100" required
                value="<?= htmlspecialchars($fullname) ?>">

            <label for="username">Tên đăng nhập</label>
            <input type="text" id="username" name="username"
                minlength="4" maxlength="50"
                pattern="[a-zA-Z0-9_]+" required
                value="<?= htmlspecialchars($username) ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                maxlength="150" required
                value="<?= htmlspecialchars($email) ?>">

            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password"
                minlength="8" required>

            <label for="confirm_password">Nhập lại mật khẩu</label>
            <input type="password" id="confirm_password"
                name="confirm_password" minlength="8" required>

            <button type="submit">Đăng ký</button>
        </form>

        <p style="margin-top: 15px; text-align: center;">
            Đã có tài khoản?
            <a href="login.php">Đăng nhập</a>
        </p>
    </div>
</main>

<footer>
    <p>&copy; 2026 FashionStore</p>
</footer>

</body>
</html>
