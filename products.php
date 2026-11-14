<?php
/**
 * Trang Sản phẩm & Tab Hàng Mới / Hàng Bán Chạy (products.php) 
 * Tone màu chủ đạo: Trắng (#ffffff) - Đen (#111111) - Đỏ (#e60023)
 */

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

$db = null;
if (isset($pdo) && $pdo instanceof PDO) {
    $db = $pdo;
} elseif (isset($conn) && $conn instanceof PDO) {
    $db = $conn;
}

if (!($db instanceof PDO)) {
    $dbHost = defined('DB_HOST') ? DB_HOST : ($host ?? '127.0.0.1');
    $dbName = defined('DB_NAME') ? DB_NAME : ($dbname ?? $database ?? 'shop_quanao');
    $dbUser = defined('DB_USER') ? DB_USER : ($username ?? $user ?? 'root');
    $portsToTry = defined('DB_PORT') ? [DB_PORT, 3306, 3307, 3308] : [3306, 3307, 3308];
    $localPasswordsToTry = ['', '123456', 'root', '12345678', '1234', 'admin'];
    if (defined('DB_PASS') && !in_array(DB_PASS, $localPasswordsToTry, true)) {
        array_unshift($localPasswordsToTry, DB_PASS);
    }

    $lastError = '';
    foreach ($portsToTry as $port) {
        foreach ([$dbHost, '127.0.0.1', 'localhost'] as $tryHost) {
            foreach ($localPasswordsToTry as $tryPass) {
                try {
                    $db = new PDO("mysql:host={$tryHost};port={$port};dbname={$dbName};charset=utf8mb4", $dbUser, $tryPass, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    $pdo = $db;
                    break 3;
                } catch (Throwable $e) {
                    $lastError = $e->getMessage();
                }
            }
        }
    }

    if (!($db instanceof PDO)) {
        die("Lỗi kết nối CSDL: " . htmlspecialchars($lastError));
    }
}

$catalogProducts = [
    1  => ['name' => 'Áo polo cổ tròn',          'slug' => 'ao-polo-co-tron',          'cat' => 2, 'price' => 279000, 'sale' => 239000, 'front' => 'uploads/products/Ao-polo-co-tron-mat-truoc.jpg',          'back' => 'uploads/products/Ao-polo-tron-mat-sau.webp',               'desc' => '<p>Áo polo cổ tròn thiết kế trẻ trung, chất vải pique thoáng mát, thấm hút mồ hôi tốt.</p>'],
    2  => ['name' => 'Áo thun oversize basic',   'slug' => 'ao-thun-oversize-basic',   'cat' => 1, 'price' => 249000, 'sale' => null,   'front' => 'uploads/products/Ao-thun-oversize-basic-mat-truoc.jpg',   'back' => 'uploads/products/Ao-thun-oversize-basic-mat-sau.webp',     'desc' => '<p>Áo thun oversize basic cotton 100% dày dặn, form rộng chuẩn phong cách Streetwear Gen Z.</p>'],
    3  => ['name' => 'Áo polo trơn',             'slug' => 'ao-polo-tron',             'cat' => 2, 'price' => 299000, 'sale' => 259000, 'front' => 'uploads/products/Ao-polo-tron-mat-truoc.jpg',             'back' => 'uploads/products/Ao-polo-tron-mat-sau.webp',               'desc' => '<p>Áo polo trơn vải cá sấu thoáng khí, cổ dệt bền form, phù hợp mặc đi học, đi làm và dạo phố.</p>'],
    4  => ['name' => 'Áo thun in logo',          'slug' => 'ao-thun-in-logo',          'cat' => 1, 'price' => 229000, 'sale' => null,   'front' => 'uploads/products/Ao-thun-in-logo-mat-truoc.jpg',          'back' => 'uploads/products/Ao-thun-in-logo-mat-sau.webp',            'desc' => '<p>Áo thun in logo sắc nét, chất cotton mềm mịn, giặt máy nhiều lần không bong tróc.</p>'],
    5  => ['name' => 'Áo croptop',               'slug' => 'ao-croptop',               'cat' => 1, 'price' => 169000, 'sale' => 129000, 'front' => 'uploads/products/Ao-croptop-mat-truoc.jpg',               'back' => 'uploads/products/Ao-croptop-mat-sau.webp',                 'desc' => '<p>Áo croptop ôm dáng vừa vặn, thun co giãn 4 chiều thoải mái, dễ phối cùng quần cạp cao.</p>'],
    9  => ['name' => 'Áo sơ mi linen nữ tay dài','slug' => 'ao-so-mi-linen-nu-tay-dai','cat' => 2, 'price' => 349000, 'sale' => null,   'front' => 'uploads/products/Ao-so-mi-linen-nu-tay-dai-mat-truoc.jpg','back' => 'uploads/products/Ao-so-mi-linen-nu-tay-dai-mat-sau.webp',  'desc' => '<p>Áo sơ mi linen nữ tay dài chất vải tự nhiên thoáng mát, nhẹ nhàng, dễ phối đồ đi học hay đi làm.</p>'],
    12 => ['name' => 'Quần jeans straight',      'slug' => 'quan-jeans-straight',      'cat' => 3, 'price' => 549000, 'sale' => null,   'front' => 'uploads/products/Quan-jeans-straight-mat-truoc.jpg',      'back' => 'uploads/products/Quan-jeans-straight-mat-sau.webp',        'desc' => '<p>Quần jeans ống đứng (straight fit) gọn gàng, chất denim dày dặn, màu wash xanh dễ phối đồ.</p>'],
    13 => ['name' => 'Quần jeans ống rộng',      'slug' => 'quan-jeans-ong-rong',      'cat' => 3, 'price' => 529000, 'sale' => 459000, 'front' => 'uploads/products/Quan-jeans-ong-rong-mat-truoc.jpg',      'back' => 'uploads/products/Quan-jeans-ong-rong-mat-sau.webp',        'desc' => '<p>Quần jeans ống rộng cạp cao tôn dáng, che khuyết điểm tốt, chất vải denim mềm mại.</p>'],
    14 => ['name' => 'Quần tây ống rộng',        'slug' => 'quan-tay-ong-rong',        'cat' => 3, 'price' => 479000, 'sale' => null,   'front' => 'uploads/products/Quan-tay-ong-rong-mat-truoc.jpg',        'back' => 'uploads/products/Quan-tay-ong-rong-mat-sau.webp',          'desc' => '<p>Quần tây ống rộng suông thẳng hiện đại, vải dày dặn chống nhăn, lên form chuẩn Hàn Quốc.</p>'],
    15 => ['name' => 'Quần kaki',                'slug' => 'quan-kaki',                'cat' => 3, 'price' => 449000, 'sale' => null,   'front' => 'uploads/products/Quan-kaki-mat-truoc.jpg',                'back' => 'uploads/products/Quan-kaki-mat-sau.webp',                  'desc' => '<p>Quần kaki form rộng rãi thoải mái, chất vải kaki bền màu, đường may tỉ mỉ chắc chắn.</p>'],
    16 => ['name' => 'Quần short thể thao',      'slug' => 'quan-short-the-thao',      'cat' => 3, 'price' => 259000, 'sale' => 219000, 'front' => 'uploads/products/Quan-short-the-thao-mat-truoc.jpg',      'back' => 'uploads/products/Quan-short-the-thao-mat-sau.webp',        'desc' => '<p>Quần short thể thao năng động, lưng thun co giãn kèm dây rút tiện lợi, thoáng mát cả ngày.</p>'],
    17 => ['name' => 'Chân váy midi',            'slug' => 'chan-vay-midi',            'cat' => 4, 'price' => 389000, 'sale' => null,   'front' => 'uploads/products/Chan-vay-midi-mat-truoc.jpg',            'back' => 'uploads/products/Chan-vay-midi-mat-sau.webp',              'desc' => '<p>Chân váy midi dáng dài thanh lịch, chất vải mềm rủ tự nhiên, tôn dáng nhẹ nhàng.</p>'],
    19 => ['name' => 'Quần short nữ',            'slug' => 'quan-short-nu',            'cat' => 3, 'price' => 289000, 'sale' => null,   'front' => 'uploads/products/Quan-short-nu-mat-truoc.jpg',            'back' => 'uploads/products/Quan-short-nu-mat-sau.webp',              'desc' => '<p>Quần short nữ cạp cao trẻ trung, ống rộng vừa phải giúp tôn dáng và thoải mái vận động.</p>'],
    21 => ['name' => 'Chân váy xếp ly',          'slug' => 'chan-vay-xep-ly',          'cat' => 4, 'price' => 359000, 'sale' => 319000, 'front' => 'uploads/products/Chan-vay-xep-ly-mat-truoc.jpg',          'back' => 'uploads/products/Chan-vay-xep-li-mat-sau.webp',            'desc' => '<p>Chân váy xếp ly trẻ trung, có quần lót bảo hộ bên trong, nếp gấp sắc nét giữ form lâu.</p>'],
    22 => ['name' => 'Áo khoác denim',           'slug' => 'ao-khoac-denim',           'cat' => 5, 'price' => 649000, 'sale' => null,   'front' => 'uploads/products/Ao-khoac-denim-mat-truoc.jpg',           'back' => 'uploads/products/Ao-khoac-denim-mat-sau.webp',             'desc' => '<p>Áo khoác denim unisex cá tính, chất jean wash dày dặn, khuy cài kim loại bền đẹp.</p>'],
    23 => ['name' => 'Áo khoác varsity',         'slug' => 'ao-khoac-varsity',         'cat' => 7, 'price' => 589000, 'sale' => 499000, 'front' => 'uploads/products/Ao-khoac-varsity-mat-truoc.jpg',         'back' => 'uploads/products/Ao-khoac-varsity-mat-sau.webp',           'desc' => '<p>Áo khoác varsity phối tay nổi bật, form rộng chuẩn Streetwear, bo cổ và cổ tay dày dặn.</p>'],
    25 => ['name' => 'Áo khoác hoodie oversize', 'slug' => 'ao-khoac-hoodie-oversize', 'cat' => 8, 'price' => 449000, 'sale' => null,   'front' => 'uploads/products/Ao-khoac-hoodie-oversize-mat-truoc.jpg', 'back' => 'uploads/products/Ao-khoac-hoodie-oversize-mat-sau.webp',   'desc' => '<p>Áo khoác hoodie oversize nỉ bông mềm ấm, mũ 2 lớp đứng form kèm túi trước rộng rãi.</p>'],
    26 => ['name' => 'Áo khoác hoodie zip',      'slug' => 'ao-khoac-hoodie-zip',      'cat' => 8, 'price' => 479000, 'sale' => 419000, 'front' => 'uploads/products/Ao-khoac-hoodie-zip-mat-truoc.jpg',      'back' => 'uploads/products/Ao-khoac-hoodie-zip-mat-sau.webp',        'desc' => '<p>Áo khoác hoodie zip khóa kéo tiện lợi, vải nỉ da cá dày vừa phải, mặc khoác nắng hay giữ ấm đều hợp.</p>'],
];

$validProductIds = array_keys($catalogProducts);
$validIdsSql     = implode(',', $validProductIds);

try {
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("DELETE FROM products WHERE id NOT IN ($validIdsSql)");
    $db->exec("DELETE FROM product_images WHERE product_id NOT IN ($validIdsSql)");

    $upStmt = $db->prepare("UPDATE products SET name = ?, slug = ?, category_id = ?, price = ?, sale_price = ?, description = ?, status = 1 WHERE id = ?");
    $delImg = $db->prepare("DELETE FROM product_images WHERE product_id = ?");
    $insImg = $db->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?)");

    foreach ($catalogProducts as $pid => $item) {
        $upStmt->execute([$item['name'], $item['slug'], $item['cat'], $item['price'], $item['sale'], $item['desc'], $pid]);
        $delImg->execute([$pid]);
        $insImg->execute([$pid, $item['front'], 1, 1]);
        $insImg->execute([$pid, $item['back'], 0, 2]);
    }
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
} catch (Throwable $e) {
}

