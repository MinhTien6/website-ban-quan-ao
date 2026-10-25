# KẾ HOẠCH VÀ BẢNG TEST CASE HỆ THỐNG
**Dự án:** Website Bán Quần Áo (shop_quanao)  
**Phụ trách chung:** TV1 (Kiến trúc, nền tảng) & TV6 (Điều phối kiểm thử)

---

## 1. Hướng dẫn kiểm tra CSDL tự động (Check DB)
Sau khi import tệp `database/shop_quanao.sql` vào MySQL/phpMyAdmin:
- **Cách 1 (Dòng lệnh CLI):**
  ```bash
  php tests/check_db.php
  ```
- **Cách 2 (Trình duyệt):**
  Truy cập: `http://localhost/website-ban-quan-ao/tests/check_db.php`
- Đảm bảo 100% các tiêu chí (bảng cốt lõi, khóa ngoại, dữ liệu mẫu, tiếng Việt UTF-8, mật khẩu hash) đều báo **PASS / ĐẠT**.

---

## 2. Bảng Test Case chi tiết theo 5 nhóm kiểm thử

### Nhóm 1: Chức năng xác thực & Quản trị cơ bản (Tuần 1 & 2 - TV1, TV2, TV3)

| Mã TC | Chức năng | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **AUTH_01** | Đăng ký hợp lệ | 1. Vào `/register.php`<br>2. Nhập họ tên, email mới, mật khẩu $\ge$ 8 ký tự<br>3. Bấm "Đăng ký" | Tạo tài khoản role `user`, thông báo thành công và chuyển sang `/login.php` | | Chờ test |
| **AUTH_02** | Đăng ký trùng email | 1. Nhập email đã tồn tại (`lan.nguyen@example.com`)<br>2. Bấm "Đăng ký" | Báo lỗi email đã được sử dụng, không tạo tài khoản mới | | Chờ test |
| **AUTH_03** | Đăng nhập User | 1. Vào `/login.php`<br>2. Nhập `lan.nguyen@example.com` / `User@123` | Đăng nhập thành công, chuyển hướng về trang chủ `/index.php`, menu hiện tên user | | Chờ test |
| **AUTH_04** | Đăng nhập Admin | 1. Vào `/login.php`<br>2. Nhập `admin@shop.local` / `Admin@123` | Đăng nhập thành công, tự động chuyển hướng vào `/admin/index.php` | | Chờ test |
| **AUTH_05** | Tài khoản bị khóa | 1. Nhập email `khoa@example.com` / `User@123` | Báo lỗi "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên." | | Chờ test |
| **AUTH_06** | Đăng xuất | 1. Bấm nút "Đăng xuất" trên thanh điều hướng | Hủy session, cookie phiên, thông báo flash và quay về `/login.php` | | Chờ test |

---

### Nhóm 2: Phân quyền & Kiểm soát truy cập (TV1, TV2, TV6)

| Mã TC | Mục tiêu kiểm thử | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **PERM_01** | Khách vào trang Admin | 1. Chưa đăng nhập<br>2. Truy cập trực tiếp URL: `/admin/index.php` | Bị chặn, hiện thông báo "Vui lòng đăng nhập để tiếp tục", chuyển hướng sang `/login.php` | | Chờ test |
| **PERM_02** | User thường vào trang Admin | 1. Đăng nhập tài khoản User (`lan.nguyen@example.com`)<br>2. Truy cập `/admin/index.php` | Bị chặn bởi `requireAdmin()`, báo "Bạn không có quyền truy cập vào trang quản trị", chuyển về `/index.php` | | Chờ test |
| **PERM_03** | Khách vào trang cần login | 1. Khách bấm xem lịch sử đơn hoặc trang tài khoản | Chuyển hướng sang `/login.php` | | Chờ test |

---

### Nhóm 3: Bảo mật chung (TV1, TV6)

| Mã TC | Tiêu chí bảo mật | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **SEC_01** | Chống SQL Injection | Nhập `' OR '1'='1` vào ô email / mật khẩu khi đăng nhập hoặc ô tìm kiếm | Truy vấn dùng Prepared Statement an toàn, không lộ lỗi CSDL hay bypass đăng nhập | | Chờ test |
| **SEC_02** | Chống XSS (Cross-Site Scripting) | Nhập `<script>alert('XSS')</script>` vào ô họ tên khi đăng ký hoặc mô tả | Dữ liệu được escape qua `e()` / `htmlspecialchars`, script không bị thực thi | | Chờ test |
| **SEC_03** | Chống CSRF (Cross-Site Request Forgery) | Submit form POST không có trường `csrf_token` hoặc token giả | Bị từ chối: "Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn" | | Chờ test |
| **SEC_04** | Kiểm tra tải lên file ảnh | Thử tải lên file đuôi `.php`, `.exe` hoặc file giả mạo MIME type | Bị chặn bởi hàm `uploadImage()`, chỉ cho phép `jpg, jpeg, png, webp`, kiểm tra qua `getimagesize()` | | Chờ test |

---

### Nhóm 4: Luồng mua hàng & Đơn hàng End-to-End (Tuần 3 - TV5, TV6)

| Mã TC | Quy trình | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **E2E_01** | Luồng mua hàng hoàn chỉnh | 1. Khách xem sản phẩm trên trang chủ<br>2. Thêm sản phẩm (chọn size, màu) vào giỏ hàng session<br>3. Vào giỏ hàng điều chỉnh số lượng<br>4. Tiến hành thanh toán (chọn COD)<br>5. Đăng nhập và đặt hàng | Đơn hàng tạo thành công trong bảng `orders`, trừ đúng số lượng tồn trong `product_variants` | | Chờ test |
| **E2E_02** | Hủy đơn hàng | Khách hàng vào xem lịch sử đơn, bấm "Hủy đơn" khi đơn còn ở trạng thái `pending` | Đơn chuyển sang `cancelled`, tồn kho các biến thể được hoàn lại đúng số lượng | | Chờ test |
| **E2E_03** | Admin duyệt đơn | Admin vào quản lý đơn hàng, đổi trạng thái từ `pending` $\rightarrow$ `confirmed` $\rightarrow$ `shipping` $\rightarrow$ `completed` | Trạng thái cập nhật chuẩn xác, dashboard cập nhật doanh thu và số đơn | | Chờ test |

---

### Nhóm 5: Giao diện & Đa thiết bị (Responsive) (Tuần 2 & 3 - TV4, TV6)

| Mã TC | Thiết bị / Trình duyệt | Tiêu chí kiểm tra | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **UI_01** | Desktop (Chrome, Edge, Firefox) | Bố cục header, navigation, banner, grid sản phẩm, bảng admin | Hiển thị rõ ràng, không vỡ layout, màu sắc đồng bộ theo `style.css` | | Chờ test |
| **UI_02** | Mobile (Màn hình < 768px) | Mở giao diện trên thiết bị di động hoặc F12 Device Mode | Menu tự co giãn, bảng admin có thanh cuộn ngang, form đăng nhập vừa vặn màn hình | | Chờ test |
