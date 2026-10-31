<?php
/**
 * Form Thêm mới & Cập nhật sản phẩm (Admin Product Form - TV3)
 * Tích hợp Rich Text Editor (CKEditor 5), upload nhiều ảnh và quản lý biến thể size/màu/tồn kho
 */

$isEdit = isset($_GET['id']) && (int)$_GET['id'] > 0;
$productId = $isEdit ? (int)$_GET['id'] : 0;
$pageTitle = $isEdit ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới';

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/models/Product.php';
require_once dirname(__DIR__) . '/models/Category.php';

$productModel = new Product($pdo);
$categoryModel = new Category($pdo);
$categories = $categoryModel->getAll();

$product = null;
$existingImages = [];
$existingVariants = [];

if ($isEdit) {
    $product = $productModel->getById($productId);
    if (!$product) {
        setFlash('error', 'Không tìm thấy sản phẩm yêu cầu.');
        redirect('admin/products.php');
    }
    $existingImages = $productModel->getImages($productId);
    $existingVariants = $productModel->getVariants($productId);
}

$errors = [];
$name = $product['name'] ?? '';
$slug = $product['slug'] ?? '';
$categoryId = $product['category_id'] ?? '';
$price = $product['price'] ?? '';
$salePrice = $product['sale_price'] ?? '';
$description = $product['description'] ?? '';
$status = $product['status'] ?? 1;

// Xử lý gửi biểu mẫu POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Mã xác thực CSRF không hợp lệ hoặc đã hết hạn.');
        redirect($isEdit ? "admin/product_form.php?id={$productId}" : 'admin/product_form.php');
    }

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $salePriceInput = trim($_POST['sale_price'] ?? '');
    $salePrice = $salePriceInput !== '' ? (float)$salePriceInput : null;
    $description = trim($_POST['description'] ?? '');
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    // Validate dữ liệu cơ bản
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

    if ($slug === '') {
        $slug = createSlug($name);
    } else {
        $slug = createSlug($slug);
    }

    // Kiểm tra trùng slug (kể cả sản phẩm đang ẩn status = 0)
    $checkSlugSql = "SELECT id FROM products WHERE slug = :slug" . ($isEdit ? " AND id != :id" : "") . " LIMIT 1";
    $checkSlugStmt = $pdo->prepare($checkSlugSql);
    $checkSlugParams = ['slug' => $slug];
    if ($isEdit) {
        $checkSlugParams['id'] = $productId;
    }
    $checkSlugStmt->execute($checkSlugParams);
    if ($checkSlugStmt->fetch()) {
        $slug .= '-' . time();
    }

    // Xử lý danh sách biến thể gửi lên
    $vSizes = $_POST['variant_size'] ?? [];
    $vColors = $_POST['variant_color'] ?? [];
    $vStocks = $_POST['variant_stock'] ?? [];

    $variants = [];
    $countV = count($vSizes);
    for ($i = 0; $i < $countV; $i++) {
        $s = trim($vSizes[$i] ?? '');
        $c = trim($vColors[$i] ?? '');
        $st = (int)($vStocks[$i] ?? 0);

        if ($s !== '' || $c !== '' || $st > 0) {
            $variants[] = [
                'size'  => $s,
                'color' => $c,
                'stock' => max(0, $st),
            ];
        }
    }

    // Xử lý tải lên nhiều ảnh (uploadMultipleImages)
    $uploadedImagePaths = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $uploadResult = uploadMultipleImages($_FILES['images'], 'products');
        if ($uploadResult['success']) {
            foreach ($uploadResult['files'] as $f) {
                $uploadedImagePaths[] = $f['path'];
            }
        } else {
            $errors = array_merge($errors, $uploadResult['errors']);
        }
    }

    // Khi thêm mới: bắt buộc có ít nhất 1 ảnh tải lên
    if (!$isEdit && empty($uploadedImagePaths)) {
        $errors[] = 'Vui lòng tải lên ít nhất 1 hình ảnh cho sản phẩm.';
    }

    if (empty($errors)) {
        $productData = [
            'name'        => $name,
            'slug'        => $slug,
            'price'       => $price,
            'sale_price'  => $salePrice,
            'description' => $description,
            'category_id' => $categoryId,
            'status'      => $status,
        ];

        try {
            if ($isEdit) {
                $mainImageId = isset($_POST['main_image_id']) ? (int)$_POST['main_image_id'] : null;
                $productModel->update($productId, $productData, $uploadedImagePaths, $mainImageId, $variants);
                setFlash('success', "Cập nhật sản phẩm #{$productId} thành công!");
                redirect('admin/products.php');
            } else {
                $mainIndex = isset($_POST['main_image_index']) ? (int)$_POST['main_image_index'] : 0;
                $newId = $productModel->create($productData, $uploadedImagePaths, $mainIndex, $variants);
                setFlash('success', "Thêm mới sản phẩm #{$newId} thành công!");
                redirect('admin/products.php');
            }
        } catch (Throwable $e) {
            $errors[] = 'Lỗi hệ thống khi lưu sản phẩm: ' . $e->getMessage();
        }
    }
}
?>

