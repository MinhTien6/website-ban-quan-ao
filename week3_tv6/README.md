# TV6 – Nhiệm vụ tuần 3

Thư mục này gom bản đầy đủ các file TV6 đã hoàn thiện, gồm phần tuần 1, tuần 2 và phần cập nhật tuần 3:

- `admin/index.php`: Dashboard thống kê trạng thái đơn, doanh thu, khách hàng và sản phẩm sắp hết hàng.
- `admin/orders.php`: Danh sách đơn có tìm kiếm, lọc và phân trang.
- `admin/order_detail.php`: Trang chi tiết và cập nhật trạng thái đơn.
- `models/Order.php`: Tìm kiếm, phân trang, thống kê dashboard và xử lý trạng thái/hoàn kho.
- `public/css/style.css`: Giao diện dashboard, bảng đơn, chi tiết đơn và responsive.
- `tests/test_cases.md`: Bộ test case của dự án, có các case TV6 tuần 1, 2 và 3.
- `tests/test_week3.php`: Kiểm thử CLI cho luồng trạng thái, CSRF, hoàn kho và thống kê.

Các bản gốc đang được tích hợp theo cấu trúc chính của website. Các file trong thư mục này là bản gom để tiện xem và nộp phần việc TV6.

Chạy kiểm thử tuần 3 bằng `php tests/test_week3.php` sau khi cấu hình kết nối MySQL. Script tạo dữ liệu kiểm thử tạm và xóa sau khi chạy.