if (!function_exists('findProductImage')) {
    function findProductImage($productId, $catalogMap) {
        $rawImg = $catalogMap[$productId]['front'] ?? '';
        $cleanDbImg = preg_replace('/^public\//', '', ltrim(trim($rawImg), '/'));
        foreach (['public/' . $cleanDbImg, $cleanDbImg] as $relPath) {
            $fullPath = __DIR__ . '/' . $relPath;
            if (file_exists($fullPath) && is_file($fullPath)) {
                return $relPath . '?v=' . filemtime($fullPath);
            }
        }
        return 'public/' . $cleanDbImg;
    }
}

$rawTab  = isset($_GET['tab']) ? trim($_GET['tab']) : '';
$rawSort = isset($_GET['sort']) ? trim($_GET['sort']) : '';
$mode    = $rawTab !== '' ? $rawTab : $rawSort;

$currentTab  = 'all';
$sortOrder   = '';
$pageHeading = 'TẤT CẢ SẢN PHẨM';

if (in_array($mode, ['new', 'newest', 'hang-moi'], true)) {
    $currentTab  = 'new';
    $sortOrder   = 'new';
    $pageHeading = 'HÀNG MỚI VỀ';
} elseif (in_array($mode, ['bestseller', 'best', 'hot', 'ban-chay'], true)) {
    $currentTab  = 'bestseller';
    $sortOrder   = 'bestseller';
    $pageHeading = 'HÀNG BÁN CHẠY';
} elseif ($mode === 'price_asc') {
    $sortOrder = 'price_asc';
} elseif ($mode === 'price_desc') {
    $sortOrder = 'price_desc';
}

