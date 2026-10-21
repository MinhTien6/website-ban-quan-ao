<?php
class Product
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll()
    {
        $sql = "SELECT products.*, categories.name AS category_name
                FROM products
                LEFT JOIN categories
                ON products.category_id = categories.id
                WHERE products.is_active = 1
                ORDER BY products.id DESC";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM products
                WHERE id = :id AND is_active = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->fetch();
    }
}
?>
