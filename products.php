<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Category.php';

$productModel = new Product($pdo);
$categoryModel = new Category($pdo);

$activeCategories = $categoryModel->getAllActive();

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
} elseif ($categoryFilter !== '' && $currentTab === 'all') {
    foreach ($activeCategories as $ac) {
        if ((string)$ac['id'] === (string)$categoryFilter || $ac['slug'] === $categoryFilter) {
            $pageHeading = mb_strtoupper($ac['name'], 'UTF-8');
            break;
        }
    }
}

$where = ["p.status = 1"];
$params = [];

if ($currentTab === 'bestseller') {
    $where[] = "p.sale_price IS NOT NULL AND p.sale_price > 0";
}

if ($categoryFilter !== '') {
    if (is_numeric($categoryFilter)) {
        $where[] = "(p.category_id = :cat_id OR p.category_id IN (SELECT id FROM categories WHERE parent_id = :cat_id_sub))";
        $params['cat_id'] = (int)$categoryFilter;
        $params['cat_id_sub'] = (int)$categoryFilter;
    } else {
        $where[] = "(c.slug = :cat_slug OR p.category_id IN (SELECT id FROM categories WHERE parent_id = (SELECT id FROM categories WHERE slug = :cat_slug_sub LIMIT 1)))";
        $params['cat_slug'] = $categoryFilter;
        $params['cat_slug_sub'] = $categoryFilter;
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

    $where[] = "(p.name LIKE :kw OR p.slug LIKE :kw_slug OR c.name LIKE :kw_cat)";
    $params['kw']      = '%' . $keyword . '%';
    $params['kw_slug'] = '%' . ($kwSlug !== '' ? $kwSlug : $keyword) . '%';
    $params['kw_cat']  = '%' . $keyword . '%';
}

if ($priceRange === 'under200') {
    $where[] = "COALESCE(NULLIF(p.sale_price, 0), p.price) < 200000";
} elseif ($priceRange === '200to350' || $priceRange === '200to400') {
    $where[] = "COALESCE(NULLIF(p.sale_price, 0), p.price) BETWEEN 200000 AND 350000";
} elseif ($priceRange === 'over350' || $priceRange === 'over400') {
    $where[] = "COALESCE(NULLIF(p.sale_price, 0), p.price) > 350000";
}

$whereSql = implode(' AND ', $where);

$orderBySql = "p.id DESC";
if ($sortOrder === 'price_asc') {
    $orderBySql = "COALESCE(NULLIF(p.sale_price, 0), p.price) ASC, p.id DESC";
} elseif ($sortOrder === 'price_desc') {
    $orderBySql = "COALESCE(NULLIF(p.sale_price, 0), p.price) DESC, p.id DESC";
} elseif ($sortOrder === 'bestseller') {
    $orderBySql = "p.sale_price DESC, p.id DESC";
}

$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 12;
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE {$whereSql}
");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $limit));

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

<style>
    .catalog-layout {
        display: flex;
        align-items: flex-start;
        gap: 32px;
        position: relative;
        transition: gap 0.35s ease, padding 0.35s ease;
    }

    .filter-sidebar-wrap {
        width: 280px;
        flex-shrink: 0;
        position: sticky !important;
        top: 90px !important;
        align-self: flex-start !important;
        z-index: 100;
        transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1), 
                    margin 0.35s ease, 
                    transform 0.35s ease,
                    opacity 0.25s ease;
    }

    .filter-sidebar,
    .filter-sidebar span,
    .filter-sidebar a,
    .filter-sidebar p,
    .catalog-wrap,
    .catalog-wrap p {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    }

    
    i[class*="fa-"], .fa, .fa-solid, .fa-regular {
        font-family: "Font Awesome 6 Free", "FontAwesome" !important;
    }

    .prod-title {
        font-family: 'Montserrat', 'Be Vietnam Pro', sans-serif !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        letter-spacing: 0.1px !important;
        color: #1a1a1a !important;
        line-height: 1.45 !important;
        transition: color 0.18s ease;
    }
    .prod-title:hover {
        color: #e60023 !important;
    }

    .catalog-heading {
        font-family: 'Montserrat', 'Be Vietnam Pro', sans-serif !important;
        font-weight: 700 !important;
        letter-spacing: -0.2px !important;
    }

    .filter-sidebar {
        background: #ffffff;
        border: 1px solid #eeeeee;
        border-radius: 16px;
        padding: 22px 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        max-height: calc(100vh - 120px);
        overflow-y: auto;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .filter-sidebar-wrap.collapsed {
        width: 0 !important;
        margin-right: 0 !important;
    }

    .filter-sidebar-wrap.collapsed .filter-sidebar {
        opacity: 0 !important;
        pointer-events: none !important;
        transform: translateX(-30px) !important;
    }

    
    .filter-collapse-handle {
        position: absolute;
        top: 140px;
        right: -20px;
        width: 22px;
        height: 48px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-left: none;
        border-radius: 0 8px 8px 0;
        box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #555555;
        z-index: 110;
        transition: color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
    }

    .filter-collapse-handle:hover {
        color: #e60023;
        background: #fff1f2;
    }

    .filter-sidebar-wrap.collapsed .filter-collapse-handle {
        position: fixed !important;
        left: 0 !important;
        top: 45vh !important;
        right: auto !important;
        border-left: none !important;
        border-radius: 0 8px 8px 0 !important;
        box-shadow: 2px 4px 12px rgba(0, 0, 0, 0.18) !important;
        background: #111111 !important;
        color: #ffffff !important;
        width: 26px !important;
        height: 52px !important;
    }

    .filter-sidebar-wrap.collapsed .filter-collapse-handle:hover {
        background: #e60023 !important;
        color: #ffffff !important;
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
        transition: all 0.18s ease;
        line-height: 1.4;
    }

    .filter-option-link:hover {
        background: #f8f8f8;
        color: #e60023;
    }

    .filter-option-link.active {
        background: #fff0f2 !important;
        color: #e60023 !important;
        font-weight: 700 !important;
    }

    .filter-top-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 14px;
        margin-bottom: 18px;
        border-bottom: 2px solid #111111;
        transition: border-color 0.25s ease, padding 0.25s ease;
    }

    .filter-top-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'Montserrat', 'Be Vietnam Pro', sans-serif !important;
        font-size: 14.5px !important;
        font-weight: 600 !important;
        color: #111;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .filter-sec-title {
        font-family: 'Montserrat', 'Be Vietnam Pro', sans-serif !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #222 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .filter-top-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-reset-link {
        font-size: 13.5px;
        font-weight: 600;
        color: #e60023;
        text-decoration: none;
        transition: opacity 0.2s;
    }
    .filter-reset-link:hover {
        text-decoration: underline;
        opacity: 0.85;
    }

    .filter-mobile-toggle-btn {
        display: none !important;
    }

    .filter-sections-wrap {
        display: block;
    }

    
    @media (max-width: 992px) {
        .catalog-layout {
            flex-direction: column !important;
            gap: 16px !important;
        }

        .filter-sidebar-wrap {
            width: 100% !important;
            max-width: 100% !important;
            position: relative !important; 
            top: 0 !important;
            align-self: stretch !important;
            margin-right: 0 !important;
            margin-bottom: 14px !important;
            z-index: 10 !important;
        }

        .filter-sidebar {
            max-height: none !important; 
            overflow-y: visible !important; 
            padding: 14px 18px !important;
            border-radius: 14px !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04) !important;
        }

        .filter-collapse-handle {
            display: none !important;
        }

        .filter-mobile-toggle-btn {
            display: inline-flex !important;
            align-items: center;
            gap: 6px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 700;
            color: #111111;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .filter-mobile-toggle-btn:hover {
            background: #e5e7eb;
        }

        .filter-top-header {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
            border-bottom: none !important;
            cursor: pointer;
        }

        .filter-sidebar-wrap:not(.mobile-collapsed) .filter-top-header {
            padding-bottom: 12px !important;
            border-bottom: 1.5px solid #eeeeee !important;
        }

        .filter-sections-wrap {
            transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.28s ease, margin 0.28s ease;
        }

        .filter-sidebar-wrap.mobile-collapsed .filter-sections-wrap {
            max-height: 0 !important;
            opacity: 0 !important;
            overflow: hidden !important;
            pointer-events: none !important;
            margin-top: 0 !important;
        }

        .filter-sidebar-wrap:not(.mobile-collapsed) .filter-sections-wrap {
            max-height: 1800px !important;
            opacity: 1 !important;
            overflow: visible !important;
            pointer-events: auto !important;
            margin-top: 14px !important;
        }

        .catalog-main {
            width: 100% !important;
            position: relative !important;
        }

        .product-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 16px !important;
        }
    }

    
    @media (min-width: 641px) and (max-width: 992px) {
        .filter-sidebar-wrap:not(.mobile-collapsed) .filter-sections-wrap {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 18px !important;
        }
        .filter-section {
            border-bottom: none !important;
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
            border-right: 1px solid #f1f1f1;
            padding-right: 14px;
        }
        .filter-section:last-child {
            border-right: none;
            padding-right: 0;
        }
    }

    @media (max-width: 576px) {
        .catalog-wrap {
            padding: 0 12px !important;
            margin: 20px auto 50px !important;
        }
        .product-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 10px !important;
        }
        .prod-body {
            padding: 10px !important;
        }
        .prod-title {
            font-size: 13.5px !important;
            min-height: 38px !important;
        }
    }
