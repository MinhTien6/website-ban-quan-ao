
<?php

require_once __DIR__ . '/../config/database.php';

class Product
{
    /**
     * Lấy các sản phẩm đang được bán.
     */
    public function getAll(): array
    {
        $pdo = db();

        $sql = "
            SELECT
                p.id,
                p.name,
                p.slug,
                p.description,
                p.status,
                MIN(v.price) AS price,
                SUM(v.stock_quantity) AS total_stock,
                (
                    SELECT pi.image_url
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_primary DESC, pi.sort_order ASC
                    LIMIT 1
                ) AS image_url
            FROM products p
            INNER JOIN product_variants v
                ON v.product_id = p.id
                AND v.status = 'active'
            WHERE p.status = 'active'
            GROUP BY
                p.id,
                p.name,
                p.slug,
                p.description,
                p.status
            ORDER BY p.id DESC
        ";

        $stmt = $pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Lấy một biến thể sản phẩm theo ID.
     */
    public function getVariantById(int $variantId): ?array
    {
        $sql = "
            SELECT
                v.id AS variant_id,
                v.product_id,
                v.sku,
                v.size,
                v.color,
                v.price,
                v.stock_quantity,
                v.status AS variant_status,
                p.name AS product_name,
                p.slug,
                p.status AS product_status
            FROM product_variants v
            INNER JOIN products p ON p.id = v.product_id
            WHERE v.id = :variant_id
            LIMIT 1
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute(['variant_id' => $variantId]);

        $variant = $stmt->fetch();

        return $variant ?: null;
    }
}
