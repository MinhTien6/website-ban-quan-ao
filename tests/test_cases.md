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
| **AUTH_01** | Đăng ký hợp lệ | 1. Vào `/register.php`<br>2. Nhập họ tên, email mới, mật khẩu $\ge$ 8 ký tự<br>3. Bấm "Đăng ký" | Tạo tài khoản role `user`, thông báo thành công và chuyển sang `/login.php` | Tài khoản được tạo với role `user` | PASS |
| **AUTH_02** | Đăng ký trùng email | 1. Nhập email đã tồn tại (`lan.nguyen@example.com`)<br>2. Bấm "Đăng ký" | Báo lỗi email đã được sử dụng, không tạo tài khoản mới | Hiển thị thông báo trùng email | PASS |
| **AUTH_03** | Đăng nhập User | 1. Vào `/login.php`<br>2. Nhập `lan.nguyen@example.com` / `User@123` | Đăng nhập thành công, chuyển hướng về trang chủ `/index.php`, menu hiện tên user | Đăng nhập thành công, session lưu đúng role | PASS |
| **AUTH_04** | Đăng nhập Admin | 1. Vào `/login.php`<br>2. Nhập `admin@shop.local` / `Admin@123` | Đăng nhập thành công, tự động chuyển hướng vào `/admin/index.php` | Chuyển hướng đúng vào khu vực quản trị | PASS |
| **AUTH_05** | Tài khoản bị khóa | 1. Nhập email `khoa@example.com` / `User@123` | Báo lỗi "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên." | Chặn đăng nhập đúng thông báo | PASS |
| **AUTH_06** | Đăng xuất | 1. Bấm nút "Đăng xuất" trên thanh điều hướng | Hủy session, cookie phiên, thông báo flash và chuyển về trang chủ `/index.php` | Session bị hủy hoàn toàn | PASS |

---

### Nhóm 2: Phân quyền & Kiểm soát truy cập (TV1, TV2, TV6)

| Mã TC | Mục tiêu kiểm thử | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **PERM_01** | Khách vào trang Admin | 1. Chưa đăng nhập<br>2. Truy cập trực tiếp URL: `/admin/index.php` | Bị chặn, hiện thông báo "Vui lòng đăng nhập để tiếp tục", chuyển hướng sang `/login.php` | Bị chặn, chuyển về login | PASS |
| **PERM_02** | User thường vào trang Admin | 1. Đăng nhập tài khoản User (`lan.nguyen@example.com`)<br>2. Truy cập `/admin/index.php` | Bị chặn bởi `requireAdmin()`, báo "Bạn không có quyền truy cập vào trang quản trị", chuyển về `/index.php` | Bị từ chối truy cập và chuyển về trang chủ | PASS |
| **PERM_03** | Khách vào trang cần login | 1. Khách bấm xem lịch sử đơn hoặc trang tài khoản | Chuyển hướng sang `/login.php` | Bắt buộc đăng nhập trước khi xem đơn | PASS |

---

### Nhóm 3: Bảo mật chung (TV1, TV6)

| Mã TC | Tiêu chí bảo mật | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **SEC_01** | Chống SQL Injection | Nhập `' OR '1'='1` vào ô email / mật khẩu khi đăng nhập hoặc ô tìm kiếm | Truy vấn dùng Prepared Statement an toàn, không lộ lỗi CSDL hay bypass đăng nhập | Truy vấn PDO tham số hóa an toàn | PASS |
| **SEC_02** | Chống XSS (Cross-Site Scripting) | Nhập `<script>alert('XSS')</script>` vào ô họ tên khi đăng ký hoặc mô tả | Dữ liệu được escape qua `e()` / `htmlspecialchars`, script không bị thực thi | Ký tự đặc biệt được mã hóa HTML entities | PASS |
| **SEC_03** | Chống CSRF (Cross-Site Request Forgery) | Submit form POST không có trường `csrf_token` hoặc token giả | Bị từ chối: "Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn" | Bị chặn và báo lỗi CSRF | PASS |
| **SEC_04** | Kiểm tra tải lên file ảnh | Thử tải lên file đuôi `.php`, `.exe` hoặc file giả mạo MIME type | Bị chặn bởi hàm `uploadImage()`, chỉ cho phép `jpg, jpeg, png, webp`, kiểm tra qua `getimagesize()` | Tệp không phải ảnh bị từ chối | PASS |

