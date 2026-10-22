# Website bán quần áo

Dự án website bán quần áo sử dụng PHP 8+ và MySQL (mô hình MVC đơn giản, PDO prepared statement).

## Cài đặt nhanh

### 1. Yêu cầu hệ thống
- **XAMPP** (Apache 2.4+, MySQL 5.7+ hoặc MariaDB 10.4+).
- **PHP 8.0 trở lên** (đã bật tiện ích mở rộng `pdo_mysql`, `mbstring`).
- Trình duyệt web hiện đại (Chrome, Edge, Firefox...).

### 2. Các bước cài đặt và chạy trên máy cá nhân
1. **Đặt mã nguồn vào thư mục `htdocs`**:
   - Sao chép hoặc clone toàn bộ thư mục dự án vào thư mục `htdocs` của XAMPP (ví dụ: `C:\xampp\htdocs\website-ban-quan-ao`).
2. **Khởi động dịch vụ trong XAMPP Control Panel**:
   - Nhấn **Start** cho cả hai dịch vụ **Apache** và **MySQL**.
3. **Import cơ sở dữ liệu**:
   - Mở trình duyệt và truy cập phpMyAdmin tại: `http://localhost/phpmyadmin/`.
   - Tạo cơ sở dữ liệu `shop_quanao` với Collation `utf8mb4_unicode_ci` (hoặc chuyển sang thẻ **Import**).
   - Chọn thẻ **Import** -> chọn tệp `database/shop_quanao.sql` trong dự án -> nhấn **Import** ở cuối trang.
4. **Cấu hình kết nối cơ sở dữ liệu**:
   - Sao chép tệp `config/database.example.php` thành `config/database.php`.
   - Mở `config/database.php` và điều chỉnh lại thông tin nếu máy bạn có mật khẩu MySQL khác mặc định:
     ```php
     $host = 'localhost';
     $dbname = 'shop_quanao';
     $username = 'root';
     $password = '';
     ```
5. **Truy cập ứng dụng**:
   - Mở trình duyệt và truy cập: `http://localhost/<tên-thư-mục>/` (ví dụ: `http://localhost/website-ban-quan-ao/`).

### 3. Danh sách tài khoản thử nghiệm (Demo Accounts)

| Vai trò | Email đăng nhập | Mật khẩu | Ghi chú |
|---|---|---|---|
| **Quản trị viên (Admin)** | `admin@shop.local` | `Admin@123` | Có quyền truy cập trang Quản trị (`admin/index.php`) |
| **Khách hàng 1 (User)** | `lan.nguyen@example.com` | `User@123` | Tài khoản người dùng bình thường |
| **Khách hàng 2 (User)** | `minh.tran@example.com` | `User@123` | Tài khoản người dùng bình thường |
| **Khách hàng 3 (Khóa)** | `khoa@example.com` | `User@123` | Tài khoản bị khóa (`status = 0`) để kiểm tra thông báo lỗi |

### 4. Kiểm tra CSDL tự động
Dự án đã tích hợp sẵn kịch bản kiểm tra toàn diện cấu trúc CSDL, các khóa ngoại và dữ liệu mẫu:
- Truy cập trên trình duyệt: `http://localhost/<tên-thư-mục>/tests/check_db.php`
- Đảm bảo tất cả các bài kiểm tra đều báo màu xanh (PASS).

## Quy ước lập trình chung (Coding Conventions)

Để 5 thành viên (TV2 -> TV6) phát triển code đồng nhất từ Tuần 2, toàn bộ nhóm thống nhất:

1. **Cơ sở dữ liệu & Truy vấn**:
   - Sử dụng đúng CSDL `shop_quanao` và schema trong `database/shop_quanao.sql`.
   - Bắt buộc dùng **PDO Prepared Statement** (biến `$pdo` từ `config/database.php`), không dùng truy vấn ghép chuỗi trực tiếp.
   - Định danh người dùng bằng **email** (không có cột `username`).
   - Cột trạng thái `status`: `1 = hoạt động / hiển thị`, `0 = khóa / ẩn`.
