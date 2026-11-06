<?php
/**
 * Các hàm nghiệp vụ Giỏ hàng bằng Session (Phụ trách: TV5)
 * File này được nạp tự động qua includes/functions.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Lấy danh sách sản phẩm trong giỏ hàng từ Session
 */
function getCart(): array
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

/**
 * Đếm tổng số lượng sản phẩm đang có trong giỏ hàng
 */
function getCartCount(): int
{
    $cart = getCart();
    $count = 0;
    foreach ($cart as $item) {
        $count += (int)($item['quantity'] ?? 0);
    }
    return $count;
}

/**
 * Tính tổng tiền tạm tính của giỏ hàng
 */
function getCartTotal(): float
{
    $cart = getCart();
    $total = 0;
    foreach ($cart as $item) {
        $price = (float)($item['price'] ?? 0);
        $qty   = (int)($item['quantity'] ?? 0);
        $total += $price * $qty;
    }
    return $total;
}

/**
 * Xóa toàn bộ giỏ hàng trong Session
 */
function clearCart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['cart'] = [];
}