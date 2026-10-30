<?php
/**
 * Chỉnh sửa sản phẩm (Admin Product Edit - TV3)
 * Hỗ trợ cập nhật thông tin, quản lý gallery ảnh (đặt ảnh đại diện, xóa ảnh, tải thêm) và cập nhật tồn kho biến thể
 */

$pageTitle = 'Chỉnh sửa sản phẩm';
require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/models/Product.php';
require_once dirname(__DIR__) . '/models/Category.php';

$productModel = new Product($pdo);
$categoryModel = new Category($pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $productModel->getById($id);

if (!$product) {
    setFlash('error', 'Không tìm thấy sản phẩm yêu cầu.');
    redirect('admin/products.php');
}

$categories = $categoryModel->getAll();
$images = $productModel->getImages($id);
$variants = $productModel->getVariants($id);
$errors = [];

// Xử lý gửi biểu mẫu POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Mã xác thực CSRF không hợp lệ hoặc đã hết hạn.');
        redirect('admin/product-edit.php?id=' . $id);
    }

    $action = $_POST['action'] ?? 'update_all';

    // 1. Thao tác nhanh: Đặt ảnh làm ảnh đại diện
    if ($action === 'set_main_image') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        if ($imageId > 0) {
            $productModel->setMainImage($id, $imageId);
            setFlash('success', 'Đã cập nhật ảnh đại diện chính!');
        }
        redirect('admin/product-edit.php?id=' . $id);
    }

    // 2. Thao tác nhanh: Xóa 1 ảnh
    if ($action === 'delete_image') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        if ($imageId > 0) {
            $productModel->deleteImage($imageId);
            setFlash('success', 'Đã xóa ảnh thành công!');
        }
        redirect('admin/product-edit.php?id=' . $id);
    }

    // 3. Cập nhật toàn bộ thông tin sản phẩm, ảnh mới và biến thể
    if ($action === 'update_all') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $salePriceInput = trim($_POST['sale_price'] ?? '');
        $salePrice = $salePriceInput !== '' ? (float)$salePriceInput : null;
        $description = trim($_POST['description'] ?? '');
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        if ($name === '') {
            $errors[] = 'Vui lòng nhập tên sản phẩm.';
        }
        if ($categoryId <= 0) {
            $errors[] = 'Vui lòng chọn danh mục cho sản phẩm.';
        }
        if ($price <= 0) {
            $errors[] = 'Giá bán phải lớn hơn 0đ.';
        }
        if ($salePrice !== null && $salePrice >= $price) {
            $errors[] = 'Giá khuyến mãi phải nhỏ hơn giá bán gốc.';
        }

        if (empty($errors)) {
            if ($slug === '') {
                $slug = createSlug($name);
            } else {
                $slug = createSlug($slug);
            }

            try {
                $pdo->beginTransaction();

                // Cập nhật bảng products
                $productModel->update($id, [
                    'name'        => $name,
                    'slug'        => $slug,
                    'price'       => $price,
                    'sale_price'  => $salePrice,
                    'description' => $description,
                    'category_id' => $categoryId,
                    'status'      => $status,
                ]);

                // Xử lý tải thêm ảnh mới (nếu có chọn)
                if (isset($_FILES['more_images']) && is_array($_FILES['more_images']['name'])) {
                    $fileCount = count($_FILES['more_images']['name']);
                    $hasExistingMain = !empty($productModel->getImages($id));

                    for ($i = 0; $i < $fileCount; $i++) {
                        if ($_FILES['more_images']['error'][$i] === UPLOAD_ERR_OK) {
                            $singleFile = [
                                'name'     => $_FILES['more_images']['name'][$i],
                                'type'     => $_FILES['more_images']['type'][$i],
                                'tmp_name' => $_FILES['more_images']['tmp_name'][$i],
                                'error'    => $_FILES['more_images']['error'][$i],
                                'size'     => $_FILES['more_images']['size'][$i],
                            ];

                            $uploadResult = uploadImage($singleFile, 'products');
                            if ($uploadResult['success']) {
                                $isMain = !$hasExistingMain && ($i === 0);
                                $productModel->addImage($id, $uploadResult['path'], $isMain, $i + 10);
                            }
                        }
                    }
                }

                // Cập nhật biến thể size/màu/tồn kho
                $vSizes = $_POST['variant_size'] ?? [];
                $vColors = $_POST['variant_color'] ?? [];
                $vStocks = $_POST['variant_stock'] ?? [];
                $updatedVariants = [];

                for ($i = 0; $i < count($vSizes); $i++) {
                    $sizeVal = trim($vSizes[$i] ?? '');
                    $colorVal = trim($vColors[$i] ?? '');
                    $stockVal = max(0, (int)($vStocks[$i] ?? 0));
                    if ($sizeVal !== '' || $colorVal !== '' || $stockVal > 0) {
                        $updatedVariants[] = [
                            'size'  => $sizeVal,
                            'color' => $colorVal,
                            'stock' => $stockVal,
                        ];
                    }
                }

                if (!empty($updatedVariants)) {
                    $productModel->syncVariants($id, $updatedVariants);
                }

                $pdo->commit();
                setFlash('success', 'Cập nhật sản phẩm #' . $id . ' thành công!');
                redirect('admin/product-edit.php?id=' . $id);
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Lỗi cập nhật: ' . $e->getMessage();
            }
        }
    }
}

