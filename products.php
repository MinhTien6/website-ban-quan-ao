<?php
/**
 * Trang Danh mục Sản phẩm & Tìm kiếm / Lọc (products.php)
 * Phụ trách: TV4 (Thư) + Tích hợp Giỏ hàng TV5 (Đức)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Category.php';

$productModel = new Product($pdo);
$categoryModel = new Category($pdo);

// Lấy danh sách danh mục hoạt động từ DB
$activeCategories = $categoryModel->getAllActive();

// Tham số lọc và phân loại
$rawTab   = trim($_GET['tab'] ?? '');
$rawSort  = trim($_GET['sort'] ?? '');
$mode     = $rawTab !== '' ? $rawTab : $rawSort;

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

$categoryFilter = trim($_GET['category'] ?? '');
$priceRange     = trim($_GET['price'] ?? '');
$keyword        = trim($_GET['keyword'] ?? ($_GET['q'] ?? ''));

if ($keyword !== '') {
    $pageHeading = 'KẾT QUẢ TÌM KIẾM: "' . mb_strtoupper($keyword, 'UTF-8') . '"';
}

// Xây dựng câu lệnh truy vấn lọc an toàn bằng Prepared Statement
$where = ["p.status = 1"];
$params = [];

// 1. Lọc theo tab
if ($currentTab === 'bestseller') {
    $where[] = "p.sale_price IS NOT NULL AND p.sale_price > 0";
}

// 2. Lọc theo danh mục (hỗ trợ ID hoặc slug)
if ($categoryFilter !== '') {
    if (is_numeric($categoryFilter)) {
        $where[] = "p.category_id = :cat_id";
        $params['cat_id'] = (int)$categoryFilter;
    } else {
        $where[] = "c.slug = :cat_slug";
        $params['cat_slug'] = $categoryFilter;
    }
}

// 3. Lọc theo từ khóa tìm kiếm (hỗ trợ tiếng Việt không dấu)
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

    $where[] = "(p.name LIKE :kw OR p.slug LIKE :kw_slug OR c.name LIKE :kw_cat)";
    $params['kw']      = '%' . $keyword . '%';
    $params['kw_slug'] = '%' . ($kwSlug !== '' ? $kwSlug : $keyword) . '%';
    $params['kw_cat']  = '%' . $keyword . '%';
}

// 4. Lọc theo khoảng giá
if ($priceRange === 'under200') {
    $where[] = "COALESCE(NULLIF(p.sale_price, 0), p.price) < 200000";
} elseif ($priceRange === '200to350' || $priceRange === '200to400') {
    $where[] = "COALESCE(NULLIF(p.sale_price, 0), p.price) BETWEEN 200000 AND 350000";
} elseif ($priceRange === 'over350' || $priceRange === 'over400') {
    $where[] = "COALESCE(NULLIF(p.sale_price, 0), p.price) > 350000";
}

$whereSql = implode(' AND ', $where);

// Sắp xếp
$orderBySql = "p.id DESC";
if ($sortOrder === 'price_asc') {
    $orderBySql = "COALESCE(NULLIF(p.sale_price, 0), p.price) ASC, p.id DESC";
} elseif ($sortOrder === 'price_desc') {
    $orderBySql = "COALESCE(NULLIF(p.sale_price, 0), p.price) DESC, p.id DESC";
} elseif ($sortOrder === 'bestseller') {
    $orderBySql = "p.sale_price DESC, p.id DESC";
}

// Phân trang
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 9;
$offset = ($page - 1) * $limit;

// Đếm tổng số sản phẩm thỏa điều kiện
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE {$whereSql}
");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $limit));

// Lấy danh sách sản phẩm kèm biến thể mặc định
$extraSubqueries = "
    (SELECT pv.id FROM product_variants pv WHERE pv.product_id = p.id AND pv.stock > 0 ORDER BY pv.id ASC LIMIT 1) AS default_variant_id,
    (SELECT pv.size FROM product_variants pv WHERE pv.product_id = p.id AND pv.stock > 0 ORDER BY pv.id ASC LIMIT 1) AS default_size,
    (SELECT pv.color FROM product_variants pv WHERE pv.product_id = p.id AND pv.stock > 0 ORDER BY pv.id ASC LIMIT 1) AS default_color
";

$sql = "
    SELECT p.*, c.name AS category_name, pi.image_path AS main_image, {$extraSubqueries}
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
    WHERE {$whereSql}
    ORDER BY {$orderBySql} 
    LIMIT {$limit} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

/**
 * Hàm hỗ trợ tạo URL kèm bộ lọc hiện tại
 */
