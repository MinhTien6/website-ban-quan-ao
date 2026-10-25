<?php
/**
 * Các hàm tiện ích dùng chung trong toàn bộ hệ thống
 */

/**
 * Mã hóa an toàn chuỗi trước khi in ra HTML (chống XSS)
 *
 * @param mixed $value Dữ liệu cần in
 * @return string Chuỗi đã escape an toàn
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Chuyển hướng tới một trang tương đối tính từ BASE_URL
 *
 * @param string $path Đường dẫn (ví dụ: 'login.php' hoặc 'admin/index.php')
 * @return never
 */
function redirect(string $path): never
{
    $url = BASE_URL . '/' . ltrim($path, '/');
    header("Location: {$url}");
    exit;
}

/**
 * Định dạng số tiền sang định dạng tiền tệ Việt Nam (VNĐ)
 *
 * @param float|int|string|null $amount Số tiền
 * @return string Chuỗi định dạng, ví dụ: "150.000đ"
 */
function formatPrice(mixed $amount): string
{
    $number = (float)($amount ?? 0);
    return number_format($number, 0, ',', '.') . 'đ';
}

/**
 * Thiết lập thông báo nhanh (Flash message) lưu trong phiên
 *
 * @param string $type Loại thông báo ('success', 'error', 'info', 'warning')
 * @param string $message Nội dung thông báo
 * @return void
 */
function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Lấy thông báo Flash và tự động xóa khỏi phiên
 *
 * @return array{type: string, message: string}|null
 */
function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Chuyển chuỗi tiếng Việt có dấu thành chuỗi URL thân thiện (slug)
 *
 * @param string $string Chuỗi ban đầu (ví dụ: "Áo sơ mi kẻ sọc")
 * @return string Chuỗi slug chuẩn (ví dụ: "ao-so-mi-ke-soc")
 */
function createSlug(string $string): string
{
    $search = [
        'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
        'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
        'ì', 'í', 'ị', 'ỉ', 'ĩ',
        'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
        'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
        'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
        'đ',
        'À', 'Á', 'Ạ', 'Ả', 'Ã', 'Â', 'Ầ', 'Ấ', 'Ậ', 'Ẩ', 'Ẫ', 'Ă', 'Ằ', 'Ắ', 'Ặ', 'Ẳ', 'Ẵ',
        'È', 'É', 'Ẹ', 'Ẻ', 'Ẽ', 'Ê', 'Ề', 'Ế', 'Ệ', 'Ể', 'Ễ',
        'Ì', 'Í', 'Ị', 'Ỉ', 'Ĩ',
        'Ò', 'Ó', 'Ọ', 'Ỏ', 'Õ', 'Ô', 'Ồ', 'Ố', 'Ộ', 'Ổ', 'Ỗ', 'Ơ', 'Ờ', 'Ớ', 'Ợ', 'Ở', 'Ỡ',
        'Ù', 'Ú', 'Ụ', 'Ủ', 'Ũ', 'Ư', 'Ừ', 'Ứ', 'Ự', 'Ử', 'Ữ',
        'Ỳ', 'Ý', 'Ỵ', 'Ỷ', 'Ỹ',
        'Đ'
    ];

    $replace = [
        'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
        'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
        'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
        'y', 'y', 'y', 'y', 'y',
        'd',
        'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
        'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
        'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
        'y', 'y', 'y', 'y', 'y',
        'd'
    ];

    $slug = str_replace($search, $replace, $string);
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Xử lý tải lên tệp tin hình ảnh an toàn
 *
 * @param array $file Mảng $_FILES['tên_input']
 * @param string $subFolder Thư mục con trong public/uploads (vd: 'products', 'banners')
 * @param int $maxSizeMb Dung lượng tối đa tính bằng MB
 * @param array $allowedExtensions Các định dạng ảnh cho phép
 * @return array{success: bool, path?: string, error?: string}
 */
function uploadImage(
    array $file,
    string $subFolder = 'products',
    int $maxSizeMb = 5,
    array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp']
): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Tham số tệp tin tải lên không hợp lệ.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => ['success' => false, 'error' => "Dung lượng ảnh vượt quá giới hạn {$maxSizeMb}MB."],
            UPLOAD_ERR_NO_FILE => ['success' => false, 'error' => 'Chưa chọn tệp hình ảnh để tải lên.'],
            default => ['success' => false, 'error' => 'Lỗi trong quá trình tải tệp tin lên máy chủ.'],
        };
    }

    if ($file['size'] > $maxSizeMb * 1024 * 1024) {
        return ['success' => false, 'error' => "Dung lượng tệp vượt quá {$maxSizeMb}MB."];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['success' => false, 'error' => 'Chỉ chấp nhận các định dạng ảnh: ' . implode(', ', $allowedExtensions) . '.'];
    }

    // Kiểm tra tính hợp lệ thực sự của file ảnh
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'Tệp tin tải lên không phải là ảnh hợp lệ.'];
    }

    // Tạo thư mục nếu chưa tồn tại
    $targetDir = ROOT_PATH . '/public/uploads/' . trim($subFolder, '/');
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        return ['success' => false, 'error' => 'Không thể tạo thư mục lưu trữ ảnh trên máy chủ.'];
    }

    // Đặt tên tệp duy nhất để tránh ghi đè
    $newFileName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetFilePath = $targetDir . '/' . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return ['success' => false, 'error' => 'Không thể lưu tệp ảnh vào thư mục đích.'];
    }

    // Trả về đường dẫn tương đối lưu vào CSDL (khớp schema: uploads/products/...)
    $relativeDbPath = 'uploads/' . trim($subFolder, '/') . '/' . $newFileName;
    return [
        'success'  => true,
        'path'     => $relativeDbPath,
        'filename' => $newFileName,
    ];
}

