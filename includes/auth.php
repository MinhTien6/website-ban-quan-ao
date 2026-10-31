<?php
/**
 * Quản lý phiên xác thực người dùng và phân quyền
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra xem người dùng hiện tại đã đăng nhập hay chưa
 *
 * @return bool True nếu đã đăng nhập, ngược lại False
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Lấy thông tin người dùng hiện tại đang đăng nhập
 *
 * @return array{id: int, full_name: string, email: string, role: string}|null
 */
function currentUser(): ?array
{
    return isLoggedIn() ? $_SESSION['user'] : null;
}

/**
 * Kiểm tra xem người dùng hiện tại có quyền quản trị viên hay không
 *
 * @return bool True nếu là admin, ngược lại False
 */
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
    $user = $userModel->findById((int)$_SESSION['user']['id']);

    if ($user === false || (int)$user['status'] !== 1) {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        return;
    }

    $_SESSION['user'] = [
        'id'        => (int)$user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role']
    ];
}

function isAdmin(): bool
{
    $user = currentUser();
    return $user !== null && ($user['role'] ?? '') === 'admin';
}

/**
 * Kiểm tra xem người dùng có vai trò cụ thể hay không
 *
 * @param string $role Vai trò cần kiểm tra ('admin', 'user')
 * @return bool
 */
function hasRole(string $role): bool
{
    $user = currentUser();
    return $user !== null && ($user['role'] ?? '') === $role;
}

/**
 * Yêu cầu người dùng phải đăng nhập trước khi xem trang
 * Nếu chưa đăng nhập, lưu thông báo lỗi và chuyển hướng sang trang đăng nhập
 *
 * @return void
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('login.php');
    }
}

/**
 * Yêu cầu quyền quản trị viên (role = 'admin')
 * Nếu không đủ quyền, lưu thông báo lỗi và chuyển hướng về trang chủ
 *
 * @return void
 */
function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        setFlash('error', 'Bạn không có quyền truy cập vào trang quản trị.');
        redirect('index.php');
    }
}

