<?php
/**
 * Quản lý danh mục sản phẩm (Admin Categories - TV3)
 */

$pageTitle = 'Quản lý danh mục';
require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/models/Category.php';

$categoryModel = new Category($pdo);

$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editCategory = null;

if ($action === 'edit' && $editId > 0) {
    $editCategory = $categoryModel->getById($editId);
    if (!$editCategory) {
        setFlash('error', 'Không tìm thấy danh mục yêu cầu.');
        redirect('admin/categories.php');
    }
}

// Xử lý gửi biểu mẫu POST (Thêm mới, Cập nhật, Xóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // Kiểm tra bảo mật CSRF
    if (!verifyCsrfToken()) {
        setFlash('error', 'Mã xác thực CSRF không hợp lệ hoặc đã hết hạn.');
        redirect('admin/categories.php');
    }

    if ($postAction === 'create') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        if ($name === '') {
            setFlash('error', 'Vui lòng nhập tên danh mục.');
        } else {
            if ($slug === '') {
                $slug = createSlug($name);
            } else {
                $slug = createSlug($slug);
            }

            // Kiểm tra trùng slug
            $existing = $categoryModel->getBySlug($slug);
            if ($existing) {
                $slug .= '-' . time();
            }

            try {
                $categoryModel->create([
                    'name'        => $name,
                    'slug'        => $slug,
                    'description' => $description,
                    'parent_id'   => $parentId,
                    'status'      => $status,
                ]);
                setFlash('success', 'Thêm mới danh mục thành công!');
                redirect('admin/categories.php');
            } catch (Exception $e) {
                setFlash('error', 'Lỗi khi tạo danh mục: ' . $e->getMessage());
            }
        }
    } elseif ($postAction === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        // Tránh chọn chính nó làm danh mục cha
        if ($parentId === $id) {
            $parentId = null;
        }

        if ($name === '') {
            setFlash('error', 'Vui lòng nhập tên danh mục.');
        } else {
            if ($slug === '') {
                $slug = createSlug($name);
            } else {
                $slug = createSlug($slug);
            }

            try {
                $categoryModel->update($id, [
                    'name'        => $name,
                    'slug'        => $slug,
                    'description' => $description,
                    'parent_id'   => $parentId,
                    'status'      => $status,
                ]);
                setFlash('success', 'Cập nhật danh mục thành công!');
                redirect('admin/categories.php');
            } catch (Exception $e) {
                setFlash('error', 'Lỗi khi cập nhật danh mục: ' . $e->getMessage());
            }
        }
    } elseif ($postAction === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $categoryModel->delete($id);
                setFlash('success', 'Xóa danh mục thành công!');
            } catch (Exception $e) {
                setFlash('error', $e->getMessage());
            }
        }
        redirect('admin/categories.php');
    }
}

// Lấy danh sách danh mục
$allCategories = $categoryModel->getAll();
// Tạo danh sách tra cứu danh mục cha
$categoryLookup = [];
foreach ($allCategories as $cat) {
    $categoryLookup[$cat['id']] = $cat['name'];
}
?>

<div class="admin-page-header">
    <div>
        <h3>Quản lý danh mục sản phẩm</h3>
        <p class="admin-subtitle">Phân loại hàng hóa giúp khách hàng dễ dàng tìm kiếm sản phẩm (Nhiệm vụ TV3)</p>
    </div>
</div>