2. **Mã nguồn PHP & Bảo mật**:
   - Mọi dữ liệu xuất ra HTML bắt buộc dùng hàm `e($value)` trong `includes/functions.php` (chống XSS).
   - Mật khẩu băm bằng `password_hash($password, PASSWORD_DEFAULT)` và kiểm tra bằng `password_verify()`.
   - Phiên đăng nhập chuẩn hóa: `$_SESSION['user'] = ['id' => ..., 'full_name' => ..., 'email' => ..., 'role' => ...]`.
   - Kiểm tra quyền: dùng `requireLogin()` (cần đăng nhập) hoặc `requireAdmin()` (cần quyền admin) từ `includes/auth.php`.
   - Định dạng tiền tệ: dùng `formatPrice($amount)`.
   - Thông báo tạm thời giữa các trang: dùng `setFlash($type, $msg)` và `getFlash()`.
   - Tải lên hình ảnh: dùng hàm `uploadImage($file, $subFolder)` trong `includes/functions.php`.
   - Phân trang: dùng hàm `paginate()` và `renderPagination()`.
   - Tạo slug: dùng hàm `createSlug()`.
3. **Quy ước đặt tên**:
   - Tên bảng, cột CSDL: `snake_case` tiếng Anh (`full_name`, `created_at`, `sale_price`).
   - Biến, hàm PHP: `camelCase` (`$fullName`, `isLoggedIn()`, `formatPrice()`).
   - Lớp (Class): `PascalCase` (`Product`, `Category`, `User`, `Order`).
   - File code lưu định dạng **UTF-8 không BOM**, ký tự xuống dòng **LF**.
   - Mỗi file và hàm đều có chú thích ngắn bằng tiếng Việt.

---

## Hướng dẫn sử dụng Git và GitHub

Phần này mô tả quy trình làm việc nhóm thống nhất cho dự án: `main` giữ phiên bản ổn định, `develop` là nhánh tích hợp, mỗi thành viên phát triển trên một nhánh `feature/*`, sau đó gửi Pull Request (PR) để review trước khi ghép code.

### 1. Chuẩn bị

Cài đặt:

- **Git**: https://git-scm.com/downloads
- **Tài khoản GitHub**: https://github.com/
- **VS Code** (khuyến nghị): https://code.visualstudio.com/

Mở Terminal hoặc PowerShell và kiểm tra Git:

```bash
git --version
```

Thiết lập tên và email sẽ được ghi vào commit. Thay thông tin ví dụ bằng tên và email của bạn:

```bash
git config --global user.name "Nguyen Van A"
git config --global user.email "nguyenvana@example.com"
```

Kiểm tra lại:

```bash
git config --global --list
```

Khi GitHub yêu cầu xác thực, đăng nhập bằng trình duyệt hoặc dùng phương thức xác thực GitHub đã cấu hình. Không đưa mật khẩu, Personal Access Token (PAT), khóa riêng hoặc thông tin kết nối cơ sở dữ liệu thật vào mã nguồn hay nội dung commit.

### 2. Hiểu các trạng thái của Git và GitHub

Git theo dõi thay đổi ở máy của bạn; GitHub lưu các nhánh đã được đẩy lên máy chủ. Một thay đổi thường đi qua các trạng thái sau:

1. **Untracked**: tệp mới, Git chưa theo dõi.
2. **Modified**: tệp đã theo dõi bị sửa nhưng chưa đưa vào vùng chuẩn bị commit.
3. **Staged**: thay đổi đã được chọn bằng `git add`, sẵn sàng đưa vào commit.
4. **Committed**: thay đổi đã được lưu thành commit trong lịch sử Git trên máy.
5. **Pushed**: commit đã được đẩy lên nhánh trên GitHub.
6. **Trong Pull Request**: thay đổi được đề xuất để review và ghép vào nhánh đích, thường là `develop`.
7. **Merged**: PR đã được ghép; thay đổi hiện có trong nhánh đích. Merge vào `develop` chưa có nghĩa là đã phát hành lên `main`.

`git status` là lệnh kiểm tra trạng thái thường dùng nhất. Với `git status --short`, các ký hiệu phổ biến là:

- `?? ten-tep`: tệp mới chưa được Git theo dõi.
- ` M ten-tep`: tệp đã sửa, chưa stage.
- `M  ten-tep`: thay đổi đã stage.
- `A  ten-tep`: tệp mới đã stage.
- `D  ten-tep`: tệp đã xóa; vị trí ký hiệu cho biết thay đổi đã stage hay chưa.

Nếu Git báo nhánh **ahead** hoặc **behind** `origin/...`, nghĩa là nhánh máy bạn có commit chưa đẩy lên hoặc đang thiếu commit có trên GitHub. Cập nhật trước khi tiếp tục để tránh làm trên nền code cũ.

Trạng thái của PR trên GitHub thường gồm:

