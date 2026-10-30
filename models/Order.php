
<?php
/**
 * Order Model
 * TV5 - Xử lý luồng đặt hàng trong Tuần 1
 */
class Order
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Tạo đơn hàng và trừ tồn kho bằng Transaction.
     *
     * @param array $orderData
     * @param array $items
     * @return int ID đơn hàng
     * @throws Exception
     */
    public function createOrder(array $orderData, array $items): int
    {
        if (empty($items)) {
            throw new Exception('Giỏ hàng không có sản phẩm.');
        }

        // Kiểm tra thông tin nhận hàng
$requiredFields = ['receiver_name', 'phone', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($orderData[$field])) {
                throw new Exception('Vui lòng nhập đầy đủ thông tin nhận hàng.');
            }
        }

      $paymentMethod = $orderData['payment_method'] ?? '';

$allowedMethods = ['cod', 'bank'];

if (!in_array($paymentMethod, $allowedMethods, true)) {
    throw new InvalidArgumentException(
        'Phương thức thanh toán không hợp lệ.'
    );
}

       

        if ($this->pdo->inTransaction()) {
            throw new Exception('Kết nối đang có một Transaction khác.');
        }

        $this->pdo->beginTransaction();

        try {
            $validatedItems = [];
            $total = 0;

            /*
             * Gộp các sản phẩm trùng biến thể.
             * Một biến thể chỉ được xử lý một lần.
             */
            $mergedItems = [];

            foreach ($items as $item) {
                $variantId = (int)($item['variant_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);

                if ($variantId <= 0 || $quantity <= 0) {
                    throw new Exception('Thông tin sản phẩm trong giỏ hàng không hợp lệ.');
                }

                if (!isset($mergedItems[$variantId])) {
                    $mergedItems[$variantId] = [
                        'variant_id' => $variantId,
                        'quantity' => 0
                    ];
                }

                $mergedItems[$variantId]['quantity'] += $quantity;
            }

            /*
             * Kiểm tra biến thể, sản phẩm và tồn kho.
             * FOR UPDATE khóa dòng tồn kho trong Transaction.
             */
            $variantSql = "
                SELECT
                    pv.id AS variant_id,
                    pv.product_id,
                    pv.stock,
                    p.price,
                    p.sale_price,
                    p.status
                FROM product_variants pv
                INNER JOIN products p ON p.id = pv.product_id
                WHERE pv.id = :variant_id
                LIMIT 1
                FOR UPDATE
            ";

            $variantStmt = $this->pdo->prepare($variantSql);

            foreach ($mergedItems as $item) {
                $variantStmt->execute([
                    'variant_id' => $item['variant_id']
                ]);

                $variant = $variantStmt->fetch();

                if (!$variant) {
                    throw new Exception('Không tìm thấy biến thể sản phẩm.');
                }

                if ($variant['status'] !== 1    ) {
                    throw new Exception('Sản phẩm hiện không còn được kinh doanh.');
                }

                if ((int)$variant['stock'] < $item['quantity']) {
                    throw new Exception(
                        'Sản phẩm mã #' . $variant['product_id']
                        . ' không đủ số lượng tồn kho.'
                    );
                }

                $price = (float)$variant['price'];
                $salePrice = (float)($variant['sale_price'] ?? 0);

                if ($salePrice > 0 && $salePrice < $price) {
                    $price = $salePrice;
                }

                $validatedItems[] = [
                    'product_id' => (int)$variant['product_id'],
                    'variant_id' => (int)$variant['variant_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price
                ];

                $total += $price * $item['quantity'];
            }

            // Tạo đơn hàng
            $orderSql = "
                INSERT INTO orders (
                    user_id,
                    receiver_name,
                    phone,
                    address,
                    total,
                    payment_method,
                    status,
                    coupon_id,
                    created_at
                )
                VALUES (
                    :user_id,
                    :receiver_name,
                    :phone,
                    :address,
                    :total,
                    :payment_method,
                    'pending',
                    :coupon_id,
                    NOW()
                )
            ";

            $orderStmt = $this->pdo->prepare($orderSql);

           $orderStmt->execute([
    'user_id' => !empty($orderData['user_id'])
        ? (int)$orderData['user_id']
        : null,
    'receiver_name' => trim($orderData['receiver_name']),
    'phone' => trim($orderData['phone']),
    'address' => trim($orderData['address']),
    'total' => $total,
    'payment_method' => $paymentMethod,
    'coupon_id' => !empty($orderData['coupon_id'])
        ? (int)$orderData['coupon_id']
        : null
]);
            $orderId = (int)$this->pdo->lastInsertId();

            // Thêm chi tiết đơn hàng
            $itemSql = "
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    variant_id,
                    quantity,
                    price
                )
                VALUES (
                    :order_id,
                    :product_id,
                    :variant_id,
                    :quantity,
                    :price
                )
            ";

            $itemStmt = $this->pdo->prepare($itemSql);

            // Trừ tồn kho với hai placeholder riêng biệt
            $stockSql = "
                UPDATE product_variants
                SET stock = stock - :deduct_quantity
                WHERE id = :variant_id
                  AND stock >= :check_quantity
            ";

            $stockStmt = $this->pdo->prepare($stockSql);

            foreach ($validatedItems as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);

                $stockStmt->execute([
                    'deduct_quantity' => $item['quantity'],
                    'variant_id' => $item['variant_id'],
                    'check_quantity' => $item['quantity']
                ]);

                if ($stockStmt->rowCount() !== 1) {
                    throw new Exception('Không thể cập nhật tồn kho. Vui lòng thử lại.');
                }
            }

            $this->pdo->commit();

            return $orderId;

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // Lấy danh sách đơn hàng của khách hàng
    public function getByUserId(int $userId): array
    {
        $sql = "
            SELECT o.*, COUNT(oi.id) AS total_items
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = :user_id
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    // Lấy chi tiết đơn hàng
    public function getDetails(int $orderId): array|false
    {
        $sql = "
            SELECT
                o.*,
                u.full_name AS customer_name,
                u.email AS customer_email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $orderId]);

        $order = $stmt->fetch();

        if (!$order) {
            return false;
        }

        $itemsSql = "
            SELECT
                oi.*,
                p.name AS product_name,
                p.slug AS product_slug,
                pv.size,
                pv.color,
                pi.image_path
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_variants pv ON oi.variant_id = pv.id
            LEFT JOIN product_images pi
                ON p.id = pi.product_id AND pi.is_main = 1
            WHERE oi.order_id = :order_id
        ";

        $itemsStmt = $this->pdo->prepare($itemsSql);
        $itemsStmt->execute(['order_id' => $orderId]);

        $order['items'] = $itemsStmt->fetchAll();

        return $order;
    }

    // Hủy đơn hàng đang chờ xác nhận
    public function cancelByUser(int $orderId, int $userId): bool
    {
        $this->pdo->beginTransaction();

        try {
            $checkSql = "
                SELECT status
                FROM orders
                WHERE id = :id AND user_id = :user_id
                LIMIT 1
                FOR UPDATE
            ";

            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([
                'id' => $orderId,
                'user_id' => $userId
            ]);

            $status = $checkStmt->fetchColumn();

            if ($status !== 'pending') {
                throw new Exception('Chỉ có thể hủy đơn hàng đang chờ xác nhận.');
            }

            $itemsStmt = $this->pdo->prepare("
                SELECT variant_id, quantity
                FROM order_items
                WHERE order_id = :order_id
            ");

            $itemsStmt->execute(['order_id' => $orderId]);

            $restoreStmt = $this->pdo->prepare("
                UPDATE product_variants
                SET stock = stock + :quantity
                WHERE id = :variant_id
            ");

            foreach ($itemsStmt->fetchAll() as $item) {
                if (!empty($item['variant_id'])) {
                    $restoreStmt->execute([
                        'quantity' => (int)$item['quantity'],
                        'variant_id' => (int)$item['variant_id']
                    ]);
                }
            }

            $updateStmt = $this->pdo->prepare("
                UPDATE orders
                SET status = 'cancelled'
                WHERE id = :id AND user_id = :user_id
            ");

            $updateStmt->execute([
                'id' => $orderId,
                'user_id' => $userId
            ]);

            $this->pdo->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
