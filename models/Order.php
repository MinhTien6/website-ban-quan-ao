
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

               if ((int)$variant['status'] !== 1) {
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
   public function getByUserId(
    int $userId,
    ?string $status = null,
    int $limit = 10,
    int $offset = 0
): array {
    $allowedStatuses = [
        'pending',
        'confirmed',
        'shipping',
        'completed',
        'cancelled'
    ];

    if ($status !== null && !in_array($status, $allowedStatuses, true)) {
        $status = null;
    }

    $limit = max(1, min($limit, 50));
    $offset = max(0, $offset);

    $sql = "
        SELECT
            o.*,
            COUNT(oi.id) AS total_items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = :user_id
    ";

    $params = ['user_id' => $userId];

    if ($status !== null) {
        $sql .= " AND o.status = :status";
        $params['status'] = $status;
    }

    $sql .= "
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $this->pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(
            ':' . $key,
            $value,
            PDO::PARAM_INT
        );
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetchAll();
}
public function countByUserId(
    int $userId,
    ?string $status = null
): int {
    $allowedStatuses = [
        'pending',
        'confirmed',
        'shipping',
        'completed',
        'cancelled'
    ];

    if ($status !== null && !in_array($status, $allowedStatuses, true)) {
        $status = null;
    }

    $sql = "
        SELECT COUNT(*)
        FROM orders
        WHERE user_id = :user_id
    ";

    $params = ['user_id' => $userId];

    if ($status !== null) {
        $sql .= " AND status = :status";
        $params['status'] = $status;
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}

    // Lấy chi tiết đơn hàng
   public function getDetails(
    int $orderId,
    ?int $userId = null
): array|false {

    $sql = "
        SELECT
            o.*,
            u.full_name AS customer_name,
            u.email AS customer_email
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = :id
    ";

    $params = ['id' => $orderId];

    // Nếu truyền userId thì chỉ lấy đơn của người dùng đó.
    if ($userId !== null) {
        $sql .= " AND o.user_id = :user_id";
        $params['user_id'] = $userId;
    }

    $sql .= " LIMIT 1";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);

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
    public function getList(string $status = 'all', int $limit = 10, int $offset = 0): array
{
    $params = [];
    $where = "WHERE 1=1";
    if ($status !== '' && $status !== 'all') {
        $where .= " AND o.status = :status";
        $params['status'] = $status;
    }
    $sql = "SELECT o.*, u.full_name AS user_name, u.email AS user_email, COUNT(oi.id) AS total_items
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            {$where}
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT {$limit} OFFSET {$offset}";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
public function count(string $status = 'all'): int
{
    $params = [];
    $where = "WHERE 1=1";
    if ($status !== '' && $status !== 'all') {
        $where .= " AND status = :status";
        $params['status'] = $status;
    }
    $sql = "SELECT COUNT(*) FROM orders {$where}";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
public function updateStatus(int $id, string $status): bool
{
    $allowed = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }
    $sql = "UPDATE orders SET status = :status WHERE id = :id";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute(['id' => $id, 'status' => $status]);
}


    // Hủy đơn hàng đang chờ xác nhận
    // Hủy đơn hàng đang chờ xác nhận
public function cancelByUser(int $orderId, int $userId): bool
{
    if ($orderId <= 0 || $userId <= 0) {
        throw new InvalidArgumentException('Thông tin đơn hàng không hợp lệ.');
    }

    if ($this->pdo->inTransaction()) {
        throw new Exception('Kết nối đang có một Transaction khác.');
    }

    $this->pdo->beginTransaction();

    try {
        // 1. Kiểm tra quyền sở hữu và khóa đơn hàng
        $checkSql = "
            SELECT status
            FROM orders
            WHERE id = :id
              AND user_id = :user_id
            LIMIT 1
            FOR UPDATE
        ";

        $checkStmt = $this->pdo->prepare($checkSql);
        $checkStmt->execute([
            'id' => $orderId,
            'user_id' => $userId
        ]);

        $status = $checkStmt->fetchColumn();

        if ($status === false) {
            throw new Exception('Không tìm thấy đơn hàng hoặc bạn không có quyền thực hiện.');
        }

        // 2. Chỉ cho phép hủy đơn đang chờ xác nhận
        if ($status !== 'pending') {
            throw new Exception('Chỉ có thể hủy đơn hàng đang chờ xác nhận.');
        }

        // 3. Lấy danh sách sản phẩm trong đơn
        $itemsStmt = $this->pdo->prepare("
            SELECT variant_id, quantity
            FROM order_items
            WHERE order_id = :order_id
        ");

        $itemsStmt->execute(['order_id' => $orderId]);

        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Hoàn lại tồn kho
        $restoreStmt = $this->pdo->prepare("
            UPDATE product_variants
            SET stock = stock + :quantity
            WHERE id = :variant_id
        ");

        foreach ($items as $item) {
            $variantId = (int)($item['variant_id'] ?? 0);
            $quantity = (int)$item['quantity'];

            // Biến thể đã bị xóa hoặc không còn liên kết
            if ($variantId <= 0) {
                continue;
            }

            if ($quantity <= 0) {
                throw new Exception('Số lượng sản phẩm trong đơn không hợp lệ.');
            }

            $restoreStmt->execute([
                'quantity' => $quantity,
                'variant_id' => $variantId
            ]);

            if ($restoreStmt->rowCount() !== 1) {
                throw new Exception('Không thể hoàn lại tồn kho sản phẩm.');
            }
        }

        // 5. Chuyển trạng thái đơn hàng thành cancelled
        $updateStmt = $this->pdo->prepare("
            UPDATE orders
            SET status = 'cancelled'
            WHERE id = :id
              AND user_id = :user_id
              AND status = 'pending'
        ");

        $updateStmt->execute([
            'id' => $orderId,
            'user_id' => $userId
        ]);

        if ($updateStmt->rowCount() !== 1) {
            throw new Exception('Không thể hủy đơn hàng. Vui lòng thử lại.');
        }

        // 6. Hoàn tất transaction
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
