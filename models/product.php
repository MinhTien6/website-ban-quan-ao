<?php
/**
 * Lớp xử lý dữ liệu sản phẩm (Product Model)
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
     * Lấy toàn bộ danh sách sản phẩm đang hoạt động kèm ảnh chính và danh mục
     *
     * @return array
     */
    public function getAll(): array
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
     * Lấy thông tin chi tiết của một sản phẩm theo ID
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
                WHERE p.id = :id AND p.status = 1
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => (int)$id]);

        return $stmt->fetch();
    }
}
