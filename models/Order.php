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
    // =========================================
    // 1. KIỂM TRA GIỎ HÀNG
    // =========================================
    if (empty($items)) {
        throw new Exception('Giỏ hàng không có sản phẩm.');
    }

    // =========================================
    // 2. KIỂM TRA THÔNG TIN NHẬN HÀNG
    // =========================================
    $requiredFields = ['receiver_name', 'phone', 'address'];

    foreach ($requiredFields as $field) {
        if (
            !isset($orderData[$field]) ||
            !is_string($orderData[$field])
        ) {
            throw new InvalidArgumentException(
                'Vui lòng nhập đầy đủ thông tin nhận hàng.'
            );
        }
    }

    $receiverName = trim($orderData['receiver_name']);
    $phone = trim($orderData['phone']);
    $address = trim($orderData['address']);

    if (
        $receiverName === '' ||
        $phone === '' ||
        $address === ''
    ) {
        throw new InvalidArgumentException(
            'Thông tin nhận hàng không được để trống.'
        );
    }

    if (
        mb_strlen($receiverName) > 150 ||
        mb_strlen($address) > 500
    ) {
        throw new InvalidArgumentException(
            'Thông tin người nhận vượt quá độ dài cho phép.'
        );
    }

    if (!preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $phone)) {
        throw new InvalidArgumentException(
            'Số điện thoại không hợp lệ.'
        );
    }

    // =========================================
    // 3. KIỂM TRA TÀI KHOẢN
    // =========================================
    $userId = (int)($orderData['user_id'] ?? 0);

    if ($userId <= 0) {
        throw new InvalidArgumentException(
            'Bạn cần đăng nhập để đặt hàng.'
        );
    }

    // =========================================
    // 4. KIỂM TRA PHƯƠNG THỨC THANH TOÁN
    // =========================================
    $paymentMethod = $orderData['payment_method'] ?? '';

    $allowedMethods = ['cod', 'bank'];

    if (!in_array($paymentMethod, $allowedMethods, true)) {
        throw new InvalidArgumentException(
            'Phương thức thanh toán không hợp lệ.'
        );
    }

    // =========================================
    // 5. KIỂM TRA TRANSACTION
    // =========================================
    if ($this->pdo->inTransaction()) {
        throw new Exception(
            'Kết nối đang có một Transaction khác.'
        );
    }

    try {
        $this->pdo->beginTransaction();

        $mergedItems = [];
        $validatedItems = [];
        $total = 0;

        // =========================================
        // 6. KIỂM TRA VÀ GỘP SẢN PHẨM TRÙNG BIẾN THỂ
        // =========================================
        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new Exception(
                    'Dữ liệu sản phẩm trong giỏ hàng không hợp lệ.'
                );
            }

            $variantId = filter_var(
                $item['variant_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $quantity = filter_var(
                $item['quantity'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                $variantId === false ||
                $variantId <= 0 ||
                $quantity === false ||
                $quantity <= 0 ||
                $quantity > 100
            ) {
                throw new Exception(
                    'Thông tin sản phẩm trong giỏ hàng không hợp lệ.'
                );
            }

            if (!isset($mergedItems[$variantId])) {
                $mergedItems[$variantId] = [
                    'variant_id' => $variantId,
                    'quantity' => 0
                ];
            }

            // Giới hạn tổng số lượng của một biến thể
            if (
                $mergedItems[$variantId]['quantity'] >
                100 - $quantity
            ) {
                throw new Exception(
                    'Tổng số lượng một sản phẩm không được vượt quá 100.'
                );
            }

            $mergedItems[$variantId]['quantity'] += $quantity;
        }

        // =========================================
        // 7. TRUY VẤN VÀ KHÓA BIẾN THỂ SẢN PHẨM
        // =========================================
        $variantSql = "
            SELECT
                pv.id AS variant_id,
                pv.product_id,
                pv.stock,
                p.price,
                p.sale_price,
                p.status
            FROM product_variants pv
            INNER JOIN products p
                ON p.id = pv.product_id
            WHERE pv.id = :variant_id
            LIMIT 1
            FOR UPDATE
        ";

        $variantStmt = $this->pdo->prepare($variantSql);

        foreach ($mergedItems as $item) {
            $variantStmt->execute([
                'variant_id' => $item['variant_id']
            ]);

            $variant = $variantStmt->fetch(PDO::FETCH_ASSOC);

            // Kiểm tra biến thể tồn tại
            if (!$variant) {
                throw new Exception(
                    'Không tìm thấy biến thể sản phẩm.'
                );
            }

            // Kiểm tra sản phẩm đang hoạt động
            if ((int)$variant['status'] !== 1) {
                throw new Exception(
                    'Sản phẩm hiện không còn được kinh doanh.'
                );
            }

            // Kiểm tra tồn kho
            $stock = (int)$variant['stock'];

            if ($stock < $item['quantity']) {
                throw new Exception(
                    'Sản phẩm mã #' . $variant['product_id']
                    . ' không đủ số lượng tồn kho.'
                );
            }

            // =========================================
            // 8. TÍNH GIÁ SẢN PHẨM TỪ DATABASE
            // =========================================
            $price = (float)$variant['price'];
            $salePrice = (float)($variant['sale_price'] ?? 0);

            if (!is_finite($price) || $price <= 0) {
                throw new Exception(
                    'Giá sản phẩm không hợp lệ.'
                );
            }

            if (!is_finite($salePrice) || $salePrice < 0) {
                throw new Exception(
                    'Giá khuyến mãi không hợp lệ.'
                );
            }

            // Chỉ áp dụng giá khuyến mãi hợp lệ
            if ($salePrice > 0 && $salePrice < $price) {
                $price = $salePrice;
            }

            $quantity = $item['quantity'];

            $validatedItems[] = [
                'product_id' => (int)$variant['product_id'],
                'variant_id' => (int)$variant['variant_id'],
                'quantity' => $quantity,
                'price' => $price
            ];

            $total += $price * $quantity;

            if (!is_finite($total)) {
                throw new Exception(
                    'Tổng giá trị đơn hàng không hợp lệ.'
                );
            }
        }

        if ($total <= 0) {
            throw new Exception(
                'Tổng giá trị đơn hàng phải lớn hơn 0.'
            );
        }

        // =========================================
        // 9. TẠO ĐƠN HÀNG
        // =========================================
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
            'user_id' => $userId,
            'receiver_name' => $receiverName,
            'phone' => $phone,
            'address' => $address,
            'total' => $total,
            'payment_method' => $paymentMethod,

            // Chưa triển khai nghiệp vụ mã giảm giá
            'coupon_id' => null
        ]);

        $orderId = (int)$this->pdo->lastInsertId();

        if ($orderId <= 0) {
            throw new Exception(
                'Không thể tạo đơn hàng.'
            );
        }

        // =========================================
        // 10. CHUẨN BỊ INSERT CHI TIẾT ĐƠN HÀNG
        // =========================================
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

        // =========================================
        // 11. CHUẨN BỊ TRỪ TỒN KHO
        // =========================================
        $stockSql = "
            UPDATE product_variants
            SET stock = stock - :deduct_quantity
            WHERE id = :variant_id
              AND stock >= :check_quantity
        ";

        $stockStmt = $this->pdo->prepare($stockSql);

        // =========================================
        // 12. INSERT CHI TIẾT VÀ TRỪ TỒN KHO
        // =========================================
        foreach ($validatedItems as $item) {

            // Thêm chi tiết đơn hàng
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ]);

            // Trừ tồn kho
            $stockStmt->execute([
                'deduct_quantity' => $item['quantity'],
                'variant_id' => $item['variant_id'],
                'check_quantity' => $item['quantity']
            ]);

            if ($stockStmt->rowCount() !== 1) {
                throw new Exception(
                    'Không thể cập nhật tồn kho. Vui lòng thử lại.'
                );
            }
        }

        // =========================================
        // 13. HOÀN TẤT TRANSACTION
        // =========================================
        $this->pdo->commit();

        return $orderId;

    } catch (Throwable $e) {

        // Có lỗi thì hoàn tác toàn bộ thao tác
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
    int $limit = 50,
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
COALESCE(SUM(oi.quantity), 0) AS total_items
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
        $key === 'user_id'
            ? PDO::PARAM_INT
            : PDO::PARAM_STR
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
        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
              AND pi.is_main = 1
            LIMIT 1
        ) AS image_path
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_variants pv ON oi.variant_id = pv.id
    WHERE oi.order_id = :order_id
