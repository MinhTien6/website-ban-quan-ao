<?php
// Kiểm tra tự động CSDL shop_quanao sau khi import file SQL.
// Cách dùng: chép file này vào C:\xampp\htdocs\ rồi mở http://localhost/test_db.php
// Test xong NHỚ XÓA file này (không đưa lên host thật).

$host = 'localhost';
$db   = 'shop_quanao';
$user = 'root';
$pass = '';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('<h3 style="color:red">Không kết nối được CSDL: ' . htmlspecialchars($e->getMessage()) . '</h3>');
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

// 1. Số bảng và engine
$tables = (int) one($pdo, "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$db]);
check('Có đủ 14 bảng', $tables === 14, "tìm thấy $tables bảng");
$notInno = (int) one($pdo, "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND ENGINE <> 'InnoDB'", [$db]);
check('Tất cả bảng dùng InnoDB', $notInno === 0, "$notInno bảng sai engine");

// 2. Số khóa ngoại (kỳ vọng 16)
$fk = (int) one($pdo, "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'", [$db]);
check('Có đủ 16 khóa ngoại', $fk === 16, "tìm thấy $fk khóa ngoại");

// 3. Số dòng dữ liệu mẫu
$expected = ['users' => 4, 'categories' => 8, 'products' => 30, 'product_images' => 90,
             'product_variants' => 176, 'orders' => 5, 'order_items' => 9, 'coupons' => 4,
             'reviews' => 5, 'wishlists' => 4, 'banners' => 3];
foreach ($expected as $table => $n) {
    $got = (int) one($pdo, "SELECT COUNT(*) FROM `$table`");
    check("Bảng $table có $n dòng", $got === $n, "thực tế $got dòng");
}

// 4. Tiếng Việt có dấu
$name = (string) one($pdo, "SELECT name FROM products WHERE id = 1");
check('Tiếng Việt hiển thị đúng dấu', $name === 'Áo thun basic cotton cổ tròn', "đọc được: $name");

// 5. Mật khẩu demo
foreach (['admin@shop.local' => 'Admin@123', 'lan.nguyen@example.com' => 'User@123'] as $email => $pw) {
    $hash = (string) one($pdo, "SELECT password_hash FROM users WHERE email = ?", [$email]);
    check("password_verify đúng cho $email", password_verify($pw, $hash));
}
$role = (string) one($pdo, "SELECT role FROM users WHERE email = 'admin@shop.local'");
check('Tài khoản admin có role = admin', $role === 'admin');
$locked = (int) one($pdo, "SELECT COUNT(*) FROM users WHERE status = 0");
check('Có 1 tài khoản bị khóa để test', $locked === 1, "tìm thấy $locked");

// 6. Dữ liệu liên kết không bị mồ côi
$orphan = (int) one($pdo, "SELECT COUNT(*) FROM order_items oi LEFT JOIN orders o ON o.id = oi.order_id WHERE o.id IS NULL");
check('Không có order_items mồ côi', $orphan === 0);
$noMain = (int) one($pdo, "SELECT COUNT(*) FROM products p WHERE NOT EXISTS (SELECT 1 FROM product_images i WHERE i.product_id = p.id AND i.is_main = 1)");
check('Mỗi sản phẩm có đúng ảnh chính', $noMain === 0, "$noMain sản phẩm thiếu ảnh chính");
$noVar = (int) one($pdo, "SELECT COUNT(*) FROM products p WHERE NOT EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id)");
check('Mỗi sản phẩm có ít nhất 1 biến thể', $noVar === 0, "$noVar sản phẩm thiếu biến thể");

// 7. Tổng tiền đơn hàng = tiền hàng - giảm giá
$bad = [];
$orders = $pdo->query("SELECT o.id, o.total, c.discount_type, c.discount_value FROM orders o LEFT JOIN coupons c ON c.id = o.coupon_id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($orders as $o) {
    $sub = (int) one($pdo, "SELECT SUM(quantity * price) FROM order_items WHERE order_id = ?", [$o['id']]);
    $disc = 0;
    if ($o['discount_type'] === 'percent') $disc = (int) floor($sub * $o['discount_value'] / 100);
    if ($o['discount_type'] === 'amount')  $disc = (int) $o['discount_value'];
    if ($sub - $disc !== (int) $o['total']) $bad[] = $o['id'];
}
check('Tổng tiền các đơn tính đúng', count($bad) === 0, $bad ? 'sai ở đơn: ' . implode(',', $bad) : '');

// 8. Khóa ngoại thật sự chặn xóa (danh mục còn sản phẩm)
try {
    $pdo->exec("DELETE FROM categories WHERE id = 1");
    check('Khóa ngoại chặn xóa danh mục còn sản phẩm', false, 'xóa được -> khóa ngoại KHÔNG hoạt động!');
} catch (PDOException $e) {
    check('Khóa ngoại chặn xóa danh mục còn sản phẩm', true, 'đã bị từ chối đúng như mong đợi');
}

// 9. Ràng buộc unique email
try {
    $pdo->exec("INSERT INTO users (full_name, email, password_hash) VALUES ('Trùng', 'admin@shop.local', 'x')");
    check('Email trùng bị từ chối', false, 'vẫn thêm được email trùng!');
} catch (PDOException $e) {
    check('Email trùng bị từ chối', true);
}

// 10. Biến thể sắp hết hàng để demo
$low = (int) one($pdo, "SELECT COUNT(*) FROM product_variants WHERE stock <= 5");
check('Có biến thể tồn kho thấp để demo cảnh báo', $low >= 2, "tìm thấy $low");

// ----- In kết quả -----
$pass = count(array_filter($results, fn($r) => $r[1]));
$total = count($results);
echo '<style>body{font-family:Segoe UI,Arial;margin:30px}td,th{padding:6px 12px;border-bottom:1px solid #ddd;text-align:left}.ok{color:#15803d}.no{color:#b91c1c;font-weight:bold}</style>';
echo "<h2>Kết quả kiểm tra CSDL <code>$db</code>: $pass / $total đạt</h2>";
echo '<table><tr><th>#</th><th>Kiểm tra</th><th>Kết quả</th><th>Chi tiết</th></tr>';
foreach ($results as $i => [$n, $ok, $d]) {
    $cls = $ok ? 'ok' : 'no';
    echo '<tr><td>' . ($i + 1) . '</td><td>' . htmlspecialchars($n) . "</td><td class=\"$cls\">" . ($ok ? 'ĐẠT' : 'LỖI')
       . '</td><td>' . htmlspecialchars($d) . '</td></tr>';
}
echo '</table>';
echo $pass === $total
    ? '<h3 class="ok">Tất cả đều đạt. File SQL sẵn sàng đưa vào repo.</h3>'
    : '<h3 class="no">Có mục bị lỗi, hãy gửi ảnh chụp màn hình này để được sửa.</h3>';