/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: shop_quanao
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_banners_status_sort` (`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES
(1,'Bộ sưu tập Thu Đông 2026','uploads/banners/banner-1.jpg','products.php',1,1),
(2,'Sale đến 30% - Áo khoác & Hoodie','uploads/banners/banner-2.jpg','products.php?category=ao-khoac',2,1),
(3,'Freeship đơn từ 500.000đ','uploads/banners/banner-3.jpg','products.php',3,1);
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cart_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_variant` (`cart_id`,`variant_id`),
  KEY `fk_ci_variant` (`variant_id`),
  CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ci_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
INSERT INTO `cart_items` VALUES
(1,1,1,1),
(2,1,25,2);
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_carts_user` (`user_id`),
  CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
INSERT INTO `carts` VALUES
(1,2);
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Áo Thun & Croptop','ao-thun-croptop','Áo thun nam nữ cotton, áo oversize và áo croptop tôn dáng',NULL,1),
(2,'Áo Sơ Mi & Polo','ao-so-mi-polo','Áo sơ mi công sở, sơ mi dạo phố và áo polo thanh lịch',NULL,1),
(3,'Áo Khoác, Bomber & Hoodie','ao-khoac-bomber-hoodie','Áo khoác denim, varsity, bomber và hoodie ấm áp',NULL,1),
(4,'Quần Jean & Quần Short','quan-jean-short','Quần jean slimfit, ống rộng, baggy và quần short năng động',NULL,1),
(5,'Váy, Đầm & Chân Váy','vay-dam-chan-vay','Váy hoa nhí, đầm công sở, đầm maxi và chân váy xếp ly',NULL,1),
(7,'Áo khoác bomber','ao-khoac-bomber','Bomber dù, bomber da thời trang',3,1),
(8,'Hoodie','hoodie','Áo hoodie nỉ bông, hoodie zip',3,1);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `discount_type` enum('percent','amount') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(12,0) NOT NULL,
  `min_order` decimal(12,0) NOT NULL DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coupons_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES
(1,'WELCOME10','percent',10,300000,'2026-12-31 23:59:59',100),
(2,'GIAM50K','amount',50000,500000,'2026-12-31 23:59:59',50),
(3,'SALE20','percent',20,800000,'2026-11-30 23:59:59',30),
(4,'HETHAN','percent',15,0,'2026-01-31 23:59:59',10);
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,0) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order` (`order_id`),
  KEY `idx_oi_product` (`product_id`),
  KEY `fk_oi_variant` (`variant_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_oi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES
(1,1,1,2,2,149000),
(2,1,10,40,1,369000),
(3,2,16,65,1,449000),
(4,2,21,85,1,549000),
(5,3,26,105,1,459000),
(6,3,5,14,1,129000),
(7,4,6,25,1,259000),
(8,5,24,95,1,399000),
(9,5,1,4,2,149000);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `receiver_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `total` decimal(12,0) NOT NULL,
  `payment_method` enum('cod','bank') NOT NULL DEFAULT 'cod',
  `status` enum('pending','confirmed','shipping','completed','cancelled') NOT NULL DEFAULT 'pending',
  `coupon_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status_created` (`status`,`created_at`),
  KEY `idx_orders_coupon` (`coupon_id`),
  CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES
(1,2,'Nguyễn Thị Lan','0912345678','12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh',762300,'cod','completed',1,'2026-10-01 09:15:00'),
(2,3,'Trần Văn Minh','0987654321','45 Lê Lợi, Quận 3, TP. Hồ Chí Minh',788000,'bank','shipping',NULL,'2026-10-12 14:40:00'),
(3,2,'Nguyễn Thị Lan','0912345678','12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh',558000,'cod','pending',2,'2026-10-20 19:05:00'),
(4,3,'Trần Văn Minh','0987654321','45 Lê Lợi, Quận 3, TP. Hồ Chí Minh',349000,'cod','cancelled',NULL,'2026-10-05 11:30:00'),
(5,2,'Nguyễn Thị Lan','0912345678','12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh',897000,'bank','confirmed',NULL,'2026-10-21 08:50:00');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pr_token` (`token`),
  KEY `idx_pr_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pi_product` (`product_id`,`sort_order`),
  CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES
