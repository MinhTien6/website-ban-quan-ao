<?php
/**
 * Trang Chi tiết Sản phẩm & Đánh giá (product_detail.php)
 * Phụ trách: TV4 (Thư) + Tích hợp Giỏ hàng TV5 (Đức)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Category.php';

$productModel = new Product($pdo);

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;
if ($productId <= 0) {
    $productId = 1;
}

// Lấy thông tin sản phẩm từ DB
$product = $productModel->getById($productId);
if (!$product) {
    $firstStmt = $pdo->query("SELECT id FROM products WHERE status = 1 ORDER BY id ASC LIMIT 1");
    $firstId = (int)$firstStmt->fetchColumn();
    if ($firstId > 0) {
        redirect('product_detail.php?id=' . $firstId);
    }

    $pageTitle = 'Không tìm thấy sản phẩm';
    require_once __DIR__ . '/includes/header.php';
    echo "<div style='max-width:800px; margin:80px auto; text-align:center; padding:40px; border:1px solid #eee; border-radius:16px;'>
            <h2>Không tìm thấy sản phẩm yêu cầu!</h2>
            <p>Sản phẩm này không tồn tại hoặc đã ngừng kinh doanh.</p>
            <a href='products.php' style='display:inline-block; margin-top:12px; padding:10px 24px; background:#e60023; color:#fff; text-decoration:none; border-radius:999px; font-weight:700;'>Quay lại cửa hàng</a>
          </div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Xử lý gửi đánh giá mới (Review submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        setFlash('error', 'Vui lòng đăng nhập để gửi đánh giá sản phẩm.');
        redirect("product_detail.php?id={$productId}");
    }

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Phiên làm việc hết hạn hoặc yêu cầu không hợp lệ.');
        redirect("product_detail.php?id={$productId}");
    }

    $userId = (int)($_SESSION['user']['id'] ?? 0);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT) ?: 5;
    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;

    $comment = trim($_POST['comment'] ?? '');

    if ($comment === '') {
        setFlash('error', 'Vui lòng nhập nội dung nhận xét của bạn.');
        redirect("product_detail.php?id={$productId}");
    }

    try {
        $insReview = $pdo->prepare("
            INSERT INTO reviews (product_id, user_id, rating, comment, status, created_at)
            VALUES (:pid, :uid, :rating, :comment, 1, NOW())
        ");
        $insReview->execute([
            'pid'     => $productId,
            'uid'     => $userId,
            'rating'  => $rating,
            'comment' => $comment,
        ]);
        setFlash('success', 'Cảm ơn bạn đã gửi đánh giá! Đánh giá đã được ghi nhận.');
    } catch (Throwable $e) {
        setFlash('error', 'Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại sau.');
    }

    redirect("product_detail.php?id={$productId}");
}

// Lấy danh sách ảnh gallery
$rawImages = $productModel->getImages($productId);
$galleryImgs = [];
foreach ($rawImages as $img) {
    if (!empty($img['image_path'])) {
        $cleanPath = ltrim($img['image_path'], '/');
        $fullPath = ROOT_PATH . '/public/' . $cleanPath;
        if (file_exists($fullPath)) {
            $galleryImgs[] = BASE_URL . '/public/' . $cleanPath;
        } elseif (file_exists(ROOT_PATH . '/' . $cleanPath)) {
            $galleryImgs[] = BASE_URL . '/' . $cleanPath;
        } else {
            $galleryImgs[] = BASE_URL . '/public/' . $cleanPath;
        }
    }
}

if (empty($galleryImgs)) {
    $galleryImgs[] = !empty($product['main_image'])
        ? BASE_URL . '/public/' . ltrim($product['main_image'], '/')
        : 'https://placehold.co/600x600/ebeef5/646c79?text=VibezClub';
}

$displayMainImage = $galleryImgs[0];

// Lấy danh sách biến thể (size, color, stock)
$variants = $productModel->getVariants($productId);

$availableColors = [];
$availableSizes = [];
foreach ($variants as $v) {
    if (!empty($v['color']) && !in_array($v['color'], $availableColors, true)) {
        $availableColors[] = $v['color'];
    }
    if (!empty($v['size']) && !in_array($v['size'], $availableSizes, true)) {
        $availableSizes[] = $v['size'];
    }
}

if (empty($availableColors)) {
    $availableColors = ['Đen'];
}
if (empty($availableSizes)) {
    $availableSizes = ['S', 'M', 'L', 'XL'];
}

$defaultColor = $availableColors[0];
$defaultSize = $availableSizes[0];
$defaultVariant = !empty($variants[0]['id']) ? (int)$variants[0]['id'] : 0;

$colorHexMap = [
    'Đen'       => '#111111',
    'Trắng'     => '#ffffff',
    'Hồng'      => '#f4b6c2',
    'Kem'       => '#e6d5b8',
    'Be'        => '#e6dcc8',
    'Xám'       => '#808080',
    'Xanh'      => '#3b6998',
    'Xanh đen'  => '#1b263b',
    'Xanh navy' => '#1e3a8a',
    'Xanh đậm'  => '#1d4ed8',
    'Xanh nhạt' => '#bfdbfe',
    'Xanh rêu'  => '#4d7c0f',
    'Đỏ'        => '#e60023',
    'Vàng'      => '#facc15',
    'Nâu'       => '#78350f',
];

// Lấy danh sách đánh giá từ database
$rStmt = $pdo->prepare("
    SELECT r.*, u.full_name 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = :pid AND r.status = 1 
    ORDER BY r.created_at DESC
");
$rStmt->execute(['pid' => $productId]);
$dbReviews = $rStmt->fetchAll();

$reviews = $dbReviews;
$totalReviews = count($reviews);
$avgRating = 0;
if ($totalReviews > 0) {
    $sumRating = 0;
    foreach ($reviews as $rvItem) {
        $sumRating += (int)($rvItem['rating'] ?? 5);
    }
    $avgRating = round($sumRating / $totalReviews, 1);
}

// Làm sạch mô tả chống Stored XSS
$rawDesc = (string)($product['description'] ?? '');
$cleanDesc = strip_tags($rawDesc, '<p><br><ul><ol><li><strong><b><em><i><h3><h4>');
if (trim($cleanDesc) === '') {
    $cleanDesc = '<p>Chất liệu cotton cao cấp 250gsm thoáng mát, đường may chuẩn xác theo phong cách Streetwear Unisex hiện đại.</p>';
}

$currPrice = (!empty($product['sale_price']) && $product['sale_price'] > 0) ? (float)$product['sale_price'] : (float)$product['price'];
$oldPrice  = (!empty($product['sale_price']) && $product['sale_price'] > 0) ? (float)$product['price'] : null;

// Lấy 4 sản phẩm liên quan cùng danh mục
$relatedProducts = $productModel->getRelated($productId, (int)$product['category_id'], 4);

$pageTitle = $product['name'] . ' | VIBEZ.CLUB';
require_once __DIR__ . '/includes/header.php';
?>

<div class="wf-detail-wrap" style="max-width:1180px; margin:36px auto 70px; padding:0 20px; font-family:'Inter', sans-serif;">
    <div class="wf-top-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:44px; background:#ffffff; border:1px solid #eeeeee; border-radius:20px; padding:32px; box-shadow:0 8px 30px rgba(0, 0, 0, 0.04);">
        <!-- CỘT ẢNH SẢN PHẨM -->
        <div>
            <div class="wf-main-img-box" style="width:100%; aspect-ratio:1/1; background:#ffffff; border:1px solid #ececec; border-radius:14px; overflow:hidden; margin-bottom:14px; display:flex; align-items:center; justify-content:center; padding:14px; box-sizing:border-box;">
                <img id="mainProductImg" src="<?= htmlspecialchars($displayMainImage) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width:100%; height:100%; object-fit:contain; display:block;" onerror="this.onerror=null; this.src='https://placehold.co/600x600/ebeef5/646c79?text=VibezClub';">
            </div>
            <div class="wf-thumb-list" style="display:flex; gap:12px; flex-wrap:wrap;">
                <?php foreach ($galleryImgs as $idx => $gUrl): ?>
                    <div class="wf-thumb-item <?= $idx === 0 ? 'active' : '' ?>" onclick="switchMainImg(this, '<?= htmlspecialchars($gUrl) ?>')" style="width:84px; height:84px; border-radius:10px; border:2px solid <?= $idx === 0 ? '#e60023' : '#e5e7eb' ?>; overflow:hidden; cursor:pointer; background:#ffffff; padding:6px; box-sizing:border-box; display:flex; align-items:center; justify-content:center;">
                        <img src="<?= htmlspecialchars($gUrl) ?>" alt="Thumb <?= $idx + 1 ?>" style="width:100%; height:100%; object-fit:contain; display:block;">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CỘT THÔNG TIN VÀ FORM MUA HÀNG -->
        <div>
            <div style="font-size:13px; font-weight:700; color:#888; text-transform:uppercase; margin-bottom:6px;">
                <?= e($product['category_name'] ?? 'Thời trang Unisex') ?>
            </div>
            <h1 class="wf-product-title" style="font-size:26px; font-weight:800; color:#111111; margin:0 0 14px 0; line-height:1.35;">
                <?= htmlspecialchars($product['name']) ?>
            </h1>

            <div class="wf-price-box" style="display:flex; align-items:baseline; gap:12px; padding-bottom:18px; margin-bottom:20px; border-bottom:1px solid #f1f1f1;">
                <span class="wf-product-price" style="font-size:28px; font-weight:900; color:#e60023;">
                    <?= number_format($currPrice, 0, ',', '.') ?>đ
                </span>
                <?php if ($oldPrice !== null): ?>
                    <span class="wf-product-old-price" style="font-size:16px; color:#9ca3af; text-decoration:line-through;">
                        <?= number_format($oldPrice, 0, ',', '.') ?>đ
                    </span>
                    <span style="font-size:13px; font-weight:800; color:#e60023; background:#fff0f2; padding:3px 8px; border-radius:4px;">
                        Tiết kiệm <?= number_format($oldPrice - $currPrice, 0, ',', '.') ?>đ
                    </span>
                <?php endif; ?>
            </div>

            <form action="<?= BASE_URL ?>/cart.php" method="POST" id="detailCartForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <input type="hidden" name="variant_id" id="selectedVariantId" value="<?= (int)$defaultVariant ?>">
                <input type="hidden" name="color" id="selectedColorInput" value="<?= htmlspecialchars($defaultColor) ?>">
                <input type="hidden" name="size" id="selectedSizeInput" value="<?= htmlspecialchars($defaultSize) ?>">

                <!-- CHỌN MÀU SẮC -->
                <div class="wf-option-block" style="margin-bottom:22px;">
                    <div class="wf-option-label" style="font-size:13px; font-weight:800; text-transform:uppercase; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                        MÀU SẮC: <span id="colorNameLabel" style="color:#e60023;"><?= htmlspecialchars(mb_strtoupper($defaultColor, 'UTF-8')) ?></span>
                    </div>
                    <div class="wf-color-list" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <?php foreach ($availableColors as $cIndex => $colorName): ?>
                            <?php $hexCode = $colorHexMap[$colorName] ?? '#111111'; ?>
                            <button type="button"
                                    class="wf-color-swatch <?= $cIndex === 0 ? 'active' : '' ?>"
                                    style="width:36px; height:36px; border-radius:50%; cursor:pointer; border:2.5px solid <?= $cIndex === 0 ? '#e60023' : '#d1d5db' ?>; padding:3px; background-clip:content-box; background-color:<?= htmlspecialchars($hexCode) ?>;"
                                    title="<?= htmlspecialchars($colorName) ?>"
                                    onclick="selectColorOption(this, '<?= htmlspecialchars($colorName, ENT_QUOTES) ?>')">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- CHỌN KÍCH CỠ -->
                <div class="wf-option-block" style="margin-bottom:22px;">
                    <div class="wf-size-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; gap:12px; flex-wrap:wrap;">
                        <div class="wf-option-label" style="margin-bottom:0; font-size:13px; font-weight:800; text-transform:uppercase;">
                            KÍCH CỠ: <span id="sizeNameLabel" style="color:#e60023;"><?= htmlspecialchars($defaultSize) ?></span>
                        </div>
                        <button type="button" class="wf-size-guide-btn" onclick="openSizeModal()" style="background:#fff0f2; color:#e60023; border:none; padding:6px 12px; border-radius:999px; font-size:12px; font-weight:700; cursor:pointer;">
                            📏 Hướng dẫn chọn size
                        </button>
                    </div>
                    <div class="wf-size-list" style="display:flex; gap:10px; flex-wrap:wrap;">
                        <?php foreach ($availableSizes as $sIdx => $sz): ?>
                            <button type="button"
                                    class="wf-size-btn <?= $sIdx === 0 ? 'active' : '' ?>"
                                    style="min-width:48px; height:42px; padding:0 14px; border-radius:8px; border:1.5px solid <?= $sIdx === 0 ? '#e60023' : '#e5e7eb' ?>; background:<?= $sIdx === 0 ? '#fff0f2' : '#ffffff' ?>; color:<?= $sIdx === 0 ? '#e60023' : '#111111' ?>; font-size:14px; font-weight:700; cursor:pointer;"
                                    onclick="selectSizeOption(this, '<?= htmlspecialchars($sz, ENT_QUOTES) ?>')">
                                <?= htmlspecialchars($sz) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SỐ LƯỢNG VÀ NÚT BẤM -->
                <div class="wf-cart-row" style="display:flex; align-items:center; gap:14px; margin-top:26px; margin-bottom:14px;">
                    <div class="wf-qty-pill" style="display:inline-flex; align-items:center; justify-content:space-between; width:125px; height:48px; border:1.5px solid #111111; border-radius:999px; padding:0 8px; background:#ffffff; flex-shrink:0;">
                        <button type="button" onclick="changeDetailQty(-1)" style="width:32px; height:32px; border-radius:50%; border:none; background:#f3f4f6; color:#111; font-size:16px; font-weight:800; cursor:pointer;">-</button>
                        <input type="number" name="quantity" id="detailQtyInput" value="1" min="1" max="100" oninput="validateQtyInput(this)" onblur="fixEmptyQty(this)" style="width:42px; border:none; text-align:center; font-size:15px; font-weight:800; outline:none; background:transparent;">
                        <button type="button" onclick="changeDetailQty(1)" style="width:32px; height:32px; border-radius:50%; border:none; background:#f3f4f6; color:#111; font-size:16px; font-weight:800; cursor:pointer;">+</button>
                    </div>
                    <button type="submit" class="wf-btn-addcart" style="flex:1; max-width:260px; height:48px; border-radius:999px; border:1.5px solid #111111; background:#ffffff; color:#111111; font-size:14px; font-weight:800; text-transform:uppercase; cursor:pointer; transition:all 0.2s ease;">
                        THÊM VÀO GIỎ HÀNG
                    </button>
                </div>

                <button type="submit" name="buy_now" value="1" class="wf-btn-buynow" style="width:100%; max-width:399px; height:50px; border-radius:999px; border:none; background:#e60023; color:#ffffff; font-size:15px; font-weight:800; text-transform:uppercase; cursor:pointer; transition:background 0.2s ease;">
                    MUA NGAY
                </button>
            </form>
        </div>
    </div>

    <!-- KHỐI MÔ TẢ VÀ ĐÁNH GIÁ -->
    <div class="wf-bottom-blocks" style="margin-top:36px; display:flex; flex-direction:column; gap:28px;">
        <!-- MÔ TẢ -->
        <div class="wf-full-box" style="background:#ffffff; border:1px solid #eeeeee; border-radius:16px; padding:26px 30px; box-shadow:0 4px 20px rgba(0, 0, 0, 0.04);">
            <h3 class="wf-full-box-title" style="font-size:16px; font-weight:800; text-transform:uppercase; margin:0 0 16px 0; padding-bottom:12px; border-bottom:2px solid #111111; display:flex; align-items:center; gap:8px;">
                <span style="color:#e60023;">|</span> Mô tả sản phẩm
            </h3>
            <div class="wf-desc-content" style="font-size:15px; line-height:1.75; color:#333333;">
                <?= $cleanDesc ?>
            </div>
        </div>

        <!-- ĐÁNH GIÁ KHÁCH HÀNG -->
        <div class="wf-full-box" style="background:#ffffff; border:1px solid #eeeeee; border-radius:16px; padding:26px 30px; box-shadow:0 4px 20px rgba(0, 0, 0, 0.04);">
            <h3 class="wf-full-box-title" style="font-size:16px; font-weight:800; text-transform:uppercase; margin:0 0 18px 0; padding-bottom:12px; border-bottom:2px solid #111111; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                <div>
                    <span style="color:#e60023;">|</span> Đánh giá khách hàng (<?= $totalReviews ?>)
                </div>
                <?php if ($totalReviews > 0): ?>
                    <span style="display:inline-flex; align-items:center; gap:8px; font-size:14px; font-weight:700; text-transform:none; background:#fff8eb; border:1px solid #fde68a; color:#111111; padding:5px 14px; border-radius:999px;">
                        <span style="color:#f59e0b;">★</span>
                        <strong><?= number_format($avgRating, 1) ?> / 5</strong>
                        <span style="color:#6b7280; font-weight:500;">(<?= $totalReviews ?> đánh giá)</span>
                    </span>
                <?php endif; ?>
            </h3>

            <!-- FORM GỬI ĐÁNH GIÁ CHO KHÁCH ĐÃ ĐĂNG NHẬP -->
            <?php if (isLoggedIn()): ?>
                <div style="background:#fafafa; border:1px solid #eee; border-radius:12px; padding:20px; margin-bottom:24px;">
                    <h4 style="margin:0 0 12px 0; font-size:15px; font-weight:700;">Viết đánh giá của bạn</h4>
                    <form action="product_detail.php?id=<?= $productId ?>" method="POST">
                        <?= csrfField() ?>
                        <div style="margin-bottom:14px;">
                            <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Xếp hạng chất lượng:</label>
                            <select name="rating" style="padding:8px 12px; border:1px solid #ccc; border-radius:6px; font-size:14px;">
                                <option value="5">⭐⭐⭐⭐⭐ Tuyệt vời (5 sao)</option>
                                <option value="4">⭐⭐⭐⭐ Rất tốt (4 sao)</option>
                                <option value="3">⭐⭐⭐ Bình thường (3 sao)</option>
                                <option value="2">⭐⭐ Tạm được (2 sao)</option>
                                <option value="1">⭐ Kém (1 sao)</option>
                            </select>
                        </div>
                        <div style="margin-bottom:14px;">
                            <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Nhận xét chi tiết:</label>
                            <textarea name="comment" rows="3" placeholder="Chia sẻ cảm nhận của bạn về chất vải, form dáng, độ bền..." style="width:100%; padding:10px 14px; border:1px solid #ccc; border-radius:6px; font-size:14px; box-sizing:border-box; font-family:'Inter', sans-serif;" required></textarea>
                        </div>
                        <button type="submit" name="submit_review" value="1" style="padding:10px 24px; background:#111; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13.5px; cursor:pointer;">
                            Gửi đánh giá
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div style="background:#f9f9f9; border:1px dashed #ddd; border-radius:8px; padding:16px 20px; margin-bottom:24px; text-align:center;">
                    <p style="margin:0; font-size:14px; color:#666;">
                        Bạn cần <a href="<?= BASE_URL ?>/login.php" style="color:#e60023; font-weight:700; text-decoration:none;">Đăng nhập</a> để gửi nhận xét và đánh giá sản phẩm này.
                    </p>
                </div>
            <?php endif; ?>

            <!-- DANH SÁCH ĐÁNH GIÁ -->
            <div class="wf-desc-content">
                <?php if (!empty($reviews)): ?>
                    <div class="wf-review-list" style="display:flex; flex-direction:column; gap:14px;">
                        <?php foreach ($reviews as $rv): ?>
                            <?php
                            $rName   = trim($rv['full_name'] ?? 'Khách hàng');
                            $rRating = max(1, min(5, (int)($rv['rating'] ?? 5)));
                            $rDate   = $rv['created_at'] ?? 'Hôm nay';
                            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $rDate)) {
                                $rDate = date('d/m/Y', strtotime($rDate));
                            }
                            $initial = mb_strtoupper(mb_substr($rName, 0, 1, 'UTF-8'), 'UTF-8');
                            ?>
                            <div class="wf-review-card" style="display:flex; gap:14px; padding:16px 18px; background:#fafafa; border:1px solid #f0f0f0; border-radius:12px;">
                                <div class="wf-review-avatar" style="width:42px; height:42px; border-radius:50%; background:#111111; color:#ffffff; font-size:15px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <?= htmlspecialchars($initial) ?>
                                </div>
                                <div class="wf-review-body" style="flex:1; min-width:0;">
                                    <div class="wf-review-top" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:4px;">
                                        <div class="wf-review-author" style="display:flex; align-items:center; gap:8px; font-size:14.5px; font-weight:700; color:#111111;">
                                            <span><?= htmlspecialchars($rName) ?></span>
                                            <span style="font-size:11.5px; font-weight:600; color:#16a34a; background:#dcfce7; padding:2px 8px; border-radius:999px;">✓ Đã mua hàng</span>
                                        </div>
                                        <span style="font-size:12.5px; color:#9ca3af;"><?= htmlspecialchars($rDate) ?></span>
                                    </div>
                                    <div class="wf-review-meta" style="display:flex; align-items:center; gap:10px; margin-bottom:6px; font-size:13px;">
                                        <span style="color:#f59e0b; letter-spacing:1px; font-size:14px;">
                                            <?= str_repeat('★', $rRating) . str_repeat('☆', 5 - $rRating) ?>
                                        </span>
                                    </div>
                                    <p style="margin:0; font-size:14px; color:#374151; line-height:1.6;">
                                        <?= htmlspecialchars($rv['comment'] ?? '') ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:18px 0;">
                        <p style="color:#6b7280; margin:0;">Sản phẩm chưa có đánh giá nào. Hãy là người đầu tiên chia sẻ cảm nhận!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SẢN PHẨM LIÊN QUAN (4 SẢN PHẨM CÙNG DANH MỤC) -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="wf-full-box" style="background:#ffffff; border:1px solid #eeeeee; border-radius:16px; padding:26px 30px; box-shadow:0 4px 20px rgba(0, 0, 0, 0.04);">
                <h3 class="wf-full-box-title" style="font-size:16px; font-weight:800; text-transform:uppercase; margin:0 0 20px 0; padding-bottom:12px; border-bottom:2px solid #111111;">
                    <span style="color:#e60023;">|</span> Sản phẩm liên quan
                </h3>
                <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:20px;">
                    <?php foreach ($relatedProducts as $relP): ?>
                        <?php
                        $relPrice = (!empty($relP['sale_price']) && $relP['sale_price'] > 0) ? (float)$relP['sale_price'] : (float)$relP['price'];
                        $relOldPrice = (!empty($relP['sale_price']) && $relP['sale_price'] > 0) ? (float)$relP['price'] : null;
                        $relImg = !empty($relP['main_image']) ? BASE_URL . '/public/' . ltrim($relP['main_image'], '/') : 'https://placehold.co/400x400/ebeef5/646c79?text=Related';
                        ?>
                        <div style="border:1px solid #eee; border-radius:10px; overflow:hidden; background:#fff; display:flex; flex-direction:column;">
                            <a href="<?= BASE_URL ?>/product_detail.php?id=<?= (int)$relP['id'] ?>" style="display:block; aspect-ratio:1/1; background:#f8f8f8; overflow:hidden;">
                                <img src="<?= e($relImg) ?>" alt="<?= e($relP['name']) ?>" style="width:100%; height:100%; object-fit:contain;" onerror="this.onerror=null; this.src='https://placehold.co/400x400/ebeef5/646c79?text=Related';">
                            </a>
                            <div style="padding:12px; display:flex; flex-direction:column; flex:1; justify-content:space-between;">
                                <a href="<?= BASE_URL ?>/product_detail.php?id=<?= (int)$relP['id'] ?>" style="font-size:14px; font-weight:700; color:#111; text-decoration:none; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:38px;">
                                    <?= e($relP['name']) ?>
                                </a>
                                <div style="margin-top:8px;">
                                    <span style="font-size:15px; font-weight:800; color:#e60023;">
                                        <?= number_format($relPrice, 0, ',', '.') ?>đ
                                    </span>
                                    <?php if ($relOldPrice !== null): ?>
                                        <span style="font-size:12px; color:#888; text-decoration:line-through; margin-left:6px;">
                                            <?= number_format($relOldPrice, 0, ',', '.') ?>đ
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL BẢNG KHUYẾN NGHỊ KÍCH CỠ (SIZE GUIDE) -->
<div id="sizeGuideModal" class="sg-modal-overlay" onclick="closeSizeModal(event)" style="position:fixed; inset:0; background:rgba(0,0,0,0.55); backdrop-filter:blur(3px); display:none; align-items:center; justify-content:center; z-index:9999; padding:16px;">
    <div class="sg-modal-box" onclick="event.stopPropagation()" style="background:#ffffff; color:#111111; width:100%; max-width:520px; border-radius:16px; padding:26px 24px 28px; position:relative; box-shadow:0 20px 50px rgba(0,0,0,0.25); border:1.5px solid #e5e7eb;">
        <button type="button" class="sg-close-btn" onclick="closeSizeModal()" style="position:absolute; top:16px; right:18px; width:34px; height:34px; border-radius:50%; background:#f3f4f6; color:#111; border:1px solid #d1d5db; font-size:20px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center;">&times;</button>
        <h3 style="text-align:center; font-size:20px; font-weight:800; text-transform:uppercase; margin:0 0 20px 0; color:#111111;">
            <span style="border-bottom:3px solid #e60023; padding-bottom:6px;">BẢNG KHUYẾN NGHỊ KÍCH CỠ</span>
        </h3>

        <table style="width:100%; border-collapse:collapse; text-align:center;">
            <thead>
                <tr style="background:#111111; color:#ffffff;">
                    <th style="padding:12px 10px; font-size:13.5px; font-weight:800;">SIZE</th>
                    <th style="padding:12px 10px; font-size:13.5px; font-weight:800;">CHIỀU CAO (CM)</th>
                    <th style="padding:12px 10px; font-size:13.5px; font-weight:800;">CÂN NẶNG (KG)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:14px 10px; font-size:18px; font-weight:900; color:#e60023; border-bottom:1px solid #e5e7eb;">S</td>
                    <td style="padding:14px 10px; font-size:15px; font-weight:700; border-bottom:1px solid #e5e7eb;">155 - 160</td>
                    <td style="padding:14px 10px; font-size:15px; font-weight:700; border-bottom:1px solid #e5e7eb;">40 - 50</td>
                </tr>
                <tr>
                    <td style="padding:14px 10px; font-size:18px; font-weight:900; color:#e60023; border-bottom:1px solid #e5e7eb;">M</td>
                    <td style="padding:14px 10px; font-size:15px; font-weight:700; border-bottom:1px solid #e5e7eb;">160 - 165</td>
                    <td style="padding:14px 10px; font-size:15px; font-weight:700; border-bottom:1px solid #e5e7eb;">50 - 57</td>
                </tr>
                <tr>
                    <td style="padding:14px 10px; font-size:18px; font-weight:900; color:#e60023; border-bottom:1px solid #e5e7eb;">L</td>
                    <td style="padding:14px 10px; font-size:15px; font-weight:700; border-bottom:1px solid #e5e7eb;">165 - 170</td>
                    <td style="padding:14px 10px; font-size:15px; font-weight:700; border-bottom:1px solid #e5e7eb;">57 - 65</td>
                </tr>
                <tr>
                    <td style="padding:14px 10px; font-size:18px; font-weight:900; color:#e60023; border-bottom:1px solid #e5e7eb;">XL</td>
                    <td style="padding:14px 10px; font-size:15px; font-weight:700; border-bottom:1px solid #e5e7eb;">170 - 175</td>
                    <td style="padding:14px 10px; font-size:15px; font-weight:700; border-bottom:1px solid #e5e7eb;">65 - 75</td>
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
            item.style.borderColor = '#e5e7eb';
        });
        el.style.borderColor = '#e60023';
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
            el.style.borderColor = '#d1d5db';
        });
        btn.style.borderColor = '#e60023';
        document.getElementById('selectedColorInput').value = colorName;
        document.getElementById('colorNameLabel').textContent = colorName.toUpperCase();
        updateMatchedVariantId();
    }

    function selectSizeOption(btn, sizeName) {
        document.querySelectorAll('.wf-size-btn').forEach(function(el) {
            el.style.borderColor = '#e5e7eb';
            el.style.backgroundColor = '#ffffff';
            el.style.color = '#111111';
        });
        btn.style.borderColor = '#e60023';
        btn.style.backgroundColor = '#fff0f2';
        btn.style.color = '#e60023';
        document.getElementById('selectedSizeInput').value = sizeName;
        document.getElementById('sizeNameLabel').textContent = sizeName;
        updateMatchedVariantId();
    }

    function changeDetailQty(delta) {
        var inp = document.getElementById('detailQtyInput');
        var val = (parseInt(inp.value, 10) || 1) + delta;
        if (val < 1) val = 1;
        if (val > 100) val = 100;
        inp.value = val;
    }

    function validateQtyInput(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value !== '' && parseInt(input.value, 10) > 100) {
            input.value = 100;
        }
    }

    function fixEmptyQty(input) {
        var val = parseInt(input.value, 10);
        if (isNaN(val) || val < 1) {
            input.value = 1;
        }
    }

    function openSizeModal() {
        document.getElementById('sizeGuideModal').style.display = 'flex';
    }

    function closeSizeModal(e) {
        if (!e || e.target.id === 'sizeGuideModal') {
            document.getElementById('sizeGuideModal').style.display = 'none';
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
