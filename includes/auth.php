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

    $user = currentUser();
    if (($user['role'] ?? '') !== 'admin') {
        setFlash('error', 'Bạn không có quyền truy cập vào trang quản trị.');
        redirect('index.php');
    }
}
