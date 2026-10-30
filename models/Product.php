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
                       SUM(pv.stock) AS total_stock
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                {$where}
                GROUP BY p.id
                ORDER BY p.created_at DESC
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
}
