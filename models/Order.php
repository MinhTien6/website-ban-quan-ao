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
     * Lấy danh sách đơn hàng cho Admin có phân trang và lọc theo trạng thái
     *
     * @param string $status Trạng thái lọc ('all' hoặc 'pending', 'confirmed', 'shipping', 'completed', 'cancelled')
     * @param int $limit
     * @param int $offset
     * @return array
     */
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

    /**
     * Đếm tổng số đơn hàng theo trạng thái
     *
     * @param string $status
     * @return int
     */
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

    /**
     * Cập nhật trạng thái đơn hàng (Admin - TV6)
     *
     * @param int $id ID đơn hàng
     * @param string $status Trạng thái mới
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $sql = "UPDATE orders SET status = :status WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'     => $id,
            'status' => $status,
        ]);
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
