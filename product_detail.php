<?php

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

$db = $pdo ?? $conn ?? null;
if (!($db instanceof PDO)) {
    $dbHost = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
    $dbName = defined('DB_NAME') ? DB_NAME : 'shop_quanao';
    $dbUser = defined('DB_USER') ? DB_USER : 'root';
    $portsToTry  = defined('DB_PORT') ? [DB_PORT, 3306, 3307, 3308] : [3306, 3307, 3308];
    $passesToTry = defined('DB_PASS') ? [DB_PASS, '', '123456', 'root'] : ['', '123456', 'root'];

    foreach ($portsToTry as $port) {
        foreach ([$dbHost, '127.0.0.1', 'localhost'] as $h) {
            foreach ($passesToTry as $p) {
                try {
                    $db = new PDO("mysql:host={$h};port={$port};dbname={$dbName};charset=utf8mb4", $dbUser, $p, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
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

$catalogProducts = [
    1  => ['name' => 'Áo polo cổ tròn',          'slug' => 'ao-polo-co-tron',          'cat' => 2, 'price' => 279000, 'sale' => 239000, 'color' => 'Xanh đen', 'front' => 'uploads/products/Ao-polo-co-tron-mat-truoc.jpg',          'back' => '',                                                        'desc' => '<p>Áo polo cổ tròn thiết kế trẻ trung, chất vải pique thoáng mát, thấm hút mồ hôi tốt.</p>'],
    2  => ['name' => 'Áo thun oversize basic',   'slug' => 'ao-thun-oversize-basic',   'cat' => 1, 'price' => 249000, 'sale' => null,   'color' => 'Đen',      'front' => 'uploads/products/Ao-thun-oversize-basic-mat-truoc.jpg',   'back' => 'uploads/products/Ao-thun-oversize-basic-mat-sau.webp',     'desc' => '<p>Áo thun oversize basic cotton 100% dày dặn, form rộng chuẩn phong cách Streetwear Gen Z.</p>'],
    3  => ['name' => 'Áo polo trơn',             'slug' => 'ao-polo-tron',             'cat' => 2, 'price' => 299000, 'sale' => 259000, 'color' => 'Trắng',    'front' => 'uploads/products/Ao-polo-tron-mat-truoc.jpg',             'back' => 'uploads/products/Ao-polo-tron-mat-sau.webp',               'desc' => '<p>Áo polo trơn vải cá sấu thoáng khí, cổ dệt bền form, phù hợp mặc đi học, đi làm và dạo phố.</p>'],
    4  => ['name' => 'Áo thun in logo',          'slug' => 'ao-thun-in-logo',          'cat' => 1, 'price' => 229000, 'sale' => null,   'color' => 'Trắng',    'front' => 'uploads/products/Ao-thun-in-logo-mat-truoc.jpg',          'back' => 'uploads/products/Ao-thun-in-logo-mat-sau.webp',            'desc' => '<p>Áo thun in logo sắc nét, chất cotton mềm mịn, giặt máy nhiều lần không bong tróc.</p>'],
    5  => ['name' => 'Áo croptop',               'slug' => 'ao-croptop',               'cat' => 1, 'price' => 169000, 'sale' => 129000, 'color' => 'Đen',      'front' => 'uploads/products/Ao-croptop-mat-truoc.jpg',               'back' => 'uploads/products/Ao-croptop-mat-sau.webp',                 'desc' => '<p>Áo croptop ôm dáng vừa vặn, thun co giãn 4 chiều thoải mái, dễ phối cùng quần cạp cao.</p>'],
    9  => ['name' => 'Áo sơ mi linen nữ tay dài','slug' => 'ao-so-mi-linen-nu-tay-dai','cat' => 2, 'price' => 349000, 'sale' => null,   'color' => 'Hồng',     'front' => 'uploads/products/Ao-so-mi-linen-nu-tay-dai-mat-truoc.jpg','back' => 'uploads/products/Ao-so-mi-linen-nu-tay-dai-mat-sau.webp',  'desc' => '<p>Áo sơ mi linen nữ tay dài chất vải tự nhiên thoáng mát, nhẹ nhàng, dễ phối đồ đi học hay đi làm.</p>'],
    12 => ['name' => 'Quần jeans straight',      'slug' => 'quan-jeans-straight',      'cat' => 3, 'price' => 549000, 'sale' => null,   'color' => 'Xanh',     'front' => 'uploads/products/Quan-jeans-straight-mat-truoc.jpg',      'back' => 'uploads/products/Quan-jeans-straight-mat-sau.webp',        'desc' => '<p>Quần jeans ống đứng (straight fit) gọn gàng, chất denim dày dặn, màu wash xanh dễ phối đồ.</p>'],
    13 => ['name' => 'Quần jeans ống rộng',      'slug' => 'quan-jeans-ong-rong',      'cat' => 3, 'price' => 529000, 'sale' => 459000, 'color' => 'Đen',      'front' => 'uploads/products/Quan-jeans-ong-rong-mat-truoc.jpg',      'back' => 'uploads/products/Quan-jeans-ong-rong-mat-sau.webp',        'desc' => '<p>Quần jeans ống rộng cạp cao tôn dáng, che khuyết điểm tốt, chất vải denim mềm mại.</p>'],
    14 => ['name' => 'Quần tây ống rộng',        'slug' => 'quan-tay-ong-rong',        'cat' => 3, 'price' => 479000, 'sale' => null,   'color' => 'Đen',      'front' => 'uploads/products/Quan-tay-ong-rong-mat-truoc.jpg',        'back' => 'uploads/products/Quan-tay-ong-rong-mat-sau.webp',          'desc' => '<p>Quần tây ống rộng suông thẳng hiện đại, vải dày dặn chống nhăn, lên form chuẩn Hàn Quốc.</p>'],
    15 => ['name' => 'Quần kaki',                'slug' => 'quan-kaki',                'cat' => 3, 'price' => 449000, 'sale' => null,   'color' => 'Kem',      'front' => 'uploads/products/Quan-kaki-mat-truoc.jpg',                'back' => 'uploads/products/Quan-kaki-mat-sau.webp',                  'desc' => '<p>Quần kaki form rộng rãi thoải mái, chất vải kaki bền màu, đường may tỉ mỉ chắc chắn.</p>'],
    16 => ['name' => 'Quần short thể thao',      'slug' => 'quan-short-the-thao',      'cat' => 3, 'price' => 259000, 'sale' => 219000, 'color' => 'Đen',      'front' => 'uploads/products/Quan-short-the-thao-mat-truoc.jpg',      'back' => 'uploads/products/Quan-short-the-thao-mat-sau.webp',        'desc' => '<p>Quần short thể thao năng động, lưng thun co giãn kèm dây rút tiện lợi, thoáng mát cả ngày.</p>'],
    17 => ['name' => 'Chân váy midi',            'slug' => 'chan-vay-midi',            'cat' => 4, 'price' => 389000, 'sale' => null,   'color' => 'Đen',      'front' => 'uploads/products/Chan-vay-midi-mat-truoc.jpg',            'back' => 'uploads/products/Chan-vay-midi-mat-sau.webp',              'desc' => '<p>Chân váy midi dáng dài thanh lịch, chất vải mềm rủ tự nhiên, tôn dáng nhẹ nhàng.</p>'],
    19 => ['name' => 'Quần short nữ',            'slug' => 'quan-short-nu',            'cat' => 3, 'price' => 289000, 'sale' => null,   'color' => 'Kem',      'front' => 'uploads/products/Quan-short-nu-mat-truoc.jpg',            'back' => 'uploads/products/Quan-short-nu-mat-sau.webp',              'desc' => '<p>Quần short nữ cạp cao trẻ trung, ống rộng vừa phải giúp tôn dáng và thoải mái vận động.</p>'],
    21 => ['name' => 'Chân váy xếp ly',          'slug' => 'chan-vay-xep-ly',          'cat' => 4, 'price' => 359000, 'sale' => 319000, 'color' => 'Đen',      'front' => 'uploads/products/Chan-vay-xep-ly-mat-truoc.jpg',          'back' => 'uploads/products/Chan-vay-xep-li-mat-sau.webp',            'desc' => '<p>Chân váy xếp ly trẻ trung, có quần lót bảo hộ bên trong, nếp gấp sắc nét giữ form lâu.</p>'],
    22 => ['name' => 'Áo khoác denim',           'slug' => 'ao-khoac-denim',           'cat' => 5, 'price' => 649000, 'sale' => null,   'color' => 'Xanh',     'front' => 'uploads/products/Ao-khoac-denim-mat-truoc.jpg',           'back' => 'uploads/products/Ao-khoac-denim-mat-sau.webp',             'desc' => '<p>Áo khoác denim unisex cá tính, chất jean wash dày dặn, khuy cài kim loại bền đẹp.</p>'],
    23 => ['name' => 'Áo khoác varsity',         'slug' => 'ao-khoac-varsity',         'cat' => 7, 'price' => 589000, 'sale' => 499000, 'color' => 'Đen',      'front' => 'uploads/products/Ao-khoac-varsity-mat-truoc.jpg',         'back' => 'uploads/products/Ao-khoac-varsity-mat-sau.webp',           'desc' => '<p>Áo khoác varsity phối tay nổi bật, form rộng chuẩn Streetwear, bo cổ và cổ tay dày dặn.</p>'],
    25 => ['name' => 'Áo khoác hoodie oversize', 'slug' => 'ao-khoac-hoodie-oversize', 'cat' => 8, 'price' => 449000, 'sale' => null,   'color' => 'Xám',      'front' => 'uploads/products/Ao-khoac-hoodie-oversize-mat-truoc.jpg', 'back' => 'uploads/products/Ao-khoac-hoodie-oversize-mat-sau.webp',   'desc' => '<p>Áo khoác hoodie oversize nỉ bông mềm ấm, mũ 2 lớp đứng form kèm túi trước rộng rãi.</p>'],
    26 => ['name' => 'Áo khoác hoodie zip',      'slug' => 'ao-khoac-hoodie-zip',      'cat' => 8, 'price' => 479000, 'sale' => 419000, 'color' => 'Đen',      'front' => 'uploads/products/Ao-khoac-hoodie-zip-mat-truoc.jpg',      'back' => 'uploads/products/Ao-khoac-hoodie-zip-mat-sau.webp',        'desc' => '<p>Áo khoác hoodie zip khóa kéo tiện lợi, vải nỉ da cá dày vừa phải, mặc khoác nắng hay giữ ấm đều hợp.</p>'],
];

$colorHexMap = [
    'Đen'      => '#111111',
    'Trắng'    => '#ffffff',
    'Hồng'     => '#f4b6c2',
    'Kem'      => '#e6d5b8',
    'Be'       => '#e6dcc8',
    'Xám'      => '#808080',
    'Xanh'     => '#3b6998',
    'Xanh đen' => '#1b263b',
    'Xanh navy'=> '#1e3a8a',
    'Xanh đậm' => '#1d4ed8',
    'Xanh nhạt'=> '#bfdbfe',
    'Xanh rêu' => '#4d7c0f',
    'Đỏ'       => '#e60023',
    'Vàng'     => '#facc15',
    'Nâu'      => '#78350f',
];

function resolveExistingImageUrl(string $rawPath): string {
    $rawPath = trim($rawPath);
    if ($rawPath === '') {
        return '';
    }
    $clean = preg_replace('/^public\//', '', ltrim($rawPath, '/'));
    foreach (['public/' . $clean, $clean] as $rel) {
        $full = __DIR__ . '/' . $rel;
        if (file_exists($full) && is_file($full)) {
            return $rel . '?v=' . filemtime($full);
        }
    }
    return '';
}

function normalizeSizeToLetter(string $rawSize, string $fallback = 'S'): string {
    $token = strtoupper(trim($rawSize));
    if (in_array($token, ['S', 'M', 'L', 'XL'], true)) {
        return $token;
    }
    if (in_array($token, ['28', '29', 'FREESIZE', 'FREE'], true)) {
        return 'S';
    }
    if ($token === '30') {
        return 'M';
    }
    if ($token === '31') {
        return 'L';
    }
    if (in_array($token, ['32', '33', '34'], true)) {
        return 'XL';
    }
    return $fallback;
}

if ($db instanceof PDO) {
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        $delImgStmt = $db->prepare("DELETE FROM product_images WHERE product_id = ?");
        $insImgStmt = $db->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?)");
        $delVarStmt = $db->prepare("DELETE FROM product_variants WHERE product_id = ?");
        $insVarStmt = $db->prepare("INSERT INTO product_variants (product_id, size, color, stock) VALUES (?, ?, ?, 25)");

        foreach ($catalogProducts as $pid => $meta) {
            $delImgStmt->execute([$pid]);
            if (resolveExistingImageUrl($meta['front']) !== '') {
                $insImgStmt->execute([$pid, $meta['front'], 1, 1]);
            }
            if (!empty($meta['back']) && $meta['back'] !== $meta['front'] && resolveExistingImageUrl($meta['back']) !== '') {
                $insImgStmt->execute([$pid, $meta['back'], 0, 2]);
            }

            $delVarStmt->execute([$pid]);
            foreach (['S', 'M', 'L', 'XL'] as $sz) {
                $insVarStmt->execute([$pid, $sz, $meta['color']]);
            }
        }
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Throwable $e) {
    }
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
if ($productId <= 0) {
    $productId = 1;
}

$itemMeta       = $catalogProducts[$productId] ?? null;
$fixedColor     = $itemMeta['color'] ?? null;
$availableSizes = ['S', 'M', 'L', 'XL'];

$product   = null;
$variants  = [];
$dbReviews = [];

if ($db instanceof PDO) {
    try {
        $stmt = $db->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? LIMIT 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $stmtFirst = $db->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id ASC LIMIT 1");
            $product = $stmtFirst->fetch(PDO::FETCH_ASSOC);
            if ($product) {
                $productId = (int)$product['id'];
                $itemMeta  = $catalogProducts[$productId] ?? null;
                $fixedColor = $itemMeta['color'] ?? null;
            }
        }
    } catch (Throwable $e) {
    }

    try {
        $vStmt = $db->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY FIELD(size, 'S', 'M', 'L', 'XL'), id ASC");
        $vStmt->execute([$productId]);
        $variants = $vStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $variants = [];
    }

    try {
        $rStmt = $db->prepare("SELECT r.*, u.full_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.status = 1 AND r.id > 5 ORDER BY r.created_at DESC");
        $rStmt->execute([$productId]);
        $dbReviews = $rStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $dbReviews = [];
    }
}

if (!$product && $itemMeta) {
    $product = [
        'id'          => $productId,
        'name'        => $itemMeta['name'],
        'price'       => $itemMeta['price'],
        'sale_price'  => $itemMeta['sale'],
        'description' => $itemMeta['desc'],
        'category_id' => $itemMeta['cat']
    ];
}

if (!$product) {
    require_once __DIR__ . '/my_header.php';
    echo "<div style='max-width:800px; margin:80px auto; text-align:center; padding:40px; border:1px solid #eee; border-radius:16px;'>
            <h2>Không tìm thấy sản phẩm!</h2>
            <a href='products.php' style='display:inline-block; margin-top:12px; padding:10px 24px; background:#e60023; color:#fff; text-decoration:none; border-radius:999px; font-weight:700;'>Quay lại cửa hàng</a>
          </div>";
    require_once __DIR__ . '/my_footer.php';
    exit;
}

$galleryImgs = [];
if ($itemMeta) {
    $frontUrl = resolveExistingImageUrl($itemMeta['front']);
    if ($frontUrl !== '') {
        $galleryImgs[] = $frontUrl;
    }
    if (!empty($itemMeta['back']) && $itemMeta['back'] !== $itemMeta['front']) {
        $backUrl = resolveExistingImageUrl($itemMeta['back']);
        if ($backUrl !== '' && !in_array($backUrl, $galleryImgs, true)) {
            $galleryImgs[] = $backUrl;
        }
    }
}

if (empty($galleryImgs) && $db instanceof PDO) {
    try {
        $imgStmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order ASC, id ASC");
        $imgStmt->execute([$productId]);
        foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $u = resolveExistingImageUrl($row['image_path'] ?? '');
            if ($u !== '' && !in_array($u, $galleryImgs, true)) {
                $galleryImgs[] = $u;
            }
        }
    } catch (Throwable $e) {
    }
}

if (empty($galleryImgs)) {
    $fallbackRaw = $itemMeta['front'] ?? ($product['image'] ?? 'uploads/products/Ao-thun-oversize-basic-mat-truoc.jpg');
    $resolvedFb  = resolveExistingImageUrl($fallbackRaw);
    $galleryImgs[] = $resolvedFb !== '' ? $resolvedFb : ('public/' . ltrim($fallbackRaw, '/'));
}

$displayMainImage = $galleryImgs[0];
$curatedReviewsByProduct = [
    2 => [
        ['full_name' => 'Phạm Gia Hân', 'rating' => 5, 'variant' => 'Size M - Be', 'created_at' => '10/11/2026', 'comment' => 'Áo mới ra là mình chốt liền, form oversize rộng rãi đúng chuẩn Hàn Quốc, vải cotton dày dặn cực kỳ.'],
        ['full_name' => 'Lê Minh Khôi', 'rating' => 5, 'variant' => 'Size L - Xám', 'created_at' => '08/11/2026', 'comment' => 'Màu xám ngoài đời đẹp hơn trong ảnh, cổ áo bo dày không sợ bị giãn sau khi giặt.']
    ],
    4 => [
        ['full_name' => 'Trần Bảo Ngọc', 'rating' => 5, 'variant' => 'Size M - Trắng', 'created_at' => '11/11/2026', 'comment' => 'Hình in graphic sắc nét, chất vải mềm mát, mặc phối với quần jean hay cargo đều hợp.']
    ],
    9 => [
    ],
    22 => [
        ['full_name' => 'Nguyễn Tuấn Kiệt', 'rating' => 5, 'variant' => 'Size L - Xanh đậm', 'created_at' => '09/11/2026', 'comment' => 'Áo khoác jean wash màu đẹp, form unisex nam nữ mặc đều chất, nút bấm chắc chắn.'],
        ['full_name' => 'Võ Thảo Vy', 'rating' => 4, 'variant' => 'Size M - Xanh nhạt', 'created_at' => '06/11/2026', 'comment' => 'Khoác ngoài đi học hay đi chơi đều xinh, vải denim dày dặn đứng form.']
    ],
    26 => [
        ['full_name' => 'Đặng Hoàng Nam', 'rating' => 5, 'variant' => 'Size XL - Xanh navy', 'created_at' => '12/11/2026', 'comment' => 'Dây khóa kéo mượt, vải nỉ da cá dày vừa phải mặc thời tiết Sài Gòn rất thoải mái.']
    ],
    3 => [
        ['full_name' => 'Trần Văn Minh', 'rating' => 5, 'variant' => 'Size L - Xanh navy', 'created_at' => '10/11/2026', 'comment' => 'Áo polo vải cá sấu thoáng khí, cổ dệt cứng cáp, mặc đi làm hay đi cà phê đều lịch sự.'],
        ['full_name' => 'Nguyễn Hoàng Long', 'rating' => 5, 'variant' => 'Size M - Trắng', 'created_at' => '07/11/2026', 'comment' => 'Form chuẩn, đường may kỹ không có chỉ thừa. Đã mua cái thứ 2 tại shop.'],
        ['full_name' => 'Lê Thu Thủy', 'rating' => 5, 'variant' => 'Size L - Trắng', 'created_at' => '04/11/2026', 'comment' => 'Mua tặng bạn trai mà ưng quá trời, vải thấm hút mồ hôi tốt, giặt máy không bị xù.'],
        ['full_name' => 'Phan Quốc Bảo', 'rating' => 4, 'variant' => 'Size S - Xanh navy', 'created_at' => '01/11/2026', 'comment' => 'Áo đẹp, giao hàng nhanh, đóng gói cẩn thận. Giá sale quá hời so với chất lượng.'],
        ['full_name' => 'Hoàng Gia Huy', 'rating' => 5, 'variant' => 'Size M - Xanh navy', 'created_at' => '28/10/2026', 'comment' => 'Chất vải pique dày dặn, lên form đứng dáng, rất đáng tiền!']
    ],
    13 => [
        ['full_name' => 'Nguyễn Khánh Linh', 'rating' => 5, 'variant' => 'Size 29 - Xanh nhạt', 'created_at' => '11/11/2026', 'comment' => 'Quần jean ống rộng cạp cao siêu hack dáng, che khuyết điểm chân cực tốt luôn ạ.'],
        ['full_name' => 'Trần Phương Anh', 'rating' => 5, 'variant' => 'Size 30 - Xanh đậm', 'created_at' => '08/11/2026', 'comment' => 'Vải denim mềm, không bị cứng, mặc cả ngày đi học vẫn rất dễ chịu.'],
        ['full_name' => 'Lê Ngọc Hân', 'rating' => 5, 'variant' => 'Size 29 - Xanh đậm', 'created_at' => '05/11/2026', 'comment' => 'Phối với áo croptop hay baby tee đều đỉnh, màu wash ở ngoài giống hệt hình mẫu.'],
        ['full_name' => 'Vũ Mai Phương', 'rating' => 4, 'variant' => 'Size 31 - Xanh nhạt', 'created_at' => '02/11/2026', 'comment' => 'Quần lên form thẳng đẹp, mình cao 1m62 mặc phủ giày nhìn chân dài hẳn ra.'],
        ['full_name' => 'Đỗ Bảo Trân', 'rating' => 5, 'variant' => 'Size 30 - Xanh nhạt', 'created_at' => '29/10/2026', 'comment' => 'Shop tư vấn size chuẩn, giao hàng siêu tốc trong ngày. Sẽ ủng hộ shop dài dài!']
    ],
    14 => [
        ['full_name' => 'Bùi Minh Nhật', 'rating' => 5, 'variant' => 'Size 31 - Xanh nhạt', 'created_at' => '10/11/2026', 'comment' => 'Form quần streetwear cực chất, vết rách gối vừa phải nhìn rất cá tính.'],
        ['full_name' => 'Ngô Gia Bảo', 'rating' => 5, 'variant' => 'Size 30 - Xanh nhạt', 'created_at' => '06/11/2026', 'comment' => 'Chất vải dày dặn, đứng ống, phối với sneaker và áo thun oversize là chuẩn bài.'],
        ['full_name' => 'Trương Hoàng Quân', 'rating' => 5, 'variant' => 'Size 32 - Xanh nhạt', 'created_at' => '03/11/2026', 'comment' => 'Màu xanh nhạt wash bụi bặm, đường chỉ may chắc chắn, rất ưng ý.'],
        ['full_name' => 'Đinh Tuấn Anh', 'rating' => 4, 'variant' => 'Size 29 - Xanh nhạt', 'created_at' => '30/10/2026', 'comment' => 'Quần đẹp đúng mô tả, form rộng rãi thoải mái vận động.'],
        ['full_name' => 'Lý Thành Đạt', 'rating' => 5, 'variant' => 'Size 31 - Xanh nhạt', 'created_at' => '25/10/2026', 'comment' => 'Mẫu bán chạy của shop có khác, cầm nặng tay, chất lượng vượt tầm giá.']
    ],
    23 => [
        ['full_name' => 'Nguyễn Thị Lan', 'rating' => 5, 'variant' => 'Size L - Đen', 'created_at' => '11/11/2026', 'comment' => 'Áo khoác 2 lớp dày dặn, lớp lót trong mềm mịn, đi nắng hay đi mưa nhẹ đều ổn.'],
        ['full_name' => 'Trần Quốc Tuấn', 'rating' => 5, 'variant' => 'Size XL - Xanh rêu', 'created_at' => '09/11/2026', 'comment' => 'Form varsity/bomber lên dáng cực ngầu, bo tay và bo cổ chắc chắn không bị dão.'],
        ['full_name' => 'Phạm Minh Thư', 'rating' => 5, 'variant' => 'Size M - Đen', 'created_at' => '05/11/2026', 'comment' => 'Nữ mặc form rộng giấu quần siêu xinh, màu đen phối trắng rất dễ mặc.'],
        ['full_name' => 'Lê Hoàng Phúc', 'rating' => 4, 'variant' => 'Size L - Xanh rêu', 'created_at' => '01/11/2026', 'comment' => 'Vải dù cản gió tốt, túi áo sâu để vừa điện thoại và ví thoải mái.'],
        ['full_name' => 'Huỳnh Anh Khoa', 'rating' => 5, 'variant' => 'Size M - Đen', 'created_at' => '27/10/2026', 'comment' => 'Đang được giảm giá nên mua liền, chất lượng 10 điểm không có chỗ chê.']
    ],
    25 => [
        ['full_name' => 'Võ Minh Triết', 'rating' => 5, 'variant' => 'Size L - Xám', 'created_at' => '12/11/2026', 'comment' => 'Hoodie nỉ bông dày ấm, mũ 2 lớp to rộng đội lên form cực đẹp!'],
        ['full_name' => 'Nguyễn Hà My', 'rating' => 5, 'variant' => 'Size M - Đen', 'created_at' => '08/11/2026', 'comment' => 'Form boxy/oversize chuẩn streetwear Gen Z, mặc đi Đà Lạt là hết nước chấm.'],
        ['full_name' => 'Trần Gia Khiêm', 'rating' => 5, 'variant' => 'Size XL - Xám', 'created_at' => '04/11/2026', 'comment' => 'Lớp bông bên trong êm, không bị rụng lông bám vào áo trong. Rất đáng mua.'],
        ['full_name' => 'Lê Bảo Châu', 'rating' => 5, 'variant' => 'Size S - Xám', 'created_at' => '31/10/2026', 'comment' => 'Túi kangaroo phía trước rộng rãi, bo tay ôm vừa vặn, màu xám tiêu cực tây.'],
        ['full_name' => 'Đoàn Trọng Nghĩa', 'rating' => 4, 'variant' => 'Size L - Đen', 'created_at' => '26/10/2026', 'comment' => 'Áo dày, cầm đằm tay, đóng gói chỉnh chu có túi zip thơm phức.']
    ],

    1 => [
        ['full_name' => 'Nguyễn Thị Lan', 'rating' => 5, 'variant' => 'Size M - Trắng', 'created_at' => '04/11/2026', 'comment' => 'Vải cotton 100% mềm mịn, mặc rất thoải mái, giao hàng nhanh.'],
        ['full_name' => 'Hoàng Minh Đức', 'rating' => 5, 'variant' => 'Size L - Đen', 'created_at' => '01/11/2026', 'comment' => 'Áo basic dễ phối đồ, giá học sinh sinh viên mà chất vải xịn bất ngờ.'],
        ['full_name' => 'Lê Thanh Trúc', 'rating' => 4, 'variant' => 'Size S - Trắng', 'created_at' => '28/10/2026', 'comment' => 'Form regular vừa vặn, vải trắng không bị mỏng hay lộ đâu nhé mọi người.']
    ],
    5 => [
        ['full_name' => 'Trần Khánh Vy', 'rating' => 5, 'variant' => 'Size M - Đen', 'created_at' => '09/11/2026', 'comment' => 'Áo croptop ôm vừa vặn, tôn vòng eo, chất thun co giãn 4 chiều rất thoải mái.'],
        ['full_name' => 'Nguyễn Ngọc Trâm', 'rating' => 5, 'variant' => 'Size M - Hồng', 'created_at' => '05/11/2026', 'comment' => 'Màu hồng xinh xỉu, phối cùng quần jean cạp cao là chuẩn Y2K luôn.'],
        ['full_name' => 'Phạm Quỳnh Như', 'rating' => 4, 'variant' => 'Size L - Đen', 'created_at' => '02/11/2026', 'comment' => 'Vải dày dặn, giặt không bị giãn, giá sale rẻ quá trời.']
    ],
    6 => [
        ['full_name' => 'Lương Thế Vinh', 'rating' => 5, 'variant' => 'Size L - Đen', 'created_at' => '07/11/2026', 'comment' => 'Áo tay dài vải cotton dày vừa phải, mặc trời se lạnh rất hợp.'],
        ['full_name' => 'Đặng Thu Hà', 'rating' => 5, 'variant' => 'Size M - Xám', 'created_at' => '03/11/2026', 'comment' => 'Form rộng rãi thoải mái, màu xám dễ phối đồ.']
    ],
    7 => [
        ['full_name' => 'Phan Thanh Tùng', 'rating' => 5, 'variant' => 'Size L - Trắng', 'created_at' => '08/11/2026', 'comment' => 'Sơ mi trắng chất vải ít nhăn, mặc đi thuyết trình trên trường hay đi làm đều đẹp.'],
        ['full_name' => 'Nguyễn Minh Quân', 'rating' => 4, 'variant' => 'Size M - Xanh nhạt', 'created_at' => '04/11/2026', 'comment' => 'Đường may ngay ngắn, cổ áo đứng form, rất lịch sự.'],
        ['full_name' => 'Trần Hải Yến', 'rating' => 5, 'variant' => 'Size S - Trắng', 'created_at' => '29/10/2026', 'comment' => 'Chất vải thoáng mát, form áo chuẩn theo bảng size của shop.']
    ],
    8 => [
        ['full_name' => 'Vũ Hoàng Nam', 'rating' => 5, 'variant' => 'Size L - Xanh', 'created_at' => '09/11/2026', 'comment' => 'Sơ mi kẻ sọc trẻ trung, khoác ngoài áo thun trắng nhìn rất Hàn Quốc.'],
        ['full_name' => 'Lê Bảo Ngọc', 'rating' => 5, 'variant' => 'Size M - Xanh navy', 'created_at' => '02/11/2026', 'comment' => 'Vải mát, màu sọc xanh bên ngoài sáng da lắm ạ.']
    ],
    10 => [
        ['full_name' => 'Trịnh Quốc Huy', 'rating' => 5, 'variant' => 'Size L - Đỏ', 'created_at' => '10/11/2026', 'comment' => 'Áo flannel caro dày dặn, khoác ngoài áo thun đen cực kỳ nổi bật.'],
        ['full_name' => 'Nguyễn Bích Phương', 'rating' => 5, 'variant' => 'Size M - Xanh rêu', 'created_at' => '06/11/2026', 'comment' => 'Phối màu caro vintage, chất vải mềm không bị dặm ngứa.'],
        ['full_name' => 'Hồ Minh Khang', 'rating' => 4, 'variant' => 'Size L - Xanh rêu', 'created_at' => '01/11/2026', 'comment' => 'Áo đẹp, giao hàng nhanh, đóng gói kỹ.']
    ],
    11 => [
        ['full_name' => 'Lê Kiều Trinh', 'rating' => 5, 'variant' => 'Size S - Kem', 'created_at' => '08/11/2026', 'comment' => 'Áo sơ mi cổ nơ nữ tính, chất vải mềm rủ nhẹ nhàng, mặc đi học rất xinh.'],
        ['full_name' => 'Nguyễn Thu Trang', 'rating' => 5, 'variant' => 'Size M - Hồng', 'created_at' => '03/11/2026', 'comment' => 'Màu hồng pastel dịu mắt, đường may tỉ mỉ.']
    ],
    12 => [
        ['full_name' => 'Nguyễn Thị Lan', 'rating' => 4, 'variant' => 'Size 30 - Xanh đậm', 'created_at' => '05/11/2026', 'comment' => 'Quần đẹp, form chuẩn, vải có độ co giãn nhẹ nên đứng ngồi thoải mái.'],
        ['full_name' => 'Phạm Anh Tuấn', 'rating' => 5, 'variant' => 'Size 31 - Xanh đậm', 'created_at' => '01/11/2026', 'comment' => 'Màu xanh đậm dễ phối áo sơ mi lẫn áo thun, khóa kéo chắc chắn.'],
        ['full_name' => 'Lê Văn Hùng', 'rating' => 5, 'variant' => 'Size 32 - Xanh đậm', 'created_at' => '27/10/2026', 'comment' => 'Vải jean dày, giặt không bị ra màu, rất đáng tiền.']
    ],
    15 => [
        ['full_name' => 'Đỗ Minh Hoàng', 'rating' => 5, 'variant' => 'Size 30 - Đen', 'created_at' => '09/11/2026', 'comment' => 'Form baggy rộng vừa phải, che khuyết điểm đùi to rất tốt.'],
        ['full_name' => 'Trần Thanh Sơn', 'rating' => 5, 'variant' => 'Size 31 - Xanh đậm', 'created_at' => '04/11/2026', 'comment' => 'Vải đứng form, mặc thoải mái cả ngày không bị gò bó.'],
        ['full_name' => 'Nguyễn Hữu Lộc', 'rating' => 4, 'variant' => 'Size 29 - Đen', 'created_at' => '30/10/2026', 'comment' => 'Quần may đẹp, đúng kích thước như bảng hướng dẫn chọn size.']
    ],
    16 => [
        ['full_name' => 'Bùi Quốc Khánh', 'rating' => 5, 'variant' => 'Size 30 - Xanh nhạt', 'created_at' => '07/11/2026', 'comment' => 'Quần short năng động, chiều dài ngang gối vừa đẹp, vải mát.'],
        ['full_name' => 'Lê Minh Tâm', 'rating' => 5, 'variant' => 'Size 31 - Đen', 'created_at' => '02/11/2026', 'comment' => 'Mặc đi chơi hay dạo phố cuối tuần rất thoải mái.']
    ],
    17 => [
        ['full_name' => 'Trần Văn Minh', 'rating' => 5, 'variant' => 'Size S - Vàng', 'created_at' => '08/11/2026', 'comment' => 'Váy xinh, đúng như hình, có lớp lót bên trong kín đáo, sẽ ủng hộ tiếp.'],
        ['full_name' => 'Nguyễn Yến Nhi', 'rating' => 5, 'variant' => 'Size M - Xanh', 'created_at' => '04/11/2026', 'comment' => 'Họa tiết vintage nhẹ nhàng, mặc đi chụp ảnh ngoại cảnh lên hình siêu thơ.'],
        ['full_name' => 'Phạm Thanh Thảo', 'rating' => 5, 'variant' => 'Size S - Vàng', 'created_at' => '30/10/2026', 'comment' => 'Chất vải mềm mại, form váy tôn eo lắm ạ.']
    ],
    18 => [
        ['full_name' => 'Hoàng Kim Ngân', 'rating' => 5, 'variant' => 'Size M - Đen', 'created_at' => '06/11/2026', 'comment' => 'Thiết kế tối giản thanh lịch, chất vải dày dặn không nhăn.'],
        ['full_name' => 'Lê Phương Thảo', 'rating' => 4, 'variant' => 'Size S - Be', 'created_at' => '01/11/2026', 'comment' => 'Màu be tôn da, mặc đi làm hay đi học đều lịch sự.']
    ],
    19 => [
        ['full_name' => 'Nguyễn Thùy Linh', 'rating' => 5, 'variant' => 'Size S - Đen', 'created_at' => '10/11/2026', 'comment' => 'Chân váy chữ A có quần bảo hộ bên trong rất tiện, form cạp cao tôn dáng.'],
        ['full_name' => 'Trần Bảo Thy', 'rating' => 5, 'variant' => 'Size M - Xám', 'created_at' => '05/11/2026', 'comment' => 'Dễ phối với mọi loại áo thun và sơ mi trong tủ đồ luôn.'],
        ['full_name' => 'Võ Ngọc Ánh', 'rating' => 5, 'variant' => 'Size S - Xám', 'created_at' => '31/10/2026', 'comment' => 'Vải dày, đường khóa kéo mượt, giá quá hợp lý.']
    ],
    20 => [
        ['full_name' => 'Đặng Mỹ Tâm', 'rating' => 5, 'variant' => 'Size M - Đỏ', 'created_at' => '07/11/2026', 'comment' => 'Form dáng thướt tha, màu đỏ nổi bật cực kỳ tôn da.'],
        ['full_name' => 'Nguyễn Lan Hương', 'rating' => 5, 'variant' => 'Size S - Xanh navy', 'created_at' => '02/11/2026', 'comment' => 'Chất vải đẹp, đường may sắc sảo như hàng thiết kế cao cấp.']
    ],
    21 => [
        ['full_name' => 'Phan Cẩm Tú', 'rating' => 5, 'variant' => 'Size S - Kem', 'created_at' => '09/11/2026', 'comment' => 'Chân váy xếp ly giữ nếp tốt, giặt xong phơi lên vẫn thẳng đẹp không cần ủi nhiều.'],
        ['full_name' => 'Lê Minh Ánh', 'rating' => 5, 'variant' => 'Size M - Hồng', 'created_at' => '04/11/2026', 'comment' => 'Phối cùng áo polo hay hoodie đều trẻ trung năng động.'],
        ['full_name' => 'Trần Hồng Nhung', 'rating' => 4, 'variant' => 'Size M - Kem', 'created_at' => '29/10/2026', 'comment' => 'Có lót quần bên trong chắc chắn, rất ưng ý.']
    ],
    24 => [
        ['full_name' => 'Nguyễn Đức Huy', 'rating' => 5, 'variant' => 'Size L - Đen', 'created_at' => '08/11/2026', 'comment' => 'Áo khoác form chuẩn, chất liệu dày dặn, mặc lên nhìn cực ngầu.'],
        ['full_name' => 'Trần Minh Khang', 'rating' => 5, 'variant' => 'Size XL - Nâu', 'created_at' => '03/11/2026', 'comment' => 'Màu nâu cổ điển đẹp, đường may và khóa kéo rất chắc chắn.'],
        ['full_name' => 'Lê Quốc Việt', 'rating' => 4, 'variant' => 'Size M - Đen', 'created_at' => '28/10/2026', 'comment' => 'Đóng gói kỹ, áo không bị gấp nếp, rất hài lòng.']
    ]
];

$reviews = array_merge($dbReviews, $curatedReviewsByProduct[$productId] ?? []);
$totalReviews = count($reviews);
$avgRating    = 0;
if ($totalReviews > 0) {
    $sumRating = 0;
    foreach ($reviews as $rvItem) {
        $sumRating += (int)($rvItem['rating'] ?? 5);
    }
    $avgRating = round($sumRating / $totalReviews, 1);
}

if ($fixedColor !== null) {
    $availableColors = [$fixedColor];
} else {
    $availableColors = [];
    foreach ($variants as $v) {
        if (!empty($v['color']) && !in_array($v['color'], $availableColors, true)) {
            $availableColors[] = $v['color'];
        }
    }
    if (empty($availableColors)) {
        $availableColors = ['Đen'];
    }
}

$defaultColor    = $availableColors[0];
$defaultSize     = 'S';
$defaultVariant  = !empty($variants[0]['id']) ? (int)$variants[0]['id'] : 1;

$rawDesc = (string)($product['description'] ?? ($itemMeta['desc'] ?? ''));
for ($i = 0; $i < 3; $i++) {
    $rawDesc = html_entity_decode(htmlspecialchars_decode($rawDesc, ENT_QUOTES), ENT_QUOTES, 'UTF-8');
}
$cleanDesc = trim($rawDesc) !== '' ? $rawDesc : '<p>Chất liệu cotton thoáng mát, đường may tỉ mỉ chuẩn form Streetwear.</p>';

$currPrice = (!empty($product['sale_price']) && $product['sale_price'] > 0) ? $product['sale_price'] : $product['price'];
$oldPrice  = (!empty($product['sale_price']) && $product['sale_price'] > 0) ? $product['price'] : null;

$pageTitle = ($product['name'] ?? 'Chi tiết sản phẩm') . ' | VIBEZ.CLUB';
require_once __DIR__ . '/my_header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
    .wf-detail-wrap,
    .wf-detail-wrap button,
    .wf-detail-wrap input,
    .wf-detail-wrap a,
    .sg-modal-box,
    .sg-modal-box button {
        font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
    }

    .wf-detail-wrap {
        max-width: 1180px;
        margin: 36px auto 70px;
        padding: 0 20px;
        color: #111111;
    }

    .wf-top-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 44px;
        background: #ffffff;
        border: 1px solid #eeeeee;
        border-radius: 20px;
        padding: 32px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
    }

    .wf-main-img-box {
        width: 100%;
        aspect-ratio: 1 / 1;
        background: #ffffff;
        border: 1px solid #ececec;
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 14px;
        box-sizing: border-box;
    }

    .wf-main-img-box img {
        width: 100%;
        height: 100%;
        object-fit: contain !important;
        transform: none !important;
        display: block;
    }

    .wf-thumb-list {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .wf-thumb-item {
        width: 92px;
        height: 92px;
        border-radius: 10px;
        border: 2px solid #e5e7eb;
        overflow: hidden;
        cursor: pointer;
        background: #ffffff;
        padding: 6px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: border-color 0.2s ease;
    }

    .wf-thumb-item.active,
    .wf-thumb-item:hover {
        border-color: #e60023;
    }

    .wf-thumb-item img {
        width: 100%;
        height: 100%;
        object-fit: contain !important;
        transform: none !important;
        display: block;
    }

    .wf-product-title {
        font-size: 28px;
        font-weight: 800;
        color: #111111;
        margin: 0 0 14px 0;
        line-height: 1.35;
    }

    .wf-price-box {
        display: flex;
        align-items: baseline;
        gap: 12px;
        padding-bottom: 18px;
        margin-bottom: 20px;
        border-bottom: 1px solid #f1f1f1;
    }

    .wf-product-price {
        font-size: 28px;
        font-weight: 900;
        color: #e60023;
    }

    .wf-product-old-price {
        font-size: 16px;
        color: #9ca3af;
        text-decoration: line-through;
    }

    .wf-option-block {
        margin-bottom: 22px;
    }

    .wf-option-label {
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .wf-option-label span {
        color: #e60023;
    }

    .wf-color-list {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .wf-color-swatch {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid #d1d5db;
        padding: 3px;
        background-clip: content-box;
        transition: transform 0.15s ease, border-color 0.15s ease;
    }

    .wf-color-swatch.active {
        border: 2.5px solid #e60023;
        transform: scale(1.06);
    }

    .wf-size-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
        gap: 12px;
        flex-wrap: wrap;
    }

    .wf-size-list {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .wf-size-btn {
        min-width: 48px;
        height: 42px;
        padding: 0 14px;
        border-radius: 8px;
        border: 1.5px solid #e5e7eb;
        background: #ffffff;
        color: #111111;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.18s ease;
    }

    .wf-size-btn:hover {
        border-color: #111111;
    }

    .wf-size-btn.active {
        border-color: #e60023;
        color: #e60023;
        background: #fff0f2;
    }

    .wf-size-guide-btn {
        background: #fff0f2;
        color: #e60023;
        border: none;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }

    .wf-size-guide-btn:hover {
        background: #e60023;
        color: #ffffff;
    }

    .wf-cart-row {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-top: 26px;
        margin-bottom: 14px;
    }

    .wf-qty-pill {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        width: 125px;
        height: 48px;
        border: 1.5px solid #111111;
        border-radius: 999px;
        padding: 0 8px;
        background: #ffffff;
        flex-shrink: 0;
    }

    .wf-qty-pill button {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: none;
        background: #f3f4f6;
        color: #111111;
        font-size: 16px;
        font-weight: 800;
        cursor: pointer;
    }

    .wf-qty-pill button:hover {
        background: #e60023;
        color: #ffffff;
    }

    .wf-qty-pill input {
        width: 42px;
        border: none;
        text-align: center;
        font-size: 15px;
        font-weight: 800;
        outline: none;
        background: transparent;
    }

    .wf-btn-addcart {
        flex: 1;
        max-width: 260px;
        height: 48px;
        border-radius: 999px;
        border: 1.5px solid #111111;
        background: #ffffff;
        color: #111111;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .wf-btn-addcart:hover {
        background: #111111;
        color: #ffffff;
    }

    .wf-btn-buynow {
        width: 100%;
        max-width: 399px;
        height: 50px;
        border-radius: 999px;
        border: none;
        background: #e60023;
        color: #ffffff;
        font-size: 15px;
        font-weight: 800;
        text-transform: uppercase;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .wf-btn-buynow:hover {
        background: #c8001e;
    }

    .wf-bottom-blocks {
        margin-top: 32px;
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .wf-full-box {
        background: #ffffff;
        border: 1px solid #eeeeee;
        border-radius: 16px;
        padding: 26px 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    }

    .wf-full-box-title {
        font-size: 16px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin: 0 0 16px 0;
        padding-bottom: 12px;
        border-bottom: 2px solid #111111;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .wf-title-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .wf-title-left::before {
        content: "";
        display: inline-block;
        width: 5px;
        height: 18px;
        background: #e60023;
        border-radius: 4px;
    }

    .wf-rating-summary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        text-transform: none;
        background: #fff8eb;
        border: 1px solid #fde68a;
        color: #111111;
        padding: 5px 14px;
        border-radius: 999px;
    }

    .wf-desc-content {
        font-size: 14.5px;
        line-height: 1.75;
        color: #333333;
    }

    .wf-review-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .wf-review-card {
        display: flex;
        gap: 14px;
        padding: 16px 18px;
        background: #fafafa;
        border: 1px solid #f0f0f0;
        border-radius: 12px;
    }

    .wf-review-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #111111;
        color: #ffffff;
        font-size: 15px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .wf-review-body {
        flex: 1;
        min-width: 0;
    }

    .wf-review-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 4px;
    }

    .wf-review-author {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14.5px;
        font-weight: 700;
        color: #111111;
    }

    .wf-verified-badge {
        font-size: 11.5px;
        font-weight: 600;
        color: #16a34a;
        background: #dcfce7;
        padding: 2px 8px;
        border-radius: 999px;
    }

    .wf-review-date {
        font-size: 12.5px;
        color: #9ca3af;
    }

    .wf-review-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
        font-size: 13px;
    }

    .wf-review-stars {
        color: #f59e0b;
        letter-spacing: 1px;
        font-size: 14px;
    }

    .wf-review-variant {
        color: #6b7280;
        font-size: 12.5px;
    }

    .wf-review-comment {
        margin: 0;
        font-size: 14px;
        color: #374151;
        line-height: 1.6;
    }

    .sg-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(3px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 16px;
    }

    .sg-modal-overlay.active {
        display: flex;
    }

    .sg-modal-box {
        background: #ffffff;
        color: #111111;
        width: 100%;
        max-width: 520px;
        border-radius: 16px;
        padding: 26px 24px 28px;
        position: relative;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        border: 1.5px solid #e5e7eb;
    }

    .sg-close-btn {
        position: absolute;
        top: 16px;
        right: 18px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #f3f4f6;
        color: #111111;
        border: 1px solid #d1d5db;
        font-size: 20px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sg-modal-title {
        text-align: center;
        font-size: 20px;
        font-weight: 800;
        text-transform: uppercase;
        margin: 0 0 20px 0;
        color: #111111;
    }

    .sg-modal-title span {
        border-bottom: 3px solid #e60023;
        padding-bottom: 6px;
    }

    .sg-table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
    }

    .sg-table thead tr {
        background: #111111;
        color: #ffffff;
    }

    .sg-table th {
        padding: 12px 10px;
        font-size: 13.5px;
        font-weight: 800;
    }

    .sg-table td {
        padding: 15px 10px;
        font-size: 17px;
        font-weight: 700;
        border-bottom: 1px solid #e5e7eb;
    }

    .sg-size-col {
        font-size: 20px !important;
        font-weight: 900 !important;
        color: #e60023 !important;
    }

    @media (max-width: 900px) {
        .wf-top-grid {
            grid-template-columns: 1fr;
            gap: 28px;
            padding: 20px;
        }
    }
</style>

<div class="wf-detail-wrap">
    <div class="wf-top-grid">
        <div>
            <div class="wf-main-img-box">
                <img id="mainProductImg" src="<?= htmlspecialchars($displayMainImage) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>
            <div class="wf-thumb-list">
                <?php foreach ($galleryImgs as $idx => $gUrl): ?>
                    <div class="wf-thumb-item <?= $idx === 0 ? 'active' : '' ?>" onclick="switchMainImg(this, '<?= htmlspecialchars($gUrl) ?>')">
                        <img src="<?= htmlspecialchars($gUrl) ?>" alt="Thumb <?= $idx + 1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <h1 class="wf-product-title"><?= htmlspecialchars($product['name']) ?></h1>

            <div class="wf-price-box">
                <span class="wf-product-price"><?= number_format((float)$currPrice, 0, ',', '.') ?>đ</span>
                <?php if ($oldPrice !== null): ?>
                    <span class="wf-product-old-price"><?= number_format((float)$oldPrice, 0, ',', '.') ?>đ</span>
                <?php endif; ?>
            </div>

            <form action="cart.php" method="POST" id="detailCartForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                <input type="hidden" name="variant_id" id="selectedVariantId" value="<?= (int)$defaultVariant ?>">
                <input type="hidden" name="color" id="selectedColorInput" value="<?= htmlspecialchars($defaultColor) ?>">
                <input type="hidden" name="size" id="selectedSizeInput" value="<?= htmlspecialchars($defaultSize) ?>">

                <div class="wf-option-block">
                    <div class="wf-option-label">
                        MÀU SẮC: <span id="colorNameLabel"><?= htmlspecialchars(mb_strtoupper($defaultColor, 'UTF-8')) ?></span>
                    </div>
                    <div class="wf-color-list">
                        <?php foreach ($availableColors as $cIndex => $colorName): ?>
                            <?php $hexCode = $colorHexMap[$colorName] ?? '#111111'; ?>
                            <button type="button"
                                    class="wf-color-swatch <?= $cIndex === 0 ? 'active' : '' ?>"
                                    style="background-color: <?= htmlspecialchars($hexCode) ?>;"
                                    title="<?= htmlspecialchars($colorName) ?>"
                                    onclick="selectColorOption(this, '<?= htmlspecialchars($colorName, ENT_QUOTES) ?>')">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="wf-option-block">
                    <div class="wf-size-header">
                        <div class="wf-option-label" style="margin-bottom:0;">KÍCH CỠ:</div>
                        <button type="button" class="wf-size-guide-btn" onclick="openSizeModal()">
                            📏 Hướng dẫn chọn size
                        </button>
                    </div>
                    <div class="wf-size-list">
                        <?php foreach ($availableSizes as $sIdx => $sz): ?>
                            <button type="button"
                                    class="wf-size-btn <?= $sIdx === 0 ? 'active' : '' ?>"
                                    onclick="selectSizeOption(this, '<?= htmlspecialchars($sz, ENT_QUOTES) ?>')">
                                <?= htmlspecialchars($sz) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="wf-cart-row">
                    <div class="wf-qty-pill">
                        <button type="button" onclick="changeDetailQty(-1)">-</button>
                        <input type="number" name="quantity" id="detailQtyInput" value="1" min="1" max="999" oninput="validateQtyInput(this)" onblur="fixEmptyQty(this)">
                        <button type="button" onclick="changeDetailQty(1)">+</button>
                    </div>
                    <button type="submit" class="wf-btn-addcart">THÊM VÀO GIỎ HÀNG</button>
                </div>

                <button type="submit" name="buy_now" value="1" class="wf-btn-buynow">MUA NGAY</button>
            </form>
        </div>
    </div>

    <div class="wf-bottom-blocks">
        <div class="wf-full-box">
            <h3 class="wf-full-box-title">
                <span class="wf-title-left">Mô tả sản phẩm</span>
            </h3>
            <div class="wf-desc-content"><?= $cleanDesc ?></div>
        </div>

        <div class="wf-full-box">
            <h3 class="wf-full-box-title">
                <span class="wf-title-left">Đánh giá khách hàng (<?= $totalReviews ?>)</span>
                <?php if ($totalReviews > 0): ?>
                    <span class="wf-rating-summary">
                        <span style="color:#f59e0b;">★</span>
                        <strong><?= number_format($avgRating, 1) ?>/5</strong>
                        <span style="color:#6b7280; font-weight:500;">(<?= $totalReviews ?> đánh giá)</span>
                    </span>
                <?php endif; ?>
            </h3>

            <div class="wf-desc-content">
                <?php if (!empty($reviews)): ?>
                    <div class="wf-review-list">
                        <?php foreach ($reviews as $rv): ?>
                            <?php
                            $rName    = trim($rv['full_name'] ?? 'Khách hàng');
                            $rRating  = max(1, min(5, (int)($rv['rating'] ?? 5)));
                            $rawVariant = (string)($rv['variant'] ?? ('Size ' . $defaultSize . ' - ' . $defaultColor));
                            $mappedSize = $defaultSize;
                            if (preg_match('/Size\s*([A-Za-z0-9]+)/i', $rawVariant, $szMatch)) {
                                $mappedSize = normalizeSizeToLetter($szMatch[1], $defaultSize);
                            }
                            $rVariant = 'Size ' . $mappedSize . ' - ' . $defaultColor;

                            $rDate = $rv['created_at'] ?? '10/11/2026';
                            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $rDate)) {
                                $rDate = date('d/m/Y', strtotime($rDate));
                            }
                            $words = preg_split('/\s+/u', $rName, -1, PREG_SPLIT_NO_EMPTY);
                            $lastWord = !empty($words) ? end($words) : 'K';
                            $initial = mb_strtoupper(mb_substr($lastWord, 0, 1, 'UTF-8'), 'UTF-8');
                            ?>
                            <div class="wf-review-card">
                                <div class="wf-review-avatar"><?= htmlspecialchars($initial) ?></div>
                                <div class="wf-review-body">
                                    <div class="wf-review-top">
                                        <div class="wf-review-author">
                                            <span><?= htmlspecialchars($rName) ?></span>
                                            <span class="wf-verified-badge">✓ Đã mua hàng</span>
                                        </div>
                                        <span class="wf-review-date"><?= htmlspecialchars($rDate) ?></span>
                                    </div>
                                    <div class="wf-review-meta">
                                        <span class="wf-review-stars"><?= str_repeat('★', $rRating) . str_repeat('☆', 5 - $rRating) ?></span>
                                        <span class="wf-review-variant">Phân loại: <?= htmlspecialchars($rVariant) ?></span>
                                    </div>
                                    <p class="wf-review-comment"><?= htmlspecialchars($rv['comment'] ?? '') ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:18px 0;">
                        <p style="color:#6b7280; margin:0;">Sản phẩm mới lên kệ — Hiện chưa có đánh giá nào cho sản phẩm này.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="sizeGuideModal" class="sg-modal-overlay" onclick="closeSizeModal(event)">
    <div class="sg-modal-box" onclick="event.stopPropagation()">
        <button type="button" class="sg-close-btn" onclick="closeSizeModal()">&times;</button>
        <h3 class="sg-modal-title"><span>BẢNG KHUYẾN NGHỊ KÍCH CỠ</span></h3>

        <table class="sg-table">
            <thead>
                <tr>
                    <th>SIZE</th>
                    <th>CHIỀU CAO (CM)</th>
                    <th>CÂN NẶNG (KG)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="sg-size-col">S</td>
                    <td>155 - 160</td>
                    <td>40 - 50</td>
                </tr>
                <tr>
                    <td class="sg-size-col">M</td>
                    <td>160 - 165</td>
                    <td>50 - 57</td>
                </tr>
                <tr>
                    <td class="sg-size-col">L</td>
                    <td>165 - 170</td>
                    <td>57 - 65</td>
                </tr>
                <tr>
                    <td class="sg-size-col">XL</td>
                    <td>170 - 175</td>
                    <td>65 - 72</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    var productVariants = <?= json_encode($variants, JSON_UNESCAPED_UNICODE) ?>;

    function switchMainImg(el, url) {
        document.getElementById('mainProductImg').src = url;
        document.querySelectorAll('.wf-thumb-item').forEach(function(item) {
            item.classList.remove('active');
        });
        el.classList.add('active');
    }

    function updateMatchedVariantId() {
        var c = document.getElementById('selectedColorInput').value;
        var s = document.getElementById('selectedSizeInput').value;
        for (var i = 0; i < productVariants.length; i++) {
            if (productVariants[i].color === c && productVariants[i].size === s) {
                document.getElementById('selectedVariantId').value = productVariants[i].id;
                return;
            }
        }
    }

    function selectColorOption(btn, colorName) {
        document.querySelectorAll('.wf-color-swatch').forEach(function(el) {
            el.classList.remove('active');
        });
        btn.classList.add('active');
        document.getElementById('selectedColorInput').value = colorName;
        document.getElementById('colorNameLabel').textContent = colorName.toUpperCase();
        updateMatchedVariantId();
    }

    function selectSizeOption(btn, sizeName) {
        document.querySelectorAll('.wf-size-btn').forEach(function(el) {
            el.classList.remove('active');
        });
        btn.classList.add('active');
        document.getElementById('selectedSizeInput').value = sizeName;
        updateMatchedVariantId();
    }

    function changeDetailQty(delta) {
        var inp = document.getElementById('detailQtyInput');
        var val = (parseInt(inp.value, 10) || 1) + delta;
        if (val < 1) val = 1;
        if (val > 999) val = 999;
        inp.value = val;
    }

    function validateQtyInput(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value !== '' && parseInt(input.value, 10) > 999) {
            input.value = 999;
        }
    }

    function fixEmptyQty(input) {
        var val = parseInt(input.value, 10);
        if (isNaN(val) || val < 1) {
            input.value = 1;
        }
    }

    function openSizeModal() {
        document.getElementById('sizeGuideModal').classList.add('active');
    }

    function closeSizeModal(e) {
        if (!e || e.target.id === 'sizeGuideModal') {
            document.getElementById('sizeGuideModal').classList.remove('active');
        }
    }
</script>

<?php require_once __DIR__ . '/my_footer.php'; ?>