(1,1,'uploads/products/Ao-thun-basic-co-tron-den-mat-truoc.jpg',1,1),
(2,1,'uploads/products/Ao-thun-basic-co-tron-den-mat-sau.jpg',0,2),
(3,2,'uploads/products/Ao-thun-oversize-basic-mat-truoc.jpg',1,1),
(4,2,'uploads/products/Ao-thun-oversize-basic-mat-sau.webp',0,2),
(5,3,'uploads/products/Ao-thun-in-logo-mat-truoc.jpg',1,1),
(6,3,'uploads/products/Ao-thun-in-logo-mat-sau.webp',0,2),
(7,4,'uploads/products/ao-thun-tay-dai-thu-dong-1.jpg',1,1),
(8,4,'uploads/products/ao-thun-tay-dai-thu-dong-2.jpg',0,2),
(9,5,'uploads/products/Ao-croptop-mat-truoc.jpg',1,1),
(10,5,'uploads/products/Ao-croptop-mat-sau.webp',0,2),
(11,6,'uploads/products/Ao-polo-tron-mat-truoc.jpg',1,1),
(12,6,'uploads/products/Ao-polo-tron-mat-sau.webp',0,2),
(13,7,'uploads/products/Ao-polo-co-tron-mat-truoc.jpg',1,1),
(14,7,'uploads/products/ao-polo-nam-vai-ca-sau-2.jpg',0,2),
(15,8,'uploads/products/ao-so-mi-trang-cong-so-1.jpg',1,1),
(16,8,'uploads/products/ao-so-mi-trang-cong-so-2.jpg',0,2),
(17,9,'uploads/products/ao-so-mi-ke-soc-xanh-1.jpg',1,1),
(18,9,'uploads/products/ao-so-mi-ke-soc-xanh-2.jpg',0,2),
(19,9,'uploads/products/ao-so-mi-ke-soc-den-mat-truoc.jpg',0,3),
(20,9,'uploads/products/ao-so-mi-ke-soc-den-mat-sau.jpg',0,4),
(21,9,'uploads/products/ao-so-mi-ke-soc-do-mat-truoc.jpg',0,5),
(22,9,'uploads/products/ao-so-mi-ke-soc-do-mat-sau.jpg',0,6),
(23,10,'uploads/products/ao-so-mi-linen-tay-ngan-1.jpg',1,1),
(24,10,'uploads/products/ao-so-mi-linen-tay-ngan-2.jpg',0,2),
(25,11,'uploads/products/Ao-khoac-denim-mat-truoc.jpg',1,1),
(26,11,'uploads/products/Ao-khoac-denim-mat-sau.webp',0,2),
(27,12,'uploads/products/Ao-khoac-varsity-mat-truoc.jpg',1,1),
(28,12,'uploads/products/Ao-khoac-varsity-mat-sau.webp',0,2),
(29,13,'uploads/products/ao-bomber-du-2-lop-1.jpg',1,1),
(30,13,'uploads/products/ao-bomber-du-2-lop-2.jpg',0,2),
(31,14,'uploads/products/ao-bomber-da-pu-1.jpg',1,1),
(32,14,'uploads/products/ao-bomber-da-pu-2.jpg',0,2),
(33,15,'uploads/products/ao-khoac-jean-unisex-1.jpg',1,1),
(34,15,'uploads/products/ao-khoac-jean-unisex-2.jpg',0,2),
(35,16,'uploads/products/Ao-khoac-hoodie-oversize-mat-truoc.jpg',1,1),
(36,16,'uploads/products/Ao-khoac-hoodie-oversize-mat-sau.webp',0,2),
(37,17,'uploads/products/Ao-khoac-hoodie-zip-mat-truoc.jpg',1,1),
(38,17,'uploads/products/Ao-khoac-hoodie-zip-mat-sau.webp',0,2),
(39,18,'uploads/products/hoodie-ni-bong-form-rong-1.jpg',1,1),
(40,18,'uploads/products/hoodie-ni-bong-form-rong-2.jpg',0,2),
(41,19,'uploads/products/hoodie-zip-khoa-keo-1.jpg',1,1),
(42,19,'uploads/products/hoodie-zip-khoa-keo-2.jpg',0,2),
(43,20,'uploads/products/ao-bomber-du-2-lop-3.jpg',1,1),
(44,20,'uploads/products/ao-bomber-da-pu-3.jpg',0,2),
(45,21,'uploads/products/Quan-jeans-straight-mat-truoc.jpg',1,1),
(46,21,'uploads/products/Quan-jeans-straight-mat-sau.webp',0,2),
(47,22,'uploads/products/Quan-jeans-ong-rong-mat-truoc.jpg',1,1),
(48,22,'uploads/products/Quan-jeans-ong-rong-mat-sau.webp',0,2),
(49,23,'uploads/products/quan-jean-rach-goi-1.jpg',1,1),
(50,23,'uploads/products/quan-jean-rach-goi-2.jpg',0,2),
(51,24,'uploads/products/quan-short-jean-nam-1.jpg',1,1),
(52,24,'uploads/products/quan-short-jean-nam-2.jpg',0,2),
(53,25,'uploads/products/Quan-short-the-thao-mat-truoc.jpg',1,1),
(54,25,'uploads/products/Quan-short-the-thao-mat-sau.webp',0,2),
(55,26,'uploads/products/Chan-vay-midi-mat-truoc.jpg',1,1),
(56,26,'uploads/products/Chan-vay-midi-mat-sau.webp',0,2),
(57,27,'uploads/products/dam-suong-cong-so-1.jpg',1,1),
(58,27,'uploads/products/dam-suong-cong-so-2.jpg',0,2),
(59,28,'uploads/products/dam-maxi-du-tiec-1.jpg',1,1),
(60,28,'uploads/products/dam-maxi-du-tiec-2.jpg',0,2),
(61,29,'uploads/products/chan-vay-chu-a-1.jpg',1,1),
(62,29,'uploads/products/chan-vay-chu-a-2.jpg',0,2),
(63,30,'uploads/products/Chan-vay-xep-ly-mat-truoc.jpg',1,1),
(64,30,'uploads/products/Chan-vay-xep-li-mat-sau.webp',0,2);
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_variant` (`product_id`,`size`,`color`),
  CONSTRAINT `fk_pv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES
