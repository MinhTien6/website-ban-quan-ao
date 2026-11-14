<?php
/**
 * Lớp xử lý dữ liệu đơn hàng (Order Model)
 * Phục vụ TV5 (Giỏ hàng, đặt hàng bằng Transaction, lịch sử đơn) và TV6 (Admin quản lý đơn hàng)
 */
class Order
{
    private PDO $pdo;

    /**
     * Khởi tạo Order model với đối tượng kết nối PDO
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Tạo đơn hàng mới kèm chi tiết các món hàng và trừ tồn kho (Dùng Transaction an toàn)
     *
     * @param array{user_id: int, receiver_name: string, phone: string, address: string, total: float|int, payment_method: string, coupon_id?: int|null} $orderData
     * @param array<array{product_id: int, variant_id: int, quantity: int, price: float|int}> $items
     * @return int ID của đơn hàng vừa tạo
     * @throws Exception Khi xảy ra lỗi hoặc hết tồn kho
     */
    public function createOrder(array $orderData, array $items): int
    {
        if (empty($items)) {
            throw new Exception("Đơn hàng phải có ít nhất một sản phẩm.");
        }

        $this->pdo->beginTransaction();

        try {
            // 1. Thêm vào bảng orders
            $orderSql = "INSERT INTO orders (user_id, receiver_name, phone, address, total, payment_method, status, coupon_id, created_at)
                         VALUES (:user_id, :receiver_name, :phone, :address, :total, :payment_method, 'pending', :coupon_id, NOW())";
            
            $orderStmt = $this->pdo->prepare($orderSql);
            $orderStmt->execute([
                'user_id'        => (int)$orderData['user_id'],
                'receiver_name'  => $orderData['receiver_name'],
                'phone'          => $orderData['phone'],
                'address'        => $orderData['address'],
                'total'          => $orderData['total'],
                'payment_method' => $orderData['payment_method'] ?? 'cod',
                'coupon_id'      => !empty($orderData['coupon_id']) ? (int)$orderData['coupon_id'] : null,
            ]);

            $orderId = (int)$this->pdo->lastInsertId();

            // 2. Thêm từng sản phẩm vào order_items và trừ tồn kho
            $itemSql = "INSERT INTO order_items (order_id, product_id, variant_id, quantity, price)
                        VALUES (:order_id, :product_id, :variant_id, :quantity, :price)";
            $itemStmt = $this->pdo->prepare($itemSql);

            $updateStockSql = "UPDATE product_variants 
                               SET stock = stock - :quantity 
                               WHERE id = :variant_id AND stock >= :quantity";
            $stockStmt = $this->pdo->prepare($updateStockSql);

            foreach ($items as $item) {
                // Thêm order item
                $itemStmt->execute([
                    'order_id'   => $orderId,
                    'product_id' => (int)$item['product_id'],
                    'variant_id' => (int)$item['variant_id'],
                    'quantity'   => (int)$item['quantity'],
                    'price'      => $item['price'],
                ]);

                // Trừ tồn kho nếu có variant_id
                if (!empty($item['variant_id'])) {
                    $stockStmt->execute([
                        'variant_id' => (int)$item['variant_id'],
                        'quantity'   => (int)$item['quantity'],
                    ]);

                    if ($stockStmt->rowCount() === 0) {
                        throw new Exception("Sản phẩm mã #{$item['product_id']} đã hết hàng hoặc không đủ số lượng tồn.");
                    }
                }
            }

            $this->pdo->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Lấy danh sách đơn hàng của một người dùng (Lịch sử đơn hàng)
     *
     * @param int $userId ID khách hàng
     * @return array
     */
    public function getByUserId(int $userId): array
    {
        $sql = "SELECT o.*, COUNT(oi.id) AS total_items
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = :user_id
                GROUP BY o.id
                ORDER BY o.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy thông tin chi tiết một đơn hàng kèm danh sách sản phẩm và thông tin người mua
     *
     * @param int $orderId
     * @return array|false
     */
    public function getDetails(int $orderId): array|false
    {
        $sql = "SELECT o.*, u.full_name AS customer_name, u.email AS customer_email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            return false;
        }

        // Lấy danh sách item kèm thông tin biến thể và ảnh sản phẩm
        $itemsSql = "SELECT oi.*, p.name AS product_name, p.slug AS product_slug, 
                            pv.size, pv.color, pi.image_path
                     FROM order_items oi
                     LEFT JOIN products p ON oi.product_id = p.id
                     LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                     LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                     WHERE oi.order_id = :order_id";

        $itemsStmt = $this->pdo->prepare($itemsSql);
        $itemsStmt->execute(['order_id' => $orderId]);
        $order['items'] = $itemsStmt->fetchAll();

        return $order;
    }

    /**
     * Lấy danh sách đơn hàng cho Admin có phân trang, lọc và tìm kiếm.
     *
     * @param string $status Trạng thái lọc ('all' hoặc 'pending', 'confirmed', 'shipping', 'completed', 'cancelled')
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getList(string $status = 'all', int $limit = 10, int $offset = 0, string $keyword = ''): array
    {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);
        $params = [];
        $where = "WHERE 1=1";

        if ($status !== '' && $status !== 'all') {
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

        $sql = "SELECT o.*, u.full_name AS user_name, u.email AS user_email, COUNT(oi.id) AS total_items
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                {$where}
                GROUP BY o.id, u.full_name, u.email
                ORDER BY o.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số đơn theo trạng thái và từ khóa tìm kiếm.
     *
     * @param string $status
     * @return int
     */
    public function count(string $status = 'all', string $keyword = ''): int
    {
        $params = [];
        $where = "WHERE 1=1";

        if ($status !== '' && $status !== 'all') {
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

    /**
     * Thống kê đơn hàng, doanh thu, khách hàng và sản phẩm sắp hết hàng.
     */
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
        $stats = $this->pdo->query($sql)->fetch() ?: [
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

    /**
     * Lấy doanh thu theo ngày trong một khoảng ngày gần đây.
     * Chỉ tính các đơn đã hoàn thành; ngày không có đơn sẽ nhận doanh thu 0.
     */
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
        foreach ($stmt->fetchAll() as $row) {
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

    /** Lấy vài đơn gần nhất để hiển thị nhanh trên Dashboard. */
    public function getRecentOrders(int $limit = 5): array
    {
        $limit = max(1, min($limit, 20));
        return $this->getList('all', $limit, 0);
    }
    /**
     * Cập nhật trạng thái đơn theo luồng xử lý của Admin.
     * Khi hủy đơn, tồn kho được hoàn lại trong cùng transaction.
     */
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
                foreach ($items->fetchAll() as $item) {
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

    /**
     * Khách hàng hủy đơn khi còn ở trạng thái chờ xác nhận 'pending' (TV5)
     * Có hoàn lại số lượng tồn kho cho các biến thể
     *
     * @param int $orderId
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function cancelByUser(int $orderId, int $userId): bool
    {
        $this->pdo->beginTransaction();

        try {
            // Kiểm tra trạng thái đơn
            $checkSql = "SELECT status FROM orders WHERE id = :id AND user_id = :user_id LIMIT 1";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute(['id' => $orderId, 'user_id' => $userId]);
            $orderStatus = $checkStmt->fetchColumn();

            if ($orderStatus !== 'pending') {
                throw new Exception("Chỉ có thể hủy đơn hàng khi đơn còn ở trạng thái chờ xác nhận.");
            }

            // Hoàn lại tồn kho
            $itemsSql = "SELECT variant_id, quantity FROM order_items WHERE order_id = :order_id";
            $itemsStmt = $this->pdo->prepare($itemsSql);
            $itemsStmt->execute(['order_id' => $orderId]);
            $items = $itemsStmt->fetchAll();

            $restoreStockSql = "UPDATE product_variants SET stock = stock + :qty WHERE id = :vid";
            $restoreStmt = $this->pdo->prepare($restoreStockSql);

            foreach ($items as $item) {
                if (!empty($item['variant_id'])) {
                    $restoreStmt->execute([
                        'qty' => (int)$item['quantity'],
                        'vid' => (int)$item['variant_id'],
                    ]);
                }
            }

            // Cập nhật trạng thái đơn thành cancelled
            $updateSql = "UPDATE orders SET status = 'cancelled' WHERE id = :id";
            $updateStmt = $this->pdo->prepare($updateSql);
            $updateStmt->execute(['id' => $orderId]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
