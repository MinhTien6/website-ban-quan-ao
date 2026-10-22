-- =====================================================================
--  CSDL: shop_quanao  |  Website bán quần áo (PHP + MySQL)
--  Bộ ký tự: utf8mb4_unicode_ci (lưu tiếng Việt có dấu)
--  Cách dùng: phpMyAdmin -> Import -> chọn file này (có thể import lại nhiều lần)
--
--  TÀI KHOẢN DEMO
--    Admin : admin@shop.local    / Admin@123
--    User 1: lan.nguyen@example.com / User@123
--    User 2: minh.tran@example.com  / User@123
--    User 3: (tài khoản bị khóa) khoa@example.com / User@123
--
--  Ảnh sản phẩm: image_path trỏ tới public/uploads/products/<slug>-1.jpg ...
--  (TV3 đặt file ảnh đúng tên, hoặc sửa đường dẫn trong bảng product_images)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS shop_quanao
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop_quanao;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa bảng cũ (theo thứ tự ngược phụ thuộc) để import lại không bị lỗi
DROP TABLE IF EXISTS cart_items, carts, wishlists, reviews, order_items, orders,
  coupons, product_variants, product_images, products, categories, banners,
  password_resets, users;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  1. TẠO BẢNG
-- =====================================================================

-- Người dùng (role: admin / user; status: 1 = hoạt động, 0 = bị khóa)
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  phone         VARCHAR(20)  DEFAULT NULL,
  address       VARCHAR(255) DEFAULT NULL,
  avatar        VARCHAR(255) DEFAULT NULL,
  role          ENUM('admin','user') NOT NULL DEFAULT 'user',
  status        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Token đặt lại mật khẩu (TV2)
