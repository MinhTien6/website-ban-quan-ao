<?php
/**
 * Lớp xử lý dữ liệu danh mục (Category Model)
 * Phục vụ TV3 (Admin danh mục) và TV4 (Giao diện hiển thị danh mục)
 */
class Category
{
    private PDO $pdo;

    /**
     * Khởi tạo Category model với đối tượng kết nối PDO
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lấy toàn bộ danh mục đang hoạt động (cho trang người dùng)
     *
     * @return array
     */
    public function getAllActive(): array
    {
        $sql = "SELECT c.*, COUNT(p.id) AS total_products
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
                WHERE c.status = 1
                GROUP BY c.id
                ORDER BY c.id ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Lấy tất cả danh mục (cho trang quản trị Admin, bao gồm cả đang ẩn)
     *
     * @return array
     */
    public function getAll(): array
    {
        $sql = "SELECT c.*, COUNT(p.id) AS total_products
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id
                GROUP BY c.id
                ORDER BY c.id ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Lấy thông tin danh mục theo ID
     *
     * @param int|string $id ID danh mục
     * @return array|false
     */
    public function getById(int|string $id): array|false
    {
        $sql = "SELECT * FROM categories WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => (int)$id]);
        return $stmt->fetch();
    }

    /**
     * Lấy thông tin danh mục theo slug
     *
     * @param string $slug Slug danh mục
     * @return array|false
     */
    public function getBySlug(string $slug): array|false
    {
        $sql = "SELECT * FROM categories WHERE slug = :slug AND status = 1 LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch();
    }

    /**
     * Thêm mới danh mục (Admin - TV3)
     *
     * @param array{name: string, slug: string, description?: string, status?: int, parent_id?: int|null} $data
     * @return int ID của danh mục vừa tạo
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO categories (name, slug, description, parent_id, status)
                VALUES (:name, :slug, :description, :parent_id, :status)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?? null,
            'parent_id'   => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            'status'      => isset($data['status']) ? (int)$data['status'] : 1,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Cập nhật thông tin danh mục (Admin - TV3)
     *
     * @param int $id ID danh mục cần sửa
     * @param array $data Dữ liệu cần cập nhật
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE categories 
                SET name = :name, slug = :slug, description = :description, parent_id = :parent_id, status = :status
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'          => $id,
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?? null,
            'parent_id'   => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            'status'      => isset($data['status']) ? (int)$data['status'] : 1,
        ]);
    }

    /**
     * Xóa danh mục nếu không còn sản phẩm nào trực thuộc
     *
     * @param int $id ID danh mục
     * @return bool
     * @throws Exception Khi danh mục vẫn còn sản phẩm
     */
    public function delete(int $id): bool
    {
        // Kiểm tra xem danh mục có sản phẩm hay không
        $checkSql = "SELECT COUNT(*) FROM products WHERE category_id = :id";
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            throw new Exception("Không thể xóa danh mục này vì vẫn còn sản phẩm bên trong.");
        }

        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
