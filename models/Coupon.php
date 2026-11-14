<?php
/**
 * Coupon Model - FashionStore
 * Quản lý mã giảm giá (CRUD Admin - Phân hệ TV5 làm thêm)
 */

class Coupon
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lấy danh sách mã giảm giá kèm số lượt đã sử dụng thực tế
     *
     * @param string|null $search Tìm theo mã code
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll(?string $search = null, int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $where = "WHERE 1=1";

        if ($search !== null && trim($search) !== '') {
            $where .= " AND c.code LIKE :search";
            $params['search'] = '%' . strtoupper(trim($search)) . '%';
        }

        $limit = max(1, min($limit, 50));
        $offset = max(0, $offset);

        $sql = "
            SELECT 
                c.*,
                COALESCE(used.count, 0) AS used_count
            FROM coupons c
            LEFT JOIN (
                SELECT coupon_id, COUNT(*) AS count
                FROM orders
                WHERE coupon_id IS NOT NULL 
                  AND status <> 'cancelled'
                GROUP BY coupon_id
            ) used ON c.id = used.coupon_id
            {$where}
            ORDER BY c.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm tổng số mã giảm giá
     *
     * @param string|null $search
     * @return int
     */
    public function count(?string $search = null): int
    {
        $params = [];
        $where = "WHERE 1=1";

        if ($search !== null && trim($search) !== '') {
            $where .= " AND code LIKE :search";
            $params['search'] = '%' . strtoupper(trim($search)) . '%';
        }

        $sql = "SELECT COUNT(*) FROM coupons {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Lấy chi tiết một mã giảm giá theo ID
     *
     * @param int $id
     * @return array|false
     */
    public function findById(int $id): array|false
    {
        $sql = "
            SELECT 
                c.*,
                COALESCE(used.count, 0) AS used_count
            FROM coupons c
            LEFT JOIN (
                SELECT coupon_id, COUNT(*) AS count
                FROM orders
                WHERE coupon_id = :id_sub 
                  AND status <> 'cancelled'
            ) used ON 1=1
            WHERE c.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'id_sub' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy mã giảm giá theo mã code
     *
     * @param string $code
     * @return array|false
     */
    public function findByCode(string $code): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM coupons WHERE code = :code LIMIT 1");
        $stmt->execute(['code' => strtoupper(trim($code))]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Thêm mới mã giảm giá
     *
     * @param array $data
     * @return int ID mã vừa tạo
     * @throws Exception
     */
    public function create(array $data): int
    {
        $code = strtoupper(trim($data['code'] ?? ''));
        $discountType = $data['discount_type'] ?? 'percent';
        $discountValue = (float)($data['discount_value'] ?? 0);
        $minOrder = (float)($data['min_order'] ?? 0);
        $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : null;
        $usageLimit = isset($data['usage_limit']) && $data['usage_limit'] !== '' ? (int)$data['usage_limit'] : null;

        if ($code === '') {
            throw new InvalidArgumentException('Mã giảm giá không được để trống.');
        }

        if (!in_array($discountType, ['percent', 'amount'], true)) {
            throw new InvalidArgumentException('Loại giảm giá không hợp lệ.');
        }

        if ($discountValue <= 0) {
            throw new InvalidArgumentException('Giá trị giảm phải lớn hơn 0.');
        }

        if ($discountType === 'percent' && $discountValue > 100) {
            throw new InvalidArgumentException('Phần trăm giảm giá không được vượt quá 100%.');
        }

        if ($this->findByCode($code)) {
            throw new Exception("Mã giảm giá \"{$code}\" đã tồn tại.");
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO coupons (
                code,
                discount_type,
                discount_value,
                min_order,
                expires_at,
                usage_limit
            ) VALUES (
                :code,
                :discount_type,
                :discount_value,
                :min_order,
                :expires_at,
                :usage_limit
            )
        ");

        $stmt->execute([
            'code'           => $code,
            'discount_type'  => $discountType,
            'discount_value' => $discountValue,
            'min_order'      => $minOrder,
            'expires_at'     => $expiresAt,
            'usage_limit'    => $usageLimit,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Cập nhật thông tin mã giảm giá
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function update(int $id, array $data): bool
    {
        $code = strtoupper(trim($data['code'] ?? ''));
        $discountType = $data['discount_type'] ?? 'percent';
        $discountValue = (float)($data['discount_value'] ?? 0);
        $minOrder = (float)($data['min_order'] ?? 0);
        $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : null;
        $usageLimit = isset($data['usage_limit']) && $data['usage_limit'] !== '' ? (int)$data['usage_limit'] : null;

        if ($code === '') {
            throw new InvalidArgumentException('Mã giảm giá không được để trống.');
        }

        if (!in_array($discountType, ['percent', 'amount'], true)) {
            throw new InvalidArgumentException('Loại giảm giá không hợp lệ.');
        }

        if ($discountValue <= 0) {
            throw new InvalidArgumentException('Giá trị giảm phải lớn hơn 0.');
        }

        if ($discountType === 'percent' && $discountValue > 100) {
            throw new InvalidArgumentException('Phần trăm giảm giá không được vượt quá 100%.');
        }

        // Kiểm tra trùng code với bản ghi khác
        $checkStmt = $this->pdo->prepare("SELECT id FROM coupons WHERE code = :code AND id <> :id LIMIT 1");
        $checkStmt->execute(['code' => $code, 'id' => $id]);
        if ($checkStmt->fetch()) {
            throw new Exception("Mã giảm giá \"{$code}\" đã tồn tại ở bản ghi khác.");
        }

        $stmt = $this->pdo->prepare("
            UPDATE coupons
            SET code = :code,
                discount_type = :discount_type,
                discount_value = :discount_value,
                min_order = :min_order,
                expires_at = :expires_at,
                usage_limit = :usage_limit
            WHERE id = :id
        ");

        return $stmt->execute([
            'id'             => $id,
            'code'           => $code,
            'discount_type'  => $discountType,
            'discount_value' => $discountValue,
            'min_order'      => $minOrder,
            'expires_at'     => $expiresAt,
            'usage_limit'    => $usageLimit,
        ]);
    }

    /**
     * Xóa mã giảm giá (kiểm tra an toàn nếu đã có đơn hàng áp dụng)
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        // Kiểm tra xem mã có đơn hàng nào tham chiếu không
        $checkOrders = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE coupon_id = :id");
        $checkOrders->execute(['id' => $id]);
        $usedCount = (int)$checkOrders->fetchColumn();

        if ($usedCount > 0) {
            throw new Exception("Không thể xóa mã giảm giá này vì đã có {$usedCount} đơn hàng sử dụng.");
        }

        $stmt = $this->pdo->prepare("DELETE FROM coupons WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Lấy số lượt đơn hàng đã áp dụng mã
     *
     * @param int $couponId
     * @return int
     */
    public function getUsedCount(int $couponId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM orders 
            WHERE coupon_id = :id 
              AND status <> 'cancelled'
        ");
        $stmt->execute(['id' => $couponId]);
        return (int)$stmt->fetchColumn();
    }
}