<div class="admin-grid-layout">
    <!-- Cột trái: Form Thêm / Sửa danh mục -->
    <div class="admin-form-col">
        <div class="admin-card">
            <h4><?= $editCategory ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' ?></h4>
            <form action="<?= BASE_URL ?>/admin/categories.php" method="POST" class="admin-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $editCategory ? 'update' : 'create' ?>">
                <?php if ($editCategory): ?>
                    <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="cat-name">Tên danh mục <span class="required">*</span></label>
                    <input type="text" id="cat-name" name="name" class="form-control" 
                           value="<?= e($editCategory['name'] ?? '') ?>" required placeholder="Ví dụ: Áo sơ mi nam">
                </div>

                <div class="form-group">
                    <label for="cat-slug">Đường dẫn thân thiện (Slug)</label>
                    <input type="text" id="cat-slug" name="slug" class="form-control" 
                           value="<?= e($editCategory['slug'] ?? '') ?>" placeholder="Để trống hệ thống sẽ tự sinh">
                    <small class="form-hint">Ví dụ: ao-so-mi-nam (chỉ gồm chữ thường không dấu và gạch ngang)</small>
                </div>

                <div class="form-group">
                    <label for="cat-parent">Danh mục cha</label>
                    <select id="cat-parent" name="parent_id" class="form-control">
                        <option value="">-- Là danh mục gốc --</option>
                        <?php foreach ($allCategories as $parent): ?>
                            <?php if ($editCategory && $parent['id'] == $editCategory['id']) continue; ?>
                            <option value="<?= $parent['id'] ?>" <?= ($editCategory && $editCategory['parent_id'] == $parent['id']) ? 'selected' : '' ?>>
                                <?= e($parent['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cat-desc">Mô tả ngắn</label>
                    <textarea id="cat-desc" name="description" class="form-control" rows="3" 
                              placeholder="Mô tả tóm tắt về loại sản phẩm này..."><?= e($editCategory['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="cat-status">Trạng thái hiển thị</label>
                    <select id="cat-status" name="status" class="form-control">
                        <option value="1" <?= (!$editCategory || $editCategory['status'] == 1) ? 'selected' : '' ?>>Đang hoạt động (Hiển thị)</option>
                        <option value="0" <?= ($editCategory && $editCategory['status'] == 0) ? 'selected' : '' ?>>Tạm ẩn (Khóa)</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button">
                        <?= $editCategory ? '💾 Cập nhật danh mục' : '➕ Thêm danh mục' ?>
                    </button>
                    <?php if ($editCategory): ?>
                        <a href="<?= BASE_URL ?>/admin/categories.php" class="button button-outline">Hủy bỏ</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Cột phải: Bảng danh sách danh mục -->
    <div class="admin-table-col">
        <div class="admin-card">
            <div class="table-card-header">
                <h4>Danh sách danh mục hiện có (<?= count($allCategories) ?>)</h4>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Tên danh mục</th>
                            <th>Slug</th>
                            <th>Danh mục cha</th>
                            <th style="width: 100px; text-align: center;">Sản phẩm</th>
                            <th style="width: 110px; text-align: center;">Trạng thái</th>
                            <th style="width: 130px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allCategories)): ?>
                            <tr>
                                <td colspan="7" class="table-empty">Chưa có danh mục nào. Hãy tạo danh mục đầu tiên!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allCategories as $cat): ?>
                                <tr>
                                    <td><strong>#<?= $cat['id'] ?></strong></td>
                                    <td>
                                        <div class="cat-title-cell">
                                            <strong><?= e($cat['name']) ?></strong>
                                            <?php if (!empty($cat['description'])): ?>
                                                <small class="cat-desc"><?= e($cat['description']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><code class="slug-badge"><?= e($cat['slug']) ?></code></td>
                                    <td>
                                        <?= !empty($cat['parent_id']) && isset($categoryLookup[$cat['parent_id']]) 
                                            ? '<span class="badge-parent">📁 ' . e($categoryLookup[$cat['parent_id']]) . '</span>' 
                                            : '<span class="text-muted">Gốc</span>' ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-count"><?= (int)$cat['total_products'] ?> SP</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($cat['status'] == 1): ?>
                                            <span class="badge-status badge-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-inactive">Đang ẩn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-buttons">
                                            <a href="<?= BASE_URL ?>/admin/categories.php?action=edit&id=<?= $cat['id'] ?>" 
                                               class="btn-action btn-edit" title="Chỉnh sửa">✏️</a>
                                            
                                            <form action="<?= BASE_URL ?>/admin/categories.php" method="POST" 
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục \'<?= e(addslashes($cat['name'])) ?>\'?');" 
                                                  style="display: inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                <button type="submit" class="btn-action btn-delete" title="Xóa danh mục">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Tự động sinh slug khi người dùng gõ tên danh mục
const nameInput = document.getElementById('cat-name');
const slugInput = document.getElementById('cat-slug');

if (nameInput && slugInput) {
    nameInput.addEventListener('input', function() {
        <?php if (!$editCategory): ?>
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
