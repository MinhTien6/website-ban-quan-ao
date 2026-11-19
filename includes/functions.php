<?php

function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    if (preg_match('#^https?://#i', $path)) {
        header("Location: {$path}");
        exit;
    }

    $cleanPath = ltrim($path, '/');
    $cleanBase = trim((string)BASE_URL, '/');

    if ($cleanBase !== '' && (str_starts_with($cleanPath, $cleanBase . '/') || $cleanPath === $cleanBase)) {
        $url = '/' . $cleanPath;
    } else {
        $url = BASE_URL . '/' . $cleanPath;
    }

    header("Location: {$url}");
    exit;
}

function formatPrice(mixed $amount): string
{
    $number = (float)($amount ?? 0);
    return number_format($number, 0, ',', '.') . 'đ';
}

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

function uploadImage(
    array $file,
    string $subFolder = 'products',
    int $maxSizeMb = 5,
    array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'],
    ?string $fileNamePrefix = null,
    int $sequence = 1
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

    
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'Tệp tin tải lên không phải là ảnh hợp lệ.'];
    }

    
    $targetDir = ROOT_PATH . '/public/uploads/' . trim($subFolder, '/');
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        return ['success' => false, 'error' => 'Không thể tạo thư mục lưu trữ ảnh trên máy chủ.'];
    }

    if ($fileNamePrefix === null) {
        $fileNameStem = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    } else {
        $safePrefix = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '-', $fileNamePrefix), '-_');
        if ($safePrefix === '') {
            return ['success' => false, 'error' => 'Tên sản phẩm không hợp lệ để đặt tên ảnh.'];
        }

        $fileNameStem = strtolower($safePrefix) . '-' . max(1, $sequence);
    }

    
    $newFileName = $fileNameStem . '.' . $extension;
    $collisionNumber = 2;
    while (file_exists($targetDir . '/' . $newFileName)) {
        $newFileName = $fileNameStem . '-' . $collisionNumber . '.' . $extension;
        $collisionNumber++;
    }
    $targetFilePath = $targetDir . '/' . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return ['success' => false, 'error' => 'Không thể lưu tệp ảnh vào thư mục đích.'];
    }

    
    $relativeDbPath = 'uploads/' . trim($subFolder, '/') . '/' . $newFileName;
    return [
        'success'  => true,
        'path'     => $relativeDbPath,
        'filename' => $newFileName,
    ];
}

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

function renderPagination(array $pagination, string $baseUrl, string $pageParam = 'page'): string
{
    if (($pagination['total_pages'] ?? 1) <= 1) {
        return '';
    }

    $currentPage = $pagination['current_page'];
    $totalPages = $pagination['total_pages'];

    $separator = str_contains($baseUrl, '?') ? '&' : '?';

    $html = '<div class="pagination">';

    
    if ($pagination['has_prev']) {
        $prevUrl = e($baseUrl . $separator . $pageParam . '=' . ($currentPage - 1));
        $html .= '<a href="' . $prevUrl . '" class="page-link page-prev">&laquo; Trước</a>';
    } else {
        $html .= '<span class="page-link page-disabled">&laquo; Trước</span>';
    }

    
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

    
    if ($pagination['has_next']) {
        $nextUrl = e($baseUrl . $separator . $pageParam . '=' . ($currentPage + 1));
        $html .= '<a href="' . $nextUrl . '" class="page-link page-next">Sau &raquo;</a>';
    } else {
        $html .= '<span class="page-link page-disabled">Sau &raquo;</span>';
    }

    $html .= '</div>';
    return $html;
}

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

function csrfField(): string
{
    $token = csrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

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

function uploadMultipleImages(
    array $files,
    string $subFolder = 'products',
    int $maxSizeMb = 5,
    array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'],
    ?string $fileNamePrefix = null
): array {
    $uploaded = [];
    $errors = [];

    
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [
            'success' => false,
            'files'   => [],
            'errors'  => ['Dữ liệu tệp tải lên không đúng định dạng mảng.'],
        ];
    }

    $fileCount = count($files['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        
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

        $result = uploadImage($singleFile, $subFolder, $maxSizeMb, $allowedExtensions, $fileNamePrefix, $i + 1);
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

if (file_exists(__DIR__ . '/cart_functions.php')) {
    require_once __DIR__ . '/cart_functions.php';
}