$newProductIds = [2, 4, 9, 22, 26];
$bestSellerIds = [3, 13, 14, 23, 25];
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : '';
$priceRange   = isset($_GET['price']) ? trim($_GET['price']) : '';
$keyword      = isset($_GET['keyword']) ? trim($_GET['keyword']) : (isset($_GET['q']) ? trim($_GET['q']) : '');

if ($keyword !== '') {
    $pageHeading = 'KẾT QUẢ TÌM KIẾM: "' . mb_strtoupper($keyword, 'UTF-8') . '"';
}

$products   = [];
$totalPages = 1;
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit      = 9;
$offset     = ($page - 1) * $limit;

$where = [
    "p.status = 1",
    "p.id IN ($validIdsSql)"
];
$params = [];

if ($currentTab === 'new') {
    $inQuery = implode(',', array_map('intval', $newProductIds));
    $where[] = "p.id IN ($inQuery)";
} elseif ($currentTab === 'bestseller') {
    $inQuery = implode(',', array_map('intval', $bestSellerIds));
    $where[] = "p.id IN ($inQuery)";
}

if ($categorySlug !== '') {
    if ($categorySlug === 'ao-thun') {
        $where[] = "p.category_id = 1";
    } elseif ($categorySlug === 'ao-so-mi') {
        $where[] = "p.category_id = 2";
    } elseif ($categorySlug === 'ao-khoac') {
        $where[] = "p.category_id IN (5, 7, 8)";
    } elseif ($categorySlug === 'quan-jean') {
        $where[] = "p.category_id = 3";
    } elseif ($categorySlug === 'vay-dam') {
        $where[] = "p.category_id = 4";
    }
}

