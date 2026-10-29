
<?php
require_once __DIR__ . '/includes/cart.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo = db();

if (empty($_SESSION['checkout_csrf'])) {
    $_SESSION['checkout_csrf'] = bin2hex(random_bytes(32));
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($amount): string
{
    return number_format((float) $amount, 0, ',', '.') . ' đ';
}

$errors = [];
$successOrderCode = null;

$old = [
    'customer_name'  => '',
    'customer_phone' => '',
    'customer_email' => '',
    'province'       => '',
    'district'       => '',
    'ward'           => '',
    'address'        => '',
    'customer_note'  => '',
    'payment_method' => 'cod',
];

/*
 * cartItems() trả về mảng dạng:
 * [variant_id => quantity]
 * Đây là cấu trúc giỏ hàng được tạo bởi includes/cart.php.
 */
$cart = cartItems();

$isSuccessRequest =
    isset($_GET['success']) &&
    $_GET['success'] === '1' &&
    !empty($_SESSION['checkout_success']);

if (empty($cart) && !$isSuccessRequest) {
    header('Location: cart.php');
    exit;
}

/**
 * Đọc sản phẩm và giá từ database.
 * Không lấy giá từ form của khách hàng.
 */
function loadCheckoutItems(PDO $pdo, array $cart, bool $lock = false): array
{
    $ids = [];

    foreach ($cart as $variantId => $quantity) {
        $id = filter_var($variantId, FILTER_VALIDATE_INT);
        $qty = filter_var($quantity, FILTER_VALIDATE_INT);

        if (!$id || !$qty || $qty < 1) {
            throw new RuntimeException('Giỏ hàng có sản phẩm hoặc số lượng không hợp lệ.');
        }

        $ids[] = $id;
    }

    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "
        SELECT
            pv.id AS variant_id,
            pv.product_id,
            pv.sku,
            pv.size,
            pv.color,
            pv.price,
            pv.stock_quantity,
            pv.status AS variant_status,
            p.name AS product_name,
            p.status AS product_status
        FROM product_variants pv
        INNER JOIN products p ON p.id = pv.product_id
        WHERE pv.id IN ($placeholders)
    ";

    if ($lock) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $byId = [];

    foreach ($rows as $row) {
        $byId[(int) $row['variant_id']] = $row;
    }

    $items = [];

    foreach ($cart as $variantId => $quantity) {
        $id = (int) $variantId;
        $qty = (int) $quantity;

        if (!isset($byId[$id])) {
            throw new RuntimeException('Một sản phẩm trong giỏ hàng không còn tồn tại.');
        }

        $row = $byId[$id];

        if (
            $row['variant_status'] !== 'active' ||
            $row['product_status'] !== 'active'
        ) {
            throw new RuntimeException(
                'Sản phẩm "' . $row['product_name'] . '" hiện không còn được bán.'
            );
        }

        if ((int) $row['stock_quantity'] < $qty) {
            throw new RuntimeException(
                'Sản phẩm "' . $row['product_name'] .
                '" không đủ tồn kho. Vui lòng quay lại giỏ hàng.'
            );
        }

        $row['quantity'] = $qty;
        $row['line_total'] = (float) $row['price'] * $qty;
        $items[] = $row;
    }

    return $items;
}

// Hiển thị giỏ hàng và tính tổng tạm tính từ database.
try {
    $items = loadCheckoutItems($pdo, $cart);
    $subtotal = array_sum(array_column($items, 'line_total'));
} catch (Throwable $ex) {
    $items = [];
    $subtotal = 0;
    $errors[] = $ex->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    foreach ($old as $key => $value) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            $old[$key] = trim($_POST[$key]);
        }
    }

    $csrf = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['checkout_csrf'], $csrf)
    ) {
        $errors[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
    }

    if ($old['customer_name'] === '' || mb_strlen($old['customer_name']) > 150) {
        $errors[] = 'Vui lòng nhập họ tên người nhận hợp lệ.';
    }

    if (
        !preg_match('/^[0-9+\s().-]{9,20}$/', $old['customer_phone'])
    ) {
        $errors[] = 'Số điện thoại không hợp lệ.';
    }

    if (
        $old['customer_email'] !== '' &&
        !filter_var($old['customer_email'], FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = 'Email không hợp lệ.';
    }

    foreach (['province', 'district', 'ward', 'address'] as $field) {
        if ($old[$field] === '') {
            $errors[] = 'Vui lòng điền đầy đủ địa chỉ nhận hàng.';
            break;
        }
    }

    if (!in_array($old['payment_method'], ['cod', 'bank_transfer'], true)) {
        $errors[] = 'Phương thức thanh toán không hợp lệ.';
    }

    if (mb_strlen($old['customer_note']) > 1000) {
        $errors[] = 'Ghi chú không được vượt quá 1000 ký tự.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Khóa các dòng biến thể để hạn chế bán vượt tồn kho.
            $items = loadCheckoutItems($pdo, $cart, true);
            $subtotal = array_sum(array_column($items, 'line_total'));

            // Phí vận chuyển mặc định; điều chỉnh theo chính sách cửa hàng.
            $shippingFee = 30000;
            $discountAmount = 0;
            $totalAmount = $subtotal - $discountAmount + $shippingFee;

            $orderCode = 'FS' . date('YmdHis') .
                strtoupper(bin2hex(random_bytes(3)));

            /*
             * Các tên cột bên dưới là giả định.
             * Nếu schema dùng tên khác, đổi tên cột cho khớp database.
             */
            $orderSql = "
                INSERT INTO orders (
                    order_code,
                    user_id,
                    coupon_id,
                    customer_name,
                    customer_email,
                    customer_phone,
                    shipping_province,
                    shipping_district,
                    shipping_ward,
                    shipping_address,
                    subtotal,
                    discount_amount,
                    shipping_fee,
                    total_amount,
                    payment_method,
                    payment_status,
                    order_status,
                    customer_note
                ) VALUES (
                    :order_code,
                    NULL,
                    NULL,
                    :customer_name,
                    :customer_email,
                    :customer_phone,
                    :province,
                    :district,
                    :ward,
                    :address,
                    :subtotal,
                    :discount_amount,
                    :shipping_fee,
                    :total_amount,
                    :payment_method,
                    'pending',
                    'pending',
                    :customer_note
                )
            ";

            $orderStmt = $pdo->prepare($orderSql);
            $orderStmt->execute([
                ':order_code'      => $orderCode,
                ':customer_name'   => $old['customer_name'],
                ':customer_email'  => $old['customer_email'] ?: null,
                ':customer_phone'  => $old['customer_phone'],
                ':province'        => $old['province'],
                ':district'        => $old['district'],
                ':ward'            => $old['ward'],
                ':address'          => $old['address'],
                ':subtotal'        => $subtotal,
                ':discount_amount' => $discountAmount,
                ':shipping_fee'    => $shippingFee,
                ':total_amount'    => $totalAmount,
                ':payment_method'  => $old['payment_method'],
                ':customer_note'   => $old['customer_note'] ?: null,
            ]);

            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_id,
                    variant_id,
                    product_name,
                    sku,
                    size,
                    color,
                    unit_price,
                    quantity,
                    line_total
                ) VALUES (
                    :order_id,
                    :variant_id,
                    :product_name,
                    :sku,
                    :size,
                    :color,
                    :unit_price,
                    :quantity,
                    :line_total
                )
            ");

            $stockStmt = $pdo->prepare("
                UPDATE product_variants
                SET stock_quantity = stock_quantity - :quantity
                WHERE id = :variant_id
                  AND stock_quantity >= :required_quantity
            ");

            foreach ($items as $item) {
                $itemStmt->execute([
                    ':order_id'    => $orderId,
                    ':variant_id'  => $item['variant_id'],
                    ':product_name'=> $item['product_name'],
                    ':sku'         => $item['sku'],
                    ':size'        => $item['size'],
                    ':color'       => $item['color'],
                    ':unit_price'  => $item['price'],
                    ':quantity'    => $item['quantity'],
                    ':line_total'  => $item['line_total'],
                ]);

                $stockStmt->execute([
                    ':quantity'          => $item['quantity'],
                    ':variant_id'        => $item['variant_id'],
                    ':required_quantity' => $item['quantity'],
                ]);

                if ($stockStmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Tồn kho vừa thay đổi. Vui lòng thử đặt hàng lại.'
                    );
                }
            }

            // Ghi nhận khoản thanh toán đang chờ xử lý.
            $paymentStmt = $pdo->prepare("
                INSERT INTO payments (
                    order_id,
                    payment_method,
                    amount,
                    status,
                    transaction_reference,
                    paid_at
                ) VALUES (
                    :order_id,
                    :payment_method,
                    :amount,
                    'pending',
                    NULL,
                    NULL
                )
            ");

            $paymentStmt->execute([
                ':order_id'       => $orderId,
                ':payment_method' => $old['payment_method'],
                ':amount'         => $totalAmount,
            ]);

            $pdo->commit();

// Chỉ xóa giỏ hàng sau khi lưu đơn thành công.
clearCart();

// Lưu thông tin để trang xác nhận hiển thị.
$_SESSION['checkout_success'] = [
    'order_id' => $orderId,
    'order_code' => $orderCode,
    'payment_method' => $old['payment_method'],
    'total_amount' => $totalAmount,
];

// Tránh gửi lại biểu mẫu bằng cách tải lại trang.
unset($_SESSION['checkout_csrf']);

header('Location: order-success.php');
exit;

            // Chỉ xóa giỏ hàng sau khi transaction thành công.
            clearCart();

            // Tránh gửi lại form khi tải lại trang.
            $_SESSION['checkout_success'] = [
                'order_code' => $orderCode,
                'payment_method' => $old['payment_method'],
                'total_amount' => $totalAmount,
            ];

            unset($_SESSION['checkout_csrf']);
            header('Location: checkout.php?success=1');
            exit;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Ghi lỗi chi tiết vào log máy chủ, không hiển thị SQL cho khách.
            error_log('FashionStore checkout error: ' . $ex->getMessage());

            $errors[] = $ex instanceof RuntimeException
                ? $ex->getMessage()
                : 'Không thể tạo đơn hàng lúc này. Vui lòng thử lại.';
        }
    }
}

