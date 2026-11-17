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
--  Ảnh sản phẩm: image_path trỏ tới public/uploads/products/ (Bộ ảnh thực tế đồng bộ từ feature/Hau, không có phụ kiện)
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
  slug        VARCHAR(100) NOT NULL,
  description TEXT         DEFAULT NULL,
  parent_id   INT          DEFAULT NULL,
  status      TINYINT(1)   NOT NULL DEFAULT 1,
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_parent (parent_id),
  KEY idx_categories_status (status),
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id)
    REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sản phẩm (price: giá gốc; sale_price: giá khuyến mãi, NULL nếu không giảm)
CREATE TABLE products (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(200)  NOT NULL,
  slug        VARCHAR(200)  NOT NULL,
  price       DECIMAL(12,0) NOT NULL,
  sale_price  DECIMAL(12,0) DEFAULT NULL,
  description LONGTEXT      DEFAULT NULL,
  category_id INT           NOT NULL,
  status      TINYINT(1)    NOT NULL DEFAULT 1,
  views       INT           NOT NULL DEFAULT 0,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_products_slug (slug),
  KEY idx_products_cat_status (category_id, status),
  KEY idx_products_created (created_at),
  KEY idx_products_views (views),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thư viện ảnh sản phẩm (is_main: 1 = ảnh đại diện)
CREATE TABLE product_images (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT          NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_main    TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order INT          NOT NULL DEFAULT 0,
  KEY idx_pi_product_main (product_id, is_main),
  CONSTRAINT fk_pi_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Biến thể sản phẩm (size + color). Tồn kho stock quản lý tại đây
CREATE TABLE product_variants (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT         NOT NULL,
  size       VARCHAR(20) NOT NULL,
  color      VARCHAR(50) NOT NULL,
  stock      INT         NOT NULL DEFAULT 0,
  UNIQUE KEY uq_variant (product_id, size, color),
  KEY idx_variants_product (product_id),
  KEY idx_variants_stock (stock),
  CONSTRAINT fk_pv_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Giỏ hàng (mỗi user 1 giỏ; khách vãng lai lưu session)
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
  variant_id INT           NOT NULL,
  quantity   INT           NOT NULL DEFAULT 1,
  price      DECIMAL(12,0) NOT NULL,
  KEY idx_oi_order (order_id),
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_oi_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Đánh giá sản phẩm (status: 0 = chờ duyệt, 1 = đã duyệt hiển thị)
CREATE TABLE reviews (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT        NOT NULL,
  user_id    INT        NOT NULL,
  rating     TINYINT(1) NOT NULL,
  comment    TEXT       DEFAULT NULL,
  status     TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_reviews_product_status (product_id, status),
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Danh sách yêu thích
CREATE TABLE wishlists (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  product_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wishlist (user_id, product_id),
  CONSTRAINT fk_wl_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_wl_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Banner quảng cáo
CREATE TABLE banners (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(150) NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  link       VARCHAR(255) DEFAULT NULL,
  sort_order INT          NOT NULL DEFAULT 0,
  status     TINYINT(1)   NOT NULL DEFAULT 1
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

-- Danh mục
INSERT INTO categories (id, name, slug, description, parent_id, status) VALUES
  (1, 'Áo thun', 'ao-thun', 'Áo thun nam nữ chất liệu cotton thoáng mát, form rộng unisex, croptop', NULL, 1),
  (2, 'Áo sơ mi', 'ao-so-mi', 'Sơ mi công sở, sơ mi caro flannel, sơ mi linen và polo thể thao', NULL, 1),
  (3, 'Quần jean', 'quan-jean', 'Quần jean ống rộng, slim fit, rách gối, baggy và quần short thời trang', NULL, 1),
  (4, 'Váy đầm', 'vay-dam', 'Váy midi hoa nhí, đầm công sở, đầm maxi dự tiệc, chân váy chữ A, xếp ly', NULL, 1),
  (5, 'Áo khoác', 'ao-khoac', 'Áo khoác denim jean, varsity jacket và áo ấm thời trang', NULL, 1),
  (7, 'Áo khoác bomber', 'ao-khoac-bomber', 'Bomber dù 2 lớp chống gió, bomber da PU cao cấp', 5, 1),
  (8, 'Hoodie', 'hoodie', 'Áo hoodie nỉ bông form rộng, hoodie zip khóa kéo thời thượng', 5, 1);

-- Sản phẩm (30 sản phẩm thời trang phong cách Streetwear Gen Z)
INSERT INTO products (id, name, slug, price, sale_price, description, category_id, status, views, created_at) VALUES
  (1, 'Áo thun basic cotton cổ tròn', 'ao-thun-basic-cotton-co-tron', 189000, 149000, '<p>Áo thun cotton 100% mềm mịn, thấm hút mồ hôi cực tốt, form regular dễ phối đồ cho cả nam và nữ.</p><ul><li>Chất liệu: 100% Cotton 2 chiều cao cấp</li><li>Độ co giãn tốt, không bai dão sau nhiều lần giặt</li><li>Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</li></ul>', 1, 1, 1250, '2026-09-01 10:00:00'),
  (2, 'Áo thun oversize form rộng unisex', 'ao-thun-oversize-form-rong', 249000, 199000, '<p>Form áo rộng rãi chuẩn phong cách Streetwear Hàn Quốc, vải dày dặn 250gsm không bai xù.</p><ul><li>Chất liệu: Cotton sợi chải kỹ 250gsm</li><li>Kiểu dáng: Oversize Unisex thoải mái vận động</li><li>Phù hợp đi học, đi chơi, dạo phố cuối tuần</li></ul>', 1, 1, 980, '2026-09-02 10:00:00'),
  (3, 'Áo thun in hình graphic streetwear', 'ao-thun-in-hinh-graphic-streetwear', 279000, 219000, '<p>Thiết kế in họa tiết graphic độc quyền nổi bật cá tính, công nghệ in lụa sắc nét bền bỉ.</p><ul><li>Chất liệu: Cotton co giãn 4 chiều</li><li>Hình in kỹ thuật số không bong tróc khi giặt máy</li><li>Phong cách Streetwear cá tính, năng động</li></ul>', 1, 1, 1420, '2026-09-03 10:00:00'),
  (4, 'Áo thun tay dài thu đông', 'ao-thun-tay-dai-thu-dong', 259000, NULL, '<p>Áo thun tay dài chất cotton dày dặn giữ ấm nhẹ nhàng, kiểu dáng basic cực dễ mix-match.</p><ul><li>Chất liệu: Cotton nỉ mỏng mềm mại, giữ nhiệt tốt</li><li>Cổ áo bo dệt chắc chắn, không lo dão cổ</li><li>Thích hợp cho tiết trời se lạnh thu đông</li></ul>', 1, 1, 650, '2026-09-04 10:00:00'),
  (5, 'Áo croptop nữ ôm body tôn dáng', 'ao-croptop-nu-om-body-ton-dang', 199000, 159000, '<p>Croptop ôm body gợi cảm, chất thun gân tăm co giãn cực tốt giúp tôn đường cong quyến rũ.</p><ul><li>Chất liệu: Thun gân tăm Cotton Spandex co giãn</li><li>Form dáng ôm sát cơ thể, tôn eo thon</li><li>Phối cực xinh cùng quần ống rộng hoặc chân váy</li></ul>', 1, 1, 880, '2026-09-05 10:00:00'),
  (6, 'Áo croptop cổ vuông tay ngắn', 'ao-croptop-co-vuong-tay-ngan', 219000, NULL, '<p>Thiết kế cổ vuông khoe xương quai xanh quyến rũ, chất vải mịn mát cho những ngày hè rạng rỡ.</p><ul><li>Chất liệu: Cotton lụa mát lịm, thấm hút nhanh</li><li>Thiết kế cổ vuông thanh lịch, trẻ trung</li><li>Đường may tinh tế, form đứng dáng</li></ul>', 1, 1, 540, '2026-09-06 10:00:00'),
  (7, 'Áo polo nam vải cá sấu pique', 'ao-polo-nam-vai-ca-sau-pique', 299000, 249000, '<p>Polo pique thoáng khí, bo cổ dệt tinh xảo, lịch sự tại công sở và thoải mái khi đi chơi.</p><ul><li>Chất liệu: Vải cá sấu Pique 100% Cotton thấm hút</li><li>Bo cổ dệt chống cong vênh chuẩn form</li><li>Cúc áo dập logo sang trọng, đường may tỉ mỉ</li></ul>', 2, 1, 1100, '2026-09-07 10:00:00'),
  (8, 'Áo polo cổ bẻ thể thao năng động', 'ao-polo-co-be-the-thao-nang-dong', 319000, NULL, '<p>Polo thể thao chất vải mè co giãn đa chiều, nhanh khô, kháng khuẩn, mang lại vẻ ngoài khỏe khoắn.</p><ul><li>Chất liệu: Poly thể thao cao cấp nhanh khô Quick-Dry</li><li>Co giãn 4 chiều cực kỳ thoải mái khi vận động</li><li>Phù hợp thể thao ngoài trời và dạo phố năng động</li></ul>', 2, 1, 720, '2026-09-08 10:00:00'),
  (9, 'Áo sơ mi trắng công sở chống nhăn', 'ao-so-mi-trang-cong-so-chong-nhan', 349000, 289000, '<p>Sơ mi trắng thanh lịch chất vải Kate Ý chống nhăn tối đa, form slim fit tôn dáng công sở.</p><ul><li>Chất liệu: Kate Ý mềm mại, chống nhăn tự nhiên</li><li>Cổ áo đứng form chuẩn chỉ, cúc áo bóng loáng</li><li>Dễ ủi, bền màu, giữ dáng suốt cả ngày làm việc</li></ul>', 2, 1, 1350, '2026-09-09 10:00:00'),
  (10, 'Áo sơ mi kẻ sọc cao cấp', 'ao-so-mi-ke-soc-cao-cap', 369000, NULL, '<p>Họa tiết kẻ sọc hiện đại tạo hiệu ứng thanh mảnh cho vóc dáng, chất vải mềm mại thoáng mát.</p><ul><li>Chất liệu: Cotton blend sợi nhỏ thoáng khí</li><li>Họa tiết sọc dọc kẻ chỉ tinh tế thời trang</li><li>Mặc đơn hoặc khoác ngoài layer cực kỳ thời thượng</li></ul>', 2, 1, 890, '2026-09-10 10:00:00'),
  (11, 'Áo sơ mi linen tay ngắn thoáng mát', 'ao-so-mi-linen-tay-ngan-thoang-mat', 329000, 269000, '<p>Chất liệu vải linen tự nhiên mộc mạc, thấm hút mồ hôi tối đa, phong cách dạo phố thư thái ngày hè.</p><ul><li>Chất liệu: 100% Linen bột tự nhiên thân thiện môi trường</li><li>Form thả nhẹ nhàng phóng khoáng</li><li>Càng giặt càng mềm mịn, mát rượi</li></ul>', 2, 1, 640, '2026-09-11 10:00:00'),
  (12, 'Áo sơ mi flannel caro thời thượng', 'ao-so-mi-flannel-caro-thoi-thuong', 389000, 319000, '<p>Áo sơ mi Flannel caro dày dặn phong cách grunge thời thượng, cực đẹp khi khoác ngoài áo thun.</p><ul><li>Chất liệu: Dạ bông Flannel dệt nổi mềm mại</li><li>Họa tiết caro sắc nét, đậm chất Streetwear</li><li>Giữ ấm tốt, phong cách bụi bặm cá tính</li></ul>', 2, 1, 920, '2026-09-12 10:00:00'),
  (13, 'Áo khoác denim jean unisex', 'ao-khoac-denim-jean-unisex', 499000, 429000, '<p>Khoác denim jean wash nhẹ bụi bặm, form rộng unisex bền đẹp vượt thời gian, dễ phối mọi outfit.</p><ul><li>Chất liệu: Denim 12oz dày dặn, không co rút</li><li>Khuy kim loại đồng đúc cao cấp chống rỉ</li><li>Wash màu vintage thời trang cá tính</li></ul>', 5, 1, 1650, '2026-09-13 10:00:00'),
  (14, 'Áo khoác varsity bóng chày', 'ao-khoac-varsity-bong-chay', 549000, 459000, '<p>Varsity jacket phong cách học đường Mỹ năng động, thêu chữ nổi tỉ mỉ, bo viền sọc kinh điển.</p><ul><li>Chất liệu: Vải nỉ dạ phối tay da PU cao cấp</li><li>Thêu chữ nổi xù 3D sắc sảo</li><li>Khuy bấm kim loại chắc chắn chuẩn form áo bóng chày</li></ul>', 5, 1, 1890, '2026-09-14 10:00:00'),
  (15, 'Áo bomber dù 2 lớp chống gió', 'ao-bomber-du-2-lop-chong-gio', 589000, 499000, '<p>Bomber vải dù cản gió và chống nước nhẹ, lót trong mềm mại giữ ấm hoàn hảo trong ngày se lạnh.</p><ul><li>Chất liệu: Vải dù trượt nước 2 lớp cao cấp</li><li>Lót trong mềm mại, túi trong tiện dụng</li><li>Bo cổ và gấu áo dệt thun đàn hồi êm ái</li></ul>', 7, 1, 1320, '2026-09-15 10:00:00'),
  (16, 'Áo bomber da PU cao cấp', 'ao-bomber-da-pu-cao-cap', 749000, NULL, '<p>Chất da PU cao cấp không bong tróc, lót lụa sang trọng, tôn phong cách nam tính mạnh mẽ và sành điệu.</p><ul><li>Chất liệu: Da PU xử lý kỹ không bong nổ</li><li>Lớp lót lụa bóng sang trọng bên trong</li><li>Khóa kéo kim loại trơn tru, đường chỉ may 2 kim bền bỉ</li></ul>', 7, 1, 1580, '2026-09-16 10:00:00'),
  (17, 'Hoodie nỉ bông form rộng', 'hoodie-ni-bong-form-rong', 449000, NULL, '<p>Hoodie nỉ lót bông mềm mại giữ ấm tuyệt đối, mũ trùm 2 lớp đứng form kèm túi kangaroo tiện lợi.</p><ul><li>Chất liệu: Nỉ bông 350gsm siêu ấm áp</li><li>Mũ trùm 2 lớp có dây rút kim loại cao cấp</li><li>Túi bụng rộng rãi giữ ấm bàn tay hiệu quả</li></ul>', 8, 1, 2100, '2026-09-17 10:00:00'),
  (18, 'Hoodie zip khóa kéo nỉ chân cua', 'hoodie-zip-khoa-keo-ni-chan-cua', 479000, 419000, '<p>Hoodie zip tiện lợi dễ mặc khoác ngoài, chất nỉ chân cua dày dặn thoáng khí chuẩn streetwear.</p><ul><li>Chất liệu: Nỉ chân cua 100% Cotton thoáng khí</li><li>Khóa kéo YKK kim loại mượt mà bền chắc</li><li>Form rộng unisex năng động thoải mái</li></ul>', 8, 1, 1750, '2026-09-18 10:00:00'),
  (19, 'Quần jean slim fit wash xanh đậm', 'quan-jean-slim-fit-wash-xanh-dam', 399000, 339000, '<p>Quần jean form slim fit ôm vừa vặn tôn dáng, vải denim co giãn nhẹ thoải mái khi di chuyển.</p><ul><li>Chất liệu: Denim pha thun Spandex co giãn</li><li>Wash màu xanh đậm lịch sự, dễ phối giày và áo</li><li>Đường may gia cố đũng quần chắc chắn</li></ul>', 3, 1, 1450, '2026-09-19 10:00:00'),
  (20, 'Quần jean ống rộng cạp cao tôn dáng', 'quan-jean-ong-rong-cap-cao-ton-dang', 429000, 369000, '<p>Quần jean ống suông rộng cạp cao hack dáng cực đỉnh, che khuyết điểm chân hoàn hảo cho bạn trẻ.</p><ul><li>Chất liệu: Denim Cotton 100% đứng form</li><li>Cạp cao ôm eo tôn vòng 2 thon gọn</li><li>Ống suông rộng chuẩn phong cách thời trang Gen Z</li></ul>', 3, 1, 1980, '2026-09-20 10:00:00'),
  (21, 'Quần jean rách gối bụi bặm cá tính', 'quan-jean-rach-goi-bui-bam-ca-tinh', 459000, 389000, '<p>Điểm nhấn rách gối cá tính mang lại vẻ ngoài nổi bật đậm chất đường phố bụi bặm hiện đại.</p><ul><li>Chất liệu: Denim cao cấp rách gối thủ công tinh xảo</li><li>Không bị tưa chỉ quá mức sau khi giặt</li><li>Phong cách cá tính phá cách cho giới trẻ</li></ul>', 3, 1, 1120, '2026-09-21 10:00:00'),
  (22, 'Quần jean baggy thời trang trẻ trung', 'quan-jean-baggy-thoi-trang-tre-trung', 419000, NULL, '<p>Quần jean form baggy rộng phần hông và ôm nhẹ dần xuống cổ chân, thoải mái suốt cả ngày dài.</p><ul><li>Chất liệu: Denim mềm mại thoáng mát</li><li>Form baggy thoải mái che mọi khuyết điểm chân</li><li>Phối cùng sneaker và áo thun cực đẹp</li></ul>', 3, 1, 870, '2026-09-22 10:00:00'),
  (23, 'Quần short jean rách gấu năng động', 'quan-short-jean-rach-gau-nang-dong', 299000, 249000, '<p>Quần short jean rách gấu tua rua phong cách trẻ trung, item không thể thiếu trong tủ đồ mùa hè.</p><ul><li>Chất liệu: Denim dày dặn rách gấu thời thượng</li><li>Lưng thun hoặc cúc cài tiện dụng tôn dáng</li><li>Thích hợp đi biển, du lịch dạo phố</li></ul>', 3, 1, 960, '2026-09-23 10:00:00'),
  (24, 'Quần short thể thao co giãn thoáng khí', 'quan-short-the-thao-co-gian-thoang-khi', 219000, 179000, '<p>Chất liệu vải dù gió siêu nhẹ, túi khóa kéo an toàn, thoải mái khi tập gym hay chạy bộ ngoài trời.</p><ul><li>Chất liệu: Vải thun dù thể thao thoáng khí siêu nhẹ</li><li>Túi zip khóa kéo tiện lợi để điện thoại không rơi</li><li>Cạp chun co giãn có dây rút điều chỉnh</li></ul>', 3, 1, 1280, '2026-09-24 10:00:00'),
  (25, 'Váy midi hoa nhí vintage', 'vay-midi-hoa-nhi-vintage', 389000, 329000, '<p>Họa tiết hoa nhí nữ tính trên nền voan cát mềm mại, dáng váy xòe bồng bềnh thanh lịch cuốn hút.</p><ul><li>Chất liệu: Voan cát lụa lót trong kín đáo mát nhẹ</li><li>Họa tiết hoa nhí phong cách Hàn Quốc ngọt ngào</li><li>Tôn dáng thướt tha trong từng bước đi</li></ul>', 4, 1, 1680, '2026-09-25 10:00:00'),
  (26, 'Đầm suông công sở thanh lịch', 'dam-suong-cong-so-thanh-lich', 429000, NULL, '<p>Đầm suông form chữ A thanh lịch che khuyết điểm vòng 2, chất vải tuyết mưa đứng form sang trọng.</p><ul><li>Chất liệu: Vải tuyết mưa cao cấp không nhăn, không xù</li><li>Dáng suông thoải mái giấu dáng cực đỉnh</li><li>Phù hợp mặc đi làm công sở và gặp gỡ đối tác</li></ul>', 4, 1, 820, '2026-09-26 10:00:00'),
  (27, 'Đầm maxi dự tiệc chiết eo dáng dài', 'dam-maxi-du-tiec-chiet-eo-dang-dai', 549000, 459000, '<p>Đầm maxi dáng dài thướt tha chiết eo tôn vóc dáng, phù hợp dự tiệc hoặc chụp ảnh du lịch biển.</p><ul><li>Chất liệu: Tơ lụa mềm mại rủ nhẹ bồng bềnh</li><li>Chiết eo cao tôn dáng chân dài miên man</li><li>Màu sắc quyến rũ sang trọng cho các buổi tiệc</li></ul>', 4, 1, 1450, '2026-09-27 10:00:00'),
  (28, 'Chân váy chữ A lưng cao tôn dáng', 'chan-vay-chu-a-lung-cao-ton-dang', 279000, 229000, '<p>Chân váy chữ A lưng cao kèm quần bảo hộ bên trong tiện dụng, dễ phối cùng áo thun hoặc croptop.</p><ul><li>Chất liệu: Kaki tuyết mưa mềm mại đứng dáng</li><li>Có lót quần bảo hộ an toàn tự tin di chuyển</li><li>Dễ dàng phối cùng mọi mẫu áo phong cách</li></ul>', 4, 1, 1390, '2026-09-28 10:00:00'),
  (29, 'Chân váy xếp ly dáng dài thanh lịch', 'chan-vay-xep-ly-dang-dai-thanh-lich', 329000, 279000, '<p>Xếp ly tỉ mỉ bằng công nghệ ép nhiệt bền lâu, cạp chun thoải mái phong cách tiểu thư đài các.</p><ul><li>Chất liệu: Voan dập ly 2 lớp bồng bềnh chuẩn form</li><li>Nếp ly sắc sảo không mất nếp khi giặt</li><li>Lưng chun co giãn êm ái tôn vòng eo</li></ul>', 4, 1, 1140, '2026-09-19 14:00:00'),
  (30, 'Chân váy tennis xếp ly ngắn năng động', 'chan-vay-tennis-xep-ly-ngan-nang-dong', 259000, 219000, '<p>Chân váy tennis xếp ly ngắn phong cách năng động thể thao, có quần bảo hộ an toàn bên trong.</p><ul><li>Chất liệu: Vải tuyết mưa xếp ly đứng nếp thời trang</li><li>Có quần bảo hộ bên trong chống hớ hênh</li><li>Cực kỳ xinh xắn khi mix cùng áo polo hoặc thun baby tee</li></ul>', 4, 1, 1720, '2026-09-20 14:00:00');

-- Thư viện ảnh sản phẩm
INSERT INTO product_images (id, product_id, image_path, is_main, sort_order) VALUES
  (1, 1, 'uploads/products/ao-thun-basic-cotton-co-tron-1.webp', 1, 1),
  (2, 1, 'uploads/products/ao-thun-basic-cotton-co-tron-2.webp', 0, 2),
  (3, 1, 'uploads/products/ao-thun-basic-cotton-co-tron-3.webp', 0, 3),
  (4, 1, 'uploads/products/Ao-thun-basic-co-tron-den-mat-truoc.jpg', 0, 4),
  (5, 2, 'uploads/products/ao-thun-oversize-form-rong-1.webp', 1, 1),
  (6, 2, 'uploads/products/ao-thun-oversize-form-rong-2.webp', 0, 2),
  (7, 2, 'uploads/products/ao-thun-oversize-form-rong-3.webp', 0, 3),
  (8, 2, 'uploads/products/Ao-thun-oversize-basic-mat-truoc.jpg', 0, 4),
  (9, 3, 'uploads/products/ao-thun-in-hinh-graphic-1.webp', 1, 1),
  (10, 3, 'uploads/products/ao-thun-in-hinh-graphic-2.webp', 0, 2),
  (11, 3, 'uploads/products/ao-thun-in-hinh-graphic-3.webp', 0, 3),
  (12, 3, 'uploads/products/Ao-thun-in-logo-mat-truoc.jpg', 0, 4),
  (13, 4, 'uploads/products/ao-thun-tay-dai-thu-dong-1.webp', 1, 1),
  (14, 4, 'uploads/products/ao-thun-tay-dai-thu-dong-2.webp', 0, 2),
  (15, 4, 'uploads/products/ao-thun-tay-dai-thu-dong-3.webp', 0, 3),
  (16, 4, 'uploads/products/ao-thun-tay-dai-thu-dong-4.webp', 0, 4),
  (17, 5, 'uploads/products/ao-croptop-nu-om-body-1.webp', 1, 1),
  (18, 5, 'uploads/products/ao-croptop-nu-om-body-2.webp', 0, 2),
  (19, 5, 'uploads/products/ao-croptop-nu-om-body-3.webp', 0, 3),
  (20, 5, 'uploads/products/Ao-croptop-mat-truoc.jpg', 0, 4),
  (21, 6, 'uploads/products/Ao-croptop-mat-truoc.jpg', 1, 1),
  (22, 6, 'uploads/products/Ao-croptop-mat-sau.webp', 0, 2),
  (23, 6, 'uploads/products/ao-croptop-nu-om-body-4.webp', 0, 3),
  (24, 7, 'uploads/products/ao-polo-co-be-the-thao-1.webp', 1, 1),
  (25, 7, 'uploads/products/ao-polo-co-be-the-thao-2.webp', 0, 2),
  (26, 7, 'uploads/products/ao-polo-co-be-the-thao-3.webp', 0, 3),
  (27, 7, 'uploads/products/Ao-polo-tron-mat-truoc.jpg', 0, 4),
  (28, 8, 'uploads/products/Ao-polo-co-be-the-thao-6.jpg', 1, 1),
  (29, 8, 'uploads/products/Ao-polo-tron-mat-truoc.jpg', 0, 2),
  (30, 8, 'uploads/products/ao-polo-co-be-the-thao-4.webp', 0, 3),
  (31, 9, 'uploads/products/ao-so-mi-trang-cong-so-1.webp', 1, 1),
  (32, 9, 'uploads/products/ao-so-mi-trang-cong-so-2.webp', 0, 2),
  (33, 9, 'uploads/products/ao-so-mi-trang-cong-so-3.webp', 0, 3),
  (34, 9, 'uploads/products/ao-so-mi-trang-cong-so-4.webp', 0, 4),
  (35, 10, 'uploads/products/ao-so-mi-ke-soc-xanh-1.jpg', 1, 1),
  (36, 10, 'uploads/products/ao-so-mi-ke-soc-den-mat-truoc.jpg', 0, 2),
  (37, 10, 'uploads/products/ao-so-mi-ke-soc-do-mat-truoc.jpg', 0, 3),
  (38, 11, 'uploads/products/Ao-so-mi-linen-nu-tay-dai-mat-truoc.jpg', 1, 1),
  (39, 11, 'uploads/products/Ao-so-mi-linen-nu-tay-dai-mat-sau.webp', 0, 2),
  (40, 11, 'uploads/products/ao-so-mi-trang-cong-so-5.webp', 0, 3),
  (41, 12, 'uploads/products/ao-so-mi-ke-soc-do-mat-sau.jpg', 1, 1),
  (42, 12, 'uploads/products/ao-so-mi-ke-soc-den-mat-sau.jpg', 0, 2),
  (43, 12, 'uploads/products/ao-so-mi-ke-soc-xanh-3.jpg', 0, 3),
  (44, 13, 'uploads/products/Ao-khoac-denim-mat-truoc.jpg', 1, 1),
  (45, 13, 'uploads/products/Ao-khoac-denim-mat-sau.webp', 0, 2),
  (46, 13, 'uploads/products/ao-khoac-jean-basic-1.webp', 0, 3),
  (47, 14, 'uploads/products/Ao-khoac-varsity-mat-truoc.jpg', 1, 1),
  (48, 14, 'uploads/products/Ao-khoac-varsity-mat-sau.webp', 0, 2),
  (49, 15, 'uploads/products/ao-bomber-du-the-thao-1.webp', 1, 1),
  (50, 15, 'uploads/products/ao-bomber-du-the-thao-2.webp', 0, 2),
  (51, 15, 'uploads/products/ao-bomber-du-the-thao-3.webp', 0, 3),
  (52, 16, 'uploads/products/ao-bomber-du-the-thao-4.webp', 1, 1),
  (53, 16, 'uploads/products/ao-bomber-du-the-thao-1.jpg', 0, 2),
  (54, 17, 'uploads/products/hoodie-ni-bong-form-rong-1.webp', 1, 1),
  (55, 17, 'uploads/products/hoodie-ni-bong-form-rong-2.webp', 0, 2),
  (56, 17, 'uploads/products/Ao-khoac-hoodie-oversize-mat-truoc.jpg', 0, 3),
  (57, 18, 'uploads/products/hoodie-zip-khoa-keo-1.webp', 1, 1),
  (58, 18, 'uploads/products/hoodie-zip-khoa-keo-2.webp', 0, 2),
  (59, 18, 'uploads/products/Ao-khoac-hoodie-zip-mat-truoc.jpg', 0, 3),
  (60, 19, 'uploads/products/Quan-jeans-straight-mat-truoc.jpg', 1, 1),
  (61, 19, 'uploads/products/Quan-jeans-straight-mat-sau.webp', 0, 2),
  (62, 20, 'uploads/products/quan-jean-ong-rong-nu-1.webp', 1, 1),
  (63, 20, 'uploads/products/quan-jean-ong-rong-nu-2.webp', 0, 2),
  (64, 20, 'uploads/products/Quan-jeans-ong-rong-mat-truoc.jpg', 0, 3),
  (65, 21, 'uploads/products/quan-jean-rach-goi-1.webp', 1, 1),
  (66, 21, 'uploads/products/quan-jean-rach-goi-2.webp', 0, 2),
  (67, 21, 'uploads/products/quan-jean-rach-goi-3.webp', 0, 3),
  (68, 22, 'uploads/products/quan-jean-baggy-1.jpg', 1, 1),
  (69, 22, 'uploads/products/quan-jean-baggy-2.jpg', 0, 2),
  (70, 22, 'uploads/products/quan-jean-baggy-3.jpg', 0, 3),
  (71, 23, 'uploads/products/quan-short-jean-nam-1.webp', 1, 1),
  (72, 23, 'uploads/products/quan-short-jean-nam-2.webp', 0, 2),
  (73, 23, 'uploads/products/Quan-short-nu-mat-truoc.jpg', 0, 3),
  (74, 24, 'uploads/products/quan-short-the-thao-nam-nu-1.webp', 1, 1),
  (75, 24, 'uploads/products/quan-short-the-thao-nam-nu-2.webp', 0, 2),
  (76, 24, 'uploads/products/Quan-short-the-thao-mat-truoc.jpg', 0, 3),
  (77, 25, 'uploads/products/vay-midi-hoa-nhi-1.jpg', 1, 1),
  (78, 25, 'uploads/products/vay-midi-hoa-nhi-2.jpg', 0, 2),
  (79, 25, 'uploads/products/vay-midi-dang-dai-1.webp', 0, 3),
  (80, 26, 'uploads/products/dam-maxi-du-tiec-1.webp', 1, 1),
  (81, 26, 'uploads/products/dam-maxi-du-tiec-2.webp', 0, 2),
  (82, 26, 'uploads/products/dam-maxi-du-tiec-3.webp', 0, 3),
  (83, 27, 'uploads/products/dam-maxi-du-tiec-4.webp', 1, 1),
  (84, 27, 'uploads/products/dam-maxi-du-tiec-5.webp', 0, 2),
  (85, 27, 'uploads/products/dam-maxi-du-tiec-6.webp', 0, 3),
  (86, 28, 'uploads/products/chan-vay-chu-a-1.webp', 1, 1),
  (87, 28, 'uploads/products/chan-vay-chu-a-2.webp', 0, 2),
  (88, 28, 'uploads/products/chan-vay-chu-a-3.webp', 0, 3),
  (89, 29, 'uploads/products/chan-vay-xep-ly-dang-dai-2.webp', 1, 1),
  (90, 29, 'uploads/products/chan-vay-xep-ly-dang-dai-3.webp', 0, 2),
  (91, 29, 'uploads/products/Chan-vay-xep-ly-mat-truoc.jpg', 0, 3),
  (92, 30, 'uploads/products/vay-xep-ly-1.jpg', 1, 1),
  (93, 30, 'uploads/products/vay-xep-ly-2.jpg', 0, 2),
  (94, 30, 'uploads/products/Chan-vay-xep-li-mat-sau.webp', 0, 3);

-- Biến thể size/màu + tồn kho
INSERT INTO product_variants (id, product_id, size, color, stock) VALUES
  (1, 1, 'S', 'Trắng', 18),
  (2, 1, 'M', 'Trắng', 21),
  (3, 1, 'L', 'Trắng', 24),
  (4, 1, 'XL', 'Trắng', 27),
  (5, 1, 'S', 'Đen', 30),
  (6, 1, 'M', 'Đen', 10),
  (7, 1, 'L', 'Đen', 13),
  (8, 1, 'XL', 'Đen', 16),
  (9, 2, 'S', 'Be', 19),
  (10, 2, 'M', 'Be', 22),
  (11, 2, 'L', 'Be', 25),
  (12, 2, 'XL', 'Be', 5),
  (13, 2, 'S', 'Xám', 8),
  (14, 2, 'M', 'Xám', 11),
  (15, 2, 'L', 'Xám', 14),
  (16, 2, 'XL', 'Xám', 17),
  (17, 2, 'S', 'Đen', 20),
  (18, 2, 'M', 'Đen', 30),
  (19, 2, 'L', 'Đen', 33),
  (20, 2, 'XL', 'Đen', 6),
  (21, 3, 'S', 'Đen', 9),
  (22, 3, 'M', 'Đen', 12),
  (23, 3, 'L', 'Đen', 15),
  (24, 3, 'XL', 'Đen', 15),
  (25, 3, 'S', 'Trắng', 18),
  (26, 3, 'M', 'Trắng', 21),
  (27, 3, 'L', 'Trắng', 24),
  (28, 3, 'XL', 'Trắng', 27),
  (29, 4, 'S', 'Xám', 30),
  (30, 4, 'M', 'Xám', 10),
  (31, 4, 'L', 'Xám', 13),
  (32, 4, 'XL', 'Xám', 16),
  (33, 4, 'S', 'Đen', 19),
  (34, 4, 'M', 'Đen', 22),
  (35, 4, 'L', 'Đen', 25),
  (36, 4, 'XL', 'Đen', 5),
  (37, 4, 'S', 'Trắng', 8),
  (38, 4, 'M', 'Trắng', 11),
  (39, 4, 'L', 'Trắng', 14),
  (40, 4, 'XL', 'Trắng', 17),
  (41, 5, 'S', 'Hồng', 0),
  (42, 5, 'M', 'Hồng', 30),
  (43, 5, 'L', 'Hồng', 33),
  (44, 5, 'S', 'Đen', 6),
  (45, 5, 'M', 'Đen', 9),
  (46, 5, 'L', 'Đen', 12),
  (47, 5, 'S', 'Trắng', 15),
  (48, 5, 'M', 'Trắng', 15),
  (49, 5, 'L', 'Trắng', 18),
  (50, 6, 'S', 'Trắng', 21),
  (51, 6, 'M', 'Trắng', 24),
  (52, 6, 'L', 'Trắng', 27),
  (53, 6, 'S', 'Kem', 30),
  (54, 6, 'M', 'Kem', 10),
  (55, 6, 'L', 'Kem', 13),
  (56, 6, 'S', 'Đen', 16),
  (57, 6, 'M', 'Đen', 19),
  (58, 6, 'L', 'Đen', 22),
  (59, 7, 'S', 'Trắng', 25),
  (60, 7, 'M', 'Trắng', 5),
  (61, 7, 'L', 'Trắng', 8),
  (62, 7, 'XL', 'Trắng', 11),
  (63, 7, 'S', 'Xanh navy', 14),
  (64, 7, 'M', 'Xanh navy', 17),
  (65, 7, 'L', 'Xanh navy', 20),
  (66, 7, 'XL', 'Xanh navy', 30),
  (67, 7, 'S', 'Đen', 33),
  (68, 7, 'M', 'Đen', 6),
  (69, 7, 'L', 'Đen', 9),
  (70, 7, 'XL', 'Đen', 12),
  (71, 8, 'S', 'Xanh đậm', 15),
  (72, 8, 'M', 'Xanh đậm', 15),
  (73, 8, 'L', 'Xanh đậm', 18),
  (74, 8, 'XL', 'Xanh đậm', 21),
  (75, 8, 'S', 'Xám', 24),
  (76, 8, 'M', 'Xám', 27),
  (77, 8, 'L', 'Xám', 30),
  (78, 8, 'XL', 'Xám', 10),
  (79, 8, 'S', 'Trắng', 13),
  (80, 8, 'M', 'Trắng', 16),
  (81, 8, 'L', 'Trắng', 19),
  (82, 8, 'XL', 'Trắng', 22),
  (83, 9, 'S', 'Trắng', 25),
  (84, 9, 'M', 'Trắng', 5),
  (85, 9, 'L', 'Trắng', 8),
  (86, 9, 'XL', 'Trắng', 11),
  (87, 9, 'S', 'Xanh nhạt', 14),
  (88, 9, 'M', 'Xanh nhạt', 17),
  (89, 9, 'L', 'Xanh nhạt', 20),
  (90, 9, 'XL', 'Xanh nhạt', 30),
  (91, 10, 'S', 'Xanh', 33),
  (92, 10, 'M', 'Xanh', 6),
  (93, 10, 'L', 'Xanh', 9),
  (94, 10, 'XL', 'Xanh', 12),
  (95, 10, 'S', 'Đen', 15),
  (96, 10, 'M', 'Đen', 15),
  (97, 10, 'L', 'Đen', 18),
  (98, 10, 'XL', 'Đen', 21),
  (99, 10, 'S', 'Đỏ', 24),
  (100, 10, 'M', 'Đỏ', 27),
  (101, 10, 'L', 'Đỏ', 30),
  (102, 10, 'XL', 'Đỏ', 10),
  (103, 11, 'S', 'Be', 13),
  (104, 11, 'M', 'Be', 16),
  (105, 11, 'L', 'Be', 19),
  (106, 11, 'XL', 'Be', 22),
  (107, 11, 'S', 'Trắng', 25),
  (108, 11, 'M', 'Trắng', 5),
  (109, 11, 'L', 'Trắng', 8),
  (110, 11, 'XL', 'Trắng', 11),
  (111, 11, 'S', 'Kem', 14),
  (112, 11, 'M', 'Kem', 17),
  (113, 11, 'L', 'Kem', 20),
  (114, 11, 'XL', 'Kem', 30),
  (115, 12, 'S', 'Đỏ đô', 33),
  (116, 12, 'M', 'Đỏ đô', 6),
  (117, 12, 'L', 'Đỏ đô', 9),
  (118, 12, 'XL', 'Đỏ đô', 12),
  (119, 12, 'S', 'Đen', 15),
  (120, 12, 'M', 'Đen', 15),
  (121, 12, 'L', 'Đen', 18),
  (122, 12, 'XL', 'Đen', 21),
  (123, 12, 'S', 'Xanh', 24),
  (124, 12, 'M', 'Xanh', 27),
  (125, 12, 'L', 'Xanh', 30),
  (126, 12, 'XL', 'Xanh', 10),
  (127, 13, 'S', 'Xanh đậm', 13),
  (128, 13, 'M', 'Xanh đậm', 16),
  (129, 13, 'L', 'Xanh đậm', 19),
  (130, 13, 'XL', 'Xanh đậm', 22),
  (131, 13, 'S', 'Xanh nhạt', 25),
  (132, 13, 'M', 'Xanh nhạt', 5),
  (133, 13, 'L', 'Xanh nhạt', 8),
  (134, 13, 'XL', 'Xanh nhạt', 11),
  (135, 14, 'S', 'Đen', 14),
  (136, 14, 'M', 'Đen', 17),
  (137, 14, 'L', 'Đen', 20),
  (138, 14, 'XL', 'Đen', 30),
  (139, 14, 'S', 'Xanh navy', 33),
  (140, 14, 'M', 'Xanh navy', 6),
  (141, 14, 'L', 'Xanh navy', 9),
  (142, 14, 'XL', 'Xanh navy', 12),
  (143, 15, 'S', 'Đen', 15),
  (144, 15, 'M', 'Đen', 15),
  (145, 15, 'L', 'Đen', 18),
  (146, 15, 'XL', 'Đen', 21),
  (147, 15, 'S', 'Rêu', 24),
  (148, 15, 'M', 'Rêu', 27),
  (149, 15, 'L', 'Rêu', 30),
  (150, 15, 'XL', 'Rêu', 10),
  (151, 16, 'S', 'Đen', 13),
  (152, 16, 'M', 'Đen', 16),
  (153, 16, 'L', 'Đen', 19),
  (154, 16, 'XL', 'Đen', 22),
  (155, 16, 'S', 'Nâu', 25),
  (156, 16, 'M', 'Nâu', 5),
  (157, 16, 'L', 'Nâu', 8),
  (158, 16, 'XL', 'Nâu', 11),
  (159, 17, 'S', 'Xám', 14),
  (160, 17, 'M', 'Xám', 17),
  (161, 17, 'L', 'Xám', 20),
  (162, 17, 'XL', 'Xám', 30),
  (163, 17, 'S', 'Đen', 33),
  (164, 17, 'M', 'Đen', 6),
  (165, 17, 'L', 'Đen', 9),
  (166, 17, 'XL', 'Đen', 12),
  (167, 17, 'S', 'Kem', 15),
  (168, 17, 'M', 'Kem', 15),
  (169, 17, 'L', 'Kem', 18),
  (170, 17, 'XL', 'Kem', 21),
  (171, 18, 'S', 'Đen', 24),
  (172, 18, 'M', 'Đen', 27),
  (173, 18, 'L', 'Đen', 30),
  (174, 18, 'XL', 'Đen', 10),
  (175, 18, 'S', 'Xám', 13),
  (176, 18, 'M', 'Xám', 16),
  (177, 18, 'L', 'Xám', 19),
  (178, 18, 'XL', 'Xám', 22),
  (179, 18, 'S', 'Xanh navy', 25),
  (180, 18, 'M', 'Xanh navy', 5),
  (181, 18, 'L', 'Xanh navy', 8),
  (182, 18, 'XL', 'Xanh navy', 11),
  (183, 19, '29', 'Xanh đậm', 14),
  (184, 19, '30', 'Xanh đậm', 17),
  (185, 19, '31', 'Xanh đậm', 20),
  (186, 19, '32', 'Xanh đậm', 30),
  (187, 19, '29', 'Xanh đen', 33),
  (188, 19, '30', 'Xanh đen', 6),
  (189, 19, '31', 'Xanh đen', 9),
  (190, 19, '32', 'Xanh đen', 12),
  (191, 20, 'S', 'Xanh nhạt', 15),
  (192, 20, 'M', 'Xanh nhạt', 15),
  (193, 20, 'L', 'Xanh nhạt', 18),
  (194, 20, 'S', 'Đen', 21),
  (195, 20, 'M', 'Đen', 24),
  (196, 20, 'L', 'Đen', 27),
  (197, 21, '29', 'Xanh đậm', 30),
  (198, 21, '30', 'Xanh đậm', 10),
  (199, 21, '31', 'Xanh đậm', 13),
  (200, 21, '32', 'Xanh đậm', 16),
  (201, 21, '29', 'Đen', 19),
  (202, 21, '30', 'Đen', 22),
  (203, 21, '31', 'Đen', 25),
  (204, 21, '32', 'Đen', 5),
  (205, 22, 'S', 'Xanh nhạt', 8),
  (206, 22, 'M', 'Xanh nhạt', 11),
  (207, 22, 'L', 'Xanh nhạt', 14),
  (208, 22, 'XL', 'Xanh nhạt', 17),
  (209, 22, 'S', 'Xanh đậm', 20),
  (210, 22, 'M', 'Xanh đậm', 30),
  (211, 22, 'L', 'Xanh đậm', 33),
  (212, 22, 'XL', 'Xanh đậm', 6),
  (213, 23, 'S', 'Xanh nhạt', 9),
  (214, 23, 'M', 'Xanh nhạt', 12),
  (215, 23, 'L', 'Xanh nhạt', 15),
  (216, 23, 'XL', 'Xanh nhạt', 15),
  (217, 23, 'S', 'Đen', 18),
  (218, 23, 'M', 'Đen', 21),
  (219, 23, 'L', 'Đen', 24),
  (220, 23, 'XL', 'Đen', 27),
  (221, 24, 'S', 'Đen', 30),
  (222, 24, 'M', 'Đen', 10),
  (223, 24, 'L', 'Đen', 13),
  (224, 24, 'XL', 'Đen', 16),
  (225, 24, 'S', 'Xám', 19),
  (226, 24, 'M', 'Xám', 22),
  (227, 24, 'L', 'Xám', 25),
  (228, 24, 'XL', 'Xám', 5),
  (229, 24, 'S', 'Xanh navy', 8),
  (230, 24, 'M', 'Xanh navy', 11),
  (231, 24, 'L', 'Xanh navy', 14),
  (232, 24, 'XL', 'Xanh navy', 17),
  (233, 25, 'S', 'Vàng', 20),
  (234, 25, 'M', 'Vàng', 30),
  (235, 25, 'L', 'Vàng', 33),
  (236, 25, 'S', 'Hồng', 6),
  (237, 25, 'M', 'Hồng', 9),
  (238, 25, 'L', 'Hồng', 12),
  (239, 25, 'S', 'Trắng', 15),
  (240, 25, 'M', 'Trắng', 15),
  (241, 25, 'L', 'Trắng', 18),
  (242, 26, 'S', 'Đen', 21),
  (243, 26, 'M', 'Đen', 24),
  (244, 26, 'L', 'Đen', 27),
  (245, 26, 'XL', 'Đen', 30),
  (246, 26, 'S', 'Be', 10),
  (247, 26, 'M', 'Be', 13),
  (248, 26, 'L', 'Be', 16),
  (249, 26, 'XL', 'Be', 19),
  (250, 26, 'S', 'Xanh navy', 22),
  (251, 26, 'M', 'Xanh navy', 25),
  (252, 26, 'L', 'Xanh navy', 5),
  (253, 26, 'XL', 'Xanh navy', 8),
  (254, 27, 'S', 'Đỏ đô', 11),
  (255, 27, 'M', 'Đỏ đô', 14),
  (256, 27, 'L', 'Đỏ đô', 17),
  (257, 27, 'S', 'Đen', 20),
  (258, 27, 'M', 'Đen', 30),
  (259, 27, 'L', 'Đen', 33),
  (260, 27, 'S', 'Trắng', 6),
  (261, 27, 'M', 'Trắng', 9),
  (262, 27, 'L', 'Trắng', 12),
  (263, 28, 'S', 'Đen', 15),
  (264, 28, 'M', 'Đen', 15),
  (265, 28, 'L', 'Đen', 18),
  (266, 28, 'S', 'Kem', 21),
  (267, 28, 'M', 'Kem', 24),
  (268, 28, 'L', 'Kem', 27),
  (269, 28, 'S', 'Nâu', 30),
  (270, 28, 'M', 'Nâu', 10),
  (271, 28, 'L', 'Nâu', 13),
  (272, 29, 'S', 'Xám', 16),
  (273, 29, 'M', 'Xám', 19),
  (274, 29, 'L', 'Xám', 22),
  (275, 29, 'S', 'Đen', 25),
  (276, 29, 'M', 'Đen', 5),
  (277, 29, 'L', 'Đen', 8),
  (278, 29, 'S', 'Be', 11),
  (279, 29, 'M', 'Be', 14),
  (280, 29, 'L', 'Be', 17),
  (281, 30, 'S', 'Trắng', 20),
  (282, 30, 'M', 'Trắng', 30),
  (283, 30, 'L', 'Trắng', 33),
  (284, 30, 'S', 'Đen', 6),
  (285, 30, 'M', 'Đen', 9),
  (286, 30, 'L', 'Đen', 12),
  (287, 30, 'S', 'Hồng', 15),
  (288, 30, 'M', 'Hồng', 15),
  (289, 30, 'L', 'Hồng', 18);

-- Mã giảm giá
INSERT INTO coupons (id, code, discount_type, discount_value, min_order, expires_at, usage_limit) VALUES
  (1, 'WELCOME10', 'percent', 10, 300000, '2026-12-31 23:59:59', 100),
  (2, 'GIAM50K', 'amount', 50000, 500000, '2026-12-31 23:59:59', 50),
  (3, 'SALE20', 'percent', 20, 800000, '2026-11-30 23:59:59', 30),
  (4, 'HETHAN', 'percent', 15, 0, '2026-01-31 23:59:59', 10);

-- Đơn hàng mẫu
INSERT INTO orders (id, user_id, receiver_name, phone, address, total, payment_method, status, coupon_id, created_at) VALUES
  (1, 2, 'Nguyễn Thị Lan', '0912345678', '12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', 762300, 'cod', 'completed', 1, '2026-10-01 09:15:00'),
  (2, 3, 'Trần Văn Minh', '0987654321', '45 Lê Lợi, Quận 3, TP. Hồ Chí Minh', 788000, 'bank', 'shipping', NULL, '2026-10-12 14:40:00'),
  (3, 2, 'Nguyễn Thị Lan', '0912345678', '12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', 528000, 'cod', 'pending', 2, '2026-10-20 19:05:00'),
  (4, 3, 'Trần Văn Minh', '0987654321', '45 Lê Lợi, Quận 3, TP. Hồ Chí Minh', 349000, 'cod', 'cancelled', NULL, '2026-10-05 11:30:00'),
  (5, 2, 'Nguyễn Thị Lan', '0912345678', '12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', 797000, 'bank', 'confirmed', NULL, '2026-10-21 08:50:00');

-- Chi tiết đơn hàng
INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, price) VALUES
  (1, 1, 1, 2, 2, 149000),
  (2, 1, 12, 68, 1, 319000),
  (3, 2, 17, 99, 1, 449000),
  (4, 2, 21, 125, 1, 389000),
  (5, 3, 25, 155, 1, 329000),
  (6, 3, 5, 26, 1, 159000),
  (7, 4, 7, 38, 1, 249000),
  (8, 5, 23, 140, 1, 249000),
  (9, 5, 1, 4, 2, 149000);

-- Đánh giá (đánh giá số 5 đang chờ duyệt)
INSERT INTO reviews (id, product_id, user_id, rating, comment, status, created_at) VALUES
  (1, 1, 2, 5, 'Vải mềm mịn, mặc rất thoáng mát, đóng gói cẩn thận và giao hàng siêu nhanh!', 1, '2026-10-04 20:00:00'),
  (2, 12, 2, 4, 'Sơ mi flannel caro form rất đẹp, mặc khoác ngoài chuẩn streetwear.', 1, '2026-10-05 21:10:00'),
  (3, 17, 3, 5, 'Hoodie nỉ bông dày dặn ấm áp, mũ đứng form cực ưng ý.', 1, '2026-10-18 09:00:00'),
  (4, 3, 3, 4, 'Áo thun graphic in hình đẹp, chất vải co giãn tốt.', 1, '2026-10-10 15:30:00'),
  (5, 21, 3, 3, 'Quần jean rách gối cá tính, vải co giãn tốt.', 0, '2026-10-19 12:45:00');

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
  (1, 1, 1, 1),
  (2, 1, 10, 2);

-- Banner trang chủ
INSERT INTO banners (id, title, image_path, link, sort_order, status) VALUES
  (1, 'Bộ sưu tập Streetwear Gen Z 2026', 'uploads/banners/banner-1.jpg', 'products.php', 1, 1),
  (2, 'Sale đến 30% - Áo khoác & Hoodie', 'uploads/banners/banner-2.jpg', 'products.php?category=5', 2, 1),
  (3, 'Freeship đơn từ 399.000đ', 'uploads/banners/banner-3.jpg', 'products.php', 3, 1);
