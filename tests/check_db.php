<?php
/**
 * Kiểm tra tự động CSDL shop_quanao sau khi import file database/shop_quanao.sql
 * Hoạt động tốt trên cả trình duyệt web và dòng lệnh (CLI).
 */

$isCli = (php_sapi_name() === 'cli');

// Tải cấu hình CSDL nếu có
$configFile = dirname(__DIR__) . '/config/database.php';
if (file_exists($configFile)) {
    require_once $configFile;
} else {
    $host = 'localhost';
    $dbname = 'shop_quanao';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        if ($isCli) {
            echo "LỖI: Không kết nối được CSDL: " . $e->getMessage() . "\n";
        } else {
            die('<h3 style="color:red">Không kết nối được CSDL: ' . htmlspecialchars($e->getMessage()) . '</h3>');
        }
        exit(1);
    }
}

$db = $dbname ?? 'shop_quanao';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
}

$results = [];
function check(string $name, bool $ok, string $detail = ''): void {
    global $results;
    $results[] = [$name, $ok, $detail];
}

function one(PDO $pdo, string $sql, array $params = []) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

// 1. Kiểm tra 7 bảng cốt lõi (theo ERD)
$coreTables = ['users', 'categories', 'products', 'product_images', 'product_variants', 'orders', 'order_items'];
$missingCore = [];
foreach ($coreTables as $ct) {
    $exists = (int) one($pdo, "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [$db, $ct]);
    if ($exists === 0) {
        $missingCore[] = $ct;
    }
}
check('Có đủ 7 bảng cốt lõi theo ERD', count($missingCore) === 0, count($missingCore) ? 'Thiếu: ' . implode(', ', $missingCore) : 'Đủ 7 bảng');

// 2. Tổng số bảng trong CSDL
$totalTables = (int) one($pdo, "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$db]);
check('CSDL chứa các bảng hệ thống', $totalTables >= 7, "Tìm thấy $totalTables bảng");

$notInno = (int) one($pdo, "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND ENGINE <> 'InnoDB'", [$db]);
check('Tất cả bảng dùng InnoDB', $notInno === 0, "$notInno bảng sai engine");

// 3. Khóa ngoại liên kết
$fk = (int) one($pdo, "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'", [$db]);
check('Có đầy đủ các ràng buộc khóa ngoại (FK)', $fk >= 7, "Tìm thấy $fk khóa ngoại");

// 4. Số dòng dữ liệu mẫu cho các bảng chính
$expected = [
    'users'            => 4,
    'categories'       => 8,
    'products'         => 30,
    'product_images'   => 90,
    'product_variants' => 176,
    'orders'           => 5,
    'order_items'      => 9,
];
foreach ($expected as $table => $n) {
    $got = (int) one($pdo, "SELECT COUNT(*) FROM `{$table}`");
    check("Bảng {$table} có ít nhất {$n} dòng mẫu", $got >= $n, "Thực tế {$got} dòng");
}

// 5. Kiểm tra Tiếng Việt có dấu UTF-8
$name = (string) one($pdo, "SELECT name FROM products WHERE id = 1");
check('Tiếng Việt hiển thị đúng dấu utf8mb4', $name === 'Áo thun basic cotton cổ tròn', "Đọc được: {$name}");

// 6. Mật khẩu mẫu bảo mật bằng bcrypt
foreach (['admin@shop.local' => 'Admin@123', 'lan.nguyen@example.com' => 'User@123'] as $email => $pw) {
    $hash = (string) one($pdo, "SELECT password_hash FROM users WHERE email = ?", [$email]);
    check("password_verify hợp lệ cho {$email}", password_verify($pw, $hash));
}

$role = (string) one($pdo, "SELECT role FROM users WHERE email = 'admin@shop.local'");
check('Tài khoản admin có role = admin', $role === 'admin');

$locked = (int) one($pdo, "SELECT COUNT(*) FROM users WHERE status = 0");
check('Có ít nhất 1 tài khoản bị khóa để test', $locked >= 1, "Tìm thấy {$locked}");

// 7. Dữ liệu liên kết không bị mồ côi
$orphan = (int) one($pdo, "SELECT COUNT(*) FROM order_items oi LEFT JOIN orders o ON o.id = oi.order_id WHERE o.id IS NULL");
check('Không có order_items mồ côi', $orphan === 0);

$noMain = (int) one($pdo, "SELECT COUNT(*) FROM products p WHERE NOT EXISTS (SELECT 1 FROM product_images i WHERE i.product_id = p.id AND i.is_main = 1)");
check('Mỗi sản phẩm có đúng ảnh chính', $noMain === 0, "{$noMain} sản phẩm thiếu ảnh chính");

$noVar = (int) one($pdo, "SELECT COUNT(*) FROM products p WHERE NOT EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id)");
check('Mỗi sản phẩm có ít nhất 1 biến thể', $noVar === 0, "{$noVar} sản phẩm thiếu biến thể");

// 8. Ràng buộc toàn vẹn: Không được trùng email
try {
    $pdo->exec("INSERT INTO users (full_name, email, password_hash) VALUES ('Trùng Email', 'admin@shop.local', 'dummy_hash')");
    check('Ràng buộc UNIQUE Email hoạt động', false, 'Vẫn thêm được email trùng!');
} catch (PDOException $e) {
    check('Ràng buộc UNIQUE Email hoạt động', true, 'Đã chặn trùng email chính xác');
}

// 9. Biến thể sắp hết hàng để demo cảnh báo
$low = (int) one($pdo, "SELECT COUNT(*) FROM product_variants WHERE stock <= 5");
check('Có biến thể tồn kho thấp để demo cảnh báo', $low >= 2, "Tìm thấy {$low} biến thể");

// Xuất kết quả
$pass = count(array_filter($results, fn($r) => $r[1]));
$total = count($results);

if ($isCli) {
    echo "========================================================\n";
    echo " KẾT QUẢ KIỂM TRA CSDL `{$db}`: {$pass} / {$total} ĐẠT\n";
    echo "========================================================\n";
    foreach ($results as $i => [$n, $ok, $d]) {
        $status = $ok ? "[PASS]" : "[FAIL]";
        echo sprintf("%2d. %-6s %-45s | %s\n", $i + 1, $status, $n, $d);
    }
    echo "========================================================\n";
    if ($pass === $total) {
        echo "TẤT CẢ ĐỀU ĐẠT. CSDL VÀ DỮ LIỆU MẪU SẴN SÀNG CHO NHÓM!\n";
    } else {
        echo "CÓ MỤC BỊ LỖI, VUI LÒNG KIỂM TRA LẠI DỮ LIỆU IMPORT.\n";
    }
} else {
    echo '<style>body{font-family:Segoe UI,Arial;margin:30px}td,th{padding:8px 14px;border-bottom:1px solid #ddd;text-align:left}.ok{color:#15803d;font-weight:bold}.no{color:#b91c1c;font-weight:bold}</style>';
    echo "<h2>Kết quả kiểm tra CSDL <code>{$db}</code>: {$pass} / {$total} đạt</h2>";
    echo '<table><tr><th>#</th><th>Tiêu chí kiểm tra</th><th>Kết quả</th><th>Chi tiết</th></tr>';
    foreach ($results as $i => [$n, $ok, $d]) {
        $cls = $ok ? 'ok' : 'no';
        echo '<tr><td>' . ($i + 1) . '</td><td>' . htmlspecialchars($n) . "</td><td class=\"{$cls}\">" . ($ok ? 'ĐẠT' : 'LỖI')
           . '</td><td>' . htmlspecialchars($d) . '</td></tr>';
    }
    echo '</table>';
    echo $pass === $total
        ? '<h3 class="ok" style="margin-top:20px;">Tất cả đều đạt. File database/shop_quanao.sql chuẩn sàng cho cả nhóm.</h3>'
        : '<h3 class="no" style="margin-top:20px;">Có mục bị lỗi, vui lòng kiểm tra lại quá trình import SQL.</h3>';
}