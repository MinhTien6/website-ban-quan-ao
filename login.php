
<?php
session_start();

require_once __DIR__ . "/config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // Tài khoản thử nghiệm
    $demoUser = "user";
    $demoPassword = "123456";

    if ($username === $demoUser && $password === $demoPassword) {
        // Tìm tài khoản thử nghiệm trong database
        $stmt = $conn->prepare(
            "SELECT id, fullname, username, email, role, status
             FROM users
             WHERE username = ?
             LIMIT 1"
        );
        $stmt->execute([$demoUser]);
        $user = $stmt->fetch();

        if ($user && $user["status"] === "active") {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["fullname"] = $user["fullname"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["logged_in"] = true;

            header("Location: index.php");
            exit;
        }

        $error = "Tài khoản thử nghiệm chưa tồn tại hoặc đã bị khóa.";
    } else {
        // Đăng nhập bằng tài khoản đã đăng ký
        $stmt = $conn->prepare(
            "SELECT id, fullname, username, email,
                    password_hash, role, status
             FROM users
             WHERE username = ?
             LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify(
            $password,
            $user["password_hash"]
        )) {
            $error = "Tên đăng nhập hoặc mật khẩu không đúng!";
        } elseif ($user["status"] !== "active") {
            $error = "Tài khoản của bạn đã bị khóa.";
        } else {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["fullname"] = $user["fullname"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["logged_in"] = true;

            if ($user["role"] === "admin") {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }

            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - FashionStore</title>
    <link rel="stylesheet" href="public/css/c.css">
</head>
<body>

<header>
    <h2>FashionStore</h2>
    <nav>
        <a href="index.php">Trang chủ</a>
        <a href="index.php#products">Sản phẩm</a>
        <a href="register.php">Đăng ký</a>
    </nav>
</header>

<main class="login-container">
    <h2>Đăng nhập</h2>
    <p>Đăng nhập vào tài khoản FashionStore của bạn.</p>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <div class="form-group">
            <label for="username">Tên đăng nhập</label>
            <input
                type="text"
                id="username"
                name="username"
                autocomplete="username"
                required
                value="<?= htmlspecialchars($_POST["username"] ?? "") ?>"
            >
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >
        </div>

        <button type="submit">Đăng nhập</button>
    </form>

    <p style="margin-top: 15px;">
    Chưa có tài khoản?
    <a href="register.php">Đăng ký ngay</a>
    </p>

    <div style="margin-top: 20px; padding: 12px; background: #f2f2f2; border-radius: 5px;">
        <h3>Tài khoản thử nghiệm</h3>
        <p>Tên đăng nhập: <strong>user</strong></p>
        <p>Mật khẩu: <strong>123456</strong></p>
        <p style="font-size: 13px;">
            coi có đăng kí được không rồi dùng tk này phòng hờ.
        </p>
    </div>

</main>

<footer>
    <p>&copy; 2026 FashionStore</p>
</footer>

</body>
</html>
