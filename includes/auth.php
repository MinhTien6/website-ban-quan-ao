<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user'])
        && is_array($_SESSION['user'])
        && !empty($_SESSION['user']['id']);
}

function currentUser(): ?array
{
    return isLoggedIn() ? $_SESSION['user'] : null;
}

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

    
    if (!$user || (int) $user['status'] !== 1) {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        return;
    }

    
    $_SESSION['user'] = [
        'id'        => (int) $user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role']
    ];
}

function isAdmin(): bool
{
    $user = currentUser();

    return $user !== null
        && ($user['role'] ?? '') === 'admin';
}

function hasRole(string $role): bool
{
    $user = currentUser();

    return $user !== null
        && ($user['role'] ?? '') === $role;
}

function requireLogin(): void
{
    
    refreshCurrentUser();

    if (!isLoggedIn()) {
        setFlash('error', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('login.php');
    }
}

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        setFlash('error', 'Bạn không có quyền truy cập vào trang quản trị.');
        redirect('index.php');
    }
}
