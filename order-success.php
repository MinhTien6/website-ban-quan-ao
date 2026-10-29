
<?php
require_once __DIR__ . '/config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Escape dữ liệu trước khi hiển thị ra HTML.
 */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($amount): string
{
    return number_format((float)$amount, 0, ',', '.') . ' ₫';
}

/**
 * Chỉ cho phép xem đơn hàng đã được xác nhận trong session
 * sau khi checkout thành công.
 */
$success = $_SESSION['checkout_success'] ?? null;

if (is_string($success)) {
    $success = ['order_code' => $success];
}

if (!is_array($success)) {
    header('Location: cart.php');
    exit;
}

$orderId = filter_var(
    $success['order_id'] ?? null,
    FILTER_VALIDATE_INT
);

$orderCode = trim((string)($success['order_code'] ?? ''));

if (!$orderId && $orderCode === '') {
    header('Location: cart.php');
    exit;
}

try {
    $pdo = db();

    if ($orderId) {
        $stmt = $pdo->prepare(
            'SELECT
                id,
                order_code,
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
                created_at
             FROM orders
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute(['id' => $orderId]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT
                id,
                order_code,
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
                created_at
             FROM orders
             WHERE order_code = :order_code
             LIMIT 1'
        );

        $stmt->execute(['order_code' => $orderCode]);
    }

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Không tìm thấy đơn hàng hợp lệ.
    if (!$order) {
        http_response_code(404);
        $errorMessage = 'Không tìm thấy thông tin đơn hàng.';
    } else {
        // Lấy các mặt hàng đã được lưu tại thời điểm đặt đơn.
        $itemStmt = $pdo->prepare(
            'SELECT
                product_name,
                sku,
                size,
                color,
                unit_price,
                quantity,
                line_total
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id ASC'
        );

        $itemStmt->execute(['order_id' => $order['id']]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $errorMessage = '';
    }
} catch (PDOException $exception) {
    error_log('Order success page error: ' . $exception->getMessage());

    http_response_code(500);
    $errorMessage = 'Không thể tải thông tin đơn hàng lúc này.';
    $order = null;
    $items = [];
}

$paymentMethod = strtolower(
    (string)($order['payment_method'] ?? '')
);

$isCOD = in_array($paymentMethod, ['cod', 'cash_on_delivery'], true);
$isBankTransfer = in_array(
    $paymentMethod,
    ['bank_transfer', 'bank', 'transfer'],
    true
);

$paymentLabels = [
    'pending' => 'Chờ thanh toán',
    'paid' => 'Đã thanh toán',
    'failed' => 'Thanh toán thất bại',
    'refunded' => 'Đã hoàn tiền',
];

$orderLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'processing' => 'Đang xử lý',
    'shipping' => 'Đang giao hàng',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
];

$paymentLabel = $paymentLabels[
    strtolower((string)($order['payment_status'] ?? ''))
] ?? ($order['payment_status'] ?? 'Chưa cập nhật');

$orderLabel = $orderLabels[
    strtolower((string)($order['order_status'] ?? ''))
] ?? ($order['order_status'] ?? 'Chưa cập nhật');

$addressParts = [
    $order['shipping_address'] ?? '',
    $order['shipping_ward'] ?? '',
    $order['shipping_district'] ?? '',
    $order['shipping_province'] ?? '',
];

$fullAddress = implode(
    ', ',
    array_filter($addressParts, static fn($part) => trim((string)$part) !== '')
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công | FashionStore</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px 16px;
            background: #f5f6f8;
            color: #1f2937;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            width: 100%;
            max-width: 850px;
            margin: 0 auto;
        }

        .card {
            margin-bottom: 20px;
            padding: 24px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        }

        .success-header {
            text-align: center;
        }

        .success-icon {
            display: inline-flex;
            width: 64px;
            height: 64px;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #dcfce7;
            color: #15803d;
            font-size: 34px;
        }

        h1 {
            margin: 16px 0 8px;
            font-size: 27px;
        }

        h2 {
            margin-top: 0;
            font-size: 19px;
        }

        .muted {
            color: #6b7280;
            line-height: 1.6;
        }

        .order-code {
            margin: 16px 0;
            color: #111827;
            font-size: 20px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 11px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: 0;
        }

        .info-row span:first-child {
            color: #6b7280;
        }

        .info-row span:last-child {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .total {
            font-size: 20px;
            font-weight: bold;
            color: #b91c1c;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #fef3c7;
            color: #92400e;
            font-size: 13px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 520px;
        }

        th, td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f9fafb;
            font-size: 13px;
        }

        .right {
            text-align: right;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 24px;
        }

        .button {
            display: inline-block;
            padding: 12px 20px;
            border: 0;
            border-radius: 7px;
            background: #111827;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .button.secondary {
            border: 1px solid #d1d5db;
            background: white;
            color: #111827;
        }

        .notice {
            padding: 14px;
            border-radius: 8px;
            background: #eff6ff;
            color: #1e40af;
            line-height: 1.6;
        }

        .error {
            color: #b91c1c;
        }

        @media (max-width: 600px) {
            .card {
                padding: 18px;
            }

            h1 {
                font-size: 23px;
            }

            .info-row {
                flex-direction: column;
                gap: 5px;
            }

            .info-row span:last-child {
                text-align: left;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <?php if (!empty($errorMessage)): ?>

        <div class="card success-header">
            <h1>Không thể hiển thị đơn hàng</h1>
            <p class="error"><?= e($errorMessage) ?></p>

            <div class="actions">
                <a class="button" href="index.php">Về trang chủ</a>
                <a class="button secondary" href="cart.php">Quay lại giỏ hàng</a>
            </div>
        </div>

    <?php else: ?>

        <div class="card success-header">
            <div class="success-icon" aria-hidden="true">&#10003;</div>
            <h1>Đặt hàng thành công!</h1>

            <p class="muted">
                Cảm ơn bạn đã mua sắm tại FashionStore.
                Thông tin đơn hàng của bạn đã được ghi nhận.
            </p>

            <div class="order-code">
                Mã đơn hàng: <?= e($order['order_code']) ?>
            </div>

            <p class="muted">
                Ngày đặt:
                <?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?>
            </p>

            <span class="status"><?= e($orderLabel) ?></span>
        </div>

        <div class="card">
            <h2>Thông tin giao hàng</h2>

            <div class="info-row">
                <span>Người nhận</span>
                <span><?= e($order['customer_name']) ?></span>
            </div>

            <div class="info-row">
                <span>Số điện thoại</span>
                <span><?= e($order['customer_phone']) ?></span>
            </div>

            <div class="info-row">
                <span>Email</span>
                <span><?= e($order['customer_email']) ?></span>
            </div>

            <div class="info-row">
                <span>Địa chỉ</span>
                <span><?= e($fullAddress ?: 'Chưa có thông tin') ?></span>
            </div>
        </div>

        <div class="card">
            <h2>Chi tiết đơn hàng</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Phân loại</th>
                        <th class="right">Đơn giá</th>
                        <th class="right">SL</th>
                        <th class="right">Thành tiền</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?= e($item['product_name']) ?></strong><br>
                                <small><?= e($item['sku']) ?></small>
                            </td>
                            <td>
                                <?= e($item['size']) ?>
                                <?php if (!empty($item['color'])): ?>
                                    / <?= e($item['color']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="right"><?= money($item['unit_price']) ?></td>
                            <td class="right"><?= (int)$item['quantity'] ?></td>
                            <td class="right"><?= money($item['line_total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="info-row">
                <span>Tạm tính</span>
                <span><?= money($order['subtotal']) ?></span>
            </div>

            <?php if ((float)$order['discount_amount'] > 0): ?>
                <div class="info-row">
                    <span>Giảm giá</span>
                    <span>-<?= money($order['discount_amount']) ?></span>
                </div>
            <?php endif; ?>

            <div class="info-row">
                <span>Phí vận chuyển</span>
                <span><?= money($order['shipping_fee']) ?></span>
            </div>

            <div class="info-row">
                <span><strong>Tổng thanh toán</strong></span>
                <span class="total"><?= money($order['total_amount']) ?></span>
            </div>
        </div>

        <div class="card">
            <h2>Thông tin thanh toán</h2>

            <div class="info-row">
                <span>Phương thức</span>
                <span>
                    <?php if ($isCOD): ?>
                        Thanh toán khi nhận hàng (COD)
                    <?php elseif ($isBankTransfer): ?>
                        Chuyển khoản ngân hàng
                    <?php else: ?>
                        <?= e($order['payment_method']) ?>
                    <?php endif; ?>
                </span>
            </div>

            <div class="info-row">
                <span>Trạng thái thanh toán</span>
                <span><?= e($paymentLabel) ?></span>
            </div>

            <?php if ($isCOD): ?>
                <div class="notice">
                    Bạn thanh toán cho đơn vị giao hàng khi nhận được sản phẩm.
                    Vui lòng chuẩn bị đúng số tiền hiển thị trong đơn hàng.
                </div>
            <?php elseif ($isBankTransfer): ?>
                <div class="notice">
                    Đơn hàng đang chờ thanh toán hoặc xác nhận thanh toán.
                    Vui lòng sử dụng thông tin chuyển khoản chính thức do
                    FashionStore cung cấp. Chỉ đánh dấu đã thanh toán sau khi
                    xác minh giao dịch thành công.
                </div>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a class="button" href="index.php">Tiếp tục mua sắm</a>
            <a class="button secondary" href="cart.php">Xem giỏ hàng</a>
        </div>

    <?php endif; ?>

</div>
</body>
</html>
