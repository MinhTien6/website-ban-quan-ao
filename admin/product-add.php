<?php
/**
 * Thêm mới sản phẩm (Admin Product Add - TV3)
 * Hỗ trợ upload nhiều ảnh và tạo biến thể size/màu/tồn kho
 */

$pageTitle = 'Thêm sản phẩm mới';
require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/models/Product.php';
require_once dirname(__DIR__) . '/models/Category.php';

$productModel = new Product($pdo);
$categoryModel = new Category($pdo);
$categories = $categoryModel->getAll();

$errors = [];
$name = '';
$slug = '';
$categoryId = '';
$price = '';
$salePrice = '';
$description = '';
$status = 1;

// Mẫu biến thể ban đầu
$submittedVariants = [
    ['size' => 'M', 'color' => 'Đen', 'stock' => 20],
    ['size' => 'L', 'color' => 'Đen', 'stock' => 20],
    ['size' => 'XL', 'color' => 'Trắng', 'stock' => 15],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Mã xác thực CSRF không hợp lệ hoặc đã hết hạn.');
        redirect('admin/product-add.php');
    }

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $salePriceInput = trim($_POST['sale_price'] ?? '');
    $salePrice = $salePriceInput !== '' ? (float)$salePriceInput : null;
    $description = trim($_POST['description'] ?? '');
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    // Lấy danh sách biến thể gửi lên
    $vSizes = $_POST['variant_size'] ?? [];
    $vColors = $_POST['variant_color'] ?? [];
    $vStocks = $_POST['variant_stock'] ?? [];
    $submittedVariants = [];

    for ($i = 0; $i < count($vSizes); $i++) {
        $submittedVariants[] = [
            'size'  => trim($vSizes[$i] ?? ''),
            'color' => trim($vColors[$i] ?? ''),
            'stock' => max(0, (int)($vStocks[$i] ?? 0)),
        ];
    }

    // Kiểm tra dữ liệu đầu vào
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

        // Đảm bảo slug không bị trùng lặp
        $existing = $productModel->getBySlug($slug);
        if ($existing) {
            $slug .= '-' . time();
        }

        try {
            $pdo->beginTransaction();

            // 1. Tạo sản phẩm
            $productId = $productModel->create([
                'name'        => $name,
                'slug'        => $slug,
                'price'       => $price,
                'sale_price'  => $salePrice,
                'description' => $description,
                'category_id' => $categoryId,
                'status'      => $status,
            ]);

            // 2. Xử lý tải lên nhiều hình ảnh
            if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
                $fileCount = count($_FILES['images']['name']);
                $isFirst = true;

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $singleFile = [
                            'name'     => $_FILES['images']['name'][$i],
                            'type'     => $_FILES['images']['type'][$i],
                            'tmp_name' => $_FILES['images']['tmp_name'][$i],
                            'error'    => $_FILES['images']['error'][$i],
                            'size'     => $_FILES['images']['size'][$i],
                        ];

                        $uploadResult = uploadImage($singleFile, 'products');
                        if ($uploadResult['success']) {
                            $productModel->addImage(
                                $productId,
                                $uploadResult['path'],
                                $isFirst, // Ảnh đầu tiên là ảnh chính
                                $i
                            );
                            $isFirst = false;
                        }
                    }
                }
            }

            // 3. Xử lý lưu các biến thể (size, màu, tồn kho)
            if (!empty($submittedVariants)) {
                $productModel->syncVariants($productId, $submittedVariants);
            } else {
                // Biến thể mặc định nếu không nhập
                $productModel->syncVariants($productId, [
                    ['size' => 'Freesize', 'color' => 'Tiêu chuẩn', 'stock' => 10]
                ]);
            }

            $pdo->commit();
            setFlash('success', 'Thêm mới sản phẩm và thiết lập biến thể thành công!');
            redirect('admin/products.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Lỗi hệ thống khi lưu sản phẩm: ' . $e->getMessage();
        }
    }
}
?>

<div class="admin-page-header">
    <div>
        <h3>Thêm sản phẩm mới</h3>
        <p class="admin-subtitle">Tạo sản phẩm, tải lên nhiều ảnh và quản lý biến thể size/màu (Nhiệm vụ TV3)</p>
    </div>
    <div>
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

