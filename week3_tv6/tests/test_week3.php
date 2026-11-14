<?php
/**
 * Kiểm thử tự động nhiệm vụ TV6 tuần 3.
 * Chạy bằng: php tests/test_week3.php
 * Cần cấu hình config/database.php và import database/shop_quanao.sql trước.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$databaseConfig = dirname(__DIR__) . '/config/database.php';
if (file_exists($databaseConfig)) {
    require_once $databaseConfig;
} else {
    fwrite(STDERR, "Thiếu config/database.php. Hãy sao chép config/database.example.php và cấu hình MySQL.\n");
    exit(2);
}

require_once dirname(__DIR__) . '/models/Order.php';

$tests = [];
$fixtureOrderIds = [];
$fixtureProductId = 0;
$fixtureMarker = 'TV6-W3-' . bin2hex(random_bytes(4));

function runTest(string $name, callable $test): void
{
    global $tests;
    try {
        $test();
        $tests[] = [$name, true, 'Đạt'];
    } catch (Throwable $error) {
        $tests[] = [$name, false, $error->getMessage()];
    }
}

function expectSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' (mong đợi: ' . var_export($expected, true)
            . ', nhận được: ' . var_export($actual, true) . ')');
    }
}

function createFixtureOrder(PDO $pdo, int $userId, int $productId, int $variantId, string $receiverName): int
{
    $order = $pdo->prepare(
        "INSERT INTO orders (user_id, receiver_name, phone, address, total, payment_method, status)
         VALUES (:user_id, :receiver_name, '0900000000', 'Đơn hàng dùng cho kiểm thử TV6 tuần 3', 1000, 'cod', 'pending')"
    );
    $order->execute(['user_id' => $userId, 'receiver_name' => $receiverName]);
    $orderId = (int)$pdo->lastInsertId();

    try {
        $item = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, variant_id, quantity, price)
             VALUES (:order_id, :product_id, :variant_id, 2, 500)'
        );
        $item->execute([
            'order_id' => $orderId,
            'product_id' => $productId,
            'variant_id' => $variantId,
        ]);
    } catch (Throwable $error) {
        $deleteOrder = $pdo->prepare('DELETE FROM orders WHERE id = :id');
        $deleteOrder->execute(['id' => $orderId]);
        throw $error;
    }

    return $orderId;
}

function getOrderStatus(PDO $pdo, int $orderId): string|false
{
    $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = :id');
    $stmt->execute(['id' => $orderId]);
    return $stmt->fetchColumn();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    fwrite(STDERR, 'config/database.php chưa tạo kết nối PDO trong biến $pdo.' . PHP_EOL);
    exit(2);
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$orderModel = new Order($pdo);

try {
    $userId = (int)$pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetchColumn();
    $categoryId = (int)$pdo->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
    if ($userId < 1 || $categoryId < 1) {
        throw new RuntimeException('CSDL chưa có tài khoản admin hoặc danh mục để tạo dữ liệu kiểm thử.');
    }

    $product = $pdo->prepare(
        'INSERT INTO products (name, slug, price, category_id, status)
         VALUES (:name, :slug, 500, :category_id, 1)'
    );
    $product->execute([
        'name' => 'Sản phẩm kiểm thử TV6',
        'slug' => strtolower($fixtureMarker),
        'category_id' => $categoryId,
    ]);
    $fixtureProductId = (int)$pdo->lastInsertId();

    $variant = $pdo->prepare(
        "INSERT INTO product_variants (product_id, size, color, stock) VALUES (:product_id, 'M', 'Test', 10)"
    );
    $variant->execute(['product_id' => $fixtureProductId]);
    $variantId = (int)$pdo->lastInsertId();

    $fixtureOrderIds['flow'] = createFixtureOrder($pdo, $userId, $fixtureProductId, $variantId, $fixtureMarker . '-flow');
    $fixtureOrderIds['invalid'] = createFixtureOrder($pdo, $userId, $fixtureProductId, $variantId, $fixtureMarker . '-invalid');
    $fixtureOrderIds['cancel'] = createFixtureOrder($pdo, $userId, $fixtureProductId, $variantId, $fixtureMarker . '-cancel');

    runTest('TV6-W3-01: Luồng pending → confirmed → shipping → completed', function () use ($pdo, $orderModel, $fixtureOrderIds, $fixtureMarker): void {
        $orderId = $fixtureOrderIds['flow'];
        foreach (['confirmed', 'shipping', 'completed'] as $nextStatus) {
            expectSame(true, $orderModel->updateStatus($orderId, $nextStatus), 'Chuyển trạng thái hợp lệ phải thành công');
            expectSame($nextStatus, getOrderStatus($pdo, $orderId), 'CSDL phải lưu trạng thái mới');
            expectSame($nextStatus, $orderModel->getDetails($orderId)['status'], 'Trang chi tiết phải đọc trạng thái mới');

            $matchingOrders = $orderModel->getList($nextStatus, 100, 0, $fixtureMarker);
            $found = false;
            foreach ($matchingOrders as $order) {
                if ((int)$order['id'] === $orderId) $found = true;
            }
            expectSame(true, $found, 'Danh sách lọc phải chứa đơn vừa đổi trạng thái');
        }
    });

    runTest('TV6-W3-02: Chặn bước trạng thái sai và trạng thái kết thúc', function () use ($pdo, $orderModel, $fixtureOrderIds): void {
        $invalidOrderId = $fixtureOrderIds['invalid'];
        expectSame(false, $orderModel->updateStatus($invalidOrderId, 'completed'), 'Không được nhảy từ pending sang completed');
        expectSame('pending', getOrderStatus($pdo, $invalidOrderId), 'Đơn phải giữ nguyên pending');
        expectSame(false, $orderModel->updateStatus($fixtureOrderIds['flow'], 'shipping'), 'Không được sửa ngược đơn completed');
        expectSame('completed', getOrderStatus($pdo, $fixtureOrderIds['flow']), 'Đơn completed phải giữ nguyên');
    });

    runTest('TV6-W3-03: CSRF thiếu hoặc sai bị từ chối', function () use ($fixtureOrderIds, $pdo, $variantId): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        $stockBefore = (int)$pdo->query('SELECT stock FROM product_variants WHERE id = ' . (int)$variantId)->fetchColumn();
        expectSame(true, verifyCsrfToken($_SESSION['csrf_token']), 'Token hợp lệ phải được chấp nhận');
        expectSame(false, verifyCsrfToken('token-sai'), 'Token sai phải bị từ chối');
        expectSame(false, verifyCsrfToken(''), 'Token trống phải bị từ chối');
        expectSame('pending', getOrderStatus($pdo, $fixtureOrderIds['invalid']), 'Kiểm tra CSRF không được đổi trạng thái đơn');
        expectSame($stockBefore, (int)$pdo->query('SELECT stock FROM product_variants WHERE id = ' . (int)$variantId)->fetchColumn(), 'Kiểm tra CSRF không được đổi tồn kho');
    });

    runTest('TV6-W3-04: Hủy đơn hoàn lại tồn kho đúng một lần', function () use ($pdo, $orderModel, $fixtureOrderIds, $variantId): void {
        $stockBefore = (int)$pdo->query('SELECT stock FROM product_variants WHERE id = ' . (int)$variantId)->fetchColumn();
        $orderId = $fixtureOrderIds['cancel'];
        expectSame(true, $orderModel->updateStatus($orderId, 'cancelled'), 'Hủy đơn pending phải thành công');
        $stockAfter = (int)$pdo->query('SELECT stock FROM product_variants WHERE id = ' . (int)$variantId)->fetchColumn();
        expectSame($stockBefore + 2, $stockAfter, 'Tồn kho phải được cộng lại đúng số lượng');
        expectSame(false, $orderModel->updateStatus($orderId, 'shipping'), 'Không được đổi đơn cancelled sang trạng thái khác');
        expectSame($stockAfter, (int)$pdo->query('SELECT stock FROM product_variants WHERE id = ' . (int)$variantId)->fetchColumn(), 'Không được hoàn kho lần thứ hai');
    });

    runTest('TV6-W3-05: Dashboard khớp số liệu CSDL', function () use ($pdo, $orderModel): void {
        $stats = $orderModel->getDashboardStats();
        $expected = [
            'total_orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'pending_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
            'confirmed_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'confirmed'")->fetchColumn(),
            'shipping_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'shipping'")->fetchColumn(),
            'completed_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn(),
            'cancelled_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn(),
            'revenue' => (float)$pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'completed'")->fetchColumn(),
            'total_customers' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
            'low_stock_products' => (int)$pdo->query(
                "SELECT COUNT(*) FROM (
                    SELECT p.id FROM products p
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    WHERE p.status = 1 GROUP BY p.id
                    HAVING COALESCE(SUM(pv.stock), 0) <= 10
                ) AS low_stock_products"
            )->fetchColumn(),
        ];

        foreach ($expected as $key => $value) {
            expectSame($value, (float)$stats[$key], 'Dashboard sai chỉ số ' . $key);
        }
        expectSame(1, $orderModel->count('completed', 'TV6-W3'), 'Bộ lọc tìm kiếm/trạng thái phải đếm được đơn test hoàn thành');
    });

    runTest('TV6-W3-06: Chi tiết và danh sách cùng hiển thị trạng thái mới', function () use ($orderModel, $fixtureOrderIds, $fixtureMarker): void {
        $orderId = $fixtureOrderIds['flow'];
        expectSame('completed', $orderModel->getDetails($orderId)['status'], 'Chi tiết đơn phải hiện completed');
        $orders = $orderModel->getList('completed', 100, 0, $fixtureMarker);
        $found = false;
        foreach ($orders as $order) {
            if ((int)$order['id'] === $orderId && $order['status'] === 'completed') $found = true;
        }
        expectSame(true, $found, 'Danh sách đơn phải hiện completed');
    });
} catch (Throwable $error) {
    fwrite(STDERR, 'Không thể chuẩn bị kiểm thử: ' . $error->getMessage() . "\n");
    $tests[] = ['Chuẩn bị dữ liệu kiểm thử', false, $error->getMessage()];
} finally {
    foreach ($fixtureOrderIds as $orderId) {
        $stmt = $pdo->prepare('DELETE FROM orders WHERE id = :id');
        $stmt->execute(['id' => $orderId]);
    }
    if ($fixtureProductId > 0) {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $fixtureProductId]);
    }
}

$passed = 0;
foreach ($tests as [$name, $success, $detail]) {
    if ($success) $passed++;
    echo sprintf("[%s] %s — %s\n", $success ? 'PASS' : 'FAIL', $name, $detail);
}

$total = count($tests);
echo "Kết quả: {$passed}/{$total} test đạt.\n";
exit($passed === $total && $total > 0 ? 0 : 1);