- **Open**: đang chờ review hoặc xử lý; chưa được ghép.
- **Draft**: PR còn đang làm, chưa yêu cầu review chính thức.
- **Approved**: có người review đồng ý; vẫn cần merge và các điều kiện bảo vệ nhánh (nếu có) phải đạt.
- **Changes requested**: cần sửa theo góp ý; commit bổ sung và push lên đúng nhánh PR.
- **Checks failing / pending**: kiểm tra tự động bị lỗi hoặc chưa chạy xong; cần xử lý hoặc chờ hoàn tất.
- **Merged**: PR đã được ghép vào nhánh đích.
- **Closed**: PR bị đóng mà không ghép; các commit trên nhánh nguồn không tự mất.

### 3. Lấy dự án về máy

Với thành viên mới, sao chép địa chỉ HTTPS của repository trên GitHub, sau đó chạy:

```bash
git clone https://github.com/<chu-tai-khoan-hoac-to-chuc>/website-ban-quan-ao.git
cd website-ban-quan-ao
```

Thay `<chu-tai-khoan-hoac-to-chuc>` bằng tên chủ sở hữu repository thật. Nếu dự án đã có sẵn trên máy, mở Terminal tại thư mục dự án và bỏ qua bước `clone`.

Kiểm tra remote và nhánh:

```bash
git remote -v
git branch --show-current
git branch -a
git status
```

`origin` thường là tên remote GitHub. `git branch -a` hiển thị các nhánh máy local và nhánh remote đã biết.

### 4. Quy ước nhánh của dự án

- `main`: phiên bản ổn định, chỉ cập nhật khi nhóm xác nhận mốc phát hành.
- `develop`: nhánh tích hợp công việc của cả nhóm.
- `feature/<ten-chuc-nang>`: nhánh làm một tính năng hoặc phần việc cụ thể, tạo từ `develop`.
- Có thể dùng `fix/<ten-loi>` cho một bản sửa lỗi độc lập nếu nhóm thống nhất cách đặt tên đó.

Đặt tên nhánh ngắn, không dấu, không khoảng trắng; dùng chữ thường và dấu gạch ngang. Ví dụ:

```text
feature/admin-products
feature/product-image-upload
feature/user-authentication
fix/cart-stock-validation
```

Không làm tính năng trực tiếp trên `main` hoặc `develop`, và không push thẳng vào hai nhánh này. Mỗi người nên làm trên nhánh riêng để công việc có thể được review và không ghi đè lẫn nhau.

### 5. Tạo nhánh `develop` (trưởng nhóm thực hiện một lần)

Nếu repository trên GitHub đã có `main` nhưng chưa có `develop`, trưởng nhóm tạo nhánh tích hợp như sau:

```bash
git switch main
git pull --ff-only origin main
git switch -c develop
git push -u origin develop
```

`-c` tạo nhánh mới và chuyển sang nhánh đó; `-u` đặt nhánh remote làm upstream để các lần `git push`/`git pull` tiếp theo ngắn gọn hơn. Nếu `develop` đã tồn tại trên GitHub, không tạo lại: cập nhật danh sách nhánh bằng `git fetch origin`, rồi chuyển sang nhánh đó:

```bash
git fetch origin
git switch --track origin/develop
```

Nếu local đã có nhánh `develop`, dùng:

```bash
git switch develop
git pull --ff-only origin develop
```

### 6. Bắt đầu một công việc trên nhánh riêng

Trước mỗi công việc mới, cập nhật `develop` để nhánh tính năng bắt đầu từ code mới nhất:

```bash
git switch develop
git pull --ff-only origin develop
git switch -c feature/<ten-chuc-nang>
```

Ví dụ tạo nhánh làm chức năng quản lý sản phẩm:

```bash
git switch develop
git pull --ff-only origin develop
git switch -c feature/admin-products
```

Kiểm tra đang ở đúng nhánh trước khi sửa code:

```bash
git branch --show-current
git status
```

Lệnh đầu cần hiển thị `feature/admin-products`. Nếu có thay đổi dở dang từ công việc trước, hãy lưu commit hoặc trao đổi với nhóm trước khi đổi nhánh; không xóa thay đổi chỉ để làm cho working tree sạch.

### 7. Lưu thay đổi bằng commit

Sau khi sửa code, xem những gì đã thay đổi:

```bash
git status
git diff
```

Chọn các tệp liên quan để stage. Nên ưu tiên thêm từng tệp thay vì `git add .` để tránh đưa nhầm tệp cấu hình, ảnh tạm hoặc dữ liệu cá nhân vào commit:

```bash
git add admin/products.php
git add controllers/ProductController.php
git diff --staged
```