---

### Nhóm 4: Luồng mua hàng & Đơn hàng End-to-End (Tuần 3 - TV5, TV6)

| Mã TC | Quy trình | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **E2E_01** | Luồng mua hàng hoàn chỉnh | 1. Khách xem sản phẩm trên trang chủ<br>2. Thêm sản phẩm (chọn size, màu) vào giỏ hàng session<br>3. Vào giỏ hàng điều chỉnh số lượng<br>4. Tiến hành thanh toán (chọn COD)<br>5. Đăng nhập và đặt hàng | Đơn hàng tạo thành công trong bảng `orders`, trừ đúng số lượng tồn trong `product_variants` | Tạo đơn thành công, trừ tồn kho chính xác | PASS |
| **E2E_02** | Hủy đơn hàng | Khách hàng vào xem lịch sử đơn, bấm "Hủy đơn" khi đơn còn ở trạng thái `pending` | Đơn chuyển sang `cancelled`, tồn kho các biến thể được hoàn lại đúng số lượng | Đơn chuyển cancelled, hoàn trả đúng tồn kho | PASS |
| **E2E_03** | Admin duyệt đơn | Admin vào quản lý đơn hàng, đổi trạng thái từ `pending` $\rightarrow$ `confirmed` $\rightarrow$ `shipping` $\rightarrow$ `completed` | Trạng thái cập nhật chuẩn xác, dashboard cập nhật doanh thu và số đơn | Cập nhật theo đúng State Machine, doanh thu tăng | PASS |

---

### Nhóm 5: Giao diện & Đa thiết bị (Responsive) (Tuần 2 & 3 - TV4, TV6)

| Mã TC | Thiết bị / Trình duyệt | Tiêu chí kiểm tra | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **UI_01** | Desktop (Chrome, Edge, Firefox) | Bố cục header, navigation, banner, grid sản phẩm, bảng admin | Hiển thị rõ ràng, không vỡ layout, màu sắc đồng bộ theo `style.css` | Giao diện chuẩn xác, cân đối | PASS |
| **UI_02** | Mobile (Màn hình < 768px) | Mở giao diện trên thiết bị di động hoặc F12 Device Mode | Menu tự co giãn, bảng admin có thanh cuộn ngang, form đăng nhập vừa vặn màn hình | Responsive tốt, không tràn màn hình | PASS |

---

## 6. Test Case chi tiết – TV2 (Xác thực, phân quyền, hồ sơ & quản lý người dùng)