CREATE TABLE password_resets (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(150) NOT NULL,
  token      VARCHAR(100) NOT NULL,
  expires_at DATETIME     NOT NULL,
  KEY idx_pr_token (token),
  KEY idx_pr_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Danh mục (parent_id: danh mục cha, NULL nếu là danh mục gốc)
CREATE TABLE categories (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  slug        VARCHAR(120) NOT NULL,
  description TEXT         DEFAULT NULL,
  parent_id   INT          DEFAULT NULL,
  status      TINYINT(1)   NOT NULL DEFAULT 1,
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_parent (parent_id),
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id)
    REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sản phẩm (sale_price NULL = không giảm giá; description là HTML từ editor)
CREATE TABLE products (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(200)  NOT NULL,
  slug        VARCHAR(220)  NOT NULL,
  price       DECIMAL(12,0) NOT NULL,
  sale_price  DECIMAL(12,0) DEFAULT NULL,
  description TEXT          DEFAULT NULL,
  category_id INT           NOT NULL,
  status      TINYINT(1)    NOT NULL DEFAULT 1,
  views       INT           NOT NULL DEFAULT 0,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_products_slug (slug),
  KEY idx_products_category (category_id),
  KEY idx_products_status_created (status, created_at),
  KEY idx_products_price (price),
  FULLTEXT KEY ft_products_name (name),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ảnh sản phẩm (nhiều ảnh / sản phẩm, is_main = ảnh đại diện)
CREATE TABLE product_images (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT          NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_main    TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order INT          NOT NULL DEFAULT 0,
  KEY idx_pi_product (product_id, sort_order),
  CONSTRAINT fk_pi_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Biến thể size/màu kèm tồn kho
CREATE TABLE product_variants (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT         NOT NULL,
  size       VARCHAR(10) DEFAULT NULL,
  color      VARCHAR(30) DEFAULT NULL,
  stock      INT         NOT NULL DEFAULT 0,
  UNIQUE KEY uq_variant (product_id, size, color),
  CONSTRAINT fk_pv_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Giỏ hàng lưu trong DB (tùy chọn; khách chưa đăng nhập dùng $_SESSION)
CREATE TABLE carts (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  UNIQUE KEY uq_carts_user (user_id),
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cart_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  cart_id    INT NOT NULL,
  variant_id INT NOT NULL,
  quantity   INT NOT NULL DEFAULT 1,
  UNIQUE KEY uq_cart_variant (cart_id, variant_id),
  CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id)
    REFERENCES carts(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ci_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mã giảm giá (discount_type: percent = %, amount = số tiền cố định)
CREATE TABLE coupons (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  code           VARCHAR(30)   NOT NULL,
  discount_type  ENUM('percent','amount') NOT NULL DEFAULT 'percent',
  discount_value DECIMAL(12,0) NOT NULL,
  min_order      DECIMAL(12,0) NOT NULL DEFAULT 0,
  expires_at     DATETIME      DEFAULT NULL,
  usage_limit    INT           DEFAULT NULL,
  UNIQUE KEY uq_coupons_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Đơn hàng. Luồng: pending -> confirmed -> shipping -> completed (nhánh: cancelled)
CREATE TABLE orders (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT           NOT NULL,
  receiver_name  VARCHAR(100)  NOT NULL,
  phone          VARCHAR(20)   NOT NULL,
  address        VARCHAR(255)  NOT NULL,
  total          DECIMAL(12,0) NOT NULL,
  payment_method ENUM('cod','bank') NOT NULL DEFAULT 'cod',
  status         ENUM('pending','confirmed','shipping','completed','cancelled') NOT NULL DEFAULT 'pending',
  coupon_id      INT           DEFAULT NULL,
  created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_orders_user (user_id),
  KEY idx_orders_status_created (status, created_at),
  KEY idx_orders_coupon (coupon_id),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_orders_coupon FOREIGN KEY (coupon_id)
    REFERENCES coupons(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chi tiết đơn hàng (price = giá tại thời điểm mua, không đổi khi shop đổi giá)
CREATE TABLE order_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT           NOT NULL,
  product_id INT           NOT NULL,
  variant_id INT           DEFAULT NULL,
  quantity   INT           NOT NULL,
  price      DECIMAL(12,0) NOT NULL,
  KEY idx_oi_order (order_id),
  KEY idx_oi_product (product_id),
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_oi_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Đánh giá (status: 0 = chờ duyệt, 1 = hiển thị)
CREATE TABLE reviews (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT        NOT NULL,
  user_id    INT        NOT NULL,
  rating     TINYINT    NOT NULL,
  comment    TEXT       DEFAULT NULL,
  status     TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_reviews_product (product_id, status),
  KEY idx_reviews_user (user_id),
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yêu thích (mỗi user chỉ thích 1 sản phẩm 1 lần)
CREATE TABLE wishlists (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  product_id INT NOT NULL,
  UNIQUE KEY uq_wishlist (user_id, product_id),
  CONSTRAINT fk_wl_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_wl_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Banner slider trang chủ
CREATE TABLE banners (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(150) DEFAULT NULL,
  image_path VARCHAR(255) NOT NULL,
  link       VARCHAR(255) DEFAULT NULL,
  sort_order INT          NOT NULL DEFAULT 0,
  status     TINYINT(1)   NOT NULL DEFAULT 1,
  KEY idx_banners_status_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  2. DỮ LIỆU MẪU
-- =====================================================================
-- Người dùng
INSERT INTO users (id, full_name, email, password_hash, phone, address, avatar, role, status, created_at) VALUES
  (1, 'Quản trị viên', 'admin@shop.local', '$2y$10$lWIn5om4nh.WMIEx6jPKO.33l9TD0YSbaJ95MYbUyN1Knxm0bkn2u', '0900000001', 'Củ Chi, TP. Hồ Chí Minh', NULL, 'admin', 1, '2026-09-01 08:00:00'),
  (2, 'Nguyễn Thị Lan', 'lan.nguyen@example.com', '$2y$10$1.Io99/lojPEJVsBNeVAG.m5GJ8rYJqbaBB/qBJofb1OWZCjh0qe.', '0912345678', '12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', NULL, 'user', 1, '2026-09-10 09:30:00'),
  (3, 'Trần Văn Minh', 'minh.tran@example.com', '$2y$10$1.Io99/lojPEJVsBNeVAG.m5GJ8rYJqbaBB/qBJofb1OWZCjh0qe.', '0987654321', '45 Lê Lợi, Quận 3, TP. Hồ Chí Minh', NULL, 'user', 1, '2026-09-15 14:20:00'),
  (4, 'Lê Đăng Khoa', 'khoa@example.com', '$2y$10$1.Io99/lojPEJVsBNeVAG.m5GJ8rYJqbaBB/qBJofb1OWZCjh0qe.', '0933221100', '8 Trần Hưng Đạo, Quận 5, TP. Hồ Chí Minh', NULL, 'user', 0, '2026-09-20 10:00:00');

-- Danh mục (7, 8 là danh mục con của 'Áo khoác')
INSERT INTO categories (id, name, slug, description, parent_id, status) VALUES
  (1, 'Áo thun', 'ao-thun', 'Áo thun nam nữ chất liệu cotton thoáng mát', NULL, 1),
  (2, 'Áo sơ mi', 'ao-so-mi', 'Sơ mi công sở và dạo phố', NULL, 1),
  (3, 'Quần jean', 'quan-jean', 'Quần jean nam nữ nhiều kiểu dáng', NULL, 1),
  (4, 'Váy đầm', 'vay-dam', 'Váy, đầm, chân váy nữ', NULL, 1),
  (5, 'Áo khoác', 'ao-khoac', 'Áo khoác và áo ấm các loại', NULL, 1),
  (6, 'Phụ kiện', 'phu-kien', 'Mũ, thắt lưng, túi, khăn', NULL, 1),
  (7, 'Áo khoác bomber', 'ao-khoac-bomber', 'Bomber dù, bomber da', 5, 1),
  (8, 'Hoodie', 'hoodie', 'Áo hoodie nỉ, hoodie zip', 5, 1);

-- Sản phẩm (30 sản phẩm; một số có giá khuyến mãi)
INSERT INTO products (id, name, slug, price, sale_price, description, category_id, status, views, created_at) VALUES
  (1, 'Áo thun basic cotton cổ tròn', 'ao-thun-basic-cotton-co-tron', 189000, 149000, '<p>Áo thun cotton 100% mềm mịn, form regular dễ phối đồ.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 1, 1, 57, '2026-09-04 10:00:00'),
  (2, 'Áo thun oversize form rộng', 'ao-thun-oversize-form-rong', 249000, NULL, '<p>Form oversize phong cách Hàn Quốc, vải dày dặn không bai xù.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 1, 1, 94, '2026-09-07 10:00:00'),
  (3, 'Áo polo nam vải cá sấu', 'ao-polo-nam-vai-ca-sau', 299000, 259000, '<p>Polo pique thoáng khí, cổ dệt bền form, hợp đi làm và đi chơi.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 1, 1, 131, '2026-09-10 10:00:00'),
  (4, 'Áo thun in hình graphic', 'ao-thun-in-hinh-graphic', 229000, NULL, '<p>Họa tiết in sắc nét, giặt nhiều lần không phai.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 1, 1, 168, '2026-09-13 10:00:00'),
  (5, 'Áo croptop nữ ôm body', 'ao-croptop-nu-om-body', 169000, 129000, '<p>Croptop co giãn 4 chiều, tôn dáng, dễ mix với quần cạp cao.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 1, 1, 205, '2026-09-16 10:00:00'),
  (6, 'Áo thun tay dài thu đông', 'ao-thun-tay-dai-thu-dong', 279000, NULL, '<p>Chất liệu cotton dày vừa, giữ ấm nhẹ cho mùa se lạnh.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 1, 1, 242, '2026-09-19 10:00:00'),
  (7, 'Áo sơ mi trắng công sở', 'ao-so-mi-trang-cong-so', 349000, NULL, '<p>Sơ mi trắng chống nhăn, phù hợp môi trường văn phòng.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 2, 1, 29, '2026-09-22 10:00:00'),
  (8, 'Áo sơ mi kẻ sọc xanh', 'ao-so-mi-ke-soc-xanh', 329000, 279000, '<p>Họa tiết kẻ sọc thanh lịch, chất vải cotton pha thoáng mát.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 2, 1, 66, '2026-09-25 10:00:00'),
  (9, 'Áo sơ mi linen tay ngắn', 'ao-so-mi-linen-tay-ngan', 369000, NULL, '<p>Vải linen tự nhiên, mát và thấm hút mồ hôi tốt.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 2, 1, 103, '2026-09-28 10:00:00'),
  (10, 'Áo sơ mi flannel caro', 'ao-so-mi-flannel-caro', 399000, NULL, '<p>Flannel caro ấm áp, mặc như áo khoác nhẹ.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 2, 1, 140, '2026-09-03 10:00:00'),
  (11, 'Áo sơ mi nữ cổ nơ', 'ao-so-mi-nu-co-no', 359000, 299000, '<p>Thiết kế cổ nơ nữ tính, chất voan nhẹ bay bổng.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 2, 1, 177, '2026-09-06 10:00:00'),
  (12, 'Quần jean slim fit xanh đậm', 'quan-jean-slim-fit-xanh-dam', 549000, NULL, '<p>Jean co giãn nhẹ, ôm vừa vặn, wash xanh đậm dễ phối.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 3, 1, 214, '2026-09-09 10:00:00'),
  (13, 'Quần jean ống rộng nữ', 'quan-jean-ong-rong-nu', 529000, 459000, '<p>Ống rộng cạp cao, tôn chân dài, che khuyết điểm tốt.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 3, 1, 251, '2026-09-12 10:00:00'),
  (14, 'Quần jean rách gối', 'quan-jean-rach-goi', 579000, NULL, '<p>Phong cách streetwear, rách gối cá tính.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 3, 1, 38, '2026-09-15 10:00:00'),
  (15, 'Quần jean baggy', 'quan-jean-baggy', 559000, NULL, '<p>Form baggy thoải mái, vải denim dày dặn.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 3, 1, 75, '2026-09-18 10:00:00'),
  (16, 'Quần short jean nam', 'quan-short-jean-nam', 399000, 349000, '<p>Short jean năng động cho mùa hè, có co giãn.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 3, 1, 112, '2026-09-21 10:00:00'),
  (17, 'Váy midi hoa nhí', 'vay-midi-hoa-nhi', 459000, NULL, '<p>Váy midi họa tiết hoa nhí vintage, chất voan lót kín đáo.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 4, 1, 149, '2026-09-24 10:00:00'),
  (18, 'Đầm suông công sở', 'dam-suong-cong-so', 489000, 429000, '<p>Đầm suông tối giản, thanh lịch, che bắp tay.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 4, 1, 186, '2026-09-27 10:00:00'),
  (19, 'Chân váy chữ A', 'chan-vay-chu-a', 289000, NULL, '<p>Chân váy chữ A có lót trong, dễ phối áo thun hay sơ mi.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 4, 1, 223, '2026-09-02 10:00:00'),
  (20, 'Đầm maxi dự tiệc', 'dam-maxi-du-tiec', 699000, NULL, '<p>Đầm maxi dáng dài, tà xòe nhẹ, phù hợp đi tiệc.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 4, 1, 260, '2026-09-05 10:00:00'),
  (21, 'Váy xếp ly', 'vay-xep-ly', 379000, 329000, '<p>Váy xếp ly ly nhỏ, không nhăn, giữ nếp lâu.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 4, 1, 47, '2026-09-08 10:00:00'),
  (22, 'Áo khoác jean unisex', 'ao-khoac-jean-unisex', 649000, NULL, '<p>Jacket jean wash nhẹ, form unisex, phong cách bụi bặm.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 5, 1, 84, '2026-09-11 10:00:00'),
  (23, 'Áo bomber dù 2 lớp', 'ao-bomber-du-2-lop', 589000, 499000, '<p>Bomber vải dù chống gió, lót trong mềm mại.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 7, 1, 121, '2026-09-14 10:00:00'),
  (24, 'Áo bomber da PU', 'ao-bomber-da-pu', 749000, NULL, '<p>Da PU cao cấp, đường may chắc chắn, cá tính.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 7, 1, 158, '2026-09-17 10:00:00'),
  (25, 'Hoodie nỉ bông form rộng', 'hoodie-ni-bong-form-rong', 449000, NULL, '<p>Nỉ bông dày ấm, mũ 2 lớp, túi kangaroo tiện lợi.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 8, 1, 195, '2026-09-20 10:00:00'),
  (26, 'Hoodie zip khóa kéo', 'hoodie-zip-khoa-keo', 479000, 419000, '<p>Hoodie khóa kéo dễ mặc, vải nỉ da cá không xù.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 8, 1, 232, '2026-09-23 10:00:00'),
  (27, 'Mũ lưỡi trai thêu logo', 'mu-luoi-trai-theu-logo', 159000, NULL, '<p>Mũ lưỡi trai cotton, khóa điều chỉnh phía sau.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 6, 1, 269, '2026-09-26 10:00:00'),
  (28, 'Thắt lưng da bò', 'that-lung-da-bo', 289000, 249000, '<p>Dây nịt da bò thật, khóa kim loại không gỉ.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 6, 1, 56, '2026-09-01 10:00:00'),
  (29, 'Túi tote vải canvas', 'tui-tote-vai-canvas', 199000, NULL, '<p>Túi tote canvas dày, có khóa kéo và ngăn phụ.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 6, 1, 93, '2026-09-04 10:00:00'),
  (30, 'Khăn choàng len', 'khan-choang-len', 219000, NULL, '<p>Khăn len mềm mịn, giữ ấm cổ cho mùa lạnh.</p><ul><li>Chất liệu: theo mô tả sản phẩm</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 6, 1, 130, '2026-09-07 10:00:00');

-- Ảnh sản phẩm: mỗi sản phẩm 3 ảnh, ảnh 1 là ảnh chính
INSERT INTO product_images (id, product_id, image_path, is_main, sort_order) VALUES
  (1, 1, 'uploads/products/ao-thun-basic-cotton-co-tron-1.jpg', 1, 1),
  (2, 1, 'uploads/products/ao-thun-basic-cotton-co-tron-2.jpg', 0, 2),
  (3, 1, 'uploads/products/ao-thun-basic-cotton-co-tron-3.jpg', 0, 3),
  (4, 2, 'uploads/products/ao-thun-oversize-form-rong-1.jpg', 1, 1),
  (5, 2, 'uploads/products/ao-thun-oversize-form-rong-2.jpg', 0, 2),
  (6, 2, 'uploads/products/ao-thun-oversize-form-rong-3.jpg', 0, 3),
  (7, 3, 'uploads/products/ao-polo-nam-vai-ca-sau-1.jpg', 1, 1),
  (8, 3, 'uploads/products/ao-polo-nam-vai-ca-sau-2.jpg', 0, 2),
  (9, 3, 'uploads/products/ao-polo-nam-vai-ca-sau-3.jpg', 0, 3),
  (10, 4, 'uploads/products/ao-thun-in-hinh-graphic-1.jpg', 1, 1),
  (11, 4, 'uploads/products/ao-thun-in-hinh-graphic-2.jpg', 0, 2),
  (12, 4, 'uploads/products/ao-thun-in-hinh-graphic-3.jpg', 0, 3),
  (13, 5, 'uploads/products/ao-croptop-nu-om-body-1.jpg', 1, 1),
  (14, 5, 'uploads/products/ao-croptop-nu-om-body-2.jpg', 0, 2),
  (15, 5, 'uploads/products/ao-croptop-nu-om-body-3.jpg', 0, 3),
  (16, 6, 'uploads/products/ao-thun-tay-dai-thu-dong-1.jpg', 1, 1),
  (17, 6, 'uploads/products/ao-thun-tay-dai-thu-dong-2.jpg', 0, 2),
  (18, 6, 'uploads/products/ao-thun-tay-dai-thu-dong-3.jpg', 0, 3),
  (19, 7, 'uploads/products/ao-so-mi-trang-cong-so-1.jpg', 1, 1),
  (20, 7, 'uploads/products/ao-so-mi-trang-cong-so-2.jpg', 0, 2),
  (21, 7, 'uploads/products/ao-so-mi-trang-cong-so-3.jpg', 0, 3),
  (22, 8, 'uploads/products/ao-so-mi-ke-soc-xanh-1.jpg', 1, 1),
  (23, 8, 'uploads/products/ao-so-mi-ke-soc-xanh-2.jpg', 0, 2),
  (24, 8, 'uploads/products/ao-so-mi-ke-soc-xanh-3.jpg', 0, 3),
  (25, 9, 'uploads/products/ao-so-mi-linen-tay-ngan-1.jpg', 1, 1),
  (26, 9, 'uploads/products/ao-so-mi-linen-tay-ngan-2.jpg', 0, 2),
  (27, 9, 'uploads/products/ao-so-mi-linen-tay-ngan-3.jpg', 0, 3),
  (28, 10, 'uploads/products/ao-so-mi-flannel-caro-1.jpg', 1, 1),
  (29, 10, 'uploads/products/ao-so-mi-flannel-caro-2.jpg', 0, 2),
  (30, 10, 'uploads/products/ao-so-mi-flannel-caro-3.jpg', 0, 3),
  (31, 11, 'uploads/products/ao-so-mi-nu-co-no-1.jpg', 1, 1),
  (32, 11, 'uploads/products/ao-so-mi-nu-co-no-2.jpg', 0, 2),
  (33, 11, 'uploads/products/ao-so-mi-nu-co-no-3.jpg', 0, 3),
  (34, 12, 'uploads/products/quan-jean-slim-fit-xanh-dam-1.jpg', 1, 1),
  (35, 12, 'uploads/products/quan-jean-slim-fit-xanh-dam-2.jpg', 0, 2),
  (36, 12, 'uploads/products/quan-jean-slim-fit-xanh-dam-3.jpg', 0, 3),
  (37, 13, 'uploads/products/quan-jean-ong-rong-nu-1.jpg', 1, 1),
  (38, 13, 'uploads/products/quan-jean-ong-rong-nu-2.jpg', 0, 2),
  (39, 13, 'uploads/products/quan-jean-ong-rong-nu-3.jpg', 0, 3),
  (40, 14, 'uploads/products/quan-jean-rach-goi-1.jpg', 1, 1),
  (41, 14, 'uploads/products/quan-jean-rach-goi-2.jpg', 0, 2),
  (42, 14, 'uploads/products/quan-jean-rach-goi-3.jpg', 0, 3),
  (43, 15, 'uploads/products/quan-jean-baggy-1.jpg', 1, 1),
  (44, 15, 'uploads/products/quan-jean-baggy-2.jpg', 0, 2),
  (45, 15, 'uploads/products/quan-jean-baggy-3.jpg', 0, 3),
  (46, 16, 'uploads/products/quan-short-jean-nam-1.jpg', 1, 1),
  (47, 16, 'uploads/products/quan-short-jean-nam-2.jpg', 0, 2),
  (48, 16, 'uploads/products/quan-short-jean-nam-3.jpg', 0, 3),
  (49, 17, 'uploads/products/vay-midi-hoa-nhi-1.jpg', 1, 1),
  (50, 17, 'uploads/products/vay-midi-hoa-nhi-2.jpg', 0, 2),
  (51, 17, 'uploads/products/vay-midi-hoa-nhi-3.jpg', 0, 3),
  (52, 18, 'uploads/products/dam-suong-cong-so-1.jpg', 1, 1),
  (53, 18, 'uploads/products/dam-suong-cong-so-2.jpg', 0, 2),
  (54, 18, 'uploads/products/dam-suong-cong-so-3.jpg', 0, 3),
  (55, 19, 'uploads/products/chan-vay-chu-a-1.jpg', 1, 1),
  (56, 19, 'uploads/products/chan-vay-chu-a-2.jpg', 0, 2),
  (57, 19, 'uploads/products/chan-vay-chu-a-3.jpg', 0, 3),
  (58, 20, 'uploads/products/dam-maxi-du-tiec-1.jpg', 1, 1),
  (59, 20, 'uploads/products/dam-maxi-du-tiec-2.jpg', 0, 2),
  (60, 20, 'uploads/products/dam-maxi-du-tiec-3.jpg', 0, 3),
  (61, 21, 'uploads/products/vay-xep-ly-1.jpg', 1, 1),
  (62, 21, 'uploads/products/vay-xep-ly-2.jpg', 0, 2),
  (63, 21, 'uploads/products/vay-xep-ly-3.jpg', 0, 3),
  (64, 22, 'uploads/products/ao-khoac-jean-unisex-1.jpg', 1, 1),
  (65, 22, 'uploads/products/ao-khoac-jean-unisex-2.jpg', 0, 2),
  (66, 22, 'uploads/products/ao-khoac-jean-unisex-3.jpg', 0, 3),
  (67, 23, 'uploads/products/ao-bomber-du-2-lop-1.jpg', 1, 1),
  (68, 23, 'uploads/products/ao-bomber-du-2-lop-2.jpg', 0, 2),
  (69, 23, 'uploads/products/ao-bomber-du-2-lop-3.jpg', 0, 3),
  (70, 24, 'uploads/products/ao-bomber-da-pu-1.jpg', 1, 1),
  (71, 24, 'uploads/products/ao-bomber-da-pu-2.jpg', 0, 2),
  (72, 24, 'uploads/products/ao-bomber-da-pu-3.jpg', 0, 3),
  (73, 25, 'uploads/products/hoodie-ni-bong-form-rong-1.jpg', 1, 1),
  (74, 25, 'uploads/products/hoodie-ni-bong-form-rong-2.jpg', 0, 2),
  (75, 25, 'uploads/products/hoodie-ni-bong-form-rong-3.jpg', 0, 3),
  (76, 26, 'uploads/products/hoodie-zip-khoa-keo-1.jpg', 1, 1),
  (77, 26, 'uploads/products/hoodie-zip-khoa-keo-2.jpg', 0, 2),
  (78, 26, 'uploads/products/hoodie-zip-khoa-keo-3.jpg', 0, 3),
  (79, 27, 'uploads/products/mu-luoi-trai-theu-logo-1.jpg', 1, 1),
  (80, 27, 'uploads/products/mu-luoi-trai-theu-logo-2.jpg', 0, 2),
  (81, 27, 'uploads/products/mu-luoi-trai-theu-logo-3.jpg', 0, 3),
  (82, 28, 'uploads/products/that-lung-da-bo-1.jpg', 1, 1),
  (83, 28, 'uploads/products/that-lung-da-bo-2.jpg', 0, 2),
  (84, 28, 'uploads/products/that-lung-da-bo-3.jpg', 0, 3),
  (85, 29, 'uploads/products/tui-tote-vai-canvas-1.jpg', 1, 1),
  (86, 29, 'uploads/products/tui-tote-vai-canvas-2.jpg', 0, 2),
  (87, 29, 'uploads/products/tui-tote-vai-canvas-3.jpg', 0, 3),
  (88, 30, 'uploads/products/khan-choang-len-1.jpg', 1, 1),
  (89, 30, 'uploads/products/khan-choang-len-2.jpg', 0, 2),
  (90, 30, 'uploads/products/khan-choang-len-3.jpg', 0, 3);

-- Biến thể size/màu + tồn kho (SP 14 sắp hết hàng, SP 5 size S hết hàng để demo cảnh báo)
INSERT INTO product_variants (id, product_id, size, color, stock) VALUES
  (1, 1, 'S', 'Trắng', 15),
  (2, 1, 'M', 'Trắng', 18),
  (3, 1, 'L', 'Trắng', 21),
  (4, 1, 'S', 'Đen', 24),
  (5, 1, 'M', 'Đen', 27),
  (6, 1, 'L', 'Đen', 30),
  (7, 2, 'S', 'Be', 10),
  (8, 2, 'M', 'Be', 13),
  (9, 2, 'L', 'Be', 16),
  (10, 2, 'S', 'Xám', 19),
  (11, 2, 'M', 'Xám', 22),
  (12, 2, 'L', 'Xám', 25),
  (13, 3, 'S', 'Xanh navy', 5),
  (14, 3, 'M', 'Xanh navy', 8),
  (15, 3, 'L', 'Xanh navy', 11),
  (16, 3, 'S', 'Trắng', 14),
  (17, 3, 'M', 'Trắng', 17),
  (18, 3, 'L', 'Trắng', 20),
  (19, 4, 'S', 'Đen', 30),
  (20, 4, 'M', 'Đen', 33),
  (21, 4, 'L', 'Đen', 6),
  (22, 4, 'S', 'Trắng', 9),
  (23, 4, 'M', 'Trắng', 12),
  (24, 4, 'L', 'Trắng', 15),
  (25, 5, 'S', 'Hồng', 0),
  (26, 5, 'M', 'Hồng', 28),
  (27, 5, 'L', 'Hồng', 31),
  (28, 5, 'S', 'Đen', 0),
  (29, 5, 'M', 'Đen', 7),
  (30, 5, 'L', 'Đen', 10),
  (31, 6, 'S', 'Xám', 20),
  (32, 6, 'M', 'Xám', 23),
  (33, 6, 'L', 'Xám', 26),
  (34, 6, 'S', 'Đen', 29),
  (35, 6, 'M', 'Đen', 32),
  (36, 6, 'L', 'Đen', 5),
  (37, 7, 'S', 'Trắng', 15),
  (38, 7, 'M', 'Trắng', 18),
  (39, 7, 'L', 'Trắng', 21),
  (40, 7, 'S', 'Xanh nhạt', 24),
  (41, 7, 'M', 'Xanh nhạt', 27),
  (42, 7, 'L', 'Xanh nhạt', 30),
  (43, 8, 'S', 'Xanh', 10),
  (44, 8, 'M', 'Xanh', 13),
  (45, 8, 'L', 'Xanh', 16),
  (46, 8, 'S', 'Xanh navy', 19),
  (47, 8, 'M', 'Xanh navy', 22),
  (48, 8, 'L', 'Xanh navy', 25),
  (49, 9, 'S', 'Be', 5),
  (50, 9, 'M', 'Be', 8),
  (51, 9, 'L', 'Be', 11),
  (52, 9, 'S', 'Trắng', 14),
  (53, 9, 'M', 'Trắng', 17),
  (54, 9, 'L', 'Trắng', 20),
  (55, 10, 'S', 'Đỏ', 30),
  (56, 10, 'M', 'Đỏ', 33),
  (57, 10, 'L', 'Đỏ', 6),
  (58, 10, 'S', 'Xanh rêu', 9),
  (59, 10, 'M', 'Xanh rêu', 12),
  (60, 10, 'L', 'Xanh rêu', 15),
  (61, 11, 'S', 'Kem', 25),
  (62, 11, 'M', 'Kem', 28),
  (63, 11, 'L', 'Kem', 31),
  (64, 11, 'S', 'Hồng', 34),
  (65, 11, 'M', 'Hồng', 7),
  (66, 11, 'L', 'Hồng', 10),
  (67, 12, '29', 'Xanh đậm', 20),
  (68, 12, '30', 'Xanh đậm', 23),
  (69, 12, '31', 'Xanh đậm', 26),
  (70, 12, '32', 'Xanh đậm', 29),
  (71, 13, '29', 'Xanh nhạt', 9),
  (72, 13, '30', 'Xanh nhạt', 12),
  (73, 13, '31', 'Xanh nhạt', 15),
  (74, 13, '32', 'Xanh nhạt', 18),
  (75, 13, '29', 'Xanh đậm', 21),
  (76, 13, '30', 'Xanh đậm', 24),
  (77, 13, '31', 'Xanh đậm', 27),
  (78, 13, '32', 'Xanh đậm', 30),
  (79, 14, '29', 'Xanh nhạt', 2),
  (80, 14, '30', 'Xanh nhạt', 2),
  (81, 14, '31', 'Xanh nhạt', 16),
  (82, 14, '32', 'Xanh nhạt', 19),
  (83, 15, '29', 'Đen', 29),
  (84, 15, '30', 'Đen', 32),
  (85, 15, '31', 'Đen', 5),
  (86, 15, '32', 'Đen', 8),
  (87, 15, '29', 'Xanh đậm', 11),
  (88, 15, '30', 'Xanh đậm', 14),
  (89, 15, '31', 'Xanh đậm', 17),
  (90, 15, '32', 'Xanh đậm', 20),
  (91, 16, '29', 'Xanh nhạt', 30),
  (92, 16, '30', 'Xanh nhạt', 33),
  (93, 16, '31', 'Xanh nhạt', 6),
  (94, 16, '32', 'Xanh nhạt', 9),
  (95, 16, '29', 'Đen', 12),
  (96, 16, '30', 'Đen', 15),
  (97, 16, '31', 'Đen', 18),
  (98, 16, '32', 'Đen', 21),
  (99, 17, 'S', 'Vàng', 31),
  (100, 17, 'M', 'Vàng', 34),
  (101, 17, 'L', 'Vàng', 7),
  (102, 17, 'S', 'Xanh', 10),
  (103, 17, 'M', 'Xanh', 13),
  (104, 17, 'L', 'Xanh', 16),
  (105, 18, 'S', 'Đen', 26),
  (106, 18, 'M', 'Đen', 29),
  (107, 18, 'L', 'Đen', 32),
  (108, 18, 'S', 'Be', 5),
  (109, 18, 'M', 'Be', 8),
  (110, 18, 'L', 'Be', 11),
  (111, 19, 'S', 'Đen', 21),
  (112, 19, 'M', 'Đen', 24),
  (113, 19, 'L', 'Đen', 27),
  (114, 19, 'S', 'Xám', 30),
  (115, 19, 'M', 'Xám', 33),
  (116, 19, 'L', 'Xám', 6),
  (117, 20, 'S', 'Đỏ', 16),
  (118, 20, 'M', 'Đỏ', 19),
  (119, 20, 'L', 'Đỏ', 22),
  (120, 20, 'S', 'Xanh navy', 25),
  (121, 20, 'M', 'Xanh navy', 28),
  (122, 20, 'L', 'Xanh navy', 31),
  (123, 21, 'S', 'Kem', 11),
  (124, 21, 'M', 'Kem', 14),
  (125, 21, 'L', 'Kem', 17),
  (126, 21, 'S', 'Hồng', 20),
  (127, 21, 'M', 'Hồng', 23),
  (128, 21, 'L', 'Hồng', 26),
  (129, 22, 'S', 'Xanh nhạt', 6),
  (130, 22, 'M', 'Xanh nhạt', 9),
  (131, 22, 'L', 'Xanh nhạt', 12),
  (132, 22, 'XL', 'Xanh nhạt', 15),
  (133, 22, 'S', 'Xanh đậm', 18),
  (134, 22, 'M', 'Xanh đậm', 21),
  (135, 22, 'L', 'Xanh đậm', 24),
  (136, 22, 'XL', 'Xanh đậm', 27),
  (137, 23, 'S', 'Đen', 7),
  (138, 23, 'M', 'Đen', 10),
  (139, 23, 'L', 'Đen', 13),
  (140, 23, 'XL', 'Đen', 16),
  (141, 23, 'S', 'Xanh rêu', 19),
  (142, 23, 'M', 'Xanh rêu', 22),
  (143, 23, 'L', 'Xanh rêu', 25),
  (144, 23, 'XL', 'Xanh rêu', 28),
  (145, 24, 'S', 'Đen', 8),
  (146, 24, 'M', 'Đen', 11),
  (147, 24, 'L', 'Đen', 14),
  (148, 24, 'XL', 'Đen', 17),
  (149, 24, 'S', 'Nâu', 20),
  (150, 24, 'M', 'Nâu', 23),
  (151, 24, 'L', 'Nâu', 26),
  (152, 24, 'XL', 'Nâu', 29),
  (153, 25, 'S', 'Xám', 9),
  (154, 25, 'M', 'Xám', 12),
  (155, 25, 'L', 'Xám', 15),
  (156, 25, 'XL', 'Xám', 18),
  (157, 25, 'S', 'Đen', 21),
  (158, 25, 'M', 'Đen', 24),
  (159, 25, 'L', 'Đen', 27),
  (160, 25, 'XL', 'Đen', 30),
  (161, 26, 'S', 'Be', 10),
  (162, 26, 'M', 'Be', 13),
  (163, 26, 'L', 'Be', 16),
  (164, 26, 'XL', 'Be', 19),
  (165, 26, 'S', 'Xanh navy', 22),
  (166, 26, 'M', 'Xanh navy', 25),
  (167, 26, 'L', 'Xanh navy', 28),
  (168, 26, 'XL', 'Xanh navy', 31),
  (169, 27, 'Free', 'Đen', 11),
  (170, 27, 'Free', 'Trắng', 14),
  (171, 28, 'Free', 'Đen', 24),
  (172, 28, 'Free', 'Nâu', 27),
  (173, 29, 'Free', 'Be', 7),
  (174, 29, 'Free', 'Đen', 10),
  (175, 30, 'Free', 'Xám', 20),
  (176, 30, 'Free', 'Đỏ', 23);

-- Mã giảm giá (HETHAN đã hết hạn để test dữ liệu biên)
INSERT INTO coupons (id, code, discount_type, discount_value, min_order, expires_at, usage_limit) VALUES
  (1, 'WELCOME10', 'percent', 10, 300000, '2026-12-31 23:59:59', 100),
  (2, 'GIAM50K', 'amount', 50000, 500000, '2026-12-31 23:59:59', 50),
  (3, 'SALE20', 'percent', 20, 800000, '2026-11-30 23:59:59', 30),
  (4, 'HETHAN', 'percent', 15, 0, '2026-01-31 23:59:59', 10);

-- Đơn hàng mẫu đủ các trạng thái (total = tổng tiền hàng - giảm giá)
INSERT INTO orders (id, user_id, receiver_name, phone, address, total, payment_method, status, coupon_id, created_at) VALUES
  (1, 2, 'Nguyễn Thị Lan', '0912345678', '12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', 762300, 'cod', 'completed', 1, '2026-10-01 09:15:00'),
  (2, 3, 'Trần Văn Minh', '0987654321', '45 Lê Lợi, Quận 3, TP. Hồ Chí Minh', 788000, 'bank', 'shipping', NULL, '2026-10-12 14:40:00'),
  (3, 2, 'Nguyễn Thị Lan', '0912345678', '12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', 558000, 'cod', 'pending', 2, '2026-10-20 19:05:00'),
  (4, 3, 'Trần Văn Minh', '0987654321', '45 Lê Lợi, Quận 3, TP. Hồ Chí Minh', 349000, 'cod', 'cancelled', NULL, '2026-10-05 11:30:00'),
  (5, 2, 'Nguyễn Thị Lan', '0912345678', '12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', 897000, 'bank', 'confirmed', NULL, '2026-10-21 08:50:00');

-- Chi tiết đơn hàng
INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, price) VALUES
  (1, 1, 1, 2, 2, 149000),
  (2, 1, 12, 68, 1, 549000),
  (3, 2, 17, 99, 1, 459000),
  (4, 2, 21, 125, 1, 329000),
  (5, 3, 25, 155, 1, 449000),
  (6, 3, 27, 169, 1, 159000),
  (7, 4, 7, 38, 1, 349000),
  (8, 5, 23, 140, 1, 499000),
  (9, 5, 29, 173, 2, 199000);

-- Đánh giá (đánh giá số 5 đang chờ duyệt)
INSERT INTO reviews (id, product_id, user_id, rating, comment, status, created_at) VALUES
  (1, 1, 2, 5, 'Vải mềm, mặc rất thoải mái, giao hàng nhanh.', 1, '2026-10-04 20:00:00'),
  (2, 12, 2, 4, 'Quần đẹp, form chuẩn, hơi dài một chút.', 1, '2026-10-05 21:10:00'),
  (3, 17, 3, 5, 'Váy xinh, đúng như hình, sẽ ủng hộ tiếp.', 1, '2026-10-18 09:00:00'),
  (4, 3, 3, 4, 'Áo polo đẹp, đường may kỹ.', 1, '2026-10-10 15:30:00'),
  (5, 21, 3, 3, 'Màu hơi khác ảnh một chút.', 0, '2026-10-19 12:45:00');

-- Yêu thích
INSERT INTO wishlists (id, user_id, product_id) VALUES
  (1, 2, 17),
  (2, 2, 23),
  (3, 3, 12),
  (4, 3, 25);

-- Giỏ hàng trong DB của user 2
INSERT INTO carts (id, user_id) VALUES
  (1, 2);

INSERT INTO cart_items (id, cart_id, variant_id, quantity) VALUES
  (1, 1, 49, 1),
  (2, 1, 162, 2);

-- Banner trang chủ
INSERT INTO banners (id, title, image_path, link, sort_order, status) VALUES
  (1, 'Bộ sưu tập Thu Đông 2026', 'uploads/banners/banner-1.jpg', 'products.php', 1, 1),
  (2, 'Sale đến 30% - Áo khoác & Hoodie', 'uploads/banners/banner-2.jpg', 'products.php?category=ao-khoac', 2, 1),
  (3, 'Freeship đơn từ 500.000đ', 'uploads/banners/banner-3.jpg', 'products.php', 3, 1);

-- Hết file. Kiểm tra nhanh:  SELECT COUNT(*) FROM products;  -- kỳ vọng 30git add database/shop_quanao.sql tests/check_db.php