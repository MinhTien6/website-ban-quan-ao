<?php

$pageTitle = 'Trang chủ | VIBEZ.CLUB Streetwear GenZ';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Category.php';

$productModel = new Product($pdo);
$categoryModel = new Category($pdo);

$categories = $categoryModel->getAllActive();

$latestProducts = $productModel->getLatest(4);

$saleStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, pi.image_path AS main_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
    WHERE p.status = 1 AND p.sale_price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price
    ORDER BY (p.price - p.sale_price) DESC, p.id DESC
    LIMIT 4
");
$saleStmt->execute();
$saleProducts = $saleStmt->fetchAll();

$allDisplayedIds = array_unique(array_filter(array_merge(
    array_column($latestProducts, 'id'),
    array_column($saleProducts, 'id')
)));

$variantsByProduct = [];
if (!empty($allDisplayedIds)) {
    $inClause = implode(',', array_map('intval', $allDisplayedIds));
    $vStmt = $pdo->query("
        SELECT id, product_id, size, color, stock 
        FROM product_variants 
        WHERE product_id IN ($inClause) AND stock > 0 
        ORDER BY id ASC
    ");
    while ($row = $vStmt->fetch()) {
        $pId = (int)$row['product_id'];
        if (!isset($variantsByProduct[$pId])) {
            $variantsByProduct[$pId] = $row;
        }
    }
}

$bannerPath = 'public/images/banner.jpg';
if (!file_exists(ROOT_PATH . '/' . $bannerPath)) {
    $bannerUrl = 'https://placehold.co/1200x480/111111/ffffff?text=VIBEZ.CLUB+STREETWEAR';
} else {
    $bannerUrl = BASE_URL . '/' . $bannerPath;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="home-main-wrap">
    
    <section class="hero-banner-img-wrap" style="position:relative; max-width:1200px; margin:0 auto 40px; padding:0 15px; border-radius:12px; overflow:hidden;">
        <div class="hero-banner-box" style="position:relative; width:100%; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">
            <img src="<?= e($bannerUrl) ?>" alt="VIBEZ.CLUB - Định hình chất riêng Gen Z" class="hero-banner-img" style="width:100%; height:auto; display:block; object-fit:cover; max-height:460px;">
            <a href="<?= BASE_URL ?>/products.php" class="hero-cta-btn" style="position:absolute; bottom:12%; left:5%; background:#e60023; color:#ffffff; font-weight:800; font-size:14px; text-decoration:none; padding:12px 24px; border-radius:6px; letter-spacing:0.5px; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 15px rgba(230,0,35,0.4); transition:all 0.2s ease;">
                KHÁM PHÁ NGAY &rarr;
            </a>
        </div>
    </section>

    
    <section class="container" style="max-width:1200px; margin:0 auto 45px; padding:0 15px;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:20px; background:#fafafa; border:1px solid #eeeeee; border-radius:10px; padding:22px 20px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:44px; height:44px; border-radius:50%; background:#111; color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <div>
                    <h5 style="margin:0 0 3px 0; font-size:14px; font-weight:700;">Freeship từ 399k</h5>
                    <p style="margin:0; font-size:12.5px; color:#666;">Giao nhanh toàn quốc</p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:44px; height:44px; border-radius:50%; background:#111; color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <div>
                    <h5 style="margin:0 0 3px 0; font-size:14px; font-weight:700;">Đổi trả 7 ngày</h5>
                    <p style="margin:0; font-size:12.5px; color:#666;">Thủ tục cực kỳ dễ dàng</p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:44px; height:44px; border-radius:50%; background:#111; color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h5 style="margin:0 0 3px 0; font-size:14px; font-weight:700;">Đồng kiểm tận tay</h5>
                    <p style="margin:0; font-size:12.5px; color:#666;">Xem hàng trước trả tiền</p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:44px; height:44px; border-radius:50%; background:#111; color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                    <i class="fa-solid fa-gem"></i>
                </div>
                <div>
                    <h5 style="margin:0 0 3px 0; font-size:14px; font-weight:700;">100% Chất Cotton</h5>
                    <p style="margin:0; font-size:12.5px; color:#666;">Định lượng 250gsm cao cấp</p>
                </div>
            </div>
        </div>
    </section>

    
    <section class="container" style="max-width:1200px; margin:0 auto 55px; padding:0 15px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
            <div>
                <h2 style="font-size:24px; font-weight:800; text-transform:uppercase; margin:0; letter-spacing:-0.5px;">
                    Danh mục nổi bật
                </h2>
                <p style="margin:5px 0 0; font-size:14px; color:#666;">Khám phá các phong cách thời trang dẫn đầu xu hướng</p>
            </div>
            <a href="<?= BASE_URL ?>/products.php" style="color:#e60023; font-weight:700; font-size:13.5px; text-decoration:none;">
                Xem tất cả &rarr;
            </a>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:18px;">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL ?>/products.php?category=<?= (int)$cat['id'] ?>" style="display:block; text-decoration:none; background:#ffffff; border:1.5px solid #ececec; border-radius:10px; padding:22px 18px; text-align:center; transition:all 0.2s ease; box-shadow:0 3px 10px rgba(0,0,0,0.03);" onmouseover="this.style.borderColor='#111'; this.style.transform='translateY(-3px)';" onmouseout="this.style.borderColor='#ececec'; this.style.transform='none';">
                    <div style="width:52px; height:52px; margin:0 auto 12px; background:#f4f4f4; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:22px; color:#111;">
                        <?php
                        $cSlug = $cat['slug'] ?? '';
                        if (str_contains($cSlug, 'ao-thun')) echo '<i class="fa-solid fa-shirt"></i>';
                        elseif (str_contains($cSlug, 'so-mi') || str_contains($cSlug, 'polo')) echo '<i class="fa-solid fa-vest"></i>';
                        elseif (str_contains($cSlug, 'khoac') || str_contains($cSlug, 'hoodie')) echo '<i class="fa-solid fa-mitten"></i>';
                        elseif (str_contains($cSlug, 'quan')) echo '<i class="fa-solid fa-socks"></i>';
                        elseif (str_contains($cSlug, 'vay') || str_contains($cSlug, 'dam')) echo '<i class="fa-solid fa-person-dress"></i>';
                        else echo '<i class="fa-solid fa-tag"></i>';
                        ?>
                    </div>
                    <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700; color:#111;">
                        <?= e($cat['name']) ?>
                    </h4>
                    <span style="font-size:12.5px; color:#888;">
                        <?= (int)($cat['total_products'] ?? 0) ?> sản phẩm
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    
    <section class="container" style="max-width:1200px; margin:0 auto 60px; padding:0 15px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
            <div>
                <h2 style="font-size:24px; font-weight:800; text-transform:uppercase; margin:0; letter-spacing:-0.5px;">
                    Sản phẩm mới về
                </h2>
                <p style="margin:5px 0 0; font-size:14px; color:#666;">Các thiết kế streetwear mới nhất vừa cập bến kho</p>
            </div>
            <a href="<?= BASE_URL ?>/products.php?sort=newest" style="color:#e60023; font-weight:700; font-size:13.5px; text-decoration:none;">
                Xem thêm mới về &rarr;
            </a>
        </div>

        <?php if (!empty($latestProducts)): ?>
            <div class="product-grid" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:20px;">
                <?php foreach ($latestProducts as $p): ?>
                    <?php
                    $pid = (int)$p['id'];
                    $currPrice = (!empty($p['sale_price']) && $p['sale_price'] > 0) ? (float)$p['sale_price'] : (float)$p['price'];
                    $oldPrice = (!empty($p['sale_price']) && $p['sale_price'] > 0) ? (float)$p['price'] : null;
                    $imgUrl = !empty($p['main_image']) ? BASE_URL . '/public/' . ltrim($p['main_image'], '/') : 'https://placehold.co/400x400/eef0f5/687082?text=VibezClub';
                    $variant = $variantsByProduct[$pid] ?? null;
                    $defVariantId = $variant ? (int)$variant['id'] : 0;
                    $defSize = $variant['size'] ?? 'M';
                    $defColor = $variant['color'] ?? 'Đen';
                    ?>
                    <div class="prod-card" style="background:#fff; border:1px solid #ebebeb; border-radius:10px; overflow:hidden; display:flex; flex-direction:column; transition:box-shadow 0.2s ease;">
                        <div class="prod-img-box" style="position:relative; width:100%; padding-top:100%; background:#f7f7f7; overflow:hidden;">
                            <span class="badge-new-red" style="position:absolute; top:10px; right:10px; background:#e60023; color:#fff; font-size:11px; font-weight:800; padding:4px 9px; border-radius:4px; text-transform:uppercase; z-index:3;">
                                NEW
                            </span>
                            <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $pid ?>" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center;">
                                <img src="<?= e($imgUrl) ?>" alt="<?= e($p['name']) ?>" style="width:100%; height:100%; object-fit:contain; transition:transform 0.3s ease;" onerror="this.onerror=null; this.src='https://placehold.co/400x400/eef0f5/687082?text=VibezClub';">
                            </a>
                        </div>
                        <div class="prod-body" style="padding:14px; display:flex; flex-direction:column; flex:1; justify-content:space-between;">
                            <div>
                                <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $pid ?>" class="prod-title" style="font-size:15px; font-weight:700; color:#111; text-decoration:none; margin-bottom:8px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:42px;">
                                    <?= e($p['name']) ?>
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
                                <input type="hidden" name="variant_id" value="<?= $defVariantId ?>">
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
            <div class="empty-state" style="text-align:center; padding:40px; background:#f9f9f9; border-radius:8px;">
                <p style="margin:0; color:#666;">Hiện chưa có sản phẩm mới nào được cập nhật.</p>
            </div>
        <?php endif; ?>
    </section>

    
    <?php if (!empty($saleProducts)): ?>
        <section class="container" style="max-width:1200px; margin:0 auto 60px; padding:0 15px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
                <div>
                    <h2 style="font-size:24px; font-weight:800; text-transform:uppercase; margin:0; letter-spacing:-0.5px; color:#e60023;">
                        🔥 Sản phẩm giảm giá hot
                    </h2>
                    <p style="margin:5px 0 0; font-size:14px; color:#666;">Ưu đãi đặc biệt có hạn - Săn ngay kẻo lỡ</p>
                </div>
                <a href="<?= BASE_URL ?>/products.php?sort=bestseller" style="color:#e60023; font-weight:700; font-size:13.5px; text-decoration:none;">
                    Xem tất cả ưu đãi &rarr;
                </a>
            </div>

            <div class="product-grid" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:20px;">
                <?php foreach ($saleProducts as $p): ?>
                    <?php
                    $pid = (int)$p['id'];
                    $currPrice = (float)$p['sale_price'];
                    $oldPrice = (float)$p['price'];
                    $percentOff = round((($oldPrice - $currPrice) / $oldPrice) * 100);
                    $imgUrl = !empty($p['main_image']) ? BASE_URL . '/public/' . ltrim($p['main_image'], '/') : 'https://placehold.co/400x400/eef0f5/687082?text=Sale';
                    $variant = $variantsByProduct[$pid] ?? null;
                    $defVariantId = $variant ? (int)$variant['id'] : 0;
                    $defSize = $variant['size'] ?? 'M';
                    $defColor = $variant['color'] ?? 'Đen';
                    ?>
                    <div class="prod-card" style="background:#fff; border:1px solid #ebebeb; border-radius:10px; overflow:hidden; display:flex; flex-direction:column; transition:box-shadow 0.2s ease;">
                        <div class="prod-img-box" style="position:relative; width:100%; padding-top:100%; background:#f7f7f7; overflow:hidden;">
                            <span class="badge-new-red" style="position:absolute; top:10px; right:10px; background:#111111; color:#fff; font-size:11px; font-weight:800; padding:4px 9px; border-radius:4px; text-transform:uppercase; z-index:3;">
                                -<?= $percentOff ?>%
                            </span>
                            <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $pid ?>" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center;">
                                <img src="<?= e($imgUrl) ?>" alt="<?= e($p['name']) ?>" style="width:100%; height:100%; object-fit:contain; transition:transform 0.3s ease;" onerror="this.onerror=null; this.src='https://placehold.co/400x400/eef0f5/687082?text=Sale';">
                            </a>
                        </div>
                        <div class="prod-body" style="padding:14px; display:flex; flex-direction:column; flex:1; justify-content:space-between;">
                            <div>
                                <a href="<?= BASE_URL ?>/product_detail.php?id=<?= $pid ?>" class="prod-title" style="font-size:15px; font-weight:700; color:#111; text-decoration:none; margin-bottom:8px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:42px;">
                                    <?= e($p['name']) ?>
                                </a>
                                <div class="prod-price-row" style="display:flex; align-items:baseline; gap:8px; margin-bottom:12px;">
                                    <span class="prod-price-current" style="font-size:17px; font-weight:900; color:#e60023;">
                                        <?= number_format($currPrice, 0, ',', '.') ?>đ
                                    </span>
                                    <span class="prod-price-old" style="font-size:13px; color:#888; text-decoration:line-through;">
                                        <?= number_format($oldPrice, 0, ',', '.') ?>đ
                                    </span>
                                </div>
                            </div>

                            <form action="<?= BASE_URL ?>/cart.php" method="POST" class="card-cart-form" style="margin-top:auto; padding-top:10px; border-top:1px solid #f1f1f1; display:flex; flex-direction:column; gap:8px;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= $pid ?>">
                                <input type="hidden" name="variant_id" value="<?= $defVariantId ?>">
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
        </section>
    <?php endif; ?>

    <div class="view-all-wrap text-center" style="text-align:center; margin:30px 0 50px;">
        <a href="<?= BASE_URL ?>/products.php" class="btn btn-view-all" style="padding:14px 36px; background:#111; color:#fff; font-size:14px; font-weight:800; text-transform:uppercase; text-decoration:none; border-radius:6px; display:inline-block; transition:background 0.2s ease;">
            Xem tất cả sản phẩm &rarr;
        </a>
    </div>
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