| Mã TC | Chức năng | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **USER_01** | Xem hồ sơ cá nhân | Đăng nhập User, truy cập trang `profile.php` | Hiển thị đúng thông tin tài khoản hiện tại (họ tên, email, SĐT, địa chỉ) | Hiển thị chính xác thông tin từ CSDL | PASS |
| **USER_02** | Cập nhật hồ sơ hợp lệ | Thay đổi họ tên, số điện thoại và địa chỉ bằng dữ liệu hợp lệ | Lưu thông tin thành công, session được cập nhật và hiển thị dữ liệu mới | Cập nhật CSDL và session ngay lập tức | PASS |
| **USER_03** | Số điện thoại không hợp lệ tại hồ sơ | Nhập số điện thoại `123abc` trong trang hồ sơ | Hiển thị lỗi định dạng số điện thoại Việt Nam, không cập nhật dữ liệu | Hiển thị thông báo lỗi định dạng SĐT | PASS |
| **USER_04** | Admin tạo User hợp lệ | Admin vào quản lý người dùng, tạo tài khoản với thông tin hợp lệ | Tạo tài khoản thành công, mật khẩu được mã hóa an toàn | Tài khoản được tạo với password_hash | PASS |
| **USER_05** | Admin tạo User với số điện thoại sai | Nhập số điện thoại `123abc` khi tạo tài khoản | Báo lỗi định dạng SĐT Việt Nam, không tạo tài khoản | Chặn tạo tài khoản và báo lỗi rõ ràng | PASS |
| **USER_06** | Admin sửa User với số điện thoại sai | Chọn sửa tài khoản, nhập số `123abc` | Báo lỗi định dạng SĐT Việt Nam, không cập nhật dữ liệu | Chặn cập nhật và báo lỗi định dạng | PASS |
| **USER_07** | Admin khóa tài khoản | Admin chọn khóa một tài khoản User khác | Trạng thái chuyển thành `0`, tài khoản không thể tiếp tục sử dụng | Status chuyển sang 0 trong CSDL | PASS |
| **USER_08** | User đang đăng nhập bị khóa | Đăng nhập User, sau đó Admin khóa tài khoản; User tải lại trang | `refreshCurrentUser()` tự động vô hiệu hóa session và chuyển hướng đăng nhập | Session bị hủy ngay lập tức khi tải trang | PASS |
| **USER_09** | Admin mở khóa tài khoản | Admin chọn mở khóa tài khoản đang bị khóa | Trạng thái chuyển thành `1`, tài khoản có thể đăng nhập lại | Status chuyển sang 1, đăng nhập bình thường | PASS |
| **USER_10** | Admin không thể tự khóa | Admin thử khóa chính tài khoản đang đăng nhập | Hệ thống từ chối thao tác, báo lỗi không thể tự khóa | Chặn thao tác khóa tài khoản hiện tại | PASS |
| **USER_11** | Admin không thể tự hạ quyền | Admin thử đổi vai trò tài khoản của chính mình thành User | Hệ thống từ chối thao tác, báo lỗi không thể tự hạ quyền | Chặn thao tác tự hạ quyền Admin | PASS |
| **USER_12** | Đổi mật khẩu hợp lệ | Nhập mật khẩu hiện tại chính xác, mật khẩu mới $\ge$ 8 ký tự và xác nhận trùng khớp | Đổi mật khẩu thành công, session được làm mới bằng `session_regenerate_id(true)` | Đổi mật khẩu thành công, phiên an toàn | PASS |
| **USER_13** | Đổi mật khẩu sai mật khẩu hiện tại | Nhập sai mật khẩu hiện tại | Báo lỗi mật khẩu hiện tại không chính xác, không thay đổi mật khẩu | Báo lỗi xác thực mật khẩu cũ | PASS |
| **USER_14** | Đổi mật khẩu dưới 8 ký tự | Nhập mật khẩu mới ngắn hơn 8 ký tự | Hệ thống từ chối đổi mật khẩu, báo lỗi độ dài | Báo lỗi mật khẩu tối thiểu 8 ký tự | PASS |
| **USER_15** | Xác nhận mật khẩu không khớp | Nhập mật khẩu mới và xác nhận khác nhau | Báo lỗi mật khẩu xác nhận không khớp, không cập nhật | Báo lỗi không trùng khớp mật khẩu | PASS |
| **USER_16** | Đổi mật khẩu trùng mật khẩu cũ | Nhập mật khẩu mới giống mật khẩu hiện tại | Đổi thành công hoặc hệ thống xử lý an toàn theo quy định | Mật khẩu được cập nhật hash mới | PASS |
| **USER_17** | Kiểm tra CSRF khi đổi mật khẩu | Gửi POST không có CSRF token hoặc token không hợp lệ | Hệ thống từ chối yêu cầu, phiên làm việc không bị ảnh hưởng | Bị chặn với lỗi CSRF không hợp lệ | PASS |
| **USER_18** | Tìm kiếm người dùng | Admin nhập email hoặc tên vào ô tìm kiếm | Hiển thị đúng người dùng phù hợp | Kết quả lọc chính xác theo từ khóa | PASS |
| **USER_19** | Phân trang người dùng | Admin chuyển qua các trang danh sách người dùng | Hiển thị đúng dữ liệu theo từng trang (10 user/trang) | Phân trang mượt mà, giữ query tìm kiếm | PASS |
| **USER_20** | Truy cập chức năng Admin bằng User | Đăng nhập User, thử truy cập trực tiếp các trang quản lý người dùng | `requireAdmin()` chặn truy cập, chuyển hướng về trang khách | Chặn truy cập và thông báo không có quyền | PASS |

