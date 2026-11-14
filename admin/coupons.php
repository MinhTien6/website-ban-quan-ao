<?php
/**
 * Quản lý mã giảm giá Admin (Admin Coupons - TV5 làm thêm)
 */

$pageTitle = 'Quản lý mã giảm giá';

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Coupon.php';

requireAdmin();

$couponModel = new Coupon($pdo);

$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editCoupon = null;

if ($action === 'edit' && $editId > 0) {
    $editCoupon = $couponModel->findById($editId);
    if (!$editCoupon) {
        setFlash('error', 'Không tìm thấy mã giảm giá yêu cầu.');
        redirect('admin/coupons.php');
    }
}

// Xử lý gửi biểu mẫu POST (Thêm mới, Cập nhật, Xóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // Kiểm tra bảo mật CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Mã xác thực CSRF không hợp lệ hoặc đã hết hạn.');
        redirect('admin/coupons.php');
    }

    if ($postAction === 'create') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discountType = $_POST['discount_type'] ?? 'percent';
        $discountValue = (float)($_POST['discount_value'] ?? 0);
        $minOrder = (float)($_POST['min_order'] ?? 0);
        $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $usageLimit = isset($_POST['usage_limit']) && $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;

        if ($code === '') {
            setFlash('error', 'Vui lòng nhập mã giảm giá.');
        } elseif ($discountValue <= 0) {
            setFlash('error', 'Giá trị giảm giá phải lớn hơn 0.');
        } elseif ($discountType === 'percent' && $discountValue > 100) {
            setFlash('error', 'Phần trăm giảm giá không được vượt quá 100%.');
        } else {
            try {
                $couponModel->create([
                    'code'           => $code,
                    'discount_type'  => $discountType,
                    'discount_value' => $discountValue,
                    'min_order'      => $minOrder,
                    'expires_at'     => $expiresAt,
                    'usage_limit'    => $usageLimit,
                ]);
                setFlash('success', "Thêm mới mã giảm giá \"{$code}\" thành công!");
                redirect('admin/coupons.php');
            } catch (Exception $e) {
                setFlash('error', 'Lỗi: ' . $e->getMessage());
            }
        }
    } elseif ($postAction === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discountType = $_POST['discount_type'] ?? 'percent';
        $discountValue = (float)($_POST['discount_value'] ?? 0);
        $minOrder = (float)($_POST['min_order'] ?? 0);
        $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $usageLimit = isset($_POST['usage_limit']) && $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;

        if ($id <= 0 || $code === '') {
            setFlash('error', 'Dữ liệu cập nhật không hợp lệ.');
        } elseif ($discountValue <= 0) {
            setFlash('error', 'Giá trị giảm giá phải lớn hơn 0.');
        } elseif ($discountType === 'percent' && $discountValue > 100) {
            setFlash('error', 'Phần trăm giảm giá không được vượt quá 100%.');
        } else {
            try {
                $couponModel->update($id, [
                    'code'           => $code,
                    'discount_type'  => $discountType,
                    'discount_value' => $discountValue,
                    'min_order'      => $minOrder,
                    'expires_at'     => $expiresAt,
                    'usage_limit'    => $usageLimit,
                ]);
                setFlash('success', "Cập nhật mã giảm giá \"{$code}\" thành công!");
                redirect('admin/coupons.php');
            } catch (Exception $e) {
                setFlash('error', 'Lỗi: ' . $e->getMessage());
            }
        }
    } elseif ($postAction === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $couponModel->delete($id);
                setFlash('success', 'Đã xóa mã giảm giá thành công.');
            } catch (Exception $e) {
                setFlash('error', $e->getMessage());
            }
        } else {
            setFlash('error', 'Mã không hợp lệ.');
        }
        redirect('admin/coupons.php');
    }
}

// Tìm kiếm và phân trang
$search = trim($_GET['search'] ?? '');
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$page = max(1, $page);
$perPage = 15;

