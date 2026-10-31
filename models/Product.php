<?php
/**
 * Lớp xử lý dữ liệu sản phẩm (Product Model)
 * Phục vụ TV3 (Admin sản phẩm, upload nhiều ảnh, biến thể) và TV4 (Giao diện hiển thị, lọc, tìm kiếm, chi tiết)
 */
class Product
{
    private PDO $pdo;

    /**
     * Khởi tạo Product model với đối tượng kết nối PDO
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lấy danh sách sản phẩm mới nhất đang hoạt động
     *
     * @param int $limit Số lượng sản phẩm cần lấy
     * @return array
     */
    public function getLatest(int $limit = 8): array
    {
        $limit = max(1, (int)$limit);

        $sql = "SELECT p.*, c.name AS category_name, pi.image_path AS main_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.status = 1
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Lấy toàn bộ danh sách sản phẩm đang hoạt động kèm ảnh chính và danh mục
     *
     * @return array
     */
    public function getAllActive(): array
    {
        $sql = "SELECT p.*, c.name AS category_name, pi.image_path AS main_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.status = 1
                ORDER BY p.created_at DESC, p.id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách sản phẩm có lọc theo danh mục, khoảng giá, sắp xếp và phân trang (TV4)
     *
     * @param int|null $categoryId
     * @param float|null $minPrice
     * @param float|null $maxPrice
     * @param string $sort ('latest', 'price_asc', 'price_desc')
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getFiltered(
        ?int $categoryId = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        string $sort = 'latest',
        int $limit = 12,
        int $offset = 0
    ): array {
        $params = [];
        $where = "WHERE p.status = 1";

        if ($categoryId !== null && $categoryId > 0) {
            $where .= " AND p.category_id = :cat_id";
            $params['cat_id'] = $categoryId;
        }

        if ($minPrice !== null && $minPrice >= 0) {
            $where .= " AND COALESCE(p.sale_price, p.price) >= :min_price";
            $params['min_price'] = $minPrice;
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $where .= " AND COALESCE(p.sale_price, p.price) <= :max_price";
            $params['max_price'] = $maxPrice;
        }

        $orderBy = match ($sort) {
            'price_asc'  => "ORDER BY COALESCE(p.sale_price, p.price) ASC, p.id DESC",
            'price_desc' => "ORDER BY COALESCE(p.sale_price, p.price) DESC, p.id DESC",
            default      => "ORDER BY p.created_at DESC, p.id DESC",
        };

        $sql = "SELECT p.*, c.name AS category_name, pi.image_path AS main_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                {$where}
                {$orderBy}
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm tổng số sản phẩm thỏa mãn điều kiện lọc
     *
     * @param int|null $categoryId
     * @param float|null $minPrice
     * @param float|null $maxPrice
     * @return int
     */
    public function countFiltered(?int $categoryId = null, ?float $minPrice = null, ?float $maxPrice = null): int
    {
        $params = [];
        $where = "WHERE status = 1";

        if ($categoryId !== null && $categoryId > 0) {
            $where .= " AND category_id = :cat_id";
            $params['cat_id'] = $categoryId;
        }

        if ($minPrice !== null && $minPrice >= 0) {
            $where .= " AND COALESCE(sale_price, price) >= :min_price";
            $params['min_price'] = $minPrice;
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $where .= " AND COALESCE(sale_price, price) <= :max_price";
            $params['max_price'] = $maxPrice;
        }

        $sql = "SELECT COUNT(*) FROM products {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Tìm kiếm sản phẩm theo từ khóa (TV4)
     *
     * @param string $keyword
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(string $keyword, int $limit = 12, int $offset = 0): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        $sql = "SELECT p.*, c.name AS category_name, pi.image_path AS main_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.status = 1 AND (p.name LIKE :kw1 OR c.name LIKE :kw2)
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'kw1' => '%' . $keyword . '%',
            'kw2' => '%' . $keyword . '%',
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số kết quả tìm kiếm theo từ khóa
     *
     * @param string $keyword
     * @return int
     */
    public function countSearch(string $keyword): int
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return 0;
        }

        $sql = "SELECT COUNT(*) 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 1 AND (p.name LIKE :kw1 OR c.name LIKE :kw2)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'kw1' => '%' . $keyword . '%',
            'kw2' => '%' . $keyword . '%',
        ]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Lấy thông tin chi tiết của một sản phẩm theo ID kèm tên danh mục và ảnh chính
     *
     * @param int|string $id ID sản phẩm
     * @return array|false Thông tin sản phẩm hoặc false nếu không tìm thấy
     */
    public function getById(int|string $id): array|false
    {
        $sql = "SELECT p.*, c.name AS category_name, pi.image_path AS main_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => (int)$id]);

        return $stmt->fetch();
    }

    /**
     * Lấy chi tiết sản phẩm theo slug
     *
     * @param string $slug
     * @return array|false
     */
    public function getBySlug(string $slug): array|false
    {
        $sql = "SELECT p.*, c.name AS category_name, pi.image_path AS main_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.slug = :slug AND p.status = 1
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch();
    }

    /**
     * Lấy danh sách tất cả hình ảnh của một sản phẩm (Gallery ảnh)
     *
     * @param int $productId
     * @return array
     */
    public function getImages(int $productId): array
    {
        $sql = "SELECT * FROM product_images 
                WHERE product_id = :id 
                ORDER BY is_main DESC, sort_order ASC, id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $productId]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách tất cả biến thể size/màu kèm tồn kho của một sản phẩm
     *
     * @param int $productId
     * @return array
     */
    public function getVariants(int $productId): array
    {
        $sql = "SELECT * FROM product_variants 
                WHERE product_id = :id 
                ORDER BY size ASC, color ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $productId]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy sản phẩm liên quan cùng danh mục
     *
     * @param int $productId ID sản phẩm hiện tại (để loại trừ)
     * @param int $categoryId
     * @param int $limit
     * @return array
     */
    public function getRelated(int $productId, int $categoryId, int $limit = 4): array
    {
        $sql = "SELECT p.*, c.name AS category_name, pi.image_path AS main_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.category_id = :cat_id AND p.id != :p_id AND p.status = 1
                ORDER BY p.id DESC
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':cat_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':p_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Danh sách sản phẩm cho Admin có tìm kiếm và phân trang
     *
     * @param string $keyword
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAdminList(string $keyword = '', int $limit = 10, int $offset = 0): array
    {
        $params = [];
        $where = "WHERE 1=1";

        if ($keyword !== '') {
            $where .= " AND (p.name LIKE :kw OR c.name LIKE :kw)";
            $params['kw'] = '%' . $keyword . '%';
        }

        $sql = "SELECT p.*, c.name AS category_name, pi.image_path AS main_image,
                       COALESCE(SUM(pv.stock), 0) AS total_stock
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                {$where}
                GROUP BY p.id
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm tổng số sản phẩm trong Admin
     *
     * @param string $keyword
     * @return int
     */
    public function countAdminList(string $keyword = ''): int
    {
        $params = [];
        $where = "WHERE 1=1";

        if ($keyword !== '') {
            $where .= " AND (p.name LIKE :kw OR c.name LIKE :kw)";
            $params['kw'] = '%' . $keyword . '%';
        }

        $sql = "SELECT COUNT(DISTINCT p.id) 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id
                {$where}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /** 
     * Thêm mới sản phẩm kèm danh sách ảnh và biến thể bằng Transaction (Phục vụ TV3 - Chuẩn Báo cáo)
     *
     * @param array $data Dữ liệu sản phẩm (name, slug, price, sale_price, description, category_id, status)
     * @param array $imagePaths Danh sách đường dẫn ảnh tải lên
     * @param int $mainIndex Chỉ số của ảnh chính trong danh sách tải lên
     * @param array $variants Danh sách biến thể size/màu/tồn kho
     * @return int ID của sản phẩm vừa tạo
     * @throws Throwable
     */
    public function create(array $data, array $imagePaths = [], int $mainIndex = 0, array $variants = []): int
    {
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO products (name, slug, price, sale_price, description, category_id, status)
                    VALUES (:name, :slug, :price, :sale_price, :description, :category_id, :status)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'name'        => $data['name'],
                'slug'        => $data['slug'],
                'price'       => (float)$data['price'],
                'sale_price'  => !empty($data['sale_price']) ? (float)$data['sale_price'] : null,
                'description' => $data['description'] ?? null,
                'category_id' => (int)$data['category_id'],
                'status'      => isset($data['status']) ? (int)$data['status'] : 1,
            ]);
            $productId = (int)$this->pdo->lastInsertId();

            // 1. Lưu nhiều ảnh sản phẩm
            if (!empty($imagePaths)) {
                $imgStmt = $this->pdo->prepare(
                    "INSERT INTO product_images (product_id, image_path, is_main, sort_order) 
                     VALUES (:product_id, :image_path, :is_main, :sort_order)"
                );
                foreach ($imagePaths as $idx => $path) {
                    $imgStmt->execute([
                        'product_id' => $productId,
                        'image_path' => $path,
                        'is_main'    => ($idx === $mainIndex) ? 1 : 0,
                        'sort_order' => $idx + 1,
                    ]);
                }
            }

            // 2. Lưu các biến thể Size / Màu / Tồn kho an toàn (Gộp trùng lặp chống lỗi uq_variant)
            if (!empty($variants)) {
                $this->syncVariants($productId, $variants);
            }

            $this->pdo->commit();
            return $productId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cập nhật sản phẩm kèm thêm ảnh mới và đồng bộ biến thể bằng Transaction (Admin - TV3)
     *
     * @param int $id ID sản phẩm cần cập nhật
     * @param array $data Dữ liệu sản phẩm
     * @param array $newImagePaths Danh sách ảnh mới tải lên thêm (nếu có)
     * @param int|null $mainImageId ID ảnh được chọn làm ảnh chính (nếu có)
     * @param array $variants Danh sách biến thể cập nhật
     * @return bool
     * @throws Throwable
     */
    public function update(int $id, array $data, array $newImagePaths = [], ?int $mainImageId = null, array $variants = []): bool
    {
        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE products 
                    SET name = :name, slug = :slug, price = :price, sale_price = :sale_price, 
                        description = :description, category_id = :category_id, status = :status
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id'          => $id,
                'name'        => $data['name'],
                'slug'        => $data['slug'],
                'price'       => (float)$data['price'],
                'sale_price'  => !empty($data['sale_price']) ? (float)$data['sale_price'] : null,
                'description' => $data['description'] ?? null,
                'category_id' => (int)$data['category_id'],
                'status'      => isset($data['status']) ? (int)$data['status'] : 1,
            ]);

            // Thêm các ảnh mới tải lên
            if (!empty($newImagePaths)) {
                $maxOrderStmt = $this->pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = :id");
                $maxOrderStmt->execute(['id' => $id]);
                $maxOrder = (int)$maxOrderStmt->fetchColumn();

                $imgStmt = $this->pdo->prepare(
                    "INSERT INTO product_images (product_id, image_path, is_main, sort_order) 
                     VALUES (:product_id, :image_path, :is_main, :sort_order)"
                );
                foreach ($newImagePaths as $idx => $path) {
                    $imgStmt->execute([
                        'product_id' => $id,
                        'image_path' => $path,
                        'is_main'    => 0,
                        'sort_order' => $maxOrder + $idx + 1,
                    ]);
                }
            }

            // Đổi ảnh chính nếu chỉ định
            if ($mainImageId !== null && $mainImageId > 0) {
                $this->pdo->prepare("UPDATE product_images SET is_main = 0 WHERE product_id = :id")->execute(['id' => $id]);
                $this->pdo->prepare("UPDATE product_images SET is_main = 1 WHERE id = :img_id AND product_id = :id")
                          ->execute(['img_id' => $mainImageId, 'id' => $id]);
            }

            // Đồng bộ lại danh sách biến thể nếu được truyền vào
            if (!empty($variants)) {
                $this->syncVariants($id, $variants);
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

    /**
     * Xóa sản phẩm an toàn (Nếu đã có đơn hàng thì tự động chuyển sang ẩn status = 0 - Chuẩn Báo cáo)
     *
     * @param int $id ID sản phẩm cần xóa
     * @return string 'hidden' nếu chuyển ẩn, 'deleted' nếu xóa hoàn toàn
     */
    public function delete(int $id): string
    {
        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = :id");
        $checkStmt->execute(['id' => $id]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            $hideStmt = $this->pdo->prepare("UPDATE products SET status = 0 WHERE id = :id");
            $hideStmt->execute(['id' => $id]);
            return 'hidden';
        }

        // Lấy ảnh để xóa file vật lý
        $images = $this->getImages($id);
        foreach ($images as $img) {
            $filePath = ROOT_PATH . '/public/' . ltrim($img['image_path'], '/');
            if (file_exists($filePath) && is_file($filePath)) {
                @unlink($filePath);
            }
        }

        $delStmt = $this->pdo->prepare("DELETE FROM products WHERE id = :id");
        $delStmt->execute(['id' => $id]);
        return 'deleted';
    }

    /**
     * Thêm hình ảnh cho sản phẩm (Admin - TV3)
     *
     * @param int $productId ID sản phẩm
     * @param string $imagePath Đường dẫn ảnh
     * @param bool $isMain Có phải ảnh đại diện chính không
     * @param int $sortOrder Thứ tự sắp xếp
     * @return int ID ảnh vừa thêm
     */
    public function addImage(int $productId, string $imagePath, bool $isMain = false, int $sortOrder = 0): int
    {
        if ($isMain) {
            $resetSql = "UPDATE product_images SET is_main = 0 WHERE product_id = :product_id";
            $resetStmt = $this->pdo->prepare($resetSql);
            $resetStmt->execute(['product_id' => $productId]);
        }

        $sql = "INSERT INTO product_images (product_id, image_path, is_main, sort_order)
                VALUES (:product_id, :image_path, :is_main, :sort_order)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'product_id' => $productId,
            'image_path' => $imagePath,
            'is_main'    => $isMain ? 1 : 0,
            'sort_order' => $sortOrder,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Xóa 1 ảnh của sản phẩm
     *
     * @param int $imageId ID ảnh cần xóa
     * @return bool
     */
    public function deleteImage(int $imageId): bool
    {
        $sql = "SELECT * FROM product_images WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $imageId]);
        $img = $stmt->fetch();
        if (!$img) {
            return false;
        }

        $filePath = ROOT_PATH . '/public/' . ltrim($img['image_path'], '/');
        if (file_exists($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }

        $delSql = "DELETE FROM product_images WHERE id = :id";
        $delStmt = $this->pdo->prepare($delSql);
        $res = $delStmt->execute(['id' => $imageId]);

        if ($img['is_main']) {
            $nextSql = "UPDATE product_images SET is_main = 1 WHERE product_id = :product_id ORDER BY sort_order ASC, id ASC LIMIT 1";
            $nextStmt = $this->pdo->prepare($nextSql);
            $nextStmt->execute(['product_id' => $img['product_id']]);
        }

        return $res;
    }

    /**
     * Đặt ảnh làm ảnh đại diện chính của sản phẩm
     *
     * @param int $productId ID sản phẩm
     * @param int $imageId ID ảnh được chọn
     * @return bool
     */
    public function setMainImage(int $productId, int $imageId): bool
    {
        $resetSql = "UPDATE product_images SET is_main = 0 WHERE product_id = :product_id";
        $resetStmt = $this->pdo->prepare($resetSql);
        $resetStmt->execute(['product_id' => $productId]);

        $sql = "UPDATE product_images SET is_main = 1 WHERE id = :id AND product_id = :product_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'         => $imageId,
            'product_id' => $productId,
        ]);
    }

    /**
     * Đồng bộ danh sách biến thể size/màu/tồn kho an toàn (Tự động gộp nếu trùng Size & Màu)
     *
     * @param int $productId ID sản phẩm
     * @param array $variants Danh sách biến thể [{'size', 'color', 'stock'}]
     * @return void
     */
    public function syncVariants(int $productId, array $variants): void
    {
        $delStmt = $this->pdo->prepare("DELETE FROM product_variants WHERE product_id = :product_id");
        $delStmt->execute(['product_id' => $productId]);

        // Gộp các dòng trùng cặp (size, color) để không bao giờ vi phạm UNIQUE KEY uq_variant
        $uniqueVariants = [];
        foreach ($variants as $v) {
            $size  = trim($v['size'] ?? '');
            $color = trim($v['color'] ?? '');
            $stock = max(0, (int)($v['stock'] ?? 0));

            if ($size === '' && $color === '') {
                continue;
            }

            $sizeVal  = $size !== '' ? $size : 'Free';
            $colorVal = $color !== '' ? $color : 'Mặc định';
            $key = mb_strtolower($sizeVal . '|' . $colorVal, 'UTF-8');

            if (!isset($uniqueVariants[$key])) {
                $uniqueVariants[$key] = [
                    'size'  => $sizeVal,
                    'color' => $colorVal,
                    'stock' => $stock,
                ];
            } else {
                $uniqueVariants[$key]['stock'] += $stock;
            }
        }

        $insStmt = $this->pdo->prepare(
            "INSERT INTO product_variants (product_id, size, color, stock) 
             VALUES (:product_id, :size, :color, :stock)"
        );

        foreach ($uniqueVariants as $uv) {
            $insStmt->execute([
                'product_id' => $productId,
                'size'       => $uv['size'],
                'color'      => $uv['color'],
                'stock'      => $uv['stock'],
            ]);
        }
    }

    /**
     * Lấy danh sách sản phẩm cảnh báo tồn kho thấp (Admin Tuần 3 - TV3)
     *
     * @param int $threshold Ngưỡng cảnh báo (mặc định 10 cái)
     * @param int $limit Số lượng sản phẩm
     * @return array
     */
    public function getLowStockProducts(int $threshold = 10, int $limit = 10): array
    {
        $sql = "SELECT p.*, c.name AS category_name, pi.image_path AS main_image,
                       COALESCE(SUM(pv.stock), 0) AS total_stock
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                WHERE p.status = 1
                GROUP BY p.id
                HAVING total_stock <= :threshold
                ORDER BY total_stock ASC
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