// Tải lại dữ liệu mới nhất sau khi xử lý
$product = $productModel->getById($id);
$images = $productModel->getImages($id);
$variants = $productModel->getVariants($id);
if (empty($variants)) {
    $variants = [['size' => 'Freesize', 'color' => 'Tiêu chuẩn', 'stock' => 10]];
}
?>

<div class="admin-page-header">
    <div>
        <h3>Chỉnh sửa sản phẩm #<?= $id ?>: <?= e($product['name']) ?></h3>
        <p class="admin-subtitle">Cập nhật giá bán, mô tả, thư viện ảnh và tồn kho theo biến thể (Nhiệm vụ TV3)</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="<?= BASE_URL ?>/admin/products.php" class="button button-outline">
            &laquo; Quay lại danh sách
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="flash-message flash-error" style="margin-bottom: 20px;">
        <ul style="list-style: inside;">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="product-form-grid">
    <!-- Cột trái: Form thông tin sản phẩm và biến thể -->
    <div class="form-col-main">
        <form id="product-edit-form" action="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" class="admin-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_all">

            <div class="admin-card">
                <h4>Thông tin chung sản phẩm</h4>

                <div class="form-group">
                    <label for="p-name">Tên sản phẩm <span class="required">*</span></label>
                    <input type="text" id="p-name" name="name" class="form-control" 
                           value="<?= e($product['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="p-slug">Đường dẫn thân thiện (Slug)</label>
                    <input type="text" id="p-slug" name="slug" class="form-control" 
                           value="<?= e($product['slug']) ?>">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="p-price">Giá gốc (VNĐ) <span class="required">*</span></label>
                        <input type="number" id="p-price" name="price" class="form-control" 
                               value="<?= (float)$product['price'] ?>" min="0" step="1000" required>
                    </div>

                    <div class="form-group">
                        <label for="p-sale">Giá khuyến mãi (VNĐ - tùy chọn)</label>
                        <input type="number" id="p-sale" name="sale_price" class="form-control" 
                               value="<?= $product['sale_price'] ? (float)$product['sale_price'] : '' ?>" min="0" step="1000" 
                               placeholder="Để trống nếu không giảm giá">
                    </div>
                </div>

                <div class="form-group">
                    <label for="p-desc">Mô tả sản phẩm</label>
                    <textarea id="p-desc" name="description" class="form-control" rows="7"><?= e($product['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Khối quản lý biến thể Size/Màu & Tồn kho -->
            <div class="admin-card">
                <div class="card-header-flex">
                    <div>
                        <h4>Biến thể (Size, Màu sắc) & Tồn kho</h4>
                        <p class="admin-subtitle" style="margin-bottom: 0;">Quản lý kho hàng chi tiết cho từng kích cỡ và màu</p>
                    </div>
                    <button type="button" id="btn-add-variant" class="button button-sm">
                        ➕ Thêm biến thể
                    </button>
                </div>

                <div class="table-responsive" style="margin-top: 15px;">
                    <table class="admin-table" id="variant-table">
                        <thead>
                            <tr>
                                <th>Kích cỡ (Size)</th>
                                <th>Màu sắc</th>
                                <th style="width: 140px;">Số lượng tồn kho</th>
                                <th style="width: 60px; text-align: center;">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="variant-tbody">
                            <?php foreach ($variants as $v): ?>
                                <tr>
                                    <td>
                                        <input type="text" name="variant_size[]" class="form-control" 
                                               value="<?= e($v['size'] ?? '') ?>" placeholder="S, M, L, XL...">
                                    </td>
                                    <td>
                                        <input type="text" name="variant_color[]" class="form-control" 
                                               value="<?= e($v['color'] ?? '') ?>" placeholder="Đen, Trắng, Be...">
                                    </td>
                                    <td>
                                        <input type="number" name="variant_stock[]" class="form-control" 
                                               value="<?= (int)$v['stock'] ?>" min="0" step="1">
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn-remove-row" title="Xóa dòng">&times;</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tải thêm ảnh -->
            <div class="admin-card">
                <h4>Tải thêm hình ảnh mới</h4>
                <div class="form-group">
                    <input type="file" name="more_images[]" class="form-control" multiple accept="image/*">
                </div>
                <small class="form-hint">Chọn thêm nhiều ảnh mới để bổ sung vào bộ sưu tập hiện có.</small>
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="button">
                    💾 Lưu tất cả thay đổi
                </button>
            </div>
        </form>
    </div>

    <!-- Cột phải: Quản lý danh mục, trạng thái và thư viện ảnh hiện tại -->
    <div class="form-col-side">
        <div class="admin-card">
            <h4>Danh mục & Phân loại</h4>
            <div class="form-group">
                <label for="side-category">Danh mục sản phẩm <span class="required">*</span></label>
                <select id="side-category" name="category_id" form="product-edit-form" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="side-status">Trạng thái bán</label>
                <select id="side-status" name="status" form="product-edit-form" class="form-control">
                    <option value="1" <?= $product['status'] == 1 ? 'selected' : '' ?>>Đang bán (Hiển thị)</option>
                    <option value="0" <?= $product['status'] == 0 ? 'selected' : '' ?>>Tạm ẩn (Khóa)</option>
                </select>
            </div>
        </div>

        <!-- Quản lý Gallery ảnh hiện tại -->
        <div class="admin-card">
            <div class="card-header-flex">
                <h4>Thư viện ảnh hiện có (<?= count($images) ?>)</h4>
            </div>
            
            <?php if (empty($images)): ?>
                <p class="table-empty" style="padding: 15px 0;">Sản phẩm chưa có hình ảnh nào. Hãy tải thêm ảnh ở cột bên trái!</p>
            <?php else: ?>
                <div class="gallery-edit-list">
                    <?php foreach ($images as $img): ?>
                        <div class="gallery-item-card <?= $img['is_main'] ? 'is-main' : '' ?>">
                            <div class="gallery-item-thumb">
                                <img src="<?= BASE_URL ?>/public/<?= e(ltrim($img['image_path'], '/')) ?>" alt="Product image">
                                <?php if ($img['is_main']): ?>
                                    <span class="badge-main-tag">Ảnh đại diện</span>
                                <?php endif; ?>
                            </div>
                            <div class="gallery-item-actions">
                                <?php if (!$img['is_main']): ?>
                                    <form action="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $id ?>" method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="set_main_image">
                                        <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                        <button type="submit" class="btn-action-sm" title="Đặt làm ảnh chính">⭐ Đặt làm ảnh chính</button>
                                    </form>
                                <?php endif; ?>

                                <form action="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $id ?>" method="POST" 
                                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa ảnh này?');" style="display:inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_image">
                                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                    <button type="submit" class="btn-action-sm btn-delete-sm" title="Xóa ảnh">🗑️ Xóa</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Thêm biến thể động
const btnAddVariant = document.getElementById('btn-add-variant');
const variantTbody = document.getElementById('variant-tbody');

if (btnAddVariant && variantTbody) {
    btnAddVariant.addEventListener('click', function() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="variant_size[]" class="form-control" placeholder="S, M, L, XL..."></td>
            <td><input type="text" name="variant_color[]" class="form-control" placeholder="Đen, Trắng, Be..."></td>
            <td><input type="number" name="variant_stock[]" class="form-control" value="10" min="0" step="1"></td>
            <td style="text-align: center;"><button type="button" class="btn-remove-row" title="Xóa dòng">&times;</button></td>
        `;
        variantTbody.appendChild(tr);
    });

    variantTbody.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-remove-row')) {
            const rows = variantTbody.querySelectorAll('tr');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
            } else {
                alert('Sản phẩm cần có ít nhất một dòng biến thể/tồn kho!');
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