/**
 * Tính toán dữ liệu phân trang
 *
 * @param int $totalItems Tổng số mục
 * @param int $currentPage Trang hiện tại
 * @param int $limit Số mục mỗi trang
 * @return array
 */
function paginate(int $totalItems, int $currentPage = 1, int $limit = 10): array
{
    $limit = max(1, $limit);
    $totalPages = (int)ceil($totalItems / $limit);
    if ($totalPages < 1) {
        $totalPages = 1;
    }

    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $limit;

    return [
        'total_items'  => $totalItems,
        'total_pages'  => $totalPages,
        'current_page' => $currentPage,
        'limit'        => $limit,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
    ];
}

/**
 * Sinh chuỗi HTML thanh phân trang (Pagination)
 *
 * @param array $pagination Mảng trả về từ hàm paginate()
 * @param string $baseUrl URL gốc (có thể chứa query string)
 * @param string $pageParam Tên tham số query phân trang (mặc định 'page')
 * @return string HTML thanh phân trang
 */
function renderPagination(array $pagination, string $baseUrl, string $pageParam = 'page'): string
{
    if (($pagination['total_pages'] ?? 1) <= 1) {
        return '';
    }

    $currentPage = $pagination['current_page'];
    $totalPages = $pagination['total_pages'];

    $separator = str_contains($baseUrl, '?') ? '&' : '?';

    $html = '<div class="pagination">';

    // Nút Trước
    if ($pagination['has_prev']) {
        $prevUrl = e($baseUrl . $separator . $pageParam . '=' . ($currentPage - 1));
        $html .= '<a href="' . $prevUrl . '" class="page-link page-prev">&laquo; Trước</a>';
    } else {
        $html .= '<span class="page-link page-disabled">&laquo; Trước</span>';
    }

    // Các số trang
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === $currentPage) {
            $html .= '<span class="page-link page-active">' . $i . '</span>';
        } elseif ($i <= 3 || $i >= $totalPages - 2 || abs($i - $currentPage) <= 1) {
            $pageUrl = e($baseUrl . $separator . $pageParam . '=' . $i);
            $html .= '<a href="' . $pageUrl . '" class="page-link">' . $i . '</a>';
        } elseif ($i === 4 && $currentPage > 4) {
            $html .= '<span class="page-ellipsis">...</span>';
        } elseif ($i === $totalPages - 3 && $currentPage < $totalPages - 3) {
            $html .= '<span class="page-ellipsis">...</span>';
        }
    }

    // Nút Sau
    if ($pagination['has_next']) {
        $nextUrl = e($baseUrl . $separator . $pageParam . '=' . ($currentPage + 1));
        $html .= '<a href="' . $nextUrl . '" class="page-link page-next">Sau &raquo;</a>';
    } else {
        $html .= '<span class="page-link page-disabled">Sau &raquo;</span>';
    }

    $html .= '</div>';
    return $html;
}

/**
 * Lấy hoặc tạo mã CSRF Token duy nhất trong phiên
 *
 * @return string Mã CSRF token
 */
function csrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Sinh trường input ẩn chứa CSRF Token dùng trong form HTML
 *
 * @return string HTML input ẩn
 */
function csrfField(): string
{
    $token = csrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Xác thực CSRF Token từ form POST
 *
 * @param string|null $token Token gửi lên từ form (mặc định lấy từ $_POST['csrf_token'])
 * @return bool True nếu hợp lệ, False nếu sai hoặc thiếu
 */
function verifyCsrfToken(?string $token = null): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $submittedToken = $token ?? ($_POST['csrf_token'] ?? '');
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if ($submittedToken === '' || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $submittedToken);
}

/**
 * Xử lý tải lên nhiều tệp tin hình ảnh cùng lúc (hỗ trợ TV3 quản lý sản phẩm)
 *
 * @param array $files Mảng $_FILES['tên_input'] (dạng đa tệp: files['name'][i], ...)
 * @param string $subFolder Thư mục con trong public/uploads
 * @param int $maxSizeMb Dung lượng tối đa mỗi ảnh (MB)
 * @param array $allowedExtensions Các định dạng ảnh cho phép
 * @return array{success: bool, files: array<array{path: string, filename: string}>, errors: array<string>}
 */
function uploadMultipleImages(
    array $files,
    string $subFolder = 'products',
    int $maxSizeMb = 5,
    array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp']
): array {
    $uploaded = [];
    $errors = [];

    // Kiểm tra cấu trúc mảng đa file của PHP
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [
            'success' => false,
            'files'   => [],
            'errors'  => ['Dữ liệu tệp tải lên không đúng định dạng mảng.'],
        ];
    }

    $fileCount = count($files['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        // Bỏ qua nếu người dùng không chọn file ở input này
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $singleFile = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];

        $result = uploadImage($singleFile, $subFolder, $maxSizeMb, $allowedExtensions);
        if ($result['success']) {
            $uploaded[] = [
                'path'     => $result['path'],
                'filename' => $result['filename'],
            ];
        } else {
            $fileName = e($files['name'][$i]);
            $errors[] = "Ảnh [{$fileName}]: " . ($result['error'] ?? 'Lỗi không xác định.');
        }
    }

    return [
        'success' => count($uploaded) > 0 && empty($errors),
        'files'   => $uploaded,
        'errors'  => $errors,
    ];
}