Nếu tên tệp trong dự án khác, thay bằng đúng đường dẫn thực tế. Sau khi xem lại nội dung đã stage, tạo commit:

```bash
git commit -m "feat: add product management"
```

Quy ước commit: ngắn gọn, mô tả đúng một nhóm thay đổi. Có thể dùng tiền tố `feat:` cho tính năng, `fix:` cho sửa lỗi, `docs:` cho tài liệu, `refactor:` cho tái cấu trúc không đổi chức năng. Ví dụ:

```text
feat: add product image upload
fix: validate product stock before checkout
docs: explain team Git workflow
```

Commit lưu thay đổi vào lịch sử local; **commit chưa đồng nghĩa với đã tải code lên GitHub**. Kiểm tra lịch sử gần nhất bằng:

```bash
git log --oneline -5
```

### 8. Đẩy nhánh lên GitHub

Lần đầu đẩy một nhánh mới, chỉ rõ upstream:

```bash
git push -u origin feature/admin-products
```

Các lần tiếp theo, nếu đang ở đúng nhánh đã thiết lập upstream:

```bash
git push
```

Sau khi push thành công, nhánh và commit xuất hiện trên GitHub. Việc push nhánh `feature/*` **không** tự động đưa thay đổi vào `develop` hoặc `main`.

Nếu Git báo remote có commit mới mà local chưa có, không dùng `--force` để ghi đè. Hãy cập nhật theo quy trình đồng bộ ở mục 11, xử lý xung đột nếu có, rồi push lại.

### 9. Tạo Pull Request trên GitHub

Sau khi push nhánh tính năng:

1. Mở repository trên GitHub; thường sẽ có nút **Compare & pull request** cho nhánh vừa push. Nếu không thấy, vào **Pull requests** → **New pull request**.
2. Chọn đúng nhánh đích **base: `develop`** và nhánh nguồn **compare: `feature/<ten-chuc-nang>`**. Kiểm tra chiều PR trước khi tạo.
3. Đặt tiêu đề mô tả thay đổi, ví dụ: `feat: quản lý sản phẩm và tải nhiều ảnh`.
4. Mô tả mục đích, chức năng đã làm, cách kiểm thử, ảnh minh họa (nếu giao diện thay đổi), và vấn đề còn tồn tại.
5. Nếu tính năng chưa sẵn sàng review, chọn **Create draft pull request**. Khi hoàn tất, bấm **Ready for review**.
6. Chọn người review (ví dụ trưởng nhóm/TV1) và đợi review cùng các kiểm tra tự động.
7. Nếu có góp ý, sửa trên **chính nhánh nguồn của PR**, tạo commit mới và `git push`. PR được cập nhật tự động.
8. Chỉ merge khi phần việc đã được review, kiểm tra cần thiết đều đạt và nhóm đồng ý. Theo quy trình dự án, merge tính năng vào `develop`, không vào `main`.

Trước khi tạo PR, xác nhận nhánh và các commit:

```bash
git branch --show-current
git status
git log --oneline origin/develop..HEAD
```

Nếu `git status` báo còn thay đổi chưa commit, hoàn tất hoặc trao đổi rõ trong PR trước khi yêu cầu review.

### 10. Xử lý góp ý và xung đột

#### Cập nhật PR sau góp ý

Chuyển sang nhánh đang gắn với PR, sửa theo góp ý, sau đó:

```bash
git status
git add <tep-da-sua>
git commit -m "fix: address product review feedback"
git push
```

PR sẽ tự nhận commit mới; không cần mở PR thứ hai cho cùng một nhánh.

#### Đồng bộ nhánh tính năng với `develop`

Khi `develop` đã có thay đổi mới trong lúc bạn đang làm, cập nhật nhánh tính năng trước khi tiếp tục hoặc trước khi merge PR:

```bash
git fetch origin
git switch feature/admin-products
git merge origin/develop
```

Nếu có xung đột, Git sẽ liệt kê tệp cần xử lý. Mở từng tệp, tìm các dấu phân cách `<<<<<<<`, `=======`, `>>>>>>>`, chọn và kết hợp nội dung đúng, rồi xóa các dấu phân cách. Sau đó kiểm tra, chạy thử chức năng liên quan và hoàn tất merge:

```bash
git status
git add <tep-da-giai-quyet>
git commit
git push
```

Không chọn một phía một cách máy móc nếu điều đó làm mất chức năng của thành viên khác. Trao đổi với tác giả phần code liên quan khi chưa chắc cách kết hợp. Nếu merge bị lỗi và chưa rõ cách xử lý, dừng lại và hỏi nhóm; không dùng lệnh reset hoặc force push để che xung đột.

