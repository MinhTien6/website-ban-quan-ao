
<?php
require_once __DIR__ . '/includes/cart.php';

if (empty($_SESSION['cart_csrf'])) {
    $_SESSION['cart_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['cart_csrf'], $token)) {
        $error = 'Phiên làm việc không hợp lệ. Hãy tải lại trang.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'add') {
                addToCart(
                    (int) ($_POST['variant_id'] ?? 0),
                    (int) ($_POST['quantity'] ?? 1)
                );
                $message = 'Đã thêm sản phẩm vào giỏ hàng.';
            } elseif ($action === 'update') {
                updateCartItem(
                    (int) ($_POST['variant_id'] ?? 0),
                    (int) ($_POST['quantity'] ?? 0)
                );
                $message = 'Đã cập nhật giỏ hàng.';
            } elseif ($action === 'remove') {
                removeCartItem((int) ($_POST['variant_id'] ?? 0));
                $message = 'Đã xóa sản phẩm khỏi giỏ hàng.';
            } elseif ($action === 'clear') {
                clearCart();
                $message = 'Đã xóa toàn bộ giỏ hàng.';
            } else {
                throw new RuntimeException('Thao tác không hợp lệ.');
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$cart = getCartDetails();

// Lấy danh sách biến thể đang bán để thử thêm vào giỏ.
$sql = "
    SELECT
        v.id AS variant_id,
        v.sku,
        v.size,
        v.color,
        v.price,
        v.stock_quantity,
        p.name AS product_name
    FROM product_variants v
    INNER JOIN products p ON p.id = v.product_id
    WHERE v.status = 'active'
      AND p.status = 'active'
      AND v.stock_quantity > 0
    ORDER BY p.name, v.color, v.size
";
$variants = db()->query($sql)->fetchAll();

function money($amount): string
{
    return number_format((float) $amount, 0, ',', '.') . ' đ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giỏ hàng | FashionStore</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 32px auto;
            padding: 0 16px;
            color: #222;
        }
        h1, h2 { margin-bottom: 16px; }
        .panel {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .success { color: #18743a; }
        .error { color: #b42318; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border-bottom: 1px solid #eee;
            padding: 12px 8px;
            text-align: left;
        }
        
        input, select, button, .button {
            padding: 9px;
            margin: 3px 0;
        }
        button, .button {
            cursor: pointer;
            border: 1px solid #222;
            border-radius: 5px;
            background: #222;
            color: white;
        }
        .inline { display: inline-flex; gap: 5px; align-items: center; }
        .total { font-size: 20px; font-weight: bold; text-align: right; }
        a { color: inherit; }
        @media (max-width: 650px) {
            .table-wrap { overflow-x: auto; }
            table { min-width: 600px; }
        }
        .checkout-button {
    display: inline-block;
    padding: 12px 20px;
    background: #111827;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    text-align: center;
}

.checkout-button:hover {
    background: #374151;
}
    </style>
</head>

<body>
    <h1>Giỏ hàng của bạn</h1>

    <?php if ($message !== ''): ?>
        <p class="success"><?= h($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <section class="panel">
        <h2>Thêm sản phẩm</h2>

        <?php if (!$variants): ?>
            <p>Chưa có biến thể sản phẩm nào đang bán và còn hàng.</p>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="csrf_token"
                       value="<?= h($_SESSION['cart_csrf']) ?>">
                <input type="hidden" name="action" value="add">

                <label for="variant_id">Sản phẩm / size / màu:</label><br>
                <select id="variant_id" name="variant_id" required>
                    <?php foreach ($variants as $v): ?>
                        <option value="<?= (int) $v['variant_id'] ?>">
                            <?= h($v['product_name']) ?>
                            — <?= h($v['size']) ?>
                            — <?= h($v['color']) ?>
                            — <?= money($v['price']) ?>
                            (còn <?= (int) $v['stock_quantity'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="add_quantity">Số lượng:</label>
                <input id="add_quantity" type="number"
                       name="quantity" min="1" value="1" required>

                <button type="submit">Thêm vào giỏ</button>
            </form>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Sản phẩm đã chọn</h2>

        <?php if (!$cart['items']): ?>
            <p>Giỏ hàng đang trống.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Size / màu</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cart['items'] as $item): ?>
                        <tr>
                            <td>
                                <?= h($item['product_name']) ?><br>
                                <small>SKU: <?= h($item['sku']) ?></small>
                            </td>
                            <td>
                                <?= h($item['size']) ?> /
                                <?= h($item['color']) ?>
                            </td>
                            <td><?= money($item['price']) ?></td>
                            <td>
                                <form method="post" class="inline">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= h($_SESSION['cart_csrf']) ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="variant_id"
                                           value="<?= (int) $item['variant_id'] ?>">
                                    <input type="number" name="quantity"
                                           min="0"
                                           max="<?= (int) $item['stock_quantity'] ?>"
                                           value="<?= (int) $item['quantity'] ?>"
                                           required style="width: 65px">
                                    <button type="submit">Cập nhật</button>
                                </form>
                            </td>
                            <td><?= money($item['line_total']) ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= h($_SESSION['cart_csrf']) ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="variant_id"
                                           value="<?= (int) $item['variant_id'] ?>">
                                    <button type="submit">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p>Tổng số lượng: <?= (int) $cart['total_quantity'] ?></p>
            <p class="total">
                Tạm tính: <?= money($cart['subtotal']) ?>
            </p>

            <form method="post">
                <input type="hidden" name="csrf_token"
                       value="<?= h($_SESSION['cart_csrf']) ?>">
                <input type="hidden" name="action" value="clear">
                <button type="submit">Xóa toàn bộ giỏ hàng</button>
            </form>

            <p>
                <a href="checkout.php" class="checkout-button">
    Tiếp tục đặt hàng
</a>
            </p>
        <?php endif; ?>
    </section>
</body>
</html>
