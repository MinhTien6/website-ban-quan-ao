
<?php

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Giỏ hàng lưu trong session:
 * [variant_id => quantity]
 */
function cartItems(): array
{
    return $_SESSION['cart'] ?? [];
}

/**
 * Lấy thông tin biến thể hợp lệ từ database.
 */
function findCartVariant(int $variantId): ?array
{
    $sql = "
        SELECT
            v.id AS variant_id,
            v.product_id,
            v.sku,
            v.size,
            v.color,
            v.price,
            v.stock_quantity,
            p.name AS product_name
        FROM product_variants v
        INNER JOIN products p ON p.id = v.product_id
        WHERE v.id = :variant_id
          AND v.status = 'active'
          AND p.status = 'active'
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute(['variant_id' => $variantId]);

    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * Thêm biến thể vào giỏ.
 */
function addToCart(int $variantId, int $quantity = 1): void
{
    if ($variantId <= 0 || $quantity <= 0) {
        throw new InvalidArgumentException('Sản phẩm hoặc số lượng không hợp lệ.');
    }

    $variant = findCartVariant($variantId);

    if (!$variant) {
        throw new RuntimeException('Sản phẩm không tồn tại hoặc đã ngừng bán.');
    }

    $cart = cartItems();
    $currentQuantity = (int) ($cart[$variantId] ?? 0);
    $newQuantity = $currentQuantity + $quantity;

    if ($newQuantity > (int) $variant['stock_quantity']) {
        throw new RuntimeException('Số lượng vượt quá tồn kho.');
    }

    $_SESSION['cart'][$variantId] = $newQuantity;
}

/**
 * Cập nhật số lượng.
 * Đặt quantity = 0 để xóa sản phẩm.
 */
function updateCartItem(int $variantId, int $quantity): void
{
    if ($variantId <= 0 || $quantity < 0) {
        throw new InvalidArgumentException('Dữ liệu giỏ hàng không hợp lệ.');
    }

    if ($quantity === 0) {
        removeCartItem($variantId);
        return;
    }

    $variant = findCartVariant($variantId);

    if (!$variant) {
        throw new RuntimeException('Sản phẩm không còn khả dụng.');
    }

    if ($quantity > (int) $variant['stock_quantity']) {
        throw new RuntimeException('Số lượng vượt quá tồn kho.');
    }

    if (!array_key_exists($variantId, cartItems())) {
        throw new RuntimeException('Sản phẩm không có trong giỏ hàng.');
    }

    $_SESSION['cart'][$variantId] = $quantity;
}

/**
 * Xóa một biến thể khỏi giỏ.
 */
function removeCartItem(int $variantId): void
{
    unset($_SESSION['cart'][$variantId]);
}

/**
 * Xóa toàn bộ giỏ hàng.
 */
function clearCart(): void
{
    $_SESSION['cart'] = [];
}

/**
 * Đọc lại giá, tên, size, màu từ database.
 * Không sử dụng giá do trình duyệt gửi lên.
 */
function getCartDetails(): array
{
    $cart = cartItems();

    if (!$cart) {
        return [
            'items' => [],
            'total_quantity' => 0,
            'subtotal' => 0,
        ];
    }

    $items = [];
    $totalQuantity = 0;
    $subtotal = 0;

    foreach ($cart as $variantId => $quantity) {
        $variantId = (int) $variantId;
        $quantity = (int) $quantity;

        if ($quantity <= 0) {
            continue;
        }

        $variant = findCartVariant($variantId);

        // Không hiển thị biến thể đã bị ẩn hoặc ngừng bán.
        if (!$variant) {
            continue;
        }

        $price = (float) $variant['price'];
        $lineTotal = $price * $quantity;

        $variant['quantity'] = $quantity;
        $variant['line_total'] = $lineTotal;

        $items[] = $variant;
        $totalQuantity += $quantity;
        $subtotal += $lineTotal;
    }

    return [
        'items' => $items,
        'total_quantity' => $totalQuantity,
        'subtotal' => $subtotal,
    ];
}
    