### 11. Cập nhật `develop` sau khi PR được merge

Sau khi PR đã **Merged**, cập nhật nhánh tích hợp trên máy:

```bash
git switch develop
git pull --ff-only origin develop
git status
```

Kết quả mong muốn là nhánh local đã đồng bộ với `origin/develop` và working tree sạch. Nhánh tính năng đã merge có thể xóa ở local:

```bash
git branch -d feature/admin-products
```

Nếu muốn lấy nhánh mới nhất của công việc khác, luôn cập nhật `develop` trước rồi tạo nhánh feature mới như mục 6.

### 12. Tạo bản ổn định trên `main` và tag

Cuối mỗi tuần, nhóm có thể kiểm thử phiên bản tích hợp trên `develop` và tạo PR từ `develop` vào `main` khi đã xác nhận bản ổn định. Chỉ trưởng nhóm hoặc người được phân công thực hiện bước phát hành:

1. Mở PR với **base: `main`**, **compare: `develop`**.
2. Kiểm tra chức năng, review và các kiểm tra tự động.
3. Merge PR khi nhóm xác nhận đủ điều kiện phát hành.
4. Cập nhật local và gắn tag cho mốc đã phát hành:

```bash
git switch main
git pull --ff-only origin main
git tag -a v0.1 -m "Weekly stable release v0.1"
git push origin v0.1
```

Thay `v0.1` bằng phiên bản chưa được sử dụng, theo mốc nhóm thống nhất (ví dụ `v0.2`). Tag đánh dấu một commit cụ thể; tạo tag sau khi phiên bản tương ứng đã được kiểm tra.

### 13. Ví dụ đầy đủ: TV3 làm chức năng quản lý sản phẩm

Ví dụ thành viên TV3 nhận việc quản lý sản phẩm và upload ảnh. Giả sử tên repository GitHub đã được thay đúng trong lệnh clone và `develop` đã có sẵn:

```bash
# Lấy code (chỉ cần làm một lần trên máy)
git clone https://github.com/<chu-tai-khoan-hoac-to-chuc>/website-ban-quan-ao.git
cd website-ban-quan-ao

# Tạo nhánh làm việc từ develop mới nhất
git switch develop
git pull --ff-only origin develop
git switch -c feature/product-image-upload

# Sau khi sửa code, kiểm tra và chọn đúng tệp
git status
git diff
git add admin/products.php
git add controllers/ProductController.php
git diff --staged

# Lưu và tải commit lên GitHub
git commit -m "feat: add product image upload"
git push -u origin feature/product-image-upload
```

Tiếp theo, TV3 tạo PR `feature/product-image-upload` → `develop` trên GitHub. TV1 review; nếu yêu cầu sửa, TV3 sửa trên nhánh đó, commit và `git push` lần nữa. Khi PR đã được review và **Merged**, TV3 cập nhật nhánh `develop` local theo mục 11. Code chỉ xuất hiện trên `main` sau quy trình phát hành riêng ở mục 12.

### 14. Các lưu ý an toàn và xử lý tình huống thường gặp

- Trước khi sửa hoặc đổi nhánh, chạy `git status` để biết có thay đổi chưa lưu hay không.
- Không commit mật khẩu, khóa API, PAT, thông tin tài khoản thật, bản sao dữ liệu riêng tư hoặc tệp cấu hình có bí mật. Dùng tệp cấu hình mẫu không chứa bí mật và cấu hình riêng trên từng máy.
- Không dùng `git push --force` lên nhánh dùng chung (`main`, `develop`) hoặc nhánh có người khác đang làm.
- Không commit trực tiếp lên `main`/`develop`; dùng nhánh riêng và PR để giữ lịch sử review.
- Không dùng `git add .` khi chưa xem `git status`; kiểm tra nội dung stage bằng `git diff --staged`.
- Nếu nhánh local và GitHub lệch nhau, chạy `git fetch origin` và xem `git status`/`git log` trước khi quyết định đồng bộ. Không xóa hoặc ghi đè commit khi chưa hiểu hậu quả.
- Khi Git báo `nothing to commit, working tree clean`, hiện không có thay đổi local chưa lưu. Nếu vừa sửa tệp mà vẫn thấy thông báo này, kiểm tra lại tệp và thư mục đang mở.
- Khi `git push` báo không có quyền, xác nhận đã đăng nhập đúng tài khoản GitHub và tài khoản đó có quyền cộng tác trên repository; không chia sẻ token cho thành viên khác.