";

    $itemsStmt = $this->pdo->prepare($itemsSql);
    $itemsStmt->execute(['order_id' => $orderId]);

    $order['items'] = $itemsStmt->fetchAll();

    return $order;
}
    public function getList(string $status = 'all', int $limit = 50, int $offset = 0): array
{
    $params = [];
    $allowedStatuses = [
    'pending',
    'confirmed',
    'shipping',
    'completed',
    'cancelled'
];

if (
    $status !== '' &&
    $status !== 'all' &&
    !in_array($status, $allowedStatuses, true)
) {
    $status = 'all';
}
    $where = "WHERE 1=1";
    if ($status !== '' && $status !== 'all') {
        $where .= " AND o.status = :status";
        $params['status'] = $status;
    }
    $limit = max(1, min($limit, 50));
$offset = max(0, $offset);
    $sql = "SELECT o.*, u.full_name AS user_name, u.email AS user_email, COALESCE(SUM(oi.quantity), 0) AS total_items
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
public function belongsToUser(int $orderId, int $userId): bool
{
    if ($orderId <= 0 || $userId <= 0) {
        return false;
    }

    $stmt = $this->pdo->prepare("
        SELECT id
        FROM orders
        WHERE id = :order_id
          AND user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'order_id' => $orderId,
        'user_id' => $userId
    ]);

    return (bool)$stmt->fetchColumn();
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
        throw new InvalidArgumentException(
            'Thông tin đơn hàng không hợp lệ.'
        );
    }

    if ($this->pdo->inTransaction()) {
        throw new Exception(
            'Kết nối đang có một Transaction khác.'
        );
    }

    $this->pdo->beginTransaction();

    try {
        // 1. Khóa đơn hàng và kiểm tra quyền sở hữu
        $checkStmt = $this->pdo->prepare("
            SELECT status
            FROM orders
            WHERE id = :id
              AND user_id = :user_id
            LIMIT 1
            FOR UPDATE
        ");

        $checkStmt->execute([
            'id' => $orderId,
            'user_id' => $userId
        ]);

        $status = $checkStmt->fetchColumn();

        if ($status === false) {
            throw new Exception(
                'Không tìm thấy đơn hàng hoặc bạn không có quyền thực hiện.'
            );
        }

        // 2. Chỉ cho phép hủy đơn pending
        if ($status !== 'pending') {
            throw new Exception(
                'Chỉ có thể hủy đơn hàng đang chờ xác nhận.'
            );
        }

        // 3. Lấy danh sách sản phẩm trong đơn
        $itemsStmt = $this->pdo->prepare("
            SELECT variant_id, quantity
            FROM order_items
            WHERE order_id = :order_id
        ");

        $itemsStmt->execute([
            'order_id' => $orderId
        ]);

        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            throw new Exception(
                'Đơn hàng không có sản phẩm để hoàn kho.'
            );
        }

        // 4. Chuẩn bị câu lệnh kiểm tra biến thể
        $variantStmt = $this->pdo->prepare("
            SELECT id
            FROM product_variants
            WHERE id = :variant_id
            FOR UPDATE
        ");

        // 5. Chuẩn bị câu lệnh hoàn kho
        $restoreStmt = $this->pdo->prepare("
            UPDATE product_variants
            SET stock = stock + :quantity
            WHERE id = :variant_id
        ");

        foreach ($items as $item) {
            $variantId = (int)($item['variant_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);

            if ($variantId <= 0 || $quantity <= 0) {
                throw new Exception(
                    'Thông tin sản phẩm trong đơn hàng không hợp lệ.'
                );
            }

            // Khóa và xác minh biến thể
            $variantStmt->execute([
                'variant_id' => $variantId
            ]);

            if ($variantStmt->fetchColumn() === false) {
                throw new Exception(
                    'Không tìm thấy biến thể sản phẩm để hoàn kho.'
                );
            }

            // Hoàn lại tồn kho
            $restoreStmt->execute([
                'quantity' => $quantity,
                'variant_id' => $variantId
            ]);

            if ($restoreStmt->rowCount() !== 1) {
                throw new Exception(
                    'Không thể hoàn lại tồn kho sản phẩm.'
                );
            }
        }

        // 6. Cập nhật trạng thái đơn hàng
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
            throw new Exception(
                'Không thể hủy đơn hàng. Vui lòng thử lại.'
            );
        }

        // 7. Commit transaction
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
