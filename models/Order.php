<?php

class Order
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    

    public function getCouponDiscount(
        string $code,
        float $subtotal
    ): array {
        $code = strtoupper(trim($code));

        if ($code === '') {
            throw new InvalidArgumentException(
                'Vui lòng nhập mã giảm giá.'
            );
        }

        if (!is_finite($subtotal) || $subtotal <= 0) {
            throw new InvalidArgumentException(
                'Tổng tiền đơn hàng không hợp lệ.'
            );
        }

        
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                code,
                discount_type,
                discount_value,
                min_order,
                expires_at,
                usage_limit
            FROM coupons
            WHERE code = :code
            LIMIT 1
        ");

        $stmt->execute(['code' => $code]);

        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coupon) {
            throw new Exception('Mã giảm giá không tồn tại.');
        }

        
        if (
            !empty($coupon['expires_at']) &&
            strtotime($coupon['expires_at']) < time()
        ) {
            throw new Exception('Mã giảm giá đã hết hạn.');
        }

        
        $minOrder = (float)$coupon['min_order'];

        if ($subtotal < $minOrder) {
            throw new Exception(
                'Đơn hàng cần đạt tối thiểu '
                . number_format($minOrder, 0, ',', '.')
                . 'đ để sử dụng mã này.'
            );
        }

        
        $usageLimit = (int)$coupon['usage_limit'];

        if ($usageLimit > 0) {
            $usageStmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM orders
                WHERE coupon_id = :coupon_id
                  AND status <> 'cancelled'
            ");

            $usageStmt->execute([
                'coupon_id' => (int)$coupon['id']
            ]);

            $usedCount = (int)$usageStmt->fetchColumn();

            if ($usedCount >= $usageLimit) {
                throw new Exception(
                    'Mã giảm giá đã hết lượt sử dụng.'
                );
            }
        }

        
        $discountValue = (float)$coupon['discount_value'];

        if (!is_finite($discountValue) || $discountValue <= 0) {
            throw new Exception(
                'Giá trị mã giảm giá không hợp lệ.'
            );
        }

        if ($coupon['discount_type'] === 'percent') {
            if ($discountValue > 100) {
                throw new Exception(
                    'Giá trị phần trăm giảm giá không hợp lệ.'
                );
            }

            $discount = round(
                $subtotal * $discountValue / 100
            );
        } elseif ($coupon['discount_type'] === 'amount') {
            $discount = round($discountValue);
        } else {
            throw new Exception(
                'Loại mã giảm giá không hợp lệ.'
            );
        }

        
        $discount = min($discount, $subtotal);
        $shippingFee = ($subtotal >= 399000 || $subtotal == 0) ? 0 : 30000;

        return [
            'coupon_id' => (int)$coupon['id'],
            'code' => $coupon['code'],
            'discount_type' => $coupon['discount_type'],
            'discount_value' => $discountValue,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'final_total' => max(0, $subtotal - $discount) + $shippingFee
        ];
    }

    

    public function createOrder(
        array $orderData,
        array $items,
        ?string $couponCode = null
    ): int {
        
        if (empty($items)) {
            throw new Exception('Giỏ hàng không có sản phẩm.');
        }

        
        $requiredFields = ['receiver_name', 'phone', 'address'];
        foreach ($requiredFields as $field) {
            if (!isset($orderData[$field]) || !is_string($orderData[$field])) {
                throw new Exception("Thiếu thông tin: {$field}");
            }
        }

        $receiverName = trim($orderData['receiver_name']);
        $phone        = trim($orderData['phone']);
        $address      = trim($orderData['address']);
        $paymentMethod = $orderData['payment_method'] ?? 'cod';
        $userId       = (int)($orderData['user_id'] ?? 0);

        if ($receiverName === '') {
            throw new Exception('Vui lòng nhập tên người nhận.');
        }

        if ($phone === '') {
            throw new Exception('Vui lòng nhập số điện thoại.');
        }

        if (!preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $phone)) {
            throw new Exception('Số điện thoại không đúng định dạng 10 số tại Việt Nam.');
        }

        if ($address === '') {
            throw new Exception('Vui lòng nhập địa chỉ nhận hàng.');
        }

        if (!in_array($paymentMethod, ['cod', 'bank'], true)) {
            throw new Exception('Phương thức thanh toán không hợp lệ.');
        }

        if ($userId <= 0) {
            throw new Exception('Bạn cần đăng nhập để đặt hàng.');
        }

        
        if ($this->pdo->inTransaction()) {
            throw new Exception('Kết nối cơ sở dữ liệu đang có một Transaction khác.');
        }

        $this->pdo->beginTransaction();

        try {
            $total = 0;
            $orderItems = [];

            
            $variantStmt = $this->pdo->prepare("
                SELECT
                    pv.id,
                    pv.product_id,
                    pv.size,
                    pv.color,
                    pv.stock,
                    p.name,
                    p.price,
                    p.sale_price,
                    p.status
                FROM product_variants pv
                INNER JOIN products p ON p.id = pv.product_id
                WHERE pv.id = :id
                LIMIT 1
                FOR UPDATE
            ");

            
            foreach ($items as $item) {
                $variantId = (int)($item['variant_id'] ?? 0);
                $quantity  = (int)($item['quantity'] ?? 0);

                if ($variantId <= 0 || $quantity <= 0) {
                    throw new Exception('Sản phẩm trong giỏ hàng không hợp lệ.');
                }

                $variantStmt->execute(['id' => $variantId]);
                $variant = $variantStmt->fetch(PDO::FETCH_ASSOC);

                if (!$variant) {
                    throw new Exception('Không tìm thấy sản phẩm trong hệ thống.');
                }

                if ((int)$variant['status'] !== 1) {
                    throw new Exception("Sản phẩm \"{$variant['name']}\" hiện đang tạm dừng bán.");
                }

                $stock = (int)$variant['stock'];
                if ($stock < $quantity) {
                    throw new Exception(
                        "Sản phẩm \"{$variant['name']} ({$variant['size']} - {$variant['color']})\" chỉ còn {$stock} sản phẩm trong kho."
                    );
                }

                
                $price = (float)$variant['price'];
                $salePrice = (float)($variant['sale_price'] ?? 0);
                if ($salePrice > 0 && $salePrice < $price) {
                    $price = $salePrice;
                }

                $subtotal = $price * $quantity;
                $total += $subtotal;

                $orderItems[] = [
                    'product_id' => (int)$variant['product_id'],
                    'variant_id' => $variantId,
                    'quantity'   => $quantity,
                    'price'      => $price,
                ];
            }

            
            $rawSubtotal = $total;
            $couponId = null;
            $discount = 0;
            $couponCode = strtoupper(trim($couponCode ?? ''));

            if ($couponCode !== '') {
                $couponStmt = $this->pdo->prepare("
                    SELECT
                        id,
                        code,
                        discount_type,
                        discount_value,
                        min_order,
                        expires_at,
                        usage_limit
                    FROM coupons
                    WHERE code = :code
                    LIMIT 1
                    FOR UPDATE
                ");

                $couponStmt->execute(['code' => $couponCode]);
                $coupon = $couponStmt->fetch(PDO::FETCH_ASSOC);

                if (!$coupon) {
                    throw new Exception('Mã giảm giá không tồn tại.');
                }

                
                $expiryStmt = $this->pdo->prepare("
                    SELECT id
                    FROM coupons
                    WHERE id = :id
                      AND expires_at IS NOT NULL
                      AND expires_at <= NOW()
                    LIMIT 1
                ");
                $expiryStmt->execute(['id' => (int)$coupon['id']]);
                if ($expiryStmt->fetchColumn()) {
                    throw new Exception('Mã giảm giá đã hết hạn.');
                }

                
                $minOrder = (float)$coupon['min_order'];
                if ($total < $minOrder) {
                    throw new Exception(
                        'Đơn hàng chưa đạt giá trị tối thiểu '
                        . number_format($minOrder, 0, ',', '.')
                        . 'đ để sử dụng mã này.'
                    );
                }

                
                $usageLimit = (int)$coupon['usage_limit'];
                if ($usageLimit > 0) {
                    $usageStmt = $this->pdo->prepare("
                        SELECT COUNT(*)
                        FROM orders
                        WHERE coupon_id = :coupon_id
                          AND status <> 'cancelled'
                    ");
                    $usageStmt->execute(['coupon_id' => (int)$coupon['id']]);
                    $usedCount = (int)$usageStmt->fetchColumn();

                    if ($usedCount >= $usageLimit) {
                        throw new Exception('Mã giảm giá đã hết lượt sử dụng.');
                    }
                }

                
                $discountValue = (float)$coupon['discount_value'];
                if (!is_finite($discountValue) || $discountValue <= 0) {
                    throw new Exception('Giá trị mã giảm giá không hợp lệ.');
                }

                if ($coupon['discount_type'] === 'percent') {
                    if ($discountValue > 100) {
                        throw new Exception('Phần trăm giảm giá không hợp lệ.');
                    }
                    $discount = round($total * $discountValue / 100);
                } elseif ($coupon['discount_type'] === 'amount') {
                    $discount = round($discountValue);
                } else {
                    throw new Exception('Loại mã giảm giá không hợp lệ.');
                }

                $discount = min($discount, $total);
                $couponId = (int)$coupon['id'];
                $total -= $discount;
            }

            $shippingFee = ($rawSubtotal >= 399000 || $rawSubtotal == 0) ? 0 : 30000;
            $total = max(0, $total) + $shippingFee;

            
            $orderStmt = $this->pdo->prepare("
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
                ) VALUES (
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
            ");

            $orderStmt->execute([
                'user_id'        => $userId,
                'receiver_name'  => $receiverName,
                'phone'          => $phone,
                'address'        => $address,
                'total'          => $total,
                'payment_method' => $paymentMethod,
                'coupon_id'      => $couponId,
            ]);

            $orderId = (int)$this->pdo->lastInsertId();

            if ($orderId <= 0) {
                throw new Exception('Không thể tạo đơn hàng.');
            }

            
            $itemStmt = $this->pdo->prepare("
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    variant_id,
                    quantity,
                    price
                ) VALUES (
                    :order_id,
                    :product_id,
                    :variant_id,
                    :quantity,
                    :price
                )
            ");

            $stockStmt = $this->pdo->prepare("
                UPDATE product_variants
                SET stock = stock - :quantity
                WHERE id = :id
                  AND stock >= :quantity_check
            ");

            foreach ($orderItems as $item) {
                $itemStmt->execute([
                    'order_id'   => $orderId,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);

                $stockStmt->execute([
                    'quantity'       => $item['quantity'],
                    'id'             => $item['variant_id'],
                    'quantity_check' => $item['quantity'],
                ]);

                if ($stockStmt->rowCount() !== 1) {
                    throw new Exception('Lỗi cập nhật số lượng tồn kho sản phẩm.');
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
                COALESCE(SUM(oi.quantity), 0) AS total_items,
                c.code AS coupon_code
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN coupons c ON o.coupon_id = c.id
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
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    

    public function getDetails(
        int $orderId,
        ?int $userId = null
    ): array|false {
        $sql = "
            SELECT
                o.*,
                u.full_name AS customer_name,
                u.email AS customer_email,
                c.code AS coupon_code,
                c.discount_type AS coupon_type,
                c.discount_value AS coupon_value
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN coupons c ON o.coupon_id = c.id
            WHERE o.id = :id
        ";

        $params = ['id' => $orderId];

        if ($userId !== null) {
            $sql .= " AND o.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $order = $stmt->fetch(PDO::FETCH_ASSOC);

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

        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }

    

    public function getList(string $status = 'all', int $limit = 10, int $offset = 0, string $keyword = ''): array
    {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);
        $params = [];
        $where = "WHERE 1=1";

        $allowedStatuses = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
        if ($status !== '' && $status !== 'all' && in_array($status, $allowedStatuses, true)) {
            $where .= " AND o.status = :status";
            $params['status'] = $status;
        }

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $where .= " AND (CAST(o.id AS CHAR) LIKE :keyword_id
                        OR o.receiver_name LIKE :keyword_receiver
                        OR o.phone LIKE :keyword_phone
                        OR u.full_name LIKE :keyword_name
                        OR u.email LIKE :keyword_email)";
            $likeKeyword = '%' . $keyword . '%';
            $params['keyword_id'] = $likeKeyword;
            $params['keyword_receiver'] = $likeKeyword;
            $params['keyword_phone'] = $likeKeyword;
            $params['keyword_name'] = $likeKeyword;
            $params['keyword_email'] = $likeKeyword;
        }

        $sql = "SELECT o.*, u.full_name AS user_name, u.email AS user_email,
                       COALESCE(SUM(oi.quantity), 0) AS total_items,
                       c.code AS coupon_code
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN coupons c ON o.coupon_id = c.id
                {$where}
                GROUP BY o.id, u.full_name, u.email, c.code
                ORDER BY o.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    

    public function count(string $status = 'all', string $keyword = ''): int
    {
        $params = [];
        $where = "WHERE 1=1";

        $allowedStatuses = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
        if ($status !== '' && $status !== 'all' && in_array($status, $allowedStatuses, true)) {
            $where .= " AND o.status = :status";
            $params['status'] = $status;
        }

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $where .= " AND (CAST(o.id AS CHAR) LIKE :keyword_id
                        OR o.receiver_name LIKE :keyword_receiver
                        OR o.phone LIKE :keyword_phone
                        OR u.full_name LIKE :keyword_name
                        OR u.email LIKE :keyword_email)";
            $likeKeyword = '%' . $keyword . '%';
            $params['keyword_id'] = $likeKeyword;
            $params['keyword_receiver'] = $likeKeyword;
            $params['keyword_phone'] = $likeKeyword;
            $params['keyword_name'] = $likeKeyword;
            $params['keyword_email'] = $likeKeyword;
        }

        $sql = "SELECT COUNT(DISTINCT o.id)
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    

    public function getDashboardStats(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_orders,
                    COALESCE(SUM(status = 'pending'), 0) AS pending_orders,
                    COALESCE(SUM(status = 'confirmed'), 0) AS confirmed_orders,
                    COALESCE(SUM(status = 'shipping'), 0) AS shipping_orders,
                    COALESCE(SUM(status = 'completed'), 0) AS completed_orders,
                    COALESCE(SUM(status = 'cancelled'), 0) AS cancelled_orders,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END), 0) AS revenue
                FROM orders";
        $stats = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [
            'total_orders' => 0,
            'pending_orders' => 0,
            'confirmed_orders' => 0,
            'shipping_orders' => 0,
            'completed_orders' => 0,
            'cancelled_orders' => 0,
            'revenue' => 0,
        ];

        $stats['total_customers'] = (int)$this->pdo
            ->query("SELECT COUNT(*) FROM users WHERE role = 'user'")
            ->fetchColumn();

        $lowStockSql = "SELECT COUNT(*) FROM (
                            SELECT p.id
                            FROM products p
                            LEFT JOIN product_variants pv ON p.id = pv.product_id
                            WHERE p.status = 1
                            GROUP BY p.id
                            HAVING COALESCE(SUM(pv.stock), 0) <= 10
                        ) AS low_stock_products";
        $stats['low_stock_products'] = (int)$this->pdo->query($lowStockSql)->fetchColumn();

        return $stats;
    }

    

    public function getRevenueChartData(int $days = 7): array
    {
        $days = max(1, min($days, 31));
        $today = new DateTimeImmutable('today');
        $startDate = $today->modify('-' . ($days - 1) . ' days');
        $endDate = $today->modify('+1 day');

        $sql = "SELECT DATE(created_at) AS revenue_date, COALESCE(SUM(total), 0) AS revenue
                FROM orders
                WHERE status = 'completed'
                  AND created_at >= :start_date
                  AND created_at < :end_date
                GROUP BY DATE(created_at)
                ORDER BY revenue_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'start_date' => $startDate->format('Y-m-d 00:00:00'),
            'end_date' => $endDate->format('Y-m-d 00:00:00'),
        ]);

        $revenueByDate = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $revenueByDate[$row['revenue_date']] = (float)$row['revenue'];
        }

        $labels = [];
        $values = [];
        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $date = $today->modify('-' . $offset . ' days');
            $dateKey = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
            $values[] = $revenueByDate[$dateKey] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    

    public function getRecentOrders(int $limit = 5): array
    {
        $limit = max(1, min($limit, 20));
        return $this->getList('all', $limit, 0);
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
        $transitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['shipping', 'cancelled'],
            'shipping' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];

        if ($id < 1 || !in_array($status, ['confirmed', 'shipping', 'completed', 'cancelled'], true)) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $check = $this->pdo->prepare('SELECT status FROM orders WHERE id = :id FOR UPDATE');
            $check->execute(['id' => $id]);
            $currentStatus = $check->fetchColumn();

            if ($currentStatus === false || !in_array($status, $transitions[$currentStatus] ?? [], true)) {
                $this->pdo->rollBack();
                return false;
            }

            if ($status === 'cancelled') {
                $items = $this->pdo->prepare('SELECT variant_id, quantity FROM order_items WHERE order_id = :id');
                $items->execute(['id' => $id]);
                $restoreStock = $this->pdo->prepare('UPDATE product_variants SET stock = stock + :quantity WHERE id = :variant_id');
                foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $item) {
                    if (!empty($item['variant_id'])) {
                        $restoreStock->execute([
                            'quantity' => (int)$item['quantity'],
                            'variant_id' => (int)$item['variant_id'],
                        ]);
                    }
                }
            }

            $update = $this->pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
            $update->execute(['status' => $status, 'id' => $id]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    

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
                throw new Exception('Không tìm thấy đơn hàng hoặc bạn không có quyền thực hiện.');
            }

            
            if ($status !== 'pending') {
                throw new Exception('Chỉ có thể hủy đơn hàng đang chờ xác nhận.');
            }

            
            $itemsStmt = $this->pdo->prepare("
                SELECT variant_id, quantity
                FROM order_items
                WHERE order_id = :order_id
            ");

            $itemsStmt->execute(['order_id' => $orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                throw new Exception('Đơn hàng không có sản phẩm để hoàn kho.');
            }

            
            $variantStmt = $this->pdo->prepare("
                SELECT id
                FROM product_variants
                WHERE id = :variant_id
                FOR UPDATE
            ");

            
            $restoreStmt = $this->pdo->prepare("
                UPDATE product_variants
                SET stock = stock + :quantity
                WHERE id = :variant_id
            ");

            foreach ($items as $item) {
                $variantId = (int)($item['variant_id'] ?? 0);
                $quantity  = (int)($item['quantity'] ?? 0);

                if ($variantId <= 0 || $quantity <= 0) {
                    throw new Exception('Thông tin sản phẩm trong đơn hàng không hợp lệ.');
                }

                $variantStmt->execute(['variant_id' => $variantId]);
                if ($variantStmt->fetchColumn() === false) {
                    throw new Exception('Không tìm thấy biến thể sản phẩm để hoàn kho.');
                }

                $restoreStmt->execute([
                    'quantity'   => $quantity,
                    'variant_id' => $variantId
                ]);

                if ($restoreStmt->rowCount() !== 1) {
                    throw new Exception('Không thể hoàn lại tồn kho sản phẩm.');
                }
            }

            
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
