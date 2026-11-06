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

/**
 * Thêm sản phẩm biến thể vào giỏ hàng Session (TV5)
 */
function addToCart(array $variant, int $quantity = 1): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $requiredFields = ['variant_id', 'product_id', 'name', 'size', 'color', 'stock', 'price'];
    foreach ($requiredFields as $field) {
        if (!isset($variant[$field])) {
            return false;
        }
    }

    $variantId = (int)$variant['variant_id'];
    $productId = (int)$variant['product_id'];
    $stock = (int)$variant['stock'];
    $price = (float)$variant['price'];
    $salePrice = (float)($variant['sale_price'] ?? 0);

    if ($variantId <= 0 || $productId <= 0 || $quantity < 1 || $stock < 1 || $price < 0) {
        return false;
    }

    if ($salePrice > 0 && $salePrice < $price) {
        $price = $salePrice;
    }

    $cart = getCart();
    $currentQuantity = (int)($cart[$variantId]['quantity'] ?? 0);

    if ($currentQuantity < 0 || ($currentQuantity + $quantity) > $stock) {
        return false;
    }

    $cart[$variantId] = [
        'product_id' => $productId,
        'variant_id' => $variantId,
        'name'       => (string)$variant['name'],
        'size'       => (string)$variant['size'],
        'color'      => (string)$variant['color'],
        'price'      => $price,
        'quantity'   => $currentQuantity + $quantity,
        'image'      => (string)($variant['main_image'] ?? ''),
    ];

    $_SESSION['cart'] = $cart;
    return true;
}
