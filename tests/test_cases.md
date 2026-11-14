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
| **AUTH_06** | Đăng xuất | 1. Bấm nút "Đăng xuất" trên thanh điều hướng | Hủy session, cookie phiên, thông báo flash và chuyển về trang chủ `/index.php` | | Chờ test |

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

---

## 6. Kiểm thử riêng nhiệm vụ TV6 (Tuần 1 & 2)

**Tiền điều kiện:** Import `database/shop_quanao.sql`, cấu hình kết nối trong `config/database.php`, đăng nhập bằng tài khoản Admin mẫu. Bộ dữ liệu SQL ban đầu có 5 đơn hàng, mỗi đơn thuộc một trạng thái.

| Mã TC | Chức năng | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **TV6-W1-01** | Bảo vệ layout Admin | Mở `/admin/orders.php` khi đã đăng xuất | Middleware chặn truy cập và chuyển người dùng sang đăng nhập | | Chờ test |
| **TV6-W1-02** | Phân quyền Admin | Đăng nhập bằng tài khoản role `user`, truy cập `/admin/orders.php` | Không xem được trang quản trị; bị chuyển về trang khách hàng | | Chờ test |
| **TV6-W1-03** | Menu Admin | Đăng nhập Admin, mở các trang dashboard và quản lý đơn | Menu hiển thị; mục hiện tại có trạng thái active; liên kết đơn hàng mở đúng trang | | Chờ test |
| **TV6-W1-04** | Use Case | Đối chiếu `docs/usecase.png` với các chức năng khách, user và admin đã có | Vai trò và các luồng chính được thể hiện đúng; Admin có thể vào khu quản trị | | Chờ test |
| **TV6-W2-01** | Dashboard thống kê | Mở `/admin/index.php` với database mẫu | Tổng đơn = 5, đơn chờ = 1, đơn hoàn thành = 1, doanh thu hoàn thành = 762.300đ; có thêm số khách và số sản phẩm sắp hết hàng khớp CSDL | | Chờ test |
| **TV6-W2-02** | Đơn hàng mới nhất | Xem bảng đơn gần đây trên Dashboard | Hiện tối đa 5 đơn, đúng người nhận, thời gian, tổng tiền và trạng thái | | Chờ test |
| **TV6-W2-03** | Danh sách đơn | Mở `/admin/orders.php` | Có 5 đơn mẫu; mỗi dòng có thông tin chính và nút Chi tiết | | Chờ test |
| **TV6-W2-04** | Lọc trạng thái | Chọn lần lượt Chờ xác nhận, Đã xác nhận, Đang giao, Hoàn thành, Đã hủy | Mỗi lựa chọn chỉ hiển thị đơn có trạng thái tương ứng; chọn Tất cả hiện lại toàn bộ | | Chờ test |
| **TV6-W2-05** | Phân trang danh sách | Tạo thêm hơn 10 đơn hoặc dùng dữ liệu nhiều trang, chuyển trang danh sách | Mỗi trang có tối đa 10 đơn; bộ lọc trạng thái được giữ khi chuyển trang | | Chờ test |
| **TV6-W2-06** | Chi tiết đơn hợp lệ | Mở chi tiết đơn #1 | Có người nhận, tài khoản, địa chỉ, điện thoại, thanh toán, trạng thái và 2 dòng sản phẩm; tổng = 762.300đ | | Chờ test |
| **TV6-W2-07** | Mã đơn không hợp lệ | Truy cập `/admin/order_detail.php?id=99999` | Trả về HTTP 404 và thông báo không tìm thấy đơn, không lộ lỗi SQL | | Chờ test |
| **TV6-W2-08** | Hiển thị responsive | Mở danh sách và chi tiết trên màn hình nhỏ | Bảng cuộn ngang, các khối chi tiết xếp một cột, không tràn khỏi màn hình | | Chờ test |
| **TV6-W2-09** | Tìm kiếm và lọc đơn | Tìm lần lượt theo mã đơn, tên người nhận/tài khoản, số điện thoại, email; kết hợp từ khóa với trạng thái | Chỉ hiện các đơn khớp; số trang đúng và giữ nguyên từ khóa/trạng thái khi chuyển trang | | Chờ test |
| **TV6-W2-10** | Chỉ số khách và sắp hết hàng | So sánh hai thẻ Dashboard với số user và số sản phẩm đang bán có tổng tồn kho không quá 10 trong CSDL | Hai chỉ số khớp truy vấn CSDL; sản phẩm không hoạt động không được tính | | Chờ test |
| **TV6-W2-11** | Tạm tính và giảm giá coupon | Mở chi tiết đơn #1 và #3 có coupon | Hiện tạm tính lần lượt 847.000đ và 608.000đ; giảm lần lượt 84.700đ và 50.000đ; tổng thanh toán khớp đơn | | Chờ test |
| **TV6-W2-12** | Ảnh sản phẩm bị thiếu | Tạm đổi đường dẫn ảnh trong dữ liệu sang tệp không tồn tại rồi mở chi tiết đơn | Hiện placeholder thay cho ảnh lỗi; trang vẫn hoạt động | | Chờ test |

Các test case ở trên là kế hoạch kiểm thử; chưa đánh dấu kết quả thực tế trước khi chạy với database của máy.

## 7. Kiểm thử riêng nhiệm vụ TV6 (Tuần 3)

Các test case dưới đây đã có mã kiểm thử CLI trong `tests/test_week3.php`. Sau khi cấu hình `config/database.php` và import dữ liệu mẫu, chạy `php tests/test_week3.php`. Script tạo dữ liệu thử riêng, kiểm tra các luồng, rồi xóa dữ liệu đó khi kết thúc.

| Mã TC | Chức năng | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **TV6-W3-01** | Admin cập nhật trạng thái hợp lệ | Tại chi tiết đơn, lần lượt cập nhật `pending` → `confirmed` → `shipping` → `completed` | Mỗi bước hợp lệ được lưu; trạng thái mới hiển thị trên trang chi tiết, danh sách và Dashboard | | Chờ test |
| **TV6-W3-02** | Chặn bước trạng thái không hợp lệ | Gửi trực tiếp POST để đổi `pending` thành `completed`, hoặc sửa trạng thái của đơn đã hoàn thành | Trạng thái không đổi và người quản trị nhận thông báo lỗi | | Chờ test |
| **TV6-W3-03** | Bảo vệ cập nhật bằng CSRF | Kiểm tra `verifyCsrfToken()` với token đúng, sai và rỗng; xác nhận fixture không đổi trạng thái/tồn kho khi token sai | Token đúng được nhận; token sai/rỗng bị từ chối; đơn và tồn kho giữ nguyên | | Chờ test |
| **TV6-W3-04** | Hủy đơn từ Admin | Từ đơn `pending` hoặc `confirmed`, chọn `Đã hủy` | Đơn thành `cancelled`, tồn kho từng biến thể trong đơn được cộng lại đúng số lượng | | Chờ test |
| **TV6-W3-05** | Dashboard theo đủ trạng thái | Mở Dashboard sau khi đổi/hủy đơn | Số đơn chờ, đã xác nhận, đang giao, hoàn thành và đã hủy khớp dữ liệu; doanh thu chỉ cộng đơn hoàn thành | | Chờ test |
| **TV6-W3-06** | Đồng bộ trạng thái giữa các màn hình | Đổi trạng thái ở chi tiết, mở lại danh sách đơn và Dashboard | Cả ba nơi hiển thị cùng trạng thái và số liệu cập nhật | | Chờ test |
