
<?php
/**
 * Quản lý phiên xác thực người dùng và phân quyền
 */

// Khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra người dùng đã đăng nhập hay chưa
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user'])
        && is_array($_SESSION['user'])
        && !empty($_SESSION['user']['id']);
}

/**
 * Lấy thông tin người dùng hiện tại
 */
function currentUser(): ?array
{
    return isLoggedIn() ? $_SESSION['user'] : null;
}

/**
 * Cập nhật thông tin người dùng trong session từ database
 */
function refreshCurrentUser(): void
{
    if (!isLoggedIn()) {
        return;
    }

    global $pdo;

    if (!isset($pdo)) {
        return;
    }

    require_once __DIR__ . '/../models/User.php';

    $userModel = new User($pdo);
    $user = $userModel->findById((int) $_SESSION['user']['id']);

    // Xóa session nếu tài khoản không tồn tại hoặc đã bị khóa
    if (!$user || (int) $user['status'] !== 1) {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        return;
    }

    // Cập nhật thông tin mới nhất vào session
    $_SESSION['user'] = [
        'id'        => (int) $user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role']
    ];
}

/**
 * Kiểm tra người dùng có phải Admin hay không
 */
function isAdmin(): bool
{
    $user = currentUser();

    return $user !== null
        && ($user['role'] ?? '') === 'admin';
}

/**
 * Kiểm tra người dùng có vai trò cụ thể hay không
 */
function hasRole(string $role): bool
{
    $user = currentUser();

    return $user !== null
        && ($user['role'] ?? '') === $role;
}

/**
 * Yêu cầu người dùng phải đăng nhập
 */
function requireLogin(): void
{
    // Luôn cập nhật trạng thái và quyền từ database
    refreshCurrentUser();

    if (!isLoggedIn()) {
        setFlash('error', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('login.php');
    }
}

/**
 * Yêu cầu người dùng phải có quyền Admin
 */
function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        setFlash('error', 'Bạn không có quyền truy cập vào trang quản trị.');
        redirect('index.php');
    }
}