<div class="admin-page-header">
    <div>
        <h3><?= $isEdit ? 'Chỉnh sửa sản phẩm #' . e($productId) : 'Thêm sản phẩm mới' ?></h3>
        <p class="admin-subtitle">Tích hợp Rich Text Editor, tải lên nhiều ảnh và quản lý biến thể (Nhiệm vụ TV3)</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/admin/products.php" class="button button-outline">
            ⬅️ Quay lại danh sách
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="flash-message flash-error">
        <strong>Vui lòng kiểm tra lại thông tin:</strong>
        <ul style="margin: 6px 0 0 18px;">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>/admin/product_form.php<?= $isEdit ? '?id=' . $productId : '' ?>" 
      method="POST" enctype="multipart/form-data" class="admin-form">
    <?= csrfField() ?>

    <div class="product-form-grid">
        <!-- Cột trái: Thông tin chính, Editor mô tả, Biến thể -->
        <div class="product-main-col">
            <div class="admin-card">
                <h4>Thông tin cơ bản</h4>
                
                <div class="form-group">
                    <label for="name">Tên sản phẩm <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?= e($name) ?>" required placeholder="Ví dụ: Áo thun basic cotton cổ tròn">
                </div>

                <div class="form-group">
                    <label for="slug">Đường dẫn thân thiện (Slug)</label>
                    <input type="text" id="slug" name="slug" class="form-control" 
                           value="<?= e($slug) ?>" placeholder="Để trống hệ thống sẽ tự sinh từ tên sản phẩm">
                    <small class="form-hint">Ví dụ: ao-thun-basic-cotton-co-tron</small>
                </div>

                <!-- Rich Text Editor (CKEditor 5) cho mô tả HTML theo yêu cầu Giảng viên -->
                <div class="form-group">
                    <label for="description">Mô tả chi tiết sản phẩm (Rich Text Editor)</label>
                    <textarea id="description" name="description" rows="8"><?= e($description) ?></textarea>
                </div>
            </div>

            <!-- Bảng quản lý biến thể Size / Màu / Tồn kho -->
            <div class="admin-card">
                <div class="card-header-flex">
                    <h4>Biến thể sản phẩm & Tồn kho</h4>
                    <button type="button" id="btn-add-variant" class="button button-outline button-sm">
                        ➕ Thêm dòng biến thể
                    </button>
                </div>
                <p class="form-hint" style="margin-bottom: 14px;">
                    Thiết lập các thuộc tính kích thước, màu sắc và số lượng tồn kho cho từng phân loại.
                </p>

                <div class="table-responsive">
                    <table class="admin-table" id="variants-table">
                        <thead>
                            <tr>
                                <th style="width: 140px;">Kích thước (Size)</th>
                                <th style="width: 180px;">Màu sắc (Color)</th>
                                <th style="width: 140px;">Số lượng kho</th>
                                <th style="width: 50px; text-align: center;">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="variants-body">
                            <?php 
                            $renderVariants = !empty($existingVariants) ? $existingVariants : [
                                ['size' => 'S', 'color' => 'Trắng', 'stock' => 20],
                                ['size' => 'M', 'color' => 'Trắng', 'stock' => 25],
                                ['size' => 'L', 'color' => 'Đen', 'stock' => 15],
                            ];
                            foreach ($renderVariants as $v): ?>
                                <tr>
                                    <td>
                                        <input type="text" name="variant_size[]" class="form-control" 
                                               value="<?= e($v['size'] ?? '') ?>" placeholder="S, M, L, XL, 29...">
                                    </td>
                                    <td>
                                        <input type="text" name="variant_color[]" class="form-control" 
                                               value="<?= e($v['color'] ?? '') ?>" placeholder="Trắng, Đen, Xanh...">
                                    </td>
                                    <td>
                                        <input type="number" name="variant_stock[]" class="form-control" 
                                               value="<?= (int)($v['stock'] ?? 0) ?>" min="0" placeholder="0">
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
        </div>

        <!-- Cột phải: Giá, Danh mục, Trạng thái, Upload ảnh -->
        <div class="product-side-col">
            <div class="admin-card">
                <h4>Giá bán & Phân loại</h4>

                <div class="form-group">
                    <label for="category_id">Danh mục sản phẩm <span class="required">*</span></label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($categoryId == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="price">Giá gốc (VNĐ) <span class="required">*</span></label>
                        <input type="number" id="price" name="price" class="form-control" 
                               value="<?= e($price) ?>" min="1000" step="1000" required placeholder="Ví dụ: 250000">
                    </div>

                    <div class="form-group">
                        <label for="sale_price">Giá khuyến mãi</label>
                        <input type="number" id="sale_price" name="sale_price" class="form-control" 
                               value="<?= e($salePrice ?? '') ?>" min="0" step="1000" placeholder="Để trống nếu không giảm">
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Trạng thái hiển thị</label>
                    <select id="status" name="status" class="form-control">
                        <option value="1" <?= $status == 1 ? 'selected' : '' ?>>Đang bán (Hiển thị)</option>
                        <option value="0" <?= $status == 0 ? 'selected' : '' ?>>Tạm ẩn (Khóa)</option>
                    </select>
                </div>
            </div>

            <!-- Upload nhiều ảnh & Quản lý Gallery -->
            <div class="admin-card">
                <h4>Hình ảnh sản phẩm (Nhiều ảnh)</h4>
                
                <?php if ($isEdit && !empty($existingImages)): ?>
                    <p class="form-hint" style="margin-bottom: 8px;"><strong>Ảnh hiện có:</strong> Chọn nút radio để đặt làm ảnh đại diện chính.</p>
                    <div class="gallery-edit-list" style="margin-bottom: 16px;">
                        <?php foreach ($existingImages as $img): ?>
                            <div class="gallery-item-card <?= $img['is_main'] ? 'is-main' : '' ?>">
                                <div class="gallery-item-thumb">
                                    <img src="<?= BASE_URL ?>/public/<?= e(ltrim($img['image_path'], '/')) ?>" alt="Ảnh">
                                    <?php if ($img['is_main']): ?>
                                        <span class="badge-main-tag">ẢNH CHÍNH</span>
                                    <?php endif; ?>
                                </div>
                                <div class="gallery-item-actions">
                                    <label style="font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                        <input type="radio" name="main_image_id" value="<?= $img['id'] ?>" <?= $img['is_main'] ? 'checked' : '' ?>>
                                        Ảnh chính
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="images">Tải lên ảnh mới (chọn nhiều ảnh) <?= !$isEdit ? '<span class="required">*</span>' : '' ?></label>
                    <input type="file" id="images" name="images[]" class="form-control" 
                           multiple accept="image/jpeg,image/png,image/webp" <?= !$isEdit ? 'required' : '' ?>>
                    <small class="form-hint">Nhấn giữ phím Ctrl để chọn 2-3 ảnh cùng lúc (.jpg, .png, .webp tối đa 5MB/ảnh).</small>
                </div>

                <div id="image-preview" class="image-preview-grid"></div>
            </div>

            <div class="admin-card">
                <button type="submit" class="button" style="width: 100%; font-size: 15px; padding: 12px;">
                    <?= $isEdit ? '💾 Lưu thay đổi sản phẩm' : '➕ Thêm sản phẩm mới' ?>
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Tích hợp CKEditor 5 theo yêu cầu Giảng viên (Không cần API key, CDN ổn định 100%) -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
ClassicEditor
    .create(document.querySelector('#description'), {
        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo']
    })
    .catch(error => console.error(error));

// Tự động sinh slug khi nhập tên sản phẩm
const nameInput = document.getElementById('name');
const slugInput = document.getElementById('slug');

if (nameInput && slugInput) {
    nameInput.addEventListener('input', function() {
        <?php if (!$isEdit): ?>
        const val = this.value;
        slugInput.value = val.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[đĐ]/g, "d")
            .replace(/([^0-9a-z-\s])/g, '')
            .replace(/(\s+)/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
        <?php endif; ?>
    });
}

// Xem trước ảnh mới khi chọn
const imageInput = document.getElementById('images');
const previewGrid = document.getElementById('image-preview');

if (imageInput && previewGrid) {
    imageInput.addEventListener('change', function() {
        previewGrid.innerHTML = '';
        const files = Array.from(this.files);

        files.forEach((file, index) => {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const item = document.createElement('div');
                item.className = 'preview-item';
                item.innerHTML = `
                    <img src="${e.target.result}" alt="Preview ${index + 1}">
                    ${index === 0 ? '<span class="preview-badge-main">ẢNH ĐẦU</span>' : ''}
                `;
                previewGrid.appendChild(item);
            };
            reader.readAsDataURL(file);
        });
    });
}

// Thêm / Xóa dòng biến thể Size - Màu - Tồn kho động
const btnAddVariant = document.getElementById('btn-add-variant');
const variantsBody = document.getElementById('variants-body');

if (btnAddVariant && variantsBody) {
    btnAddVariant.addEventListener('click', function() {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="text" name="variant_size[]" class="form-control" placeholder="S, M, L, XL...">
            </td>
            <td>
                <input type="text" name="variant_color[]" class="form-control" placeholder="Trắng, Đen, Xanh...">
            </td>
            <td>
                <input type="number" name="variant_stock[]" class="form-control" value="20" min="0" placeholder="0">
            </td>
            <td style="text-align: center;">
                <button type="button" class="btn-remove-row" title="Xóa dòng">&times;</button>
            </td>
        `;
        variantsBody.appendChild(row);
    });

    variantsBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-row')) {
            const rows = variantsBody.querySelectorAll('tr');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
            } else {
                alert('Sản phẩm cần có ít nhất 1 dòng cấu hình biến thể.');
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