---

## 7. Kiểm thử riêng nhiệm vụ TV6 (Tuần 1 & 2)

**Tiền điều kiện:** Import `database/shop_quanao.sql`, cấu hình kết nối trong `config/database.php`, đăng nhập bằng tài khoản Admin mẫu.

| Mã TC | Chức năng | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **TV6-W1-01** | Bảo vệ layout Admin | Mở `/admin/orders.php` khi đã đăng xuất | Middleware chặn truy cập và chuyển người dùng sang đăng nhập | Chặn và chuyển hướng sang login | PASS |
| **TV6-W1-02** | Phân quyền Admin | Đăng nhập bằng tài khoản role `user`, truy cập `/admin/orders.php` | Không xem được trang quản trị; bị chuyển về trang khách hàng | Chặn user thường vào khu vực admin | PASS |
| **TV6-W1-03** | Menu Admin | Đăng nhập Admin, mở các trang dashboard và quản lý đơn | Menu hiển thị; mục hiện tại có trạng thái active; liên kết đơn hàng mở đúng trang | Menu sáng đúng mục active | PASS |
| **TV6-W1-04** | Use Case | Đối chiếu `docs/usecase.png` với các chức năng khách, user và admin đã có | Vai trò và các luồng chính được thể hiện đúng; Admin có thể vào khu quản trị | Khớp sơ đồ Use Case hệ thống | PASS |
| **TV6-W2-01** | Dashboard thống kê | Mở `/admin/index.php` với database mẫu | 9 thẻ thống kê hiển thị đầy đủ, số liệu khớp truy vấn database | Hiển thị đủ 9 card chỉ số hoạt động | PASS |
| **TV6-W2-02** | Đơn hàng mới nhất | Xem bảng đơn gần đây trên Dashboard | Hiện tối đa 5 đơn, đúng người nhận, thời gian, tổng tiền và trạng thái | Bảng 5 đơn mới nhất hiển thị chuẩn | PASS |
| **TV6-W2-03** | Danh sách đơn | Mở `/admin/orders.php` | Có danh sách đơn mẫu; mỗi dòng có thông tin chính và nút Chi tiết | Danh sách hiển thị đầy đủ thông tin | PASS |
| **TV6-W2-04** | Lọc trạng thái | Chọn lần lượt Chờ xác nhận, Đã xác nhận, Đang giao, Hoàn thành, Đã hủy | Mỗi lựa chọn chỉ hiển thị đơn có trạng thái tương ứng; chọn Tất cả hiện lại toàn bộ | Lọc chính xác theo từng trạng thái | PASS |
| **TV6-W2-05** | Phân trang danh sách | Truy cập phân trang danh sách đơn | Mỗi trang tối đa 10 đơn; giữ nguyên tham số lọc trạng thái/từ khóa khi chuyển trang | Phân trang mượt mà, giữ query params | PASS |
| **TV6-W2-06** | Chi tiết đơn hợp lệ | Mở chi tiết đơn `#1` | Có người nhận, tài khoản, địa chỉ, điện thoại, thanh toán, trạng thái và dòng sản phẩm | Hiển thị chi tiết đơn chính xác | PASS |
| **TV6-W2-07** | Mã đơn không hợp lệ | Truy cập `/admin/order_detail.php?id=99999` | Trả về HTTP 404 và thông báo không tìm thấy đơn, không lộ lỗi SQL | HTTP 404 chuẩn, không lộ lỗi | PASS |
| **TV6-W2-08** | Hiển thị responsive | Mở danh sách và chi tiết trên màn hình nhỏ | Bảng cuộn ngang, các khối chi tiết xếp một cột, không tràn khỏi màn hình | Responsive tốt trên màn hình nhỏ | PASS |
| **TV6-W2-09** | Tìm kiếm và lọc đơn | Tìm theo mã đơn, tên người nhận, tài khoản, số điện thoại, email | Lọc chính xác, giữ nguyên từ khóa khi chuyển trang | Tìm kiếm đa trường hoạt động tốt | PASS |
| **TV6-W2-10** | Chỉ số khách và sắp hết hàng | So sánh thẻ Dashboard với số user và số sản phẩm tồn kho $\le$ 10 | Hai chỉ số khớp truy vấn CSDL | Khớp dữ liệu thực tế | PASS |
| **TV6-W2-11** | Tạm tính và giảm giá coupon | Mở chi tiết đơn có coupon | Hiện đầy đủ dòng Tạm tính, Giảm giá (mã coupon) và Tổng thanh toán | Hiển thị mã coupon và số tiền giảm rõ ràng | PASS |
| **TV6-W2-12** | Ảnh sản phẩm bị thiếu | Tạm đổi đường dẫn ảnh trong dữ liệu sang tệp không tồn tại rồi mở chi tiết đơn | Hiện placeholder thay cho ảnh lỗi; trang vẫn hoạt động | Placeholder hiển thị an toàn | PASS |