if (
    isset($_GET['success']) &&
    $_GET['success'] === '1' &&
    !empty($_SESSION['checkout_success'])
) {
    $successOrderCode = $_SESSION['checkout_success']['order_code'];
    $successData = $_SESSION['checkout_success'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thanh toán - FashionStore</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f5f6f8;
            color: #222;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 1100px;
            margin: 35px auto;
            padding: 20px;
        }
        .layout {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
        }
        .panel {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 3px 15px #0000000c;
        }
        h1, h2 { margin-top: 0; }
        label {
            display: block;
            font-weight: 600;
            margin: 15px 0 6px;
        }
        input, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #d5d8df;
            border-radius: 7px;
            font: inherit;
        }
        textarea { min-height: 85px; resize: vertical; }
        .radio {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin: 13px 0;
        }
        .radio input { width: auto; margin-top: 3px; }
        .radio label { margin: 0; font-weight: normal; }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin: 13px 0;
        }
        .total {
            font-size: 20px;
            font-weight: bold;
            border-top: 1px solid #ddd;
            padding-top: 16px;
        }
        .btn {
            display: inline-block;
            width: 100%;
            padding: 13px;
            border: 0;
            border-radius: 8px;
            background: #171717;
            color: #fff;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }
        .error {
            padding: 12px 16px;
            background: #fff0f0;
            border: 1px solid #efb8b8;
            border-radius: 8px;
            margin-bottom: 18px;
        }
        .success {
            padding: 18px;
            background: #effaf1;
            border: 1px solid #b7e2bf;
            border-radius: 8px;
        }
        .muted { color: #666; font-size: 14px; }
        @media (max-width: 750px) {
            .layout { grid-template-columns: 1fr; }
            .container { margin: 10px auto; padding: 12px; }
        }
    </style>
</head>
<body>
<div class="container">
    <?php if ($successOrderCode !== null): ?>
        <div class="panel">
            <div class="success">
                <h1>Đặt hàng thành công!</h1>
                <p>Mã đơn hàng của bạn:</p>
                <h2><?= e($successOrderCode) ?></h2>
                <p>
                    Tổng thanh toán:
                    <strong><?= money($successData['total_amount']) ?></strong>
                </p>

                <?php if ($successData['payment_method'] === 'cod'): ?>
                    <p>Bạn thanh toán khi nhận hàng (COD).</p>
                <?php else: ?>
                    <p>
                        Đơn hàng đang chờ thanh toán chuyển khoản.
                        Vui lòng liên hệ cửa hàng để nhận thông tin tài khoản
                        và nội dung chuyển khoản.
                    </p>
                <?php endif; ?>
            </div>
            <p class="muted">
                Hãy lưu lại mã đơn hàng để tiện tra cứu.
            </p>
            <a class="btn" href="index.php">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <h1>Thanh toán</h1>
        <p class="muted">Bạn có thể đặt hàng mà không cần đăng nhập.</p>

        <?php if ($errors): ?>
            <div class="error">
                <strong>Vui lòng kiểm tra:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="checkout.php" class="layout">
            <section class="panel">
                <h2>Thông tin nhận hàng</h2>

                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION['checkout_csrf']) ?>">

                <label for="customer_name">Họ tên người nhận *</label>
                <input id="customer_name" name="customer_name"
                       maxlength="150" required
                       value="<?= e($old['customer_name']) ?>">

                <label for="customer_phone">Số điện thoại *</label>
                <input id="customer_phone" name="customer_phone"
                       type="tel" maxlength="20" required
                       value="<?= e($old['customer_phone']) ?>">

                <label for="customer_email">Email</label>
                <input id="customer_email" name="customer_email"
                       type="email" maxlength="255"
                       value="<?= e($old['customer_email']) ?>">

                <label for="province">Tỉnh / thành phố *</label>
                <input id="province" name="province" required
                       value="<?= e($old['province']) ?>">

                <label for="district">Quận / huyện *</label>
                <input id="district" name="district" required
                       value="<?= e($old['district']) ?>">

                <label for="ward">Phường / xã *</label>
                <input id="ward" name="ward" required
                       value="<?= e($old['ward']) ?>">

                <label for="address">Địa chỉ cụ thể *</label>
                <input id="address" name="address" maxlength="255" required
                       value="<?= e($old['address']) ?>">

                <label for="customer_note">Ghi chú đơn hàng</label>
                <textarea id="customer_note" name="customer_note"
                          maxlength="1000"><?= e($old['customer_note']) ?></textarea>

                <h2 style="margin-top:28px">Phương thức thanh toán</h2>

                <div class="radio">
                    <input id="cod" type="radio" name="payment_method"
                           value="cod"
                        <?= $old['payment_method'] === 'cod' ? 'checked' : '' ?>>
                    <label for="cod">
                        <strong>Thanh toán khi nhận hàng (COD)</strong>
                        <div class="muted">Thanh toán trực tiếp cho đơn vị giao hàng.</div>
                    </label>
                </div>

                <div class="radio">
                    <input id="bank_transfer" type="radio" name="payment_method"
                           value="bank_transfer"
                        <?= $old['payment_method'] === 'bank_transfer' ? 'checked' : '' ?>>
                    <label for="bank_transfer">
                        <strong>Chuyển khoản ngân hàng</strong>
                        <div class="muted">
                            Đơn hàng sẽ ở trạng thái chờ thanh toán.
                        </div>
                    </label>
                </div>

                <button class="btn" type="submit">Xác nhận đặt hàng</button>
            </section>

            <aside class="panel">
                <h2>Đơn hàng của bạn</h2>

                <?php foreach ($items as $item): ?>
                    <div class="row">
                        <div>
                            <strong><?= e($item['product_name']) ?></strong>
                            <div class="muted">
                                Size: <?= e($item['size']) ?> |
                                Màu: <?= e($item['color']) ?> |
                                SL: <?= (int) $item['quantity'] ?>
                            </div>
                        </div>
                        <div><?= money($item['line_total']) ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="row">
                    <span>Tạm tính</span>
                    <strong><?= money($subtotal) ?></strong>
                </div>
                <div class="row">
                    <span>Phí vận chuyển</span>
                    <strong><?= money(30000) ?></strong>
                </div>
                <div class="row">
                    <span>Giảm giá</span>
                    <strong><?= money(0) ?></strong>
                </div>
                <div class="row total">
                    <span>Tổng cộng</span>
                    <span><?= money($subtotal + 30000) ?></span>
                </div>

                <p class="muted">
                    Giá và số lượng sẽ được kiểm tra lại trước khi tạo đơn hàng.
                </p>
                <a href="cart.php">← Quay lại giỏ hàng</a>
            </aside>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