$totalCoupons = $couponModel->count($search !== '' ? $search : null);
$pagination = paginate($totalCoupons, $page, $perPage);
$coupons = $couponModel->getAll($search !== '' ? $search : null, $pagination['limit'], $pagination['offset']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div class="page-title-group">
        <h3>🎟️ Quản lý mã giảm giá (Coupons)</h3>
        <p class="text-muted">Tạo, cập nhật và quản lý các chương trình ưu đãi, khuyến mãi giảm giá cho khách hàng.</p>
    </div>
</div>

<div class="admin-grid-layout">
    <!-- Cột trái: Form Thêm / Sửa mã giảm giá -->
    <div class="admin-form-col">
        <div class="admin-card">
            <h4><?= $editCoupon ? '✏️ Chỉnh sửa mã giảm giá' : '➕ Thêm mã giảm giá mới' ?></h4>
            <form action="<?= BASE_URL ?>/admin/coupons.php" method="POST" class="admin-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $editCoupon ? 'update' : 'create' ?>">
                <?php if ($editCoupon): ?>
                    <input type="hidden" name="id" value="<?= (int)$editCoupon['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="coupon-code">Mã giảm giá (Code) <span class="required">*</span></label>
                    <input type="text" id="coupon-code" name="code" class="form-control"
                           style="text-transform: uppercase; font-weight: 700; letter-spacing: 1px;"
                           value="<?= e($editCoupon['code'] ?? '') ?>" required
                           maxlength="30" placeholder="VD: SALE20, GIAM50K">
                    <small class="form-hint">Viết liền, không dấu (hệ thống tự chuyển thành chữ hoa).</small>
                </div>

                <div class="form-group">
                    <label for="discount-type">Loại giảm giá <span class="required">*</span></label>
                    <select id="discount-type" name="discount_type" class="form-control" required>
                        <option value="percent" <?= (!$editCoupon || $editCoupon['discount_type'] === 'percent') ? 'selected' : '' ?>>
                            Phần trăm (%)
                        </option>
                        <option value="amount" <?= ($editCoupon && $editCoupon['discount_type'] === 'amount') ? 'selected' : '' ?>>
                            Số tiền cố định (VNĐ)
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="discount-value">Mức giảm <span class="required">*</span></label>
                    <input type="number" id="discount-value" name="discount_value" class="form-control"
                           value="<?= (float)($editCoupon['discount_value'] ?? 0) ?>" required min="1" step="any"
                           placeholder="VD: 10 (nếu là %), 50000 (nếu là VNĐ)">
                    <small class="form-hint" id="discount-hint">Nếu chọn %, nhập từ 1 đến 100.</small>
                </div>

                <div class="form-group">
                    <label for="min-order">Đơn hàng tối thiểu (VNĐ)</label>
                    <input type="number" id="min-order" name="min_order" class="form-control"
                           value="<?= (float)($editCoupon['min_order'] ?? 0) ?>" min="0" step="1000"
                           placeholder="0 là không giới hạn">
                    <small class="form-hint">Giá trị giỏ hàng tối thiểu để được áp dụng mã này.</small>
                </div>

                <div class="form-group">
                    <label for="expires-at">Hạn sử dụng</label>
                    <?php
                        $expiresVal = '';
                        if (!empty($editCoupon['expires_at'])) {
                            $expiresVal = date('Y-m-d\TH:i', strtotime($editCoupon['expires_at']));
                        }
                    ?>
                    <input type="datetime-local" id="expires-at" name="expires_at" class="form-control"
                           value="<?= e($expiresVal) ?>">
                    <small class="form-hint">Để trống nếu không giới hạn thời gian.</small>
                </div>

                <div class="form-group">
                    <label for="usage-limit">Giới hạn số lượt dùng</label>
                    <input type="number" id="usage-limit" name="usage_limit" class="form-control"
                           value="<?= isset($editCoupon['usage_limit']) ? (int)$editCoupon['usage_limit'] : '' ?>"
                           min="1" placeholder="Để trống là không giới hạn">
                    <small class="form-hint">Tổng số đơn hàng tối đa có thể áp dụng mã này.</small>
                </div>

                <div class="form-actions" style="margin-top: 20px;">
                    <button type="submit" class="button">
                        <?= $editCoupon ? '💾 Cập nhật mã' : '➕ Thêm mã giảm giá' ?>
                    </button>
                    <?php if ($editCoupon): ?>
                        <a href="<?= BASE_URL ?>/admin/coupons.php" class="button button-outline">Hủy bỏ</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Cột phải: Danh sách mã giảm giá -->
    <div class="admin-table-col">
        <div class="admin-card">
            <div class="table-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 18px;">
                <h4>Danh sách mã giảm giá (<?= (int)$totalCoupons ?>)</h4>

                <form method="GET" action="<?= BASE_URL ?>/admin/coupons.php" style="display: flex; gap: 8px;">
                    <input type="text" name="search" class="form-control" style="width: 200px; padding: 7px 12px; font-size: 13.5px;"
                           placeholder="Tìm theo mã..." value="<?= e($search) ?>">
                    <button type="submit" class="button" style="padding: 7px 14px;">Tìm</button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= BASE_URL ?>/admin/coupons.php" class="button button-outline" style="padding: 7px 12px;">✕</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Mã Code</th>
                            <th>Loại giảm</th>
                            <th>Mức giảm</th>
                            <th>Đơn tối thiểu</th>
                            <th>Lượt dùng</th>
                            <th>Hạn dùng</th>
                            <th style="text-align: center;">Trạng thái</th>
                            <th style="width: 120px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coupons)): ?>
                            <tr>
                                <td colspan="9" class="table-empty" style="text-align: center; padding: 30px;">
                                    <?= $search !== '' ? 'Không tìm thấy mã giảm giá phù hợp.' : 'Chưa có mã giảm giá nào. Hãy tạo mã đầu tiên!' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($coupons as $c): ?>
                                <?php
                                    $isExpired = !empty($c['expires_at']) && strtotime($c['expires_at']) < time();
                                    $isExhausted = !empty($c['usage_limit']) && (int)$c['used_count'] >= (int)$c['usage_limit'];
                                ?>
                                <tr>
                                    <td><strong>#<?= (int)$c['id'] ?></strong></td>
                                    <td>
                                        <span style="display: inline-block; background: #e0f2fe; color: #0284c7; padding: 3px 8px; border-radius: 5px; font-weight: 700; font-size: 13px; letter-spacing: 0.5px;">
                                            <?= e($c['code']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $c['discount_type'] === 'percent' ? 'Phần trăm (%)' : 'Số tiền cố định' ?>
                                    </td>
                                    <td>
                                        <strong style="color: #a52a3a;">
                                            <?= $c['discount_type'] === 'percent'
                                                ? (float)$c['discount_value'] . '%'
                                                : formatPrice($c['discount_value']) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?= (float)$c['min_order'] > 0 ? formatPrice($c['min_order']) : 'Không giới hạn' ?>
                                    </td>
                                    <td>
                                        <strong><?= (int)$c['used_count'] ?></strong> / <?= !empty($c['usage_limit']) ? (int)$c['usage_limit'] : '∞' ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['expires_at'])): ?>
                                            <small style="color: <?= $isExpired ? '#dc2626' : '#475569' ?>;">
                                                <?= e(date('d/m/Y H:i', strtotime($c['expires_at']))) ?>
                                            </small>
                                        <?php else: ?>
                                            <small style="color: #64748b;">Vô thời hạn</small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($isExpired): ?>
                                            <span class="badge" style="background: #fee2e2; color: #b91c1c; padding: 3px 8px; border-radius: 12px; font-size: 11.5px; font-weight: 600;">
                                                Hết hạn
                                            </span>
                                        <?php elseif ($isExhausted): ?>
                                            <span class="badge" style="background: #fef3c7; color: #b45309; padding: 3px 8px; border-radius: 12px; font-size: 11.5px; font-weight: 600;">
                                                Hết lượt
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 12px; font-size: 11.5px; font-weight: 600;">
                                                Hoạt động
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <a href="<?= BASE_URL ?>/admin/coupons.php?action=edit&id=<?= (int)$c['id'] ?>"
                                           class="button-action edit" title="Chỉnh sửa">
                                            ✏️
                                        </a>

                                        <form action="<?= BASE_URL ?>/admin/coupons.php" method="POST"
                                              style="display: inline-block;"
                                              onsubmit="return confirm('Bạn chắc chắn muốn xóa mã giảm giá <?= e($c['code']) ?>?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="button-action delete" title="Xóa"
                                                    style="background: none; border: none; cursor: pointer;">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Phân trang -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div style="margin-top: 20px; display: flex; justify-content: center;">
                    <?php
                        $pageBaseUrl = BASE_URL . '/admin/coupons.php';
                        if ($search !== '') {
                            $pageBaseUrl .= '?search=' . urlencode($search);
                        }
                        echo renderPagination($pagination, $pageBaseUrl, 'page');
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