(1,1,'S','Trắng',17),
(2,1,'M','Trắng',24),
(3,1,'L','Trắng',31),
(4,1,'S','Đen',13),
(5,1,'M','Đen',20),
(6,1,'L','Đen',27),
(7,1,'S','Xám',34),
(8,1,'M','Xám',16),
(9,1,'L','Xám',23),
(10,2,'S','Trắng',30),
(11,2,'M','Trắng',12),
(12,2,'L','Trắng',19),
(13,2,'S','Be',26),
(14,2,'M','Be',33),
(15,2,'L','Be',15),
(16,2,'S','Xám',22),
(17,2,'M','Xám',29),
(18,2,'L','Xám',11),
(19,3,'S','Đen',18),
(20,3,'M','Đen',25),
(21,3,'L','Đen',32),
(22,3,'S','Trắng',14),
(23,3,'M','Trắng',21),
(24,3,'L','Trắng',28),
(25,4,'S','Xám',10),
(26,4,'M','Xám',17),
(27,4,'L','Xám',24),
(28,4,'S','Đen',31),
(29,4,'M','Đen',13),
(30,4,'L','Đen',20),
(31,4,'S','Xanh rêu',27),
(32,4,'M','Xanh rêu',34),
(33,4,'L','Xanh rêu',16),
(34,5,'S','Đen',0),
(35,5,'M','Đen',30),
(36,5,'L','Đen',12),
(37,5,'S','Trắng',19),
(38,5,'M','Trắng',26),
(39,5,'L','Trắng',33),
(40,5,'S','Hồng',15),
(41,5,'M','Hồng',22),
(42,5,'L','Hồng',29),
(43,6,'S','Xanh navy',11),
(44,6,'M','Xanh navy',18),
(45,6,'L','Xanh navy',25),
(46,6,'XL','Xanh navy',32),
(47,6,'S','Trắng',14),
(48,6,'M','Trắng',21),
(49,6,'L','Trắng',28),
(50,6,'XL','Trắng',10),
(51,6,'S','Đen',17),
(52,6,'M','Đen',24),
(53,6,'L','Đen',31),
(54,6,'XL','Đen',13),
(55,7,'S','Xám',20),
(56,7,'M','Xám',27),
(57,7,'L','Xám',34),
(58,7,'XL','Xám',16),
(59,7,'S','Đen',23),
(60,7,'M','Đen',30),
(61,7,'L','Đen',12),
(62,7,'XL','Đen',19),
(63,7,'S','Trắng',26),
(64,7,'M','Trắng',33),
(65,7,'L','Trắng',15),
(66,7,'XL','Trắng',22),
(67,8,'S','Trắng',29),
(68,8,'M','Trắng',11),
(69,8,'L','Trắng',18),
(70,8,'XL','Trắng',25),
(71,8,'S','Xanh nhạt',32),
(72,8,'M','Xanh nhạt',14),
(73,8,'L','Xanh nhạt',21),
(74,8,'XL','Xanh nhạt',28),
(75,9,'S','Kẻ sọc xanh',10),
(76,9,'M','Kẻ sọc xanh',17),
(77,9,'L','Kẻ sọc xanh',24),
(78,9,'XL','Kẻ sọc xanh',31),
(79,9,'S','Kẻ sọc đen',13),
(80,9,'M','Kẻ sọc đen',20),
(81,9,'L','Kẻ sọc đen',27),
(82,9,'XL','Kẻ sọc đen',34),
(83,9,'S','Kẻ sọc đỏ đô',16),
(84,9,'M','Kẻ sọc đỏ đô',23),
(85,9,'L','Kẻ sọc đỏ đô',30),
(86,9,'XL','Kẻ sọc đỏ đô',12),
(87,10,'S','Be',19),
(88,10,'M','Be',26),
(89,10,'L','Be',33),
(90,10,'XL','Be',15),
(91,10,'S','Trắng',22),
(92,10,'M','Trắng',29),
(93,10,'L','Trắng',11),
(94,10,'XL','Trắng',18),
(95,10,'S','Xanh rêu',25),
(96,10,'M','Xanh rêu',32),
(97,10,'L','Xanh rêu',14),
(98,10,'XL','Xanh rêu',21),
(99,11,'S','Xanh nhạt',28),
(100,11,'M','Xanh nhạt',10),
(101,11,'L','Xanh nhạt',17),
(102,11,'XL','Xanh nhạt',24),
(103,11,'S','Xanh đậm',31),
(104,11,'M','Xanh đậm',13),
(105,11,'L','Xanh đậm',20),
(106,11,'XL','Xanh đậm',27),
(107,12,'S','Đen phối trắng',34),
(108,12,'M','Đen phối trắng',16),
(109,12,'L','Đen phối trắng',23),
(110,12,'XL','Đen phối trắng',30),
(111,12,'S','Xanh navy phối trắng',12),
(112,12,'M','Xanh navy phối trắng',19),
(113,12,'L','Xanh navy phối trắng',26),
(114,12,'XL','Xanh navy phối trắng',33),
(115,13,'S','Đen',2),
(116,13,'M','Đen',22),
(117,13,'L','Đen',29),
(118,13,'XL','Đen',11),
(119,13,'S','Xanh rêu',18),
(120,13,'M','Xanh rêu',25),
(121,13,'L','Xanh rêu',32),
(122,13,'XL','Xanh rêu',14),
(123,14,'S','Đen',21),
(124,14,'M','Đen',28),
(125,14,'L','Đen',10),
(126,14,'XL','Đen',17),
(127,14,'S','Nâu bò',24),
(128,14,'M','Nâu bò',31),
(129,14,'L','Nâu bò',13),
(130,14,'XL','Nâu bò',20),
(131,15,'S','Xanh wash',27),
(132,15,'M','Xanh wash',34),
(133,15,'L','Xanh wash',16),
(134,15,'XL','Xanh wash',23),
(135,15,'S','Đen denim',30),
(136,15,'M','Đen denim',12),
(137,15,'L','Đen denim',19),
(138,15,'XL','Đen denim',26),
(139,16,'S','Xám tiêu',33),
(140,16,'M','Xám tiêu',15),
(141,16,'L','Xám tiêu',22),
(142,16,'XL','Xám tiêu',29),
(143,16,'S','Đen',11),
(144,16,'M','Đen',18),
(145,16,'L','Đen',25),
(146,16,'XL','Đen',32),
(147,16,'S','Xanh pastel',14),
(148,16,'M','Xanh pastel',21),
(149,16,'L','Xanh pastel',28),
(150,16,'XL','Xanh pastel',10),
(151,17,'S','Xám chì',17),
(152,17,'M','Xám chì',24),
(153,17,'L','Xám chì',31),
(154,17,'XL','Xám chì',13),
(155,17,'S','Đen',20),
(156,17,'M','Đen',27),
(157,17,'L','Đen',34),
(158,17,'XL','Đen',16),
(159,17,'S','Be',23),
(160,17,'M','Be',30),
(161,17,'L','Be',12),
(162,17,'XL','Be',19),
(163,18,'S','Đen',26),
(164,18,'M','Đen',33),
(165,18,'L','Đen',15),
(166,18,'XL','Đen',22),
(167,18,'S','Trắng kem',29),
(168,18,'M','Trắng kem',11),
(169,18,'L','Trắng kem',18),
(170,18,'XL','Trắng kem',25),
(171,19,'S','Đen',32),
(172,19,'M','Đen',14),
(173,19,'L','Đen',21),
(174,19,'XL','Đen',28),
(175,19,'S','Xám ghi',10),
(176,19,'M','Xám ghi',17),
(177,19,'L','Xám ghi',24),
(178,19,'XL','Xám ghi',31),
(179,20,'S','Đen',13),
(180,20,'M','Đen',20),
(181,20,'L','Đen',27),
(182,20,'XL','Đen',34),
(183,20,'S','Xanh than',16),
(184,20,'M','Xanh than',23),
(185,20,'L','Xanh than',30),
(186,20,'XL','Xanh than',12),
(187,21,'29','Xanh đậm',19),
(188,21,'30','Xanh đậm',26),
(189,21,'31','Xanh đậm',33),
(190,21,'32','Xanh đậm',15),
(191,21,'29','Đen',22),
(192,21,'30','Đen',29),
(193,21,'31','Đen',11),
(194,21,'32','Đen',18),
(195,22,'29','Xanh nhạt',25),
(196,22,'30','Xanh nhạt',32),
(197,22,'31','Xanh nhạt',14),
(198,22,'32','Xanh nhạt',21),
(199,22,'29','Xanh retro',28),
(200,22,'30','Xanh retro',10),
(201,22,'31','Xanh retro',17),
(202,22,'32','Xanh retro',24),
(203,23,'29','Xanh wash rách',31),
(204,23,'30','Xanh wash rách',13),
(205,23,'31','Xanh wash rách',20),
(206,23,'32','Xanh wash rách',27),
(207,23,'29','Đen wash rách',34),
(208,23,'30','Đen wash rách',16),
(209,23,'31','Đen wash rách',23),
(210,23,'32','Đen wash rách',30),
(211,24,'29','Xanh nhạt',12),
(212,24,'30','Xanh nhạt',19),
(213,24,'31','Xanh nhạt',26),
(214,24,'32','Xanh nhạt',33),
(215,24,'29','Đen denim',15),
(216,24,'30','Đen denim',22),
(217,24,'31','Đen denim',29),
(218,24,'32','Đen denim',11),
(219,25,'M','Đen',18),
(220,25,'L','Đen',25),
(221,25,'XL','Đen',32),
(222,25,'M','Xám',14),
(223,25,'L','Xám',21),
(224,25,'XL','Xám',28),
(225,25,'M','Xanh than',10),
(226,25,'L','Xanh than',17),
(227,25,'XL','Xanh than',24),
(228,26,'S','Hoa vàng vintage',31),
(229,26,'M','Hoa vàng vintage',13),
(230,26,'L','Hoa vàng vintage',20),
(231,26,'S','Hoa xanh pastel',27),
(232,26,'M','Hoa xanh pastel',34),
(233,26,'L','Hoa xanh pastel',16),
(234,27,'S','Đen',23),
(235,27,'M','Đen',30),
(236,27,'L','Đen',12),
(237,27,'S','Be cát',19),
(238,27,'M','Be cát',26),
(239,27,'L','Be cát',33),
(240,27,'S','Hồng phấn',15),
(241,27,'M','Hồng phấn',22),
(242,27,'L','Hồng phấn',29),
(243,28,'S','Đỏ rượu',11),
(244,28,'M','Đỏ rượu',18),
(245,28,'L','Đỏ rượu',25),
(246,28,'S','Trắng ngọc',32),
(247,28,'M','Trắng ngọc',14),
(248,28,'L','Trắng ngọc',21),
(249,28,'S','Đen tuyền',28),
(250,28,'M','Đen tuyền',10),
(251,28,'L','Đen tuyền',17),
(252,29,'S','Đen',24),
(253,29,'M','Đen',31),
(254,29,'L','Đen',13),
(255,29,'S','Xám ghi',20),
(256,29,'M','Xám ghi',27),
(257,29,'L','Xám ghi',34),
(258,29,'S','Nâu tây',16),
(259,29,'M','Nâu tây',23),
(260,29,'L','Nâu tây',30),
(261,30,'S','Kem be',12),
(262,30,'M','Kem be',19),
(263,30,'L','Kem be',26),
(264,30,'S','Hồng đất',33),
(265,30,'M','Hồng đất',15),
(266,30,'L','Hồng đất',22);
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `price` decimal(12,0) NOT NULL,
  `sale_price` decimal(12,0) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `views` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_status_created` (`status`,`created_at`),
  KEY `idx_products_price` (`price`),
  FULLTEXT KEY `ft_products_name` (`name`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,'Áo thun basic cotton cổ tròn','ao-thun-basic-cotton-co-tron',189000,149000,'<p>Áo thun cotton 100% mềm mịn, thoáng mát, form regular dễ phối đồ hàng ngày.</p>',1,1,150,'2026-09-04 10:00:00'),
(2,'Áo thun oversize form rộng unisex','ao-thun-oversize-form-rong',249000,NULL,'<p>Form oversize phong cách streetwear Hàn Quốc, vải dày 250gsm không bai dão.</p>',1,1,95,'2026-09-07 10:00:00'),
(3,'Áo thun in hình graphic cá tính','ao-thun-in-hinh-graphic',229000,NULL,'<p>Họa tiết in sắc nét, độ bền màu cao, giặt máy thoải mái không lo bong tróc.</p>',1,1,168,'2026-09-13 10:00:00'),
(4,'Áo thun tay dài thu đông','ao-thun-tay-dai-thu-dong',279000,NULL,'<p>Chất liệu cotton co giãn nhẹ, giữ ấm vừa phải cho những ngày se lạnh.</p>',1,1,242,'2026-09-19 10:00:00'),
(5,'Áo croptop nữ ôm body tôn dáng','ao-croptop-nu-om-body',169000,129000,'<p>Croptop co giãn 4 chiều, ôm sát vòng eo thon gọn, dễ mix cùng quần cạp cao.</p>',1,1,205,'2026-09-16 10:00:00'),
(6,'Áo polo nam vải cá sấu pique','ao-polo-nam-vai-ca-sau',299000,259000,'<p>Polo pique dệt tổ ong thấm hút mồ hôi, cổ dệt bền form sang trọng.</p>',2,1,131,'2026-09-10 10:00:00'),
(7,'Áo polo cổ bẻ thể thao năng động','ao-polo-co-be-the-thao',319000,NULL,'<p>Kiểu dáng thể thao khỏe khoắn, vải co giãn vận động cực kỳ thoải mái.</p>',2,1,110,'2026-09-12 10:00:00'),
(8,'Áo sơ mi trắng công sở chống nhăn','ao-so-mi-trang-cong-so',349000,NULL,'<p>Sơ mi trắng chống nhăn cao cấp từ sợi Bamboo, thoáng mát cả ngày.</p>',2,1,145,'2026-09-22 10:00:00'),
(9,'Áo sơ mi kẻ sọc cao cấp','ao-so-mi-ke-soc',329000,279000,'<p>Áo sơ mi họa tiết kẻ sọc thanh lịch. Đầy đủ 3 màu: Kẻ sọc xanh, Kẻ sọc đen, Kẻ sọc đỏ đô.</p>',2,1,166,'2026-09-25 10:00:00'),
(10,'Áo sơ mi linen tay ngắn thoáng mát','ao-so-mi-linen-tay-ngan',369000,NULL,'<p>Chất liệu 100% linen tự nhiên, thấm hút mồ hôi tối ưu, phong cách phóng khoáng.</p>',2,1,103,'2026-09-28 10:00:00'),
(11,'Áo khoác denim jean unisex','ao-khoac-jean-unisex',649000,NULL,'<p>Jacket denim wash nhẹ, form rộng unisex, phong cách bụi bặm đường phố.</p>',3,1,84,'2026-09-11 10:00:00'),
(12,'Áo khoác varsity bóng chày','ao-khoac-varsity-bong-chay',629000,549000,'<p>Áo khoác varsity phong cách học đường năng động, phối màu tương phản cá tính.</p>',3,1,175,'2026-09-18 10:00:00'),
(13,'Áo khoác bomber dù 2 lớp chống gió','ao-bomber-du-2-lop',589000,499000,'<p>Bomber vải dù cản gió và chống nước nhẹ, lót trong mềm mại giữ ấm.</p>',7,1,121,'2026-09-14 10:00:00'),
(14,'Áo khoác bomber da PU cao cấp','ao-bomber-da-pu',749000,NULL,'<p>Chất da PU cao cấp chống nổ, lót lụa sang trọng, tôn phong cách nam tính.</p>',7,1,158,'2026-09-17 10:00:00'),
(15,'Áo khoác jean basic wash bụi bặm','ao-khoac-jean-basic',599000,519000,'<p>Chất denim wash nhẹ cổ điển, đường may chỉ nổi tinh xảo, khuy kim loại đúc.</p>',3,1,98,'2026-09-19 10:00:00'),
(16,'Hoodie nỉ bông form rộng ấm áp','hoodie-ni-bong-form-rong',449000,NULL,'<p>Nỉ bông 380gsm dày dặn, mũ 2 lớp đứng form, túi kangaroo rộng rãi tiện lợi.</p>',8,1,195,'2026-09-20 10:00:00'),
(17,'Hoodie zip khóa kéo nỉ chân cua','hoodie-zip-khoa-keo',479000,419000,'<p>Hoodie khóa kéo dễ mặc, vải nỉ da cá thoáng khí, khóa kéo kim loại mượt mà.</p>',8,1,232,'2026-09-23 10:00:00'),
(18,'Hoodie in hình graphic streetwear','hoodie-in-hinh-graphic',469000,NULL,'<p>Họa tiết in nổi bật sau lưng, form rộng oversize phong cách Gen Z năng động.</p>',8,1,160,'2026-09-24 10:00:00'),
(19,'Hoodie zip basic phong cách tối giản','hoodie-zip-basic',439000,389000,'<p>Form dáng regular thanh thoát, chất nỉ êm ái, thích hợp đi học và dạo phố.</p>',8,1,140,'2026-09-25 10:00:00'),
(20,'Áo bomber dù thể thao đa năng','ao-bomber-du-the-thao',529000,NULL,'<p>Áo bomber nhẹ thoáng khí, bo tay co giãn tốt, tiện lợi khoác ngoài thể thao.</p>',7,1,115,'2026-09-26 10:00:00'),
(21,'Quần jean slim fit wash xanh đậm','quan-jean-slim-fit-xanh-dam',549000,NULL,'<p>Jean co giãn 2% spandex, ôm vừa vặn bắp chân, wash xanh đậm lịch lãm.</p>',4,1,214,'2026-09-09 10:00:00'),
(22,'Quần jean ống rộng cạp cao tôn dáng','quan-jean-ong-rong-nu',529000,459000,'<p>Ống suông rộng cạp cao, tôn chân dài và che khuyết điểm chân hoàn hảo.</p>',4,1,251,'2026-09-12 10:00:00'),
(23,'Quần jean rách gối bụi bặm cá tính','quan-jean-rach-goi',579000,NULL,'<p>Cào rách gối thủ công tạo điểm nhấn mạnh mẽ, phong cách streetwear đường phố.</p>',4,1,138,'2026-09-15 10:00:00'),
(24,'Quần short jean rách gấu năng động','quan-short-jean-nam',399000,349000,'<p>Short jean mùa hè thoáng mát, gấu quần xước nhẹ phong cách trẻ trung.</p>',4,1,112,'2026-09-21 10:00:00'),
(25,'Quần short thể thao co giãn thoáng khí','quan-short-the-thao-nam-nu',289000,249000,'<p>Vải dù co giãn 4 chiều siêu nhẹ, nhanh khô, lưng thun dây rút thoải mái.</p>',4,1,95,'2026-09-22 10:00:00'),
(26,'Váy midi hoa nhí vintage dáng dài','vay-midi-hoa-nhi',459000,NULL,'<p>Váy midi hoa nhí vintage, chất voan tơ mềm mại kèm lớp lót kín đáo.</p>',5,1,149,'2026-09-24 10:00:00'),
(27,'Đầm suông công sở thanh lịch','dam-suong-cong-so',489000,429000,'<p>Đầm suông thiết kế tối giản, che khuyết điểm eo và bắp tay hiệu quả.</p>',5,1,186,'2026-09-27 10:00:00'),
(28,'Đầm maxi dự tiệc chiết eo dáng dài','dam-maxi-du-tiec',699000,NULL,'<p>Đầm maxi dáng dài thướt tha, đường cắt cúp tôn vòng eo sang trọng.</p>',5,1,260,'2026-09-05 10:00:00'),
(29,'Chân váy chữ A lưng cao tôn dáng','chan-vay-chu-a',289000,NULL,'<p>Chân váy chữ A cạp cao tôn dáng, có quần bảo hộ bên trong an toàn tiện lợi.</p>',5,1,223,'2026-09-02 10:00:00'),
(30,'Chân váy xếp ly dáng dài thanh lịch','chan-vay-xep-ly-dang-dai',379000,329000,'<p>Xếp ly ép nhiệt vĩnh viễn, chất voan cát 2 lớp dày dặn, bồng bềnh nữ tính.</p>',5,1,147,'2026-09-08 10:00:00');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reviews_product` (`product_id`,`status`),
  KEY `idx_reviews_user` (`user_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_reviews_rating` CHECK (`rating` between 1 and 5)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES
(1,1,2,5,'Vải mềm mịn, mặc rất thoải mái, chuẩn ảnh mặt trước sau.',1,'2026-10-04 20:00:00'),
(2,9,2,4,'Áo kẻ sọc đường may đẹp, màu đỏ đô và sọc đen mặc rất tôn da.',1,'2026-10-05 21:10:00'),
(3,16,3,5,'Hoodie nỉ bông dày dặn ấm cực kỳ, form unisex rất ưng.',1,'2026-10-18 09:00:00'),
(4,6,3,4,'Áo polo đẹp, vải cá sấu mặc thoáng khí.',1,'2026-10-10 15:30:00'),
(5,21,3,3,'Quần jean rách cá tính, hơi dài một chút.',1,'2026-10-19 12:45:00');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role_status` (`role`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Quản trị viên','admin@shop.local','$2y$10$lWIn5om4nh.WMIEx6jPKO.33l9TD0YSbaJ95MYbUyN1Knxm0bkn2u','0900000001','Củ Chi, TP. Hồ Chí Minh',NULL,'admin',1,'2026-09-01 08:00:00'),
(2,'Nguyễn Thị Lan','lan.nguyen@example.com','$2y$10$1.Io99/lojPEJVsBNeVAG.m5GJ8rYJqbaBB/qBJofb1OWZCjh0qe.','0912345678','12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh',NULL,'user',1,'2026-09-10 09:30:00'),
(3,'Trần Văn Minh','minh.tran@example.com','$2y$10$1.Io99/lojPEJVsBNeVAG.m5GJ8rYJqbaBB/qBJofb1OWZCjh0qe.','0987654321','45 Lê Lợi, Quận 3, TP. Hồ Chí Minh',NULL,'user',1,'2026-09-15 14:20:00'),
(4,'Lê Đăng Khoa','khoa@example.com','$2y$10$1.Io99/lojPEJVsBNeVAG.m5GJ8rYJqbaBB/qBJofb1OWZCjh0qe.','0933221100','8 Trần Hưng Đạo, Quận 5, TP. Hồ Chí Minh',NULL,'user',0,'2026-09-20 10:00:00');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wishlist` (`user_id`,`product_id`),
  KEY `fk_wl_product` (`product_id`),
  CONSTRAINT `fk_wl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlists`
--

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
INSERT INTO `wishlists` VALUES
(1,2,16),
(2,2,21),
(3,3,11),
(4,3,26);
/*!40000 ALTER TABLE `wishlists` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-13 18:55:30