---

## 8. Kiểm thử tự động nhiệm vụ TV6 (Tuần 3 - CLI Script `tests/test_week3.php`)

Sau khi cấu hình `config/database.php` và import dữ liệu mẫu, chạy lệnh:
```bash
php tests/test_week3.php
```

| Mã TC | Chức năng | Các bước thực hiện | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
|---|---|---|---|---|---|
| **TV6-W3-01** | Admin cập nhật trạng thái hợp lệ | Tại chi tiết đơn, lần lượt cập nhật `pending` → `confirmed` → `shipping` → `completed` | Mỗi bước hợp lệ được lưu; trạng thái mới hiển thị trên trang chi tiết, danh sách và Dashboard | Đạt luồng State Machine chuyển trạng thái | PASS |
| **TV6-W3-02** | Chặn bước trạng thái không hợp lệ | Gửi trực tiếp POST để đổi `pending` thành `completed`, hoặc sửa trạng thái của đơn đã hoàn thành | Trạng thái không đổi và người quản trị nhận thông báo lỗi | Chặn nhảy cóc trạng thái và sửa đơn completed | PASS |
| **TV6-W3-03** | Bảo vệ cập nhật bằng CSRF | Kiểm tra `verifyCsrfToken()` với token đúng, sai và rỗng; xác nhận fixture không đổi trạng thái/tồn kho khi token sai | Token đúng được nhận; token sai/rỗng bị từ chối; đơn và tồn kho giữ nguyên | Kiểm tra CSRF chặt chẽ chống giả mạo | PASS |
| **TV6-W3-04** | Hủy đơn từ Admin | Từ đơn `pending` hoặc `confirmed`, chọn `Đã hủy` | Đơn thành `cancelled`, tồn kho từng biến thể trong đơn được cộng lại đúng số lượng | Hoàn tồn kho trong Transaction đúng 1 lần | PASS |
| **TV6-W3-05** | Dashboard theo đủ trạng thái | Mở Dashboard sau khi đổi/hủy đơn | Số đơn chờ, đã xác nhận, đang giao, hoàn thành và đã hủy khớp dữ liệu; doanh thu chỉ cộng đơn hoàn thành | 9 chỉ số trên Dashboard đồng bộ chính xác | PASS |
| **TV6-W3-06** | Đồng bộ trạng thái giữa các màn hình | Đổi trạng thái ở chi tiết, mở lại danh sách đơn và Dashboard | Cả ba nơi hiển thị cùng trạng thái và số liệu cập nhật | Đồng bộ dữ liệu xuyên suốt các màn hình | PASS |
| **TV6-W3-07** | Biểu đồ doanh thu 7 ngày (Bonus) | Gọi `Order::getRevenueChartData(7)` | Trả về mảng gồm `labels` (7 ngày) và `values` (7 giá trị doanh thu), ngày không có đơn nhận 0đ | Cấu trúc dữ liệu Chart.js chuẩn 7 ngày | PASS |
