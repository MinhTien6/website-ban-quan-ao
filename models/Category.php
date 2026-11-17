<?php

class Category
{
    private PDO $pdo;

    

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    

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

    

    public function getById(int|string $id): array|false
    {
        $sql = "SELECT * FROM categories WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => (int)$id]);
        return $stmt->fetch();
    }

    

    public function getBySlug(string $slug): array|false
    {
        $sql = "SELECT * FROM categories WHERE slug = :slug AND status = 1 LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch();
    }

    

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

    

    public function delete(int $id): bool
    {
        
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