if ($keyword !== '') {
    $vnAccents = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
        'À'=>'a','Á'=>'a','Ạ'=>'a','Ả'=>'a','Ã'=>'a','Â'=>'a','Ầ'=>'a','Ấ'=>'a','Ậ'=>'a','Ẩ'=>'a','Ẫ'=>'a','Ă'=>'a','Ằ'=>'a','Ắ'=>'a','Ặ'=>'a','Ẳ'=>'a','Ẵ'=>'a',
        'È'=>'e','É'=>'e','Ẹ'=>'e','Ẻ'=>'e','Ẽ'=>'e','Ê'=>'e','Ề'=>'e','Ế'=>'e','Ệ'=>'e','Ể'=>'e','Ễ'=>'e',
        'Ì'=>'i','Í'=>'i','Ị'=>'i','Ỉ'=>'i','Ĩ'=>'i',
        'Ò'=>'o','Ó'=>'o','Ọ'=>'o','Ỏ'=>'o','Õ'=>'o','Ô'=>'o','Ồ'=>'o','Ố'=>'o','Ộ'=>'o','Ổ'=>'o','Ỗ'=>'o','Ơ'=>'o','Ờ'=>'o','Ớ'=>'o','Ợ'=>'o','Ở'=>'o','Ỡ'=>'o',
        'Ù'=>'u','Ú'=>'u','Ụ'=>'u','Ủ'=>'u','Ũ'=>'u','Ư'=>'u','Ừ'=>'u','Ứ'=>'u','Ự'=>'u','Ử'=>'u','Ữ'=>'u',
        'Ỳ'=>'y','Ý'=>'y','Ỵ'=>'y','Ỷ'=>'y','Ỹ'=>'y','Đ'=>'d'
    ];
    $kwSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtr(mb_strtolower($keyword, 'UTF-8'), $vnAccents)), '-');

    $where[] = "(p.name LIKE :kw OR p.slug LIKE :kw_slug)";
    $params[':kw']      = '%' . $keyword . '%';
    $params[':kw_slug'] = '%' . ($kwSlug !== '' ? $kwSlug : $keyword) . '%';
}

if ($priceRange === 'under200') {
    $where[] = "COALESCE(NULLIF(p.sale_price, 0), p.price) < 200000";
} elseif ($priceRange === '200to350' || $priceRange === '200to400') {
    $where[] = "COALESCE(NULLIF(p.sale_price, 0), p.price) BETWEEN 200000 AND 350000";
} elseif ($priceRange === 'over350' || $priceRange === 'over400') {
    $where[] = "COALESCE(NULLIF(p.sale_price, 0), p.price) > 350000";
}