<form action="<?= BASE_URL ?>/admin/product-add.php" method="POST" enctype="multipart/form-data" class="admin-form">
    <?= csrfField() ?>

    <div class="product-form-grid">
        <!-- Cột trái: Thông tin cơ bản & Mô tả -->
        <div class="form-col-main">
            <div class="admin-card">
                <h4>Thông tin cơ bản sản phẩm</h4>
                
                <div class="form-group">
                    <label for="p-name">Tên sản phẩm <span class="required">*</span></label>
                    <input type="text" id="p-name" name="name" class="form-control" 
                           value="<?= e($name) ?>" required placeholder="Ví dụ: Áo Sơ Mi Nam Oxford Cổ Bẻ">
                </div>

                <div class="form-group">
                    <label for="p-slug">Đường dẫn thân thiện (Slug)</label>
                    <input type="text" id="p-slug" name="slug" class="form-control" 
                           value="<?= e($slug) ?>" placeholder="Để trống hệ thống sẽ tự động tạo">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="p-price">Giá gốc (VNĐ) <span class="required">*</span></label>
                        <input type="number" id="p-price" name="price" class="form-control" 
                               value="<?= e($price ?: '') ?>" min="0" step="1000" required placeholder="Ví dụ: 350000">
                    </div>

                    <div class="form-group">
                        <label for="p-sale">Giá khuyến mãi (VNĐ - tùy chọn)</label>
                        <input type="number" id="p-sale" name="sale_price" class="form-control" 
                               value="<?= e($salePrice ?: '') ?>" min="0" step="1000" placeholder="Ví dụ: 299000">
                    </div>
                </div>

                <div class="form-group">
                    <label for="p-desc">Mô tả chi tiết sản phẩm</label>
                    <textarea id="p-desc" name="description" class="form-control" rows="7" 
                              placeholder="Mô tả chất liệu, phong cách, hướng dẫn giặt là và bảng size..."><?= e($description) ?></textarea>
                </div>
            </div>

            <!-- Khối quản lý biến thể size/màu & tồn kho -->
            <div class="admin-card">
                <div class="card-header-flex">
                    <div>
                        <h4>Biến thể (Size, Màu sắc) & Tồn kho</h4>
                        <p class="admin-subtitle" style="margin-bottom: 0;">Mỗi biến thể có số lượng tồn kho riêng biệt</p>
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
                            <?php foreach ($submittedVariants as $idx => $v): ?>
                                <tr>
                                    <td>
                                        <input type="text" name="variant_size[]" class="form-control" 
                                               value="<?= e($v['size']) ?>" placeholder="S, M, L, XL...">
                                    </td>
                                    <td>
                                        <input type="text" name="variant_color[]" class="form-control" 
                                               value="<?= e($v['color']) ?>" placeholder="Đen, Trắng, Be...">
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
        </div>

        <!-- Cột phải: Danh mục, Trạng thái & Tải lên nhiều ảnh -->
        <div class="form-col-side">
            <div class="admin-card">
                <h4>Phân loại & Hiển thị</h4>

                <div class="form-group">
                    <label for="p-cat">Danh mục sản phẩm <span class="required">*</span></label>
                    <select id="p-cat" name="category_id" class="form-control" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="p-status">Trạng thái bán</label>
                    <select id="p-status" name="status" class="form-control">
                        <option value="1" <?= $status == 1 ? 'selected' : '' ?>>Đang bán (Hiển thị)</option>
                        <option value="0" <?= $status == 0 ? 'selected' : '' ?>>Tạm ẩn (Khóa)</option>
                    </select>
                </div>
            </div>

            <!-- Tải lên nhiều ảnh -->
            <div class="admin-card">
                <h4>Hình ảnh sản phẩm</h4>
                <p class="admin-subtitle">Chọn 1 hoặc nhiều ảnh (JPG, PNG, WEBP, tối đa 5MB/ảnh). Ảnh đầu tiên sẽ tự động làm ảnh đại diện chính.</p>
                
                <div class="form-group">
                    <input type="file" name="images[]" id="p-images" class="form-control" multiple accept="image/*">
                </div>

                <div id="image-preview" class="image-preview-grid"></div>
            </div>

            <!-- Nút hành động Submit -->
            <div class="admin-card form-submit-box">
                <button type="submit" class="button button-block">
                    💾 Lưu và xuất bản sản phẩm
                </button>
                <a href="<?= BASE_URL ?>/admin/products.php" class="button button-outline button-block" style="margin-top: 10px;">
                    Hủy bỏ
                </a>
            </div>
        </div>
    </div>
</form>

<script>
// Tự động tạo slug từ tên sản phẩm
const pName = document.getElementById('p-name');
const pSlug = document.getElementById('p-slug');
if (pName && pSlug) {
    pName.addEventListener('input', function() {
        if (!pSlug.dataset.manual) {
            pSlug.value = this.value.toLowerCase()
                .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                .replace(/[đĐ]/g, "d")
                .replace(/([^0-9a-z-\s])/g, '')
                .replace(/(\s+)/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    });
    pSlug.addEventListener('input', function() {
        this.dataset.manual = "true";
    });
}

// Thêm dòng biến thể động
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

// Xem trước ảnh tải lên
const pImages = document.getElementById('p-images');
const imagePreview = document.getElementById('image-preview');

if (pImages && imagePreview) {
    pImages.addEventListener('change', function() {
        imagePreview.innerHTML = '';
        if (this.files) {
            Array.from(this.files).forEach((file, idx) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        ${idx === 0 ? '<span class="preview-badge-main">Ảnh chính</span>' : ''}
                    `;
                    imagePreview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
