# HƯỚNG DẪN CÀI ĐẶT VÀ CHẠY DỰ ÁN WEBSITE BÁN QUẦN ÁO THỜI TRANG (VIBEZ.CLUB)

Tài liệu này hướng dẫn chi tiết các bước thiết lập, cấu hình cơ sở dữ liệu và vận hành hệ thống website trên môi trường cục bộ (Localhost) sử dụng XAMPP.

---

## 1. Yêu cầu hệ thống (Prerequisites)

Để khởi chạy dự án, máy tính cần cài đặt sẵn:
- **XAMPP** (Phiên bản khuyến nghị: PHP 8.0 trở lên).
- Dịch vụ **Apache** Web Server.
- Dịch vụ **MySQL / MariaDB** Server.
- Trình duyệt web hiện đại: Google Chrome, Microsoft Edge, Cốc Cốc, Firefox...

---

## 2. Vị trí đặt mã nguồn dự án

Thư mục mã nguồn dự án cần được đặt bên trong thư mục `htdocs` của XAMPP:
```text
C:\xampp\htdocs\website-ban-quan-ao\
(hoặc đường dẫn thư mục XAMPP trên máy bạn, ví dụ: E:\XM\htdocs\website-ban-quan-ao\)
```

---

## 3. Các bước khởi chạy dự án

### Bước 1: Khởi động dịch vụ trên XAMPP
1. Mở ứng dụng **XAMPP Control Panel**.
2. Tại dòng **Apache**, nhấn nút **Start**.
3. Tại dòng **MySQL**, nhấn nút **Start**.
> *Lưu ý: Cả 2 mục chuyển sang màu xanh lá cây và hiển thị số cổng hoạt động (Apache: 80/443, MySQL: 3306) là đã khởi động thành công.*

---

### Bước 2: Thiết lập Cơ sở dữ liệu (Database)

Có 2 cách thuận tiện để nạp dữ liệu:

#### Cách 1: Nạp qua phpMyAdmin (Giao diện web trực quan)
1. Mở trình duyệt và truy cập: `http://127.0.0.1/phpmyadmin/`
2. Tạo mới một cơ sở dữ liệu:
   - Tên cơ sở dữ liệu: `shop_quanao`
   - Bảng mã (Collation): `utf8mb4_unicode_ci`
3. Nhấp vào tab **Import** (Nhập).
4. Chọn tệp `database/shop_quanao.sql` từ thư mục dự án.
5. Nhấn nút **Import** ở cuối trang để hoàn tất quá trình nạp dữ liệu.

#### Cách 2: Khởi tạo nhanh bằng lệnh PHP CLI
Mở terminal hoặc PowerShell tại thư mục dự án và chạy lệnh:
```bash
php database/apply_new_30_products.php
```

---

### Bước 3: Kiểm tra cấu hình kết nối CSDL

File cấu hình kết nối CSDL nằm tại `config/database.php`:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'shop_quanao');
define('DB_USER', 'root');
define('DB_PASS', '');
```
*(Nếu MySQL của bạn có đặt mật khẩu `root`, hãy điền mật khẩu vào `DB_PASS`).*

---

### Bước 4: Kiểm tra toàn vẹn CSDL (Tùy chọn)

Để kiểm tra nhanh xem hệ thống đã nạp đủ bảng, dữ liệu 30 sản phẩm và khóa ngoại hay chưa, bạn có thể chạy:
```bash
php tests/check_db.php
```
*(Hệ thống sẽ chạy kiểm thử tự động và trả về kết quả 21/21 tiêu chí đạt chuẩn).*

---

## 4. Đường dẫn truy cập hệ thống

Mở trình duyệt web và truy cập vào các liên kết sau:

| Mục tiêu | Đường dẫn truy cập (Khuyến nghị dùng IP 127.0.0.1) | Chức năng chính |
| :--- | :--- | :--- |
| **Trang khách hàng** | [http://127.0.0.1/website-ban-quan-ao/](http://127.0.0.1/website-ban-quan-ao/) | Xem sản phẩm, tìm kiếm, lọc danh mục & giá, xem chi tiết biến thể size/màu, giỏ hàng, đặt hàng |
| **Trang quản trị (Admin)** | [http://127.0.0.1/website-ban-quan-ao/admin/](http://127.0.0.1/website-ban-quan-ao/admin/) | Quản lý sản phẩm, danh mục, đơn hàng, người dùng, mã giảm giá, thống kê doanh thu |
| **Cơ sở dữ liệu** | [http://127.0.0.1/phpmyadmin/](http://127.0.0.1/phpmyadmin/) | Quản lý dữ liệu trực tiếp trên phpMyAdmin |

---

## 5. Danh sách tài khoản thử nghiệm (Demo Accounts)

Dữ liệu mẫu đã được tích hợp sẵn các tài khoản sau để phục vụ trải nghiệm và kiểm thử:

| Vai trò | Email đăng nhập | Mật khẩu | Quyền hạn / Mục đích kiểm thử |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@shop.local` | `Admin@123` | Toàn quyền trang quản trị (`/admin`) |
| **Khách hàng 1 (User)** | `lan.nguyen@example.com` | `User@123` | Đặt hàng, tra cứu lịch sử đơn mua, đổi mật khẩu, sửa hồ sơ |
| **Khách hàng 2 (User)** | `minh.tran@example.com` | `User@123` | Tài khoản khách hàng thông thường |
| **Tài khoản bị khóa** | `khoa@example.com` | `User@123` | Kiểm tra thông báo lỗi khi tài khoản bị khóa (`status = 0`) |

---

## 6. Lưu ý và khắc phục sự cố thường gặp

1. **Trình duyệt bị quay tròn (loading) vô tận khi gõ `localhost`:**
   - **Nguyên nhân:** Trình duyệt Chrome tự động ép sang giao thức bảo mật `https://` hoặc bị trễ phân giải DNS IPv6 (`::1`).
   - **Cách xử lý:** Luôn sử dụng địa chỉ IP trực tiếp kèm `http://`:
     👉 `http://127.0.0.1/website-ban-quan-ao/`

2. **Lỗi `MySQL shutdown unexpectedly` trên XAMPP Control Panel:**
   - **Nguyên nhân:** Do máy tính bị tắt đột ngột, sleep khiến tệp nhật ký giao dịch InnoDB của MySQL bị lỗi checkpoint.
   - **Cách xử lý:** Sao chép các tệp sạch từ thư mục `xampp/mysql/backup` sang `xampp/mysql/data`, sau đó import lại tệp `database/shop_quanao.sql` và khởi động lại MySQL.

3. **Giao diện không cập nhật thay đổi:**
   - Nhấn tổ hợp phím **Ctrl + F5** (hoặc **Shift + F5**) trên trình duyệt để xóa bộ nhớ đệm (cache) và tải lại trang mới nhất.