$whereSql = implode(' AND ', $where);

$orderBySql = "p.id ASC";
if ($sortOrder === 'price_asc') {
    $orderBySql = "COALESCE(NULLIF(p.sale_price, 0), p.price) ASC";
} elseif ($sortOrder === 'price_desc') {
    $orderBySql = "COALESCE(NULLIF(p.sale_price, 0), p.price) DESC";
}

$extraSubqueries = "
    (SELECT pv.id FROM product_variants pv WHERE pv.product_id = p.id ORDER BY pv.stock DESC, pv.id ASC LIMIT 1) AS default_variant_id,
    (SELECT pv.size FROM product_variants pv WHERE pv.product_id = p.id ORDER BY pv.stock DESC, pv.id ASC LIMIT 1) AS default_size,
    (SELECT pv.color FROM product_variants pv WHERE pv.product_id = p.id ORDER BY pv.stock DESC, pv.id ASC LIMIT 1) AS default_color
";

try {
    if ($currentTab === 'new' || $currentTab === 'bestseller') {
        $sql  = "SELECT p.*, $extraSubqueries FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereSql ORDER BY $orderBySql LIMIT 5";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $countStmt = $db->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereSql");
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalItems / $limit));

        $sql  = "SELECT p.*, $extraSubqueries FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereSql ORDER BY $orderBySql LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $sql  = "SELECT p.* FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereSql ORDER BY $orderBySql LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buildFilterUrl($overrides = []) {
    $params = $_GET;
    unset($params['page']);
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    $qs = http_build_query($params);
    return 'products.php' . ($qs !== '' ? '?' . $qs : '');
}

if ($currentTab === 'new') {
    $pageTitle = 'Hàng Mới Về | VIBEZ.CLUB';
} elseif ($currentTab === 'bestseller') {
    $pageTitle = 'Hàng Bán Chạy | VIBEZ.CLUB';
} else {
    $pageTitle = 'Tất Cả Sản Phẩm | VIBEZ.CLUB';
}