function buildFilterUrl(array $overrides = []): string {
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

$pageTitle = $pageHeading . ' | VIBEZ.CLUB';
require_once __DIR__ . '/includes/header.php';
?>

<div class="catalog-wrap" style="max-width:1220px; margin:36px auto 70px; padding:0 20px; font-family:'Inter', sans-serif;">
    <div class="catalog-layout" style="display:flex; align-items:flex-start; gap:32px; position:relative;">
        
        <!-- THANH BỘ LỌC BÊN TRÁI -->
        <div id="filterSidebarWrap" class="filter-sidebar-wrap" style="position:relative; width:280px; flex-shrink:0;">
            <aside class="filter-sidebar" style="background:#ffffff; border:1px solid #eeeeee; border-radius:16px; padding:22px 20px; box-shadow:0 4px 20px rgba(0,0,0,0.04);">
                <div class="filter-top-header" style="display:flex; align-items:center; justify-content:space-between; padding-bottom:14px; margin-bottom:18px; border-bottom:2px solid #111111;">
                    <div class="filter-top-title" style="display:flex; align-items:center; gap:8px; font-size:16px; font-weight:900; color:#111; text-transform:uppercase;">
                        <i class="fa-solid fa-filter" style="color:#e60023;"></i>
                        <span>BỘ LỌC</span>
                    </div>
                    <a href="<?= BASE_URL ?>/products.php" class="filter-reset-link" style="font-size:13.5px; font-weight:600; color:#e60023; text-decoration:none;">
                        Xóa lọc
                    </a>
                </div>

                <!-- SẮP XẾP THEO -->
                <div class="filter-section" style="padding-bottom:16px; margin-bottom:18px; border-bottom:1px solid #f1f1f1;">
                    <div class="filter-sec-head" onclick="toggleFilterSection(this)" style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; padding:4px 0; margin-bottom:10px;">
                        <span class="filter-sec-title" style="font-size:13.5px; font-weight:800; color:#111; text-transform:uppercase;">SẮP XẾP THEO</span>
                        <i class="fa-solid fa-chevron-down filter-sec-arrow" style="font-size:12px; color:#555;"></i>
                    </div>
                    <div class="filter-sec-body">
                        <ul class="filter-options" style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:4px;">
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['tab' => 'new', 'sort' => null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'new' ? 'active' : '' ?>"
                                   style="display:flex; align-items:center; justify-content:space-between; padding:9px 12px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:<?= $sortOrder === 'new' ? '700' : '500' ?>; color:<?= $sortOrder === 'new' ? '#e60023' : '#222' ?>; background:<?= $sortOrder === 'new' ? '#fff0f2' : 'transparent' ?>;">
                                    <span>Mới nhất</span>
                                    <?php if ($sortOrder === 'new'): ?><i class="fa-solid fa-check" style="color:#e60023;"></i><?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['tab' => 'bestseller', 'sort' => null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'bestseller' ? 'active' : '' ?>"
                                   style="display:flex; align-items:center; justify-content:space-between; padding:9px 12px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:<?= $sortOrder === 'bestseller' ? '700' : '500' ?>; color:<?= $sortOrder === 'bestseller' ? '#e60023' : '#222' ?>; background:<?= $sortOrder === 'bestseller' ? '#fff0f2' : 'transparent' ?>;">
                                    <span>Bán chạy nhất</span>
                                    <?php if ($sortOrder === 'bestseller'): ?><i class="fa-solid fa-check" style="color:#e60023;"></i><?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['sort' => 'price_asc', 'tab' => null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'price_asc' ? 'active' : '' ?>"
                                   style="display:flex; align-items:center; justify-content:space-between; padding:9px 12px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:<?= $sortOrder === 'price_asc' ? '700' : '500' ?>; color:<?= $sortOrder === 'price_asc' ? '#e60023' : '#222' ?>; background:<?= $sortOrder === 'price_asc' ? '#fff0f2' : 'transparent' ?>;">
                                    <span>Giá: Thấp đến Cao</span>
                                    <?php if ($sortOrder === 'price_asc'): ?><i class="fa-solid fa-check" style="color:#e60023;"></i><?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['sort' => 'price_desc', 'tab' => null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'price_desc' ? 'active' : '' ?>"
                                   style="display:flex; align-items:center; justify-content:space-between; padding:9px 12px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:<?= $sortOrder === 'price_desc' ? '700' : '500' ?>; color:<?= $sortOrder === 'price_desc' ? '#e60023' : '#222' ?>; background:<?= $sortOrder === 'price_desc' ? '#fff0f2' : 'transparent' ?>;">
                                    <span>Giá: Cao đến Thấp</span>
                                    <?php if ($sortOrder === 'price_desc'): ?><i class="fa-solid fa-check" style="color:#e60023;"></i><?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- DANH MỤC SẢN PHẨM -->
                <div class="filter-section" style="padding-bottom:16px; margin-bottom:18px; border-bottom:1px solid #f1f1f1;">
                    <div class="filter-sec-head" onclick="toggleFilterSection(this)" style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; padding:4px 0; margin-bottom:10px;">
                        <span class="filter-sec-title" style="font-size:13.5px; font-weight:800; color:#111; text-transform:uppercase;">DANH MỤC SẢN PHẨM</span>
                        <i class="fa-solid fa-chevron-down filter-sec-arrow" style="font-size:12px; color:#555;"></i>
                    </div>
                    <div class="filter-sec-body">
                        <ul class="filter-options" style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:4px;">
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['category' => null])) ?>"
                                   class="filter-option-link <?= $categoryFilter === '' ? 'active' : '' ?>"
                                   style="display:flex; align-items:center; justify-content:space-between; padding:9px 12px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:<?= $categoryFilter === '' ? '700' : '500' ?>; color:<?= $categoryFilter === '' ? '#e60023' : '#222' ?>; background:<?= $categoryFilter === '' ? '#fff0f2' : 'transparent' ?>;">
                                    <span>Tất cả danh mục</span>
                                    <?php if ($categoryFilter === ''): ?><i class="fa-solid fa-check" style="color:#e60023;"></i><?php endif; ?>
                                </a>
                            </li>
                            <?php foreach ($activeCategories as $cat): ?>
                                <?php 
                                $isCatActive = ($categoryFilter == $cat['id'] || $categoryFilter === $cat['slug']);
                                ?>
                                <li>
                                    <a href="<?= htmlspecialchars(buildFilterUrl(['category' => $cat['id']])) ?>"
                                       class="filter-option-link <?= $isCatActive ? 'active' : '' ?>"
                                       style="display:flex; align-items:center; justify-content:space-between; padding:9px 12px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:<?= $isCatActive ? '700' : '500' ?>; color:<?= $isCatActive ? '#e60023' : '#222' ?>; background:<?= $isCatActive ? '#fff0f2' : 'transparent' ?>;">
                                        <span><?= e($cat['name']) ?></span>
                                        <?php if ($isCatActive): ?><i class="fa-solid fa-check" style="color:#e60023;"></i><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- KHOẢNG GIÁ -->
                <div class="filter-section" style="padding-bottom:0; margin-bottom:0; border-bottom:none;">
                    <div class="filter-sec-head" onclick="toggleFilterSection(this)" style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; padding:4px 0; margin-bottom:10px;">
                        <span class="filter-sec-title" style="font-size:13.5px; font-weight:800; color:#111; text-transform:uppercase;">KHOẢNG GIÁ</span>
                        <i class="fa-solid fa-chevron-down filter-sec-arrow" style="font-size:12px; color:#555;"></i>
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
                        <ul class="filter-options" style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:4px;">
                            <?php foreach ($priceList as $pKey => $pLabel): ?>
                                <li>
                                    <a href="<?= htmlspecialchars(buildFilterUrl(['price' => $pKey !== '' ? $pKey : null])) ?>"
                                       class="filter-option-link <?= $priceRange === $pKey ? 'active' : '' ?>"
                                       style="display:flex; align-items:center; justify-content:space-between; padding:9px 12px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:<?= $priceRange === $pKey ? '700' : '500' ?>; color:<?= $priceRange === $pKey ? '#e60023' : '#222' ?>; background:<?= $priceRange === $pKey ? '#fff0f2' : 'transparent' ?>;">
                                        <span><?= htmlspecialchars($pLabel) ?></span>
                                        <?php if ($priceRange === $pKey): ?><i class="fa-solid fa-check" style="color:#e60023;"></i><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>

        <!-- DANH SÁCH SẢN PHẨM -->
        <main class="catalog-main" style="flex:1; min-width:0;">
            <div class="catalog-header" style="margin-bottom:22px; padding-bottom:12px; border-bottom:1.5px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between;">
                <h1 class="catalog-heading" style="font-size:22px; font-weight:800; text-transform:uppercase; margin:0;">
                    <?= htmlspecialchars($pageHeading) ?>
                </h1>
                <span style="font-size:13.5px; color:#666; font-weight:600;">
                    Hiển thị <?= count($products) ?> / <?= $totalItems ?> sản phẩm
                </span>
            </div>

            <?php if (!empty($products)): ?>
                <div class="product-grid" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:22px;">
                    <?php foreach ($products as $p): ?>
                        <?php
                        $pid        = (int)$p['id'];
                        $prodName   = $p['name'];
                        $currPrice  = (!empty($p['sale_price']) && $p['sale_price'] > 0) ? (float)$p['sale_price'] : (float)$p['price'];
                        $oldPrice   = (!empty($p['sale_price']) && $p['sale_price'] > 0) ? (float)$p['price'] : null;
                        $imgUrl     = !empty($p['main_image']) ? BASE_URL . '/public/' . ltrim($p['main_image'], '/') : 'https://placehold.co/400x400/ebeef5/646c79?text=VibezClub';
                        $defVariant = (int)($p['default_variant_id'] ?? 0);
                        $defSize    = $p['default_size'] ?? 'M';
                        $defColor   = $p['default_color'] ?? 'Đen';
                        ?>
                        <div class="prod-card" style="background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; transition:transform 0.2s ease, box-shadow 0.2s ease; display:flex; flex-direction:column;">
                            <div class="prod-img-box" style="width:100%; aspect-ratio:1/1; background:#f3f4f6; position:relative; overflow:hidden;">
                                <?php if ($oldPrice !== null): ?>
                                    <span class="badge-new-red" style="position:absolute; top:10px; right:10px; background:#e60023; color:#fff; font-size:11px; font-weight:800; padding:4px 9px; border-radius:4px; text-transform:uppercase; z-index:3;">
                                        SALE
                                    </span>
                                <?php elseif ($currentTab === 'new'): ?>
                                    <span class="badge-new-red" style="position:absolute; top:10px; right:10px; background:#111; color:#fff; font-size:11px; font-weight:800; padding:4px 9px; border-radius:4px; text-transform:uppercase; z-index:3;">
                                        NEW
                                    </span>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $pid ?>" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center;">
                                    <img src="<?= e($imgUrl) ?>" alt="<?= e($prodName) ?>" style="width:100%; height:100%; object-fit:contain; transition:transform 0.3s ease;" onerror="this.onerror=null; this.src='https://placehold.co/400x400/ebeef5/646c79?text=VibezClub';">
                                </a>
                            </div>
                            <div class="prod-body" style="padding:14px; display:flex; flex-direction:column; flex:1; justify-content:space-between;">
                                <div>
                                    <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $pid ?>" class="prod-title" style="font-size:15px; font-weight:700; color:#111; text-decoration:none; margin-bottom:8px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:42px;">
                                        <?= e($prodName) ?>
                                    </a>
                                    <div class="prod-price-row" style="display:flex; align-items:baseline; gap:8px; margin-bottom:12px;">
                                        <span class="prod-price-current" style="font-size:17px; font-weight:900; color:#e60023;">
                                            <?= number_format($currPrice, 0, ',', '.') ?>đ
                                        </span>
                                        <?php if ($oldPrice !== null): ?>
                                            <span class="prod-price-old" style="font-size:13px; color:#888; text-decoration:line-through;">
                                                <?= number_format($oldPrice, 0, ',', '.') ?>đ
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <form action="<?= BASE_URL ?>/cart.php" method="POST" class="card-cart-form" style="margin-top:auto; padding-top:10px; border-top:1px solid #f1f1f1; display:flex; flex-direction:column; gap:8px;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?= $pid ?>">
                                    <input type="hidden" name="variant_id" value="<?= $defVariant ?>">
                                    <input type="hidden" name="size" value="<?= e($defSize) ?>">
                                    <input type="hidden" name="color" value="<?= e($defColor) ?>">

                                    <div class="card-action-top" style="display:flex; align-items:center; gap:8px; width:100%;">
                                        <div class="card-qty-pill" style="display:inline-flex; align-items:center; justify-content:space-between; width:92px; height:38px; border:1.5px solid #d1d5db; border-radius:999px; padding:0 4px; background:#ffffff; box-sizing:border-box; flex-shrink:0;">
                                            <button type="button" onclick="adjustCardQty(this, -1)" style="width:25px; height:25px; border-radius:50%; border:none; background:#f3f4f6; color:#111; font-size:15px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; padding:0; flex-shrink:0;">-</button>
                                            <input type="number" name="quantity" value="1" min="1" max="100" oninput="validateCardQty(this)" onblur="fixEmptyCardQty(this)" style="width:32px; height:28px; border:none; background:transparent; text-align:center; font-size:13.5px; font-weight:800; color:#111; outline:none; padding:0;">
                                            <button type="button" onclick="adjustCardQty(this, 1)" style="width:25px; height:25px; border-radius:50%; border:none; background:#f3f4f6; color:#111; font-size:15px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; padding:0; flex-shrink:0;">+</button>
                                        </div>
                                        <button type="submit" class="card-btn-addcart" style="flex:1; height:38px; background:#ffffff; color:#111; border:1.5px solid #111; border-radius:999px; font-size:11px; font-weight:800; text-transform:uppercase; cursor:pointer; padding:0 6px; white-space:nowrap; transition:all 0.18s ease;">
                                            THÊM VÀO GIỎ
                                        </button>
                                    </div>

                                    <button type="submit" name="buy_now" value="1" class="card-btn-buynow" style="width:100%; height:38px; background:#e60023; color:#fff; border:none; border-radius:999px; font-size:12.5px; font-weight:800; text-transform:uppercase; cursor:pointer; transition:all 0.18s ease;">
                                        MUA NGAY
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:60px 20px; background:#fff; border:1px solid #eee; border-radius:12px;">
                    <p style="font-size:16px; color:#555; margin-bottom:14px;">
                        Không tìm thấy sản phẩm nào phù hợp với bộ lọc hiện tại.
                    </p>
                    <a href="<?= BASE_URL ?>/products.php" style="display:inline-block; padding:10px 24px; background:#e60023; color:#fff; border-radius:999px; text-decoration:none; font-weight:700;">
                        Xóa tất cả bộ lọc
                    </a>
                </div>
            <?php endif; ?>

            <!-- PHÂN TRANG TRÒN HIỆN ĐẠI -->
            <?php if ($totalPages > 1): ?>
                <div class="round-pagination" style="display:flex; align-items:center; justify-content:center; gap:10px; margin-top:40px;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php
                        $queryParams = $_GET;
                        $queryParams['page'] = $i;
                        $pageUrl = 'products.php?' . http_build_query($queryParams);
                        ?>
                        <a href="<?= htmlspecialchars($pageUrl) ?>" style="width:40px; height:40px; border-radius:50%; border:1.5px solid <?= $i === $page ? '#111' : '#d1d5db' ?>; background:<?= $i === $page ? '#111' : '#fff' ?>; color:<?= $i === $page ? '#fff' : '#111' ?>; font-size:14px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; transition:all 0.2s ease;">
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
            var body = section.querySelector('.filter-sec-body');
            var arrow = section.querySelector('.filter-sec-arrow');
            if (body.style.display === 'none') {
                body.style.display = 'block';
                if (arrow) arrow.style.transform = 'none';
            } else {
                body.style.display = 'none';
                if (arrow) arrow.style.transform = 'rotate(-90deg)';
            }
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
        if (next > 100) next = 100;
        input.value = next;
    }

    function validateCardQty(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value !== '' && parseInt(input.value, 10) > 100) {
            input.value = 100;
        }
    }

    function fixEmptyCardQty(input) {
        var val = parseInt(input.value, 10);
        if (isNaN(val) || val < 1) {
            input.value = 1;
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