</style>

<div class="catalog-wrap" style="max-width:1220px; margin:36px auto 70px; padding:0 20px; font-family:'Inter', sans-serif;">
    <div class="catalog-layout">
        
        
        <?php
        $svgFilter = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#e60023" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>';
        $svgChevron = '<svg class="filter-sec-arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#555555" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transition:transform 0.22s ease; flex-shrink:0;"><polyline points="6 9 12 15 18 9"></polyline></svg>';
        $svgCheck = '<svg class="filter-check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#e60023" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        $svgLeft = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>';

        
        $activeFilterCount = 0;
        if ($sortOrder !== 'new') $activeFilterCount++;
        if ($categoryFilter !== '') $activeFilterCount++;
        if ($priceRange !== '') $activeFilterCount++;
        $hasActiveFilter = ($activeFilterCount > 0);
        ?>
        <div id="filterSidebarWrap" class="filter-sidebar-wrap mobile-collapsed">
            <aside class="filter-sidebar">
                <div class="filter-top-header" onclick="toggleMobileFilter()">
                    <div class="filter-top-title">
                        <?= $svgFilter ?>
                        <span>BỘ LỌC</span>
                    </div>
                    <div class="filter-top-actions">
                        <?php if ($hasActiveFilter): ?>
                            <a href="<?= BASE_URL ?>/products.php" class="filter-reset-link" onclick="event.stopPropagation()">
                                Xóa lọc
                            </a>
                        <?php endif; ?>
                        <button type="button" id="filterMobileToggleBtn" class="filter-mobile-toggle-btn" aria-label="Đóng/Mở bộ lọc">
                            <span id="filterMobileToggleText">Xem bộ lọc</span>
                            <span id="filterMobileToggleIcon">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </span>
                        </button>
                    </div>
                </div>

                <div id="filterSectionsWrap" class="filter-sections-wrap">

                
                <div class="filter-section" style="padding-bottom:16px; margin-bottom:18px; border-bottom:1px solid #f1f1f1;">
                    <div class="filter-sec-head" onclick="toggleFilterSection(this)" style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; padding:4px 0; margin-bottom:10px;">
                        <span class="filter-sec-title" style="font-size:13.5px; font-weight:800; color:#111; text-transform:uppercase;">SẮP XẾP THEO</span>
                        <?= $svgChevron ?>
                    </div>
                    <div class="filter-sec-body">
                        <ul class="filter-options" style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:4px;">
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['tab' => 'new', 'sort' => null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'new' ? 'active' : '' ?>">
                                    <span>Mới nhất</span>
                                    <?php if ($sortOrder === 'new'): ?><?= $svgCheck ?><?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['tab' => 'bestseller', 'sort' => null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'bestseller' ? 'active' : '' ?>">
                                    <span>Bán chạy nhất</span>
                                    <?php if ($sortOrder === 'bestseller'): ?><?= $svgCheck ?><?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['sort' => 'price_asc', 'tab' => null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'price_asc' ? 'active' : '' ?>">
                                    <span>Giá: Thấp đến Cao</span>
                                    <?php if ($sortOrder === 'price_asc'): ?><?= $svgCheck ?><?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['sort' => 'price_desc', 'tab' => null])) ?>"
                                   class="filter-option-link <?= $sortOrder === 'price_desc' ? 'active' : '' ?>"
                                   style="display:flex; align-items:center; justify-content:space-between; padding:9px 12px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:<?= $sortOrder === 'price_desc' ? '700' : '500' ?>; color:<?= $sortOrder === 'price_desc' ? '#e60023' : '#222' ?>; background:<?= $sortOrder === 'price_desc' ? '#fff0f2' : 'transparent' ?>;">
                                    <span>Giá: Cao đến Thấp</span>
                                    <?php if ($sortOrder === 'price_desc'): ?><?= $svgCheck ?><?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                
                <div class="filter-section" style="padding-bottom:16px; margin-bottom:18px; border-bottom:1px solid #f1f1f1;">
                    <div class="filter-sec-head" onclick="toggleFilterSection(this)" style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; padding:4px 0; margin-bottom:10px;">
                        <span class="filter-sec-title" style="font-size:13.5px; font-weight:800; color:#111; text-transform:uppercase;">DANH MỤC SẢN PHẨM</span>
                        <?= $svgChevron ?>
                    </div>
                    <div class="filter-sec-body">
                        <ul class="filter-options" style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:4px;">
                            <li>
                                <a href="<?= htmlspecialchars(buildFilterUrl(['category' => null])) ?>"
                                   class="filter-option-link <?= $categoryFilter === '' ? 'active' : '' ?>">
                                    <span>Tất cả danh mục</span>
                                    <?php if ($categoryFilter === ''): ?><?= $svgCheck ?><?php endif; ?>
                                </a>
                            </li>

                            
                            <li style="margin-top:10px; padding:6px 6px 2px; border-top:1px dashed #eee;">
                                <a href="<?= htmlspecialchars(buildFilterUrl(['category' => 10])) ?>"
                                   class="filter-option-link <?= ($categoryFilter == '10' || $categoryFilter === 'ao-unisex-kieu') ? 'active' : '' ?>"
                                   style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; padding:6px 8px; color:<?= ($categoryFilter == '10' || $categoryFilter === 'ao-unisex-kieu') ? '#e60023' : '#666' ?>;">
                                    <span>ÁO UNISEX & KIỂU</span>
                                    <?php if ($categoryFilter == '10' || $categoryFilter === 'ao-unisex-kieu'): ?><?= $svgCheck ?><?php endif; ?>
                                </a>
                            </li>
                            <?php
                            $groupUnisex = [
                                ['id' => 1, 'slug' => 'ao-thun-streetwear', 'name' => 'Áo thun Streetwear'],
                                ['id' => 2, 'slug' => 'ao-so-mi-polo',       'name' => 'Áo sơ mi & Polo'],
                                ['id' => 5, 'slug' => 'ao-khoac-hoodie',     'name' => 'Áo khoác & Hoodie'],
                            ];
                            foreach ($groupUnisex as $c):
                                $isCatActive = ($categoryFilter == $c['id'] || $categoryFilter === $c['slug']);
                            ?>
                                <li style="padding-left:8px;">
                                    <a href="<?= htmlspecialchars(buildFilterUrl(['category' => $c['id']])) ?>"
                                       class="filter-option-link <?= $isCatActive ? 'active' : '' ?>">
                                        <span><?= e($c['name']) ?></span>
                                        <?php if ($isCatActive): ?><?= $svgCheck ?><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>

                            
                            <li style="margin-top:10px; padding:6px 6px 2px; border-top:1px dashed #eee;">
                                <a href="<?= htmlspecialchars(buildFilterUrl(['category' => 20])) ?>"
                                   class="filter-option-link <?= ($categoryFilter == '20' || $categoryFilter === 'quan-vay-dam') ? 'active' : '' ?>"
                                   style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; padding:6px 8px; color:<?= ($categoryFilter == '20' || $categoryFilter === 'quan-vay-dam') ? '#e60023' : '#666' ?>;">
                                    <span>QUẦN & VÁY ĐẦM</span>
                                    <?php if ($categoryFilter == '20' || $categoryFilter === 'quan-vay-dam'): ?><?= $svgCheck ?><?php endif; ?>
                                </a>
                            </li>
                            <?php
                            $groupBottom = [
                                ['id' => 3, 'slug' => 'quan-jeans-short', 'name' => 'Quần Jeans & Short'],
                                ['id' => 4, 'slug' => 'vay-dam-chan-vay', 'name' => 'Váy, Đầm & Chân váy'],
                            ];
                            foreach ($groupBottom as $c):
                                $isCatActive = ($categoryFilter == $c['id'] || $categoryFilter === $c['slug']);
                            ?>
                                <li style="padding-left:8px;">
                                    <a href="<?= htmlspecialchars(buildFilterUrl(['category' => $c['id']])) ?>"
                                       class="filter-option-link <?= $isCatActive ? 'active' : '' ?>">
                                        <span><?= e($c['name']) ?></span>
                                        <?php if ($isCatActive): ?><?= $svgCheck ?><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                
                <div class="filter-section" style="padding-bottom:0; margin-bottom:0; border-bottom:none;">
                    <div class="filter-sec-head" onclick="toggleFilterSection(this)" style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; padding:4px 0; margin-bottom:10px;">
                        <span class="filter-sec-title" style="font-size:13.5px; font-weight:800; color:#111; text-transform:uppercase;">KHOẢNG GIÁ</span>
                        <?= $svgChevron ?>
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
                                       class="filter-option-link <?= $priceRange === $pKey ? 'active' : '' ?>">
                                        <span><?= htmlspecialchars($pLabel) ?></span>
                                        <?php if ($priceRange === $pKey): ?><?= $svgCheck ?><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div> 
        </aside>
            <button type="button" id="filterCollapseBtn" class="filter-collapse-handle" onclick="toggleSidebarCollapse()" title="Thu gọn / Mở bộ lọc">
                <?= $svgLeft ?>
            </button>
        </div>

        
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
                                    <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $pid ?>" class="prod-title" style="font-size:14px; color:#111; text-decoration:none; margin-bottom:8px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:40px;">
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
    function toggleMobileFilter() {
        var wrap = document.getElementById('filterSidebarWrap');
        if (!wrap) return;
        wrap.classList.toggle('mobile-collapsed');
        var isCol = wrap.classList.contains('mobile-collapsed');
        var txt = document.getElementById('filterMobileToggleText');
        var icn = document.getElementById('filterMobileToggleIcon');
        if (txt) {
            txt.textContent = isCol ? 'Xem bộ lọc' : 'Thu gọn';
        }
        if (icn) {
            icn.innerHTML = isCol
                ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>'
                : '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>';
        }
    }

    
    (function initFilterResponsive() {
        var wrap = document.getElementById('filterSidebarWrap');
        if (!wrap) return;
        if (window.innerWidth <= 992) {
            wrap.classList.add('mobile-collapsed');
        } else {
            wrap.classList.remove('mobile-collapsed');
        }
    })();

    window.addEventListener('resize', function() {
        var wrap = document.getElementById('filterSidebarWrap');
        if (!wrap) return;
        if (window.innerWidth > 992) {
            wrap.classList.remove('mobile-collapsed');
        }
    });

    function toggleSidebarCollapse() {
        var wrap = document.getElementById('filterSidebarWrap');
        var btn  = document.getElementById('filterCollapseBtn');
        if (!wrap) return;
        wrap.classList.toggle('collapsed');
        if (wrap.classList.contains('collapsed')) {
            if (btn) btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>';
        } else {
            if (btn) btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>';
        }
    }

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