if (file_exists(__DIR__ . '/my_header.php')) {
    require_once __DIR__ . '/my_header.php';
} elseif (file_exists(__DIR__ . '/includes/header.php')) {
    require_once __DIR__ . '/includes/header.php';
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
    .catalog-wrap,
    .catalog-wrap button,
    .catalog-wrap input,
    .catalog-wrap a {
        font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
    }

    .catalog-wrap {
        max-width: 1220px;
        margin: 36px auto 70px;
        padding: 0 20px;
        color: #111111;
    }

    .catalog-layout {
        display: flex;
        align-items: flex-start;
        gap: 32px;
        position: relative;
    }

    /* THANH BỘ LỌC BÊN TRÁI */
    .filter-sidebar-wrap {
        position: relative;
        width: 280px;
        flex-shrink: 0;
        transition: width 0.28s ease;
    }

    .filter-sidebar-wrap.collapsed {
        width: 0;
        margin-right: -12px;
    }

    .filter-sidebar {
        background: #ffffff;
        border: 1px solid #eeeeee;
        border-radius: 16px;
        padding: 22px 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        transition: opacity 0.22s ease, transform 0.25s ease;
    }

    .filter-sidebar-wrap.collapsed .filter-sidebar {
        opacity: 0;
        pointer-events: none;
        transform: translateX(-20px);
    }

    .filter-collapse-handle {
        position: absolute;
        top: 50%;
        right: -18px;
        transform: translateY(-50%);
        width: 22px;
        height: 48px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-left: none;
        border-radius: 0 8px 8px 0;
        box-shadow: 3px 0 8px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #9ca3af;
        z-index: 10;
        transition: color 0.2s ease, background 0.2s ease;
    }

    .filter-collapse-handle:hover {
        color: #e60023;
        background: #fff1f2;
    }

    .filter-top-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 14px;
        margin-bottom: 18px;
        border-bottom: 2px solid #111111;
    }

    .filter-top-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
        font-weight: 900;
        color: #111111;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .filter-reset-link {
        font-size: 13.5px;
        font-weight: 600;
        color: #e60023;
        text-decoration: none;
    }

    .filter-reset-link:hover {
        text-decoration: underline;
    }

    .filter-section {
        padding-bottom: 16px;
        margin-bottom: 18px;
        border-bottom: 1px solid #f1f1f1;
    }

    .filter-section:last-child {
        padding-bottom: 0;
        margin-bottom: 0;
        border-bottom: none;
    }

    .filter-sec-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        padding: 4px 0;
        margin-bottom: 10px;
    }

    .filter-sec-title {
        font-size: 13.5px;
        font-weight: 800;
        color: #111111;
        text-transform: uppercase;
    }

    .filter-sec-arrow {
        width: 16px;
        height: 16px;
        color: #374151;
        transition: transform 0.22s ease;
    }

    .filter-section.closed .filter-sec-arrow {
        transform: rotate(-90deg);
    }

    .filter-section.closed .filter-sec-body {
        display: none;
    }

    .filter-options {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .filter-option-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 12px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        color: #222222;
        transition: background 0.18s ease, color 0.18s ease;
    }

    .filter-option-link:hover {
        background: #f9fafb;
        color: #e60023;
    }

    .filter-option-link.active {
        background: #fff0f2;
        color: #e60023;
        font-weight: 700;
    }

    .filter-check-icon {
        display: none;
        width: 16px;
        height: 16px;
        color: #e60023;
        flex-shrink: 0;
    }

    .filter-option-link.active .filter-check-icon {
        display: block;
    }

    .catalog-main {
        flex: 1;
        min-width: 0;
    }

    .catalog-header {
        margin-bottom: 22px;
        padding-bottom: 12px;
        border-bottom: 1.5px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .catalog-heading {
        font-size: 24px;
        font-weight: 800;
        text-transform: uppercase;
        margin: 0;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 22px;
    }

    .prod-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        flex-direction: column;
    }

    .prod-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
    }

    .prod-img-box {
        width: 100%;
        aspect-ratio: 1 / 1;
        background: #f3f4f6;
        position: relative !important;
        overflow: hidden;
    }

    .prod-img-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .badge-new-red {
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

    .prod-body {
        padding: 14px;
        display: flex;
        flex-direction: column;
        flex: 1;
        justify-content: space-between;
    }

    .prod-title {
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

    .prod-title:hover {
        color: #e60023;
    }

    .prod-price-row {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 12px;
    }

    .prod-price-current {
        font-size: 17px;
        font-weight: 900;
        color: #e60023;
    }

    .prod-price-old {
        font-size: 13px;
        color: #888888;
        text-decoration: line-through;
    }

    .card-cart-form {
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px solid #f1f1f1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .card-action-top {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        width: 100% !important;
    }

    .card-qty-pill {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        width: 96px !important;
        height: 38px !important;
        border: 1.5px solid #d1d5db !important;
        border-radius: 999px !important;
        padding: 0 4px !important;
        background: #ffffff !important;
        box-sizing: border-box !important;
        flex-shrink: 0 !important;
    }

    .card-qty-pill:focus-within {
        border-color: #e60023 !important;
        box-shadow: 0 0 0 2px #fff0f2 !important;
    }

    .card-qty-pill button {
        width: 26px !important;
        height: 26px !important;
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

    .card-qty-pill button:hover {
        background: #e60023 !important;
        color: #ffffff !important;
    }

    .card-qty-pill input {
        width: 34px !important;
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

    .card-qty-pill input::-webkit-outer-spin-button,
    .card-qty-pill input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .card-qty-pill input[type="number"] {
        -moz-appearance: textfield;
    }

    .card-btn-addcart {
        flex: 1 !important;
        height: 38px !important;
        background: #ffffff !important;
        color: #111111 !important;
        border: 1.5px solid #111111 !important;
        border-radius: 999px !important;
        font-size: 11.5px !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        cursor: pointer !important;
        padding: 0 8px !important;
        white-space: nowrap !important;
        transition: all 0.18s ease !important;
    }

    .card-btn-addcart:hover {
        background: #111111 !important;
        color: #ffffff !important;
    }

    .card-btn-buynow {
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

    .card-btn-buynow:hover {
        background: #c8001e !important;
    }

    .round-pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 36px;
    }

    .page-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 1.5px solid #d1d5db;
        background: #ffffff;
        color: #111111;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .page-circle:hover,
    .page-circle.active {
        background: #111111;
        color: #ffffff;
        border-color: #111111;
    }

    @media (max-width: 900px) {
        .catalog-layout {
            flex-direction: column;
        }
        .filter-sidebar-wrap {
            width: 100%;
        }
        .filter-collapse-handle {
            display: none;
        }
        .product-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="catalog-wrap">
    <div class="catalog-layout">
        <div id="filterSidebarWrap" class="filter-sidebar-wrap">
            <aside class="filter-sidebar">
                <div class="filter-top-header">
                    <div class="filter-top-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#e60023" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 4C3 3.44772 3.44772 3 4 3H20C20.5523 3 21 3.44772 21 4V6.58579C21 6.851 20.8946 7.10536 20.7071 7.29289L14.2929 13.7071C14.1054 13.8946 14 14.149 14 14.4142V19L10 21V14.4142C10 14.149 9.89464 13.8946 9.70711 13.7071L3.29289 7.29289C3.10536 7.10536 3 6.851 3 6.58579V4Z"/>
                        </svg>
                        <span>BỘ LỌC</span>
                    </div>
                    <a href="products.php" class="filter-reset-link">Xóa lọc</a>
                </div>

                <div class="filter-section">
                    <div class="filter-sec-head" onclick="toggleFilterSection(this)">
                        <span class="filter-sec-title">SẮP XẾP THEO</span>
                        <svg class="filter-sec-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="filter-sec-body">
                        <ul class="filter-options">
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['tab' => 'new', 'sort' => null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'new' ? 'active' : '' ?>">
                                    <span>Mới nhất</span>
                                    <svg class="filter-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </a>
                            </li>
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['tab' => 'bestseller', 'sort' => null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'bestseller' ? 'active' : '' ?>">
                                    <span>Bán chạy nhất</span>
                                    <svg class="filter-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </a>
                            </li>
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['sort' => 'price_asc', 'tab' => $currentTab !== 'all' ? $currentTab : null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'price_asc' ? 'active' : '' ?>">
                                    <span>Giá: Thấp đến Cao</span>
                                    <svg class="filter-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </a>
                            </li>
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['sort' => 'price_desc', 'tab' => $currentTab !== 'all' ? $currentTab : null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'price_desc' ? 'active' : '' ?>">
                                    <span>Giá: Cao đến Thấp</span>
                                    <svg class="filter-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="filter-section">
                    <div class="filter-sec-head" onclick="toggleFilterSection(this)">
                        <span class="filter-sec-title">DANH MỤC SẢN PHẨM</span>
                        <svg class="filter-sec-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="filter-sec-body">
                        <?php
                        $catList = [
                            ''          => 'Tất cả sản phẩm',
                            'ao-thun'   => 'Áo Thun & Croptop',
                            'ao-so-mi'  => 'Áo Sơ Mi & Polo',
                            'ao-khoac'  => 'Áo Khoác, Varsity & Hoodie',
                            'quan-jean' => 'Quần Jeans, Tây, Kaki & Short',
                            'vay-dam'   => 'Chân Váy Midi & Xếp Ly'
                        ];
                        ?>
                        <ul class="filter-options">
                            <?php foreach ($catList as $slugKey => $catLabel): ?>
                                <li>
                                    <a href="<?= htmlspecialchars(buildFilterUrl(['category' => $slugKey !== '' ? $slugKey : null])) ?>"
                                       class="filter-option-link <?= $categorySlug === $slugKey ? 'active' : '' ?>">
                                        <span><?= htmlspecialchars($catLabel) ?></span>
                                        <svg class="filter-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="filter-section">
                    <div class="filter-sec-head" onclick="toggleFilterSection(this)">
                        <span class="filter-sec-title">KHOẢNG GIÁ</span>
                        <svg class="filter-sec-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="filter-sec-body">
                        <?php
                        $priceList = [
                            ''         => 'Tất cả mức giá',
                            'under200' => 'Dưới 200.000đ',
                            '200to350' => 'Từ 200.000đ - 350.000đ',
                            'over350'  => 'Trên 350.000đ'
                        ];
                        ?>
                        <ul class="filter-options">
                            <?php foreach ($priceList as $pKey => $pLabel): ?>
                                <li>
                                    <a href="<?= htmlspecialchars(buildFilterUrl(['price' => $pKey !== '' ? $pKey : null])) ?>"
                                       class="filter-option-link <?= $priceRange === $pKey ? 'active' : '' ?>">
                                        <span><?= htmlspecialchars($pLabel) ?></span>
                                        <svg class="filter-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </aside>

            <button type="button" id="filterCollapseBtn" class="filter-collapse-handle" onclick="toggleSidebarCollapse()" title="Thu gọn / Mở bộ lọc">
                &#10094;
            </button>
        </div>

        <main class="catalog-main">
            <div class="catalog-header">
                <h1 class="catalog-heading"><?= htmlspecialchars($pageHeading) ?></h1>
            </div>

            <?php if (!empty($products)): ?>
                <div class="product-grid">
                    <?php foreach ($products as $p): ?>
                        <?php
                        $pid        = (int)$p['id'];
                        $prodName   = $catalogProducts[$pid]['name'] ?? $p['name'];
                        $imgUrl     = findProductImage($pid, $catalogProducts);
                        $currPrice  = (!empty($p['sale_price']) && $p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
                        $oldPrice   = (!empty($p['sale_price']) && $p['sale_price'] > 0) ? $p['price'] : null;
                        $isNewItem  = in_array($pid, $newProductIds, true);
                        $isBestItem = in_array($pid, $bestSellerIds, true);
                        $defVariant = (int)($p['default_variant_id'] ?? 0);
                        $defSize    = $p['default_size'] ?? 'M';
                        $defColor   = $p['default_color'] ?? 'Đen';
                        ?>
                        <div class="prod-card">
                            <div class="prod-img-box">
                                <?php if ($currentTab === 'bestseller' || $isBestItem): ?>
                                    <span class="badge-new-red">HOT</span>
                                <?php elseif ($currentTab === 'new' || $isNewItem): ?>
                                    <span class="badge-new-red">NEW</span>
                                <?php endif; ?>
                                <a href="product_detail.php?id=<?= $pid ?>">
                                    <img src="<?= htmlspecialchars($imgUrl) ?>"
                                         alt="<?= htmlspecialchars($prodName) ?>"
                                         onerror="this.onerror=null; this.src='https://placehold.co/400x400/ebeef5/646c79?text=GenZWear';">
                                </a>
                            </div>
                            <div class="prod-body">
                                <div>
                                    <a href="product_detail.php?id=<?= $pid ?>" class="prod-title">
                                        <?= htmlspecialchars($prodName) ?>
                                    </a>
                                    <div class="prod-price-row">
                                        <span class="prod-price-current"><?= number_format((float)$currPrice, 0, ',', '.') ?>đ</span>
                                        <?php if ($oldPrice !== null): ?>
                                            <span class="prod-price-old"><?= number_format((float)$oldPrice, 0, ',', '.') ?>đ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <form action="cart.php" method="POST" class="card-cart-form">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?= $pid ?>">
                                    <input type="hidden" name="id" value="<?= $pid ?>">
                                    <input type="hidden" name="variant_id" value="<?= $defVariant ?>">
                                    <input type="hidden" name="size" value="<?= htmlspecialchars($defSize) ?>">
                                    <input type="hidden" name="color" value="<?= htmlspecialchars($defColor) ?>">

                                    <div class="card-action-top">
                                        <div class="card-qty-pill">
                                            <button type="button" onclick="adjustCardQty(this, -1)">-</button>
                                            <input type="number"
                                                   name="quantity"
                                                   value="1"
                                                   min="1"
                                                   max="999"
                                                   oninput="validateCardQty(this)"
                                                   onblur="fixEmptyCardQty(this)">
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
                <div style="text-align:center; padding:50px 20px; background:#fff; border:1px solid #eee; border-radius:12px;">
                    <p style="font-size:16px; color:#555; margin-bottom:14px;">Không tìm thấy sản phẩm nào khớp với từ khóa <strong>"<?= htmlspecialchars($keyword) ?>"</strong>.</p>
                    <a href="products.php" style="display:inline-block; padding:10px 24px; background:#e60023; color:#fff; border-radius:999px; text-decoration:none; font-weight:700;">Xem tất cả sản phẩm</a>
                </div>
            <?php endif; ?>

            <?php if ($currentTab === 'all' && $totalPages > 1): ?>
                <div class="round-pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php
                        $queryParams = $_GET;
                        $queryParams['page'] = $i;
                        $pageUrl = 'products.php?' . http_build_query($queryParams);
                        ?>
                        <a href="<?= htmlspecialchars($pageUrl) ?>" class="page-circle <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>

    </div>
</div>

<script>
    function toggleFilterSection(headEl) {
        var section = headEl.closest('.filter-section');
        if (section) {
            section.classList.toggle('closed');
        }
    }

    function toggleSidebarCollapse() {
        var wrap = document.getElementById('filterSidebarWrap');
        var btn  = document.getElementById('filterCollapseBtn');
        if (!wrap) return;
        wrap.classList.toggle('collapsed');
        if (wrap.classList.contains('collapsed')) {
            btn.innerHTML = '&#10095;';
        } else {
            btn.innerHTML = '&#10094;';
        }
    }

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

<?php
if (file_exists(__DIR__ . '/my_footer.php')) {
    require_once __DIR__ . '/my_footer.php';
} elseif (file_exists(__DIR__ . '/includes/footer.php')) {
    require_once __DIR__ . '/includes/footer.php';
}
?>

