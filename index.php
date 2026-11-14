<?php
/**
 * Trang chủ (index.php) - Phụ trách: TV4 (Thư)
 */
$pageTitle = 'Trang chủ | GenZ Fashion Store';

if (file_exists(__DIR__ . '/config/config.php')) {
    try {
        require_once __DIR__ . '/config/config.php';
    } catch (Throwable $e) {
    }
}
if (file_exists(__DIR__ . '/config/database.php')) {
    try {
        require_once __DIR__ . '/config/database.php';
    } catch (Throwable $e) {
    }
}
if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
}
if (file_exists(__DIR__ . '/includes/auth.php')) {
    require_once __DIR__ . '/includes/auth.php';
}

if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    define('BASE_URL', rtrim($scriptDir, '/'));
}
if (!function_exists('e')) {
    function e($str)
    {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('formatPrice')) {
    function formatPrice($price)
    {
        return number_format((float) $price, 0, ',', '.') . 'đ';
    }
}

$db = $pdo ?? $conn ?? null;
if (!($db instanceof PDO)) {
    $dbHost = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
    $dbName = defined('DB_NAME') ? DB_NAME : 'shop_quanao';
    $dbUser = defined('DB_USER') ? DB_USER : 'root';
    $portsToTry = defined('DB_PORT') ? [DB_PORT, 3306, 3307, 3308] : [3306, 3307, 3308];
    $passesToTry = defined('DB_PASS') ? [DB_PASS, '', '123456', 'root'] : ['', '123456', 'root'];

    foreach ($portsToTry as $port) {
        foreach ([$dbHost, '127.0.0.1', 'localhost'] as $h) {
            foreach ($passesToTry as $p) {
                try {
                    $db = new PDO("mysql:host={$h};port={$port};dbname={$dbName};charset=utf8mb4", $dbUser, $p, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    $pdo = $db;
                    break 3;
                } catch (Throwable $ex) {
                }
            }
        }
    }
}

function getHeroBannerImage()
{
    $folders = ['public/images', 'public/uploads/banners', 'public/uploads', '.'];
    $newestFile = '';
    $newestTime = 0;

    foreach ($folders as $dir) {
        $fullDir = __DIR__ . ($dir === '.' ? '' : '/' . $dir);
        if (is_dir($fullDir)) {
            $files = @scandir($fullDir);
            if (is_array($files)) {
                foreach ($files as $f) {
                    if (preg_match('/^(banner|watermarked_img|hero).*\.(png|jpg|jpeg|webp)$/i', $f)) {
                        $fullPath = $fullDir . '/' . $f;
                        $mtime = @filemtime($fullPath);
                        if ($mtime >= $newestTime) {
                            $newestTime = $mtime;
                            $newestFile = ($dir === '.' ? '' : $dir . '/') . $f . '?v=' . $mtime;
                        }
                    }
                }
            }
        }
    }
    return $newestFile;
}

$heroBannerUrl = getHeroBannerImage();

$catalogProducts = [
    1 => ['name' => 'Áo polo cổ tròn', 'slug' => 'ao-polo-co-tron', 'cat' => 2, 'price' => 279000, 'sale' => 239000, 'front' => 'uploads/products/Ao-polo-co-tron-mat-truoc.jpg', 'back' => 'uploads/products/Ao-polo-tron-mat-sau.webp', 'desc' => '<p>Áo polo cổ tròn thiết kế trẻ trung, chất vải pique thoáng mát, thấm hút mồ hôi tốt.</p>'],
    2 => ['name' => 'Áo thun oversize basic', 'slug' => 'ao-thun-oversize-basic', 'cat' => 1, 'price' => 249000, 'sale' => null, 'front' => 'uploads/products/Ao-thun-oversize-basic-mat-truoc.jpg', 'back' => 'uploads/products/Ao-thun-oversize-basic-mat-sau.webp', 'desc' => '<p>Áo thun oversize basic cotton 100% dày dặn, form rộng chuẩn phong cách Streetwear Gen Z.</p>'],
    3 => ['name' => 'Áo polo trơn', 'slug' => 'ao-polo-tron', 'cat' => 2, 'price' => 299000, 'sale' => 259000, 'front' => 'uploads/products/Ao-polo-tron-mat-truoc.jpg', 'back' => 'uploads/products/Ao-polo-tron-mat-sau.webp', 'desc' => '<p>Áo polo trơn vải cá sấu thoáng khí, cổ dệt bền form, phù hợp mặc đi học, đi làm và dạo phố.</p>'],
    4 => ['name' => 'Áo thun in logo', 'slug' => 'ao-thun-in-logo', 'cat' => 1, 'price' => 229000, 'sale' => null, 'front' => 'uploads/products/Ao-thun-in-logo-mat-truoc.jpg', 'back' => 'uploads/products/Ao-thun-in-logo-mat-sau.webp', 'desc' => '<p>Áo thun in logo sắc nét, chất cotton mềm mịn, giặt máy nhiều lần không bong tróc.</p>'],
    5 => ['name' => 'Áo croptop', 'slug' => 'ao-croptop', 'cat' => 1, 'price' => 169000, 'sale' => 129000, 'front' => 'uploads/products/Ao-croptop-mat-truoc.jpg', 'back' => 'uploads/products/Ao-croptop-mat-sau.webp', 'desc' => '<p>Áo croptop ôm dáng vừa vặn, thun co giãn 4 chiều thoải mái, dễ phối cùng quần cạp cao.</p>'],
    9 => ['name' => 'Áo sơ mi linen nữ tay dài', 'slug' => 'ao-so-mi-linen-nu-tay-dai', 'cat' => 2, 'price' => 349000, 'sale' => null, 'front' => 'uploads/products/Ao-so-mi-linen-nu-tay-dai-mat-truoc.jpg', 'back' => 'uploads/products/Ao-so-mi-linen-nu-tay-dai-mat-sau.webp', 'desc' => '<p>Áo sơ mi linen nữ tay dài chất vải tự nhiên thoáng mát, nhẹ nhàng, dễ phối đồ đi học hay đi làm.</p>'],
    12 => ['name' => 'Quần jeans straight', 'slug' => 'quan-jeans-straight', 'cat' => 3, 'price' => 549000, 'sale' => null, 'front' => 'uploads/products/Quan-jeans-straight-mat-truoc.jpg', 'back' => 'uploads/products/Quan-jeans-straight-mat-sau.webp', 'desc' => '<p>Quần jeans ống đứng (straight fit) gọn gàng, chất denim dày dặn, màu wash xanh dễ phối đồ.</p>'],
    13 => ['name' => 'Quần jeans ống rộng', 'slug' => 'quan-jeans-ong-rong', 'cat' => 3, 'price' => 529000, 'sale' => 459000, 'front' => 'uploads/products/Quan-jeans-ong-rong-mat-truoc.jpg', 'back' => 'uploads/products/Quan-jeans-ong-rong-mat-sau.webp', 'desc' => '<p>Quần jeans ống rộng cạp cao tôn dáng, che khuyết điểm tốt, chất vải denim mềm mại.</p>'],
    14 => ['name' => 'Quần tây ống rộng', 'slug' => 'quan-tay-ong-rong', 'cat' => 3, 'price' => 479000, 'sale' => null, 'front' => 'uploads/products/Quan-tay-ong-rong-mat-truoc.jpg', 'back' => 'uploads/products/Quan-tay-ong-rong-mat-sau.webp', 'desc' => '<p>Quần tây ống rộng suông thẳng hiện đại, vải dày dặn chống nhăn, lên form chuẩn Hàn Quốc.</p>'],
    15 => ['name' => 'Quần kaki', 'slug' => 'quan-kaki', 'cat' => 3, 'price' => 449000, 'sale' => null, 'front' => 'uploads/products/Quan-kaki-mat-truoc.jpg', 'back' => 'uploads/products/Quan-kaki-mat-sau.webp', 'desc' => '<p>Quần kaki form rộng rãi thoải mái, chất vải kaki bền màu, đường may tỉ mỉ chắc chắn.</p>'],
    16 => ['name' => 'Quần short thể thao', 'slug' => 'quan-short-the-thao', 'cat' => 3, 'price' => 259000, 'sale' => 219000, 'front' => 'uploads/products/Quan-short-the-thao-mat-truoc.jpg', 'back' => 'uploads/products/Quan-short-the-thao-mat-sau.webp', 'desc' => '<p>Quần short thể thao năng động, lưng thun co giãn kèm dây rút tiện lợi, thoáng mát cả ngày.</p>'],
    17 => ['name' => 'Chân váy midi', 'slug' => 'chan-vay-midi', 'cat' => 4, 'price' => 389000, 'sale' => null, 'front' => 'uploads/products/Chan-vay-midi-mat-truoc.jpg', 'back' => 'uploads/products/Chan-vay-midi-mat-sau.webp', 'desc' => '<p>Chân váy midi dáng dài thanh lịch, chất vải mềm rủ tự nhiên, dễ kết hợp cùng áo thun và sơ mi.</p>'],
    19 => ['name' => 'Quần short nữ', 'slug' => 'quan-short-nu', 'cat' => 3, 'price' => 289000, 'sale' => null, 'front' => 'uploads/products/Quan-short-nu-mat-truoc.jpg', 'back' => 'uploads/products/Quan-short-nu-mat-sau.webp', 'desc' => '<p>Quần short nữ cạp cao trẻ trung, ống rộng vừa phải giúp tôn dáng và thoải mái vận động.</p>'],
    21 => ['name' => 'Chân váy xếp ly', 'slug' => 'chan-vay-xep-ly', 'cat' => 4, 'price' => 359000, 'sale' => 319000, 'front' => 'uploads/products/Chan-vay-xep-ly-mat-truoc.jpg', 'back' => 'uploads/products/Chan-vay-xep-li-mat-sau.webp', 'desc' => '<p>Chân váy xếp ly trẻ trung, có quần lót bảo hộ bên trong, nếp gấp sắc nét giữ form lâu.</p>'],
    22 => ['name' => 'Áo khoác denim', 'slug' => 'ao-khoac-denim', 'cat' => 5, 'price' => 649000, 'sale' => null, 'front' => 'uploads/products/Ao-khoac-denim-mat-truoc.jpg', 'back' => 'uploads/products/Ao-khoac-denim-mat-sau.webp', 'desc' => '<p>Áo khoác denim unisex cá tính, chất jean wash dày dặn, khuy cài kim loại bền đẹp.</p>'],
    23 => ['name' => 'Áo khoác varsity', 'slug' => 'ao-khoac-varsity', 'cat' => 7, 'price' => 589000, 'sale' => 499000, 'front' => 'uploads/products/Ao-khoac-varsity-mat-truoc.jpg', 'back' => 'uploads/products/Ao-khoac-varsity-mat-sau.webp', 'desc' => '<p>Áo khoác varsity phối tay nổi bật, form rộng chuẩn Streetwear, bo cổ và cổ tay dày dặn.</p>'],
    25 => ['name' => 'Áo khoác hoodie oversize', 'slug' => 'ao-khoac-hoodie-oversize', 'cat' => 8, 'price' => 449000, 'sale' => null, 'front' => 'uploads/products/Ao-khoac-hoodie-oversize-mat-truoc.jpg', 'back' => 'uploads/products/Ao-khoac-hoodie-oversize-mat-sau.webp', 'desc' => '<p>Áo khoác hoodie oversize nỉ bông mềm ấm, mũ 2 lớp đứng form kèm túi trước rộng rãi.</p>'],
    26 => ['name' => 'Áo khoác hoodie zip', 'slug' => 'ao-khoac-hoodie-zip', 'cat' => 8, 'price' => 479000, 'sale' => 419000, 'front' => 'uploads/products/Ao-khoac-hoodie-zip-mat-truoc.jpg', 'back' => 'uploads/products/Ao-khoac-hoodie-zip-mat-sau.webp', 'desc' => '<p>Áo khoác hoodie zip khóa kéo tiện lợi, vải nỉ da cá dày vừa phải, mặc khoác nắng hay giữ ấm đều hợp.</p>'],
];

if ($pdo instanceof PDO) {
    try {
        $validIdsList = implode(',', array_keys($catalogProducts));
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("DELETE FROM products WHERE id NOT IN ($validIdsList)");
        $pdo->exec("DELETE FROM product_images WHERE product_id NOT IN ($validIdsList)");
        $pdo->exec("DELETE FROM categories WHERE id = 6 OR slug = 'phu-kien'");

        $upStmt = $pdo->prepare("UPDATE products SET name = ?, slug = ?, category_id = ?, price = ?, sale_price = ?, description = ?, status = 1 WHERE id = ?");
        $delImg = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
        $insImg = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?)");

        foreach ($catalogProducts as $pid => $item) {
            $upStmt->execute([$item['name'], $item['slug'], $item['cat'], $item['price'], $item['sale'], $item['desc'], $pid]);
            $delImg->execute([$pid]);
            $insImg->execute([$pid, $item['front'], 1, 1]);
            $insImg->execute([$pid, $item['back'], 0, 2]);
        }
        // Đồng bộ toàn bộ kích cỡ Quần & Váy từ size số (28, 29, 30, 31, 32...) về chuẩn S, M, L, XL
        $pdo->exec("UPDATE product_variants SET size = 'S'  WHERE size IN ('28', '29', 'Freesize', 'Free')");
        $pdo->exec("UPDATE product_variants SET size = 'M'  WHERE size IN ('30')");
        $pdo->exec("UPDATE product_variants SET size = 'L'  WHERE size IN ('31')");
        $pdo->exec("UPDATE product_variants SET size = 'XL' WHERE size IN ('32', '33', '34')");

        // Đảm bảo tất cả 18 sản phẩm đều có đủ 4 size S, M, L, XL trong bảng product_variants
        $standardSizes = ['S', 'M', 'L', 'XL'];
        $checkVarStmt = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ? AND size = ?");
        $insVarStmt = $pdo->prepare("INSERT INTO product_variants (product_id, size, color, stock) VALUES (?, ?, 'Đen', 25)");

        foreach (array_keys($catalogProducts) as $pid) {
            foreach ($standardSizes as $sz) {
                $checkVarStmt->execute([$pid, $sz]);
                if ((int) $checkVarStmt->fetchColumn() === 0) {
                    $insVarStmt->execute([$pid, $sz]);
                }
            }
        }
        // Xóa các dòng bị trùng lặp size sau khi quy đổi và chỉ giữ lại S, M, L, XL
        $pdo->exec("DELETE FROM product_variants WHERE size NOT IN ('S', 'M', 'L', 'XL')");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Throwable $e) {
    }
}

function getHomeProductImage($product, $catalogMap)
{
    $id = (int) ($product['id'] ?? 0);
    $rawPath = $catalogMap[$id]['front'] ?? ($product['main_image'] ?? '');
    $clean = preg_replace('/^public\//', '', ltrim(trim($rawPath), '/'));

    foreach (['public/' . $clean, $clean] as $rel) {
        $full = __DIR__ . '/' . $rel;
        if (file_exists($full) && is_file($full)) {
            return $rel . '?v=' . filemtime($full);
        }
    }
    return 'public/' . $clean;
}

$newProductIds = [2, 4, 9, 22, 26];
$bestSellerIds = [3, 13, 14, 23, 25];
$featuredIds = [2, 3, 4, 13, 9, 23, 22, 25];
$inFeatured = implode(',', array_map('intval', $featuredIds));

$extraSubqueries = "
    (SELECT pv.id FROM product_variants pv WHERE pv.product_id = p.id ORDER BY pv.stock DESC, pv.id ASC LIMIT 1) AS default_variant_id,
    (SELECT pv.size FROM product_variants pv WHERE pv.product_id = p.id ORDER BY pv.stock DESC, pv.id ASC LIMIT 1) AS default_size,
    (SELECT pv.color FROM product_variants pv WHERE pv.product_id = p.id ORDER BY pv.stock DESC, pv.id ASC LIMIT 1) AS default_color
";

$latestProducts = [];
if ($pdo instanceof PDO) {
    try {
        $sql = "SELECT p.*, $extraSubqueries FROM products p WHERE p.status = 1 AND p.id IN ($inFeatured) ORDER BY FIELD(p.id, $inFeatured)";
        $stmt = $pdo->query($sql);
        $latestProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $stmt = $pdo->query("SELECT p.* FROM products p WHERE p.status = 1 AND p.id IN ($inFeatured) ORDER BY FIELD(p.id, $inFeatured)");
        $latestProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

require_once __DIR__ . '/my_header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap"
    rel="stylesheet">

<style>
    .home-main-wrap,
    .home-main-wrap a,
    .home-main-wrap button,
    .home-main-wrap input {
        font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
    }

    .home-main-wrap .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        box-sizing: border-box;
    }

    .home-main-wrap .mb-40 {
        margin-bottom: 60px;
    }

    .home-main-wrap .hero-banner-img-wrap {
        width: 100%;
        background: #111111;
        border-bottom: 4px solid #e60023;
        margin-bottom: 50px;
    }

    .home-main-wrap .hero-banner-box {
        position: relative;
        max-width: 1440px;
        margin: 0 auto;
        line-height: 0;
    }

    .home-main-wrap .hero-banner-img {
        width: 100%;
        height: auto;
        display: block;
    }

    .home-main-wrap .hero-cta-btn {
        position: absolute;
        left: 5.3%;
        bottom: 10%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #e60023;
        color: #ffffff !important;
        text-decoration: none !important;
        padding: 13px 26px;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 800;
        line-height: 1.2;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.45);
        cursor: pointer;
        z-index: 10;
        transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }

    .home-main-wrap .hero-cta-btn:hover {
        background: #111111;
        color: #ffffff !important;
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(230, 0, 35, 0.4);
    }

    .home-main-wrap .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-weight: 700;
        border-radius: 999px;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .home-main-wrap .btn-primary {
        background: #e60023;
        color: #ffffff;
        padding: 14px 32px;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 8px 20px rgba(230, 0, 35, 0.3);
    }

    .home-main-wrap .btn-primary:hover {
        background: #c8001e;
        transform: translateY(-2px);
    }

    .home-main-wrap .section-title {
        font-size: 26px;
        font-weight: 800;
        color: #111111;
        text-transform: uppercase;
        text-align: center;
        margin: 0 0 32px 0;
        padding: 0;
        border: none;
    }

    .home-main-wrap .section-title::after {
        display: none !important;
    }

    .home-main-wrap .product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 22px;
    }

    .home-main-wrap .prod-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        flex-direction: column;
    }

    .home-main-wrap .prod-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
    }

    .home-main-wrap .prod-img-box {
        width: 100%;
        aspect-ratio: 1 / 1;
        background: #f3f4f6;
        position: relative !important;
        overflow: hidden;
    }

    .home-main-wrap .prod-img-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .home-main-wrap .badge-new-red {
        position: absolute !important;
        top: 10px !important;
        right: 10px !important;
        left: auto !important;
        background: #e60023 !important;
        color: #ffffff !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        line-height: 1.2 !important;
        padding: 4px 9px !important;
        border-radius: 4px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        z-index: 5 !important;
        display: inline-block !important;
    }

    .home-main-wrap .prod-body {
        padding: 14px;
        display: flex;
        flex-direction: column;
        flex: 1;
        justify-content: space-between;
    }

    .home-main-wrap .prod-title {
        font-size: 15px;
        font-weight: 700;
        color: #111111;
        text-decoration: none;
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 42px;
    }

    .home-main-wrap .prod-title:hover {
        color: #e60023;
    }

    .home-main-wrap .prod-price-row {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 12px;
    }

    .home-main-wrap .prod-price-current {
        font-size: 17px;
        font-weight: 900;
        color: #e60023;
    }

    .home-main-wrap .prod-price-old {
        font-size: 13px;
        color: #888888;
        text-decoration: line-through;
    }

    .home-main-wrap .card-cart-form {
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px solid #f1f1f1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .home-main-wrap .card-action-top {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        width: 100% !important;
    }

    .home-main-wrap .card-qty-pill {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        width: 92px !important;
        height: 38px !important;
        border: 1.5px solid #d1d5db !important;
        border-radius: 999px !important;
        padding: 0 4px !important;
        background: #ffffff !important;
        box-sizing: border-box !important;
        flex-shrink: 0 !important;
    }

    .home-main-wrap .card-qty-pill:focus-within {
        border-color: #e60023 !important;
        box-shadow: 0 0 0 2px #fff0f2 !important;
    }

    .home-main-wrap .card-qty-pill button {
        width: 25px !important;
        height: 25px !important;
        border-radius: 50% !important;
        border: none !important;
        background: #f3f4f6 !important;
        color: #111111 !important;
        font-size: 15px !important;
        font-weight: 800 !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        flex-shrink: 0 !important;
        transition: all 0.15s ease !important;
    }

    .home-main-wrap .card-qty-pill button:hover {
        background: #e60023 !important;
        color: #ffffff !important;
    }

    .home-main-wrap .card-qty-pill input {
        width: 32px !important;
        height: 28px !important;
        border: none !important;
        background: transparent !important;
        text-align: center !important;
        font-size: 13.5px !important;
        font-weight: 800 !important;
        color: #111111 !important;
        outline: none !important;
        padding: 0 !important;
    }

    .home-main-wrap .card-qty-pill input::-webkit-outer-spin-button,
    .home-main-wrap .card-qty-pill input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .home-main-wrap .card-qty-pill input[type="number"] {
        -moz-appearance: textfield;
    }

    .home-main-wrap .card-btn-addcart {
        flex: 1 !important;
        height: 38px !important;
        background: #ffffff !important;
        color: #111111 !important;
        border: 1.5px solid #111111 !important;
        border-radius: 999px !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        cursor: pointer !important;
        padding: 0 6px !important;
        white-space: nowrap !important;
        transition: all 0.18s ease !important;
    }

    .home-main-wrap .card-btn-addcart:hover {
        background: #111111 !important;
        color: #ffffff !important;
    }

    .home-main-wrap .card-btn-buynow {
        width: 100% !important;
        height: 38px !important;
        background: #e60023 !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 999px !important;
        font-size: 12.5px !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        cursor: pointer !important;
        transition: all 0.18s ease !important;
    }

    .home-main-wrap .card-btn-buynow:hover {
        background: #c8001e !important;
    }

    .home-main-wrap .view-all-wrap {
        text-align: center;
        margin-top: 36px;
    }

    .home-main-wrap .btn-view-all {
        padding: 12px 30px;
        background: #111111;
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .home-main-wrap .btn-view-all:hover {
        background: #e60023;
    }

    @media (max-width: 992px) {
        .home-main-wrap .product-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .home-main-wrap .hero-cta-btn {
            font-size: 13px;
            padding: 9px 18px;
            bottom: 8%;
        }

        .home-main-wrap .hero-banner h1 {
            font-size: 30px;
        }
    }
</style>

<div class="home-main-wrap">
    <section class="hero-banner-img-wrap">
        <div class="hero-banner-box">
            <img src="<?= e($heroBannerUrl) ?>" alt="VIBEZ.CLUB - Định hình chất riêng Gen Z" class="hero-banner-img">
            <a href="products.php" class="hero-cta-btn">KHÁM PHÁ NGAY &rarr;</a>
        </div>
    </section>

    <section class="container mb-40">
        <h2 class="section-title">Sản phẩm nổi bật</h2>

        <?php if (!empty($latestProducts)): ?>
            <div class="product-grid">
                <?php foreach ($latestProducts as $p): ?>
                    <?php
                    $pid = (int) ($p['id'] ?? 0);
                    $prodName = $catalogProducts[$pid]['name'] ?? ($p['name'] ?? 'Sản phẩm');
                    $currPrice = (!empty($p['sale_price']) && $p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
                    $oldPrice = (!empty($p['sale_price']) && $p['sale_price'] > 0) ? $p['price'] : null;
                    $imgUrl = getHomeProductImage($p, $catalogProducts);
                    $isNew = in_array($pid, $newProductIds, true);
                    $defVariant = (int) ($p['default_variant_id'] ?? 0);
                    $defSize = $p['default_size'] ?? 'M';
                    $defColor = $p['default_color'] ?? 'Đen';
                    ?>
                    <div class="prod-card">
                        <div class="prod-img-box">
                            <?php if ($isNew): ?>
                                <span class="badge-new-red">NEW</span>
                            <?php else: ?>
                                <span class="badge-new-red">HOT</span>
                            <?php endif; ?>
                            <a href="product_detail.php?id=<?= $pid ?>">
                                <img src="<?= e($imgUrl) ?>" alt="<?= e($prodName) ?>"
                                    onerror="this.onerror=null; this.src='https://placehold.co/400x400/eef0f5/687082?text=GenZWear';">
                            </a>
                        </div>
                        <div class="prod-body">
                            <div>
                                <a href="product_detail.php?id=<?= $pid ?>" class="prod-title">
                                    <?= e($prodName) ?>
                                </a>
                                <div class="prod-price-row">
                                    <span
                                        class="prod-price-current"><?= number_format((float) $currPrice, 0, ',', '.') ?>đ</span>
                                    <?php if ($oldPrice !== null): ?>
                                        <span class="prod-price-old"><?= number_format((float) $oldPrice, 0, ',', '.') ?>đ</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <form action="cart.php" method="POST" class="card-cart-form">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= $pid ?>">
                                <input type="hidden" name="id" value="<?= $pid ?>">
                                <input type="hidden" name="variant_id" value="<?= $defVariant ?>">
                                <input type="hidden" name="size" value="<?= e($defSize) ?>">
                                <input type="hidden" name="color" value="<?= e($defColor) ?>">

                                <div class="card-action-top">
                                    <div class="card-qty-pill">
                                        <button type="button" onclick="adjustCardQty(this, -1)">-</button>
                                        <input type="number" name="quantity" value="1" min="1" max="999"
                                            oninput="validateCardQty(this)" onblur="fixEmptyCardQty(this)">
                                        <button type="button" onclick="adjustCardQty(this, 1)">+</button>
                                    </div>
                                    <button type="submit" class="card-btn-addcart">THÊM VÀO GIỎ HÀNG</button>
                                </div>

                                <button type="submit" name="buy_now" value="1" class="card-btn-buynow">MUA NGAY</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="text-align:center; padding:40px;">
                <p>Hiện chưa có sản phẩm nào được cập nhật.</p>
            </div>
        <?php endif; ?>

        <div class="view-all-wrap text-center">
            <a href="products.php" class="btn btn-view-all">Tất cả sản phẩm →</a>
        </div>
    </section>
</div>

<script>
    function adjustCardQty(btnEl, delta) {
        var pill = btnEl.closest('.card-qty-pill');
        if (!pill) return;
        var input = pill.querySelector('input[name="quantity"]');
        if (!input) return;
        var current = parseInt(input.value, 10) || 1;
        var next = current + delta;
        if (next < 1) next = 1;
        if (next > 999) next = 999;
        input.value = next;
    }

    function validateCardQty(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value !== '' && parseInt(input.value, 10) > 999) {
            input.value = 999;
        }
    }

    function fixEmptyCardQty(input) {
        var val = parseInt(input.value, 10);
        if (isNaN(val) || val < 1) {
            input.value = 1;
        }
    }
</script>

<?php require_once __DIR__ . '/my_footer.php'; ?>