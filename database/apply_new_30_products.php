<?php
/**
 * Script cập nhật trực tiếp 30 sản phẩm mới vào MySQL (không dùng transaction để tránh lỗi DDL auto-commit)
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

echo "========================================================\n";
echo " BẮT ĐẦU CẬP NHẬT 30 SẢN PHẨM MỚI VÀO DATABASE\n";
echo "========================================================\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Cập nhật danh mục
    $pdo->exec("TRUNCATE TABLE categories;");
    $categories = [
        [1, 'Áo Thun & Croptop', 'ao-thun-croptop', 'Áo thun nam nữ cotton, áo oversize và áo croptop tôn dáng', null, 1],
        [2, 'Áo Sơ Mi & Polo', 'ao-so-mi-polo', 'Áo sơ mi công sở, sơ mi dạo phố và áo polo thanh lịch', null, 1],
        [3, 'Áo Khoác, Bomber & Hoodie', 'ao-khoac-bomber-hoodie', 'Áo khoác denim, varsity, bomber và hoodie ấm áp', null, 1],
        [4, 'Quần Jean & Quần Short', 'quan-jean-short', 'Quần jean slimfit, ống rộng, baggy và quần short năng động', null, 1],
        [5, 'Váy, Đầm & Chân Váy', 'vay-dam-chan-vay', 'Váy hoa nhí, đầm công sở, đầm maxi và chân váy xếp ly', null, 1],
        [7, 'Áo khoác bomber', 'ao-khoac-bomber', 'Bomber dù, bomber da thời trang', 3, 1],
        [8, 'Hoodie', 'hoodie', 'Áo hoodie nỉ bông, hoodie zip', 3, 1],
    ];
    $catStmt = $pdo->prepare("INSERT INTO categories (id, name, slug, description, parent_id, status) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
    }
    echo "1. [OK] Đã cập nhật " . count($categories) . " danh mục (5 nhóm chính theo hình + 2 nhóm con).\n";

    // 2. 30 sản phẩm mới (mỗi loại đúng 5 cái)
    $pdo->exec("TRUNCATE TABLE products;");
    $products = [
        // --- 1. ÁO THUN & CROPTOP (5 sản phẩm) ---
        [1, 'Áo thun basic cotton cổ tròn', 'ao-thun-basic-cotton-co-tron', 189000, 149000, '<p>Áo thun cotton 100% mềm mịn, thoáng mát, form regular dễ phối đồ hàng ngày.</p>', 1, 1, 150, '2026-09-04 10:00:00'],
        [2, 'Áo thun oversize form rộng unisex', 'ao-thun-oversize-form-rong', 249000, null, '<p>Form oversize phong cách streetwear Hàn Quốc, vải dày 250gsm không bai dão.</p>', 1, 1, 95, '2026-09-07 10:00:00'],
        [3, 'Áo thun in hình graphic cá tính', 'ao-thun-in-hinh-graphic', 229000, null, '<p>Họa tiết in sắc nét, độ bền màu cao, giặt máy thoải mái không lo bong tróc.</p>', 1, 1, 168, '2026-09-13 10:00:00'],
        [4, 'Áo thun tay dài thu đông', 'ao-thun-tay-dai-thu-dong', 279000, null, '<p>Chất liệu cotton co giãn nhẹ, giữ ấm vừa phải cho những ngày se lạnh.</p>', 1, 1, 242, '2026-09-19 10:00:00'],
        [5, 'Áo croptop nữ ôm body tôn dáng', 'ao-croptop-nu-om-body', 169000, 129000, '<p>Croptop co giãn 4 chiều, ôm sát vòng eo thon gọn, dễ mix cùng quần cạp cao.</p>', 1, 1, 205, '2026-09-16 10:00:00'],

        // --- 2. ÁO SƠ MI & POLO (5 sản phẩm) ---
        [6, 'Áo polo nam vải cá sấu pique', 'ao-polo-nam-vai-ca-sau', 299000, 259000, '<p>Polo pique dệt tổ ong thấm hút mồ hôi, cổ dệt bền form sang trọng.</p>', 2, 1, 131, '2026-09-10 10:00:00'],
        [7, 'Áo polo cổ bẻ thể thao năng động', 'ao-polo-co-be-the-thao', 319000, null, '<p>Kiểu dáng thể thao khỏe khoắn, vải co giãn vận động cực kỳ thoải mái.</p>', 2, 1, 110, '2026-09-12 10:00:00'],
        [8, 'Áo sơ mi trắng công sở chống nhăn', 'ao-so-mi-trang-cong-so', 349000, null, '<p>Sơ mi trắng chống nhăn cao cấp từ sợi Bamboo, thoáng mát cả ngày.</p>', 2, 1, 145, '2026-09-22 10:00:00'],
        [9, 'Áo sơ mi kẻ sọc cao cấp', 'ao-so-mi-ke-soc', 329000, 279000, '<p>Áo sơ mi họa tiết kẻ sọc thanh lịch. Đầy đủ 3 màu: Kẻ sọc xanh, Kẻ sọc đen, Kẻ sọc đỏ đô.</p>', 2, 1, 166, '2026-09-25 10:00:00'],
        [10, 'Áo sơ mi linen tay ngắn thoáng mát', 'ao-so-mi-linen-tay-ngan', 369000, null, '<p>Chất liệu 100% linen tự nhiên, thấm hút mồ hôi tối ưu, phong cách phóng khoáng.</p>', 2, 1, 103, '2026-09-28 10:00:00'],

        // --- 3. ÁO KHOÁC (5 sản phẩm) ---
        [11, 'Áo khoác denim jean unisex', 'ao-khoac-jean-unisex', 649000, null, '<p>Jacket denim wash nhẹ, form rộng unisex, phong cách bụi bặm đường phố.</p>', 3, 1, 84, '2026-09-11 10:00:00'],
        [12, 'Áo khoác varsity bóng chày', 'ao-khoac-varsity-bong-chay', 629000, 549000, '<p>Áo khoác varsity phong cách học đường năng động, phối màu tương phản cá tính.</p>', 3, 1, 175, '2026-09-18 10:00:00'],
        [13, 'Áo khoác bomber dù 2 lớp chống gió', 'ao-bomber-du-2-lop', 589000, 499000, '<p>Bomber vải dù cản gió và chống nước nhẹ, lót trong mềm mại giữ ấm.</p>', 7, 1, 121, '2026-09-14 10:00:00'],
        [14, 'Áo khoác bomber da PU cao cấp', 'ao-bomber-da-pu', 749000, null, '<p>Chất da PU cao cấp chống nổ, lót lụa sang trọng, tôn phong cách nam tính.</p>', 7, 1, 158, '2026-09-17 10:00:00'],
        [15, 'Áo khoác jean basic wash bụi bặm', 'ao-khoac-jean-basic', 599000, 519000, '<p>Chất denim wash nhẹ cổ điển, đường may chỉ nổi tinh xảo, khuy kim loại đúc.</p>', 3, 1, 98, '2026-09-19 10:00:00'],

        // --- 4. BOMBER & HOODIE (5 sản phẩm) ---
        [16, 'Hoodie nỉ bông form rộng ấm áp', 'hoodie-ni-bong-form-rong', 449000, null, '<p>Nỉ bông 380gsm dày dặn, mũ 2 lớp đứng form, túi kangaroo rộng rãi tiện lợi.</p>', 8, 1, 195, '2026-09-20 10:00:00'],
        [17, 'Hoodie zip khóa kéo nỉ chân cua', 'hoodie-zip-khoa-keo', 479000, 419000, '<p>Hoodie khóa kéo dễ mặc, vải nỉ da cá thoáng khí, khóa kéo kim loại mượt mà.</p>', 8, 1, 232, '2026-09-23 10:00:00'],
        [18, 'Hoodie in hình graphic streetwear', 'hoodie-in-hinh-graphic', 469000, null, '<p>Họa tiết in nổi bật sau lưng, form rộng oversize phong cách Gen Z năng động.</p>', 8, 1, 160, '2026-09-24 10:00:00'],
        [19, 'Hoodie zip basic phong cách tối giản', 'hoodie-zip-basic', 439000, 389000, '<p>Form dáng regular thanh thoát, chất nỉ êm ái, thích hợp đi học và dạo phố.</p>', 8, 1, 140, '2026-09-25 10:00:00'],
        [20, 'Áo bomber dù thể thao đa năng', 'ao-bomber-du-the-thao', 529000, null, '<p>Áo bomber nhẹ thoáng khí, bo tay co giãn tốt, tiện lợi khoác ngoài thể thao.</p>', 7, 1, 115, '2026-09-26 10:00:00'],

        // --- 5. QUẦN JEAN & QUẦN SHORT (5 sản phẩm) ---
        [21, 'Quần jean slim fit wash xanh đậm', 'quan-jean-slim-fit-xanh-dam', 549000, null, '<p>Jean co giãn 2% spandex, ôm vừa vặn bắp chân, wash xanh đậm lịch lãm.</p>', 4, 1, 214, '2026-09-09 10:00:00'],
        [22, 'Quần jean ống rộng cạp cao tôn dáng', 'quan-jean-ong-rong-nu', 529000, 459000, '<p>Ống suông rộng cạp cao, tôn chân dài và che khuyết điểm chân hoàn hảo.</p>', 4, 1, 251, '2026-09-12 10:00:00'],
        [23, 'Quần jean rách gối bụi bặm cá tính', 'quan-jean-rach-goi', 579000, null, '<p>Cào rách gối thủ công tạo điểm nhấn mạnh mẽ, phong cách streetwear đường phố.</p>', 4, 1, 138, '2026-09-15 10:00:00'],
        [24, 'Quần short jean rách gấu năng động', 'quan-short-jean-nam', 399000, 349000, '<p>Short jean mùa hè thoáng mát, gấu quần xước nhẹ phong cách trẻ trung.</p>', 4, 1, 112, '2026-09-21 10:00:00'],
        [25, 'Quần short thể thao co giãn thoáng khí', 'quan-short-the-thao-nam-nu', 289000, 249000, '<p>Vải dù co giãn 4 chiều siêu nhẹ, nhanh khô, lưng thun dây rút thoải mái.</p>', 4, 1, 95, '2026-09-22 10:00:00'],

        // --- 6. VÁY, ĐẦM & CHÂN VÁY (5 sản phẩm) ---
        [26, 'Váy midi hoa nhí vintage dáng dài', 'vay-midi-hoa-nhi', 459000, null, '<p>Váy midi hoa nhí vintage, chất voan tơ mềm mại kèm lớp lót kín đáo.</p>', 5, 1, 149, '2026-09-24 10:00:00'],
        [27, 'Đầm suông công sở thanh lịch', 'dam-suong-cong-so', 489000, 429000, '<p>Đầm suông thiết kế tối giản, che khuyết điểm eo và bắp tay hiệu quả.</p>', 5, 1, 186, '2026-09-27 10:00:00'],
        [28, 'Đầm maxi dự tiệc chiết eo dáng dài', 'dam-maxi-du-tiec', 699000, null, '<p>Đầm maxi dáng dài thướt tha, đường cắt cúp tôn vòng eo sang trọng.</p>', 5, 1, 260, '2026-09-05 10:00:00'],
        [29, 'Chân váy chữ A lưng cao tôn dáng', 'chan-vay-chu-a', 289000, null, '<p>Chân váy chữ A cạp cao tôn dáng, có quần bảo hộ bên trong an toàn tiện lợi.</p>', 5, 1, 223, '2026-09-02 10:00:00'],
        [30, 'Chân váy xếp ly dáng dài thanh lịch', 'chan-vay-xep-ly-dang-dai', 379000, 329000, '<p>Xếp ly ép nhiệt vĩnh viễn, chất voan cát 2 lớp dày dặn, bồng bềnh nữ tính.</p>', 5, 1, 147, '2026-09-08 10:00:00']
    ];

    $prodStmt = $pdo->prepare("INSERT INTO products (id, name, slug, price, sale_price, description, category_id, status, views, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($products as $p) {
        $prodStmt->execute($p);
    }
    echo "2. [OK] Đã nạp chính xác " . count($products) . " sản phẩm thời trang (mỗi nhóm 5 cái).\n";

    // 3. Ảnh sản phẩm (Đầy đủ ảnh mặt trước làm ảnh chính và ảnh mặt sau)
    $pdo->exec("TRUNCATE TABLE product_images;");
    $images = [
        // SP 1: Áo thun basic cotton
        [1, 1, 'uploads/products/Ao-thun-basic-co-tron-den-mat-truoc.jpg', 1, 1],
        [2, 1, 'uploads/products/Ao-thun-basic-co-tron-den-mat-sau.jpg', 0, 2],

        // SP 2: Áo thun oversize
        [3, 2, 'uploads/products/Ao-thun-oversize-basic-mat-truoc.jpg', 1, 1],
        [4, 2, 'uploads/products/Ao-thun-oversize-basic-mat-sau.webp', 0, 2],

        // SP 3: Áo thun in graphic
        [5, 3, 'uploads/products/Ao-thun-in-logo-mat-truoc.jpg', 1, 1],
        [6, 3, 'uploads/products/Ao-thun-in-logo-mat-sau.webp', 0, 2],

        // SP 4: Áo thun tay dài
        [7, 4, 'uploads/products/ao-thun-tay-dai-thu-dong-1.jpg', 1, 1],
        [8, 4, 'uploads/products/ao-thun-tay-dai-thu-dong-2.jpg', 0, 2],

        // SP 5: Croptop ôm body
        [9, 5, 'uploads/products/Ao-croptop-mat-truoc.jpg', 1, 1],
        [10, 5, 'uploads/products/Ao-croptop-mat-sau.webp', 0, 2],

        // SP 6: Áo polo nam cá sấu
        [11, 6, 'uploads/products/Ao-polo-tron-mat-truoc.jpg', 1, 1],
        [12, 6, 'uploads/products/Ao-polo-tron-mat-sau.webp', 0, 2],

        // SP 7: Áo polo cổ bẻ thể thao
        [13, 7, 'uploads/products/Ao-polo-co-tron-mat-truoc.jpg', 1, 1],
        [14, 7, 'uploads/products/ao-polo-nam-vai-ca-sau-2.jpg', 0, 2],

        // SP 8: Áo sơ mi trắng
        [15, 8, 'uploads/products/ao-so-mi-trang-cong-so-1.jpg', 1, 1],
        [16, 8, 'uploads/products/ao-so-mi-trang-cong-so-2.jpg', 0, 2],

        // SP 9: Áo sơ mi kẻ sọc (3 màu kẻ sọc: Xanh, Đen, Đỏ đô - đầy đủ mặt trước & sau)
        [17, 9, 'uploads/products/ao-so-mi-ke-soc-xanh-1.jpg', 1, 1],
        [18, 9, 'uploads/products/ao-so-mi-ke-soc-xanh-2.jpg', 0, 2],
        [19, 9, 'uploads/products/ao-so-mi-ke-soc-den-mat-truoc.jpg', 0, 3],
        [20, 9, 'uploads/products/ao-so-mi-ke-soc-den-mat-sau.jpg', 0, 4],
        [21, 9, 'uploads/products/ao-so-mi-ke-soc-do-mat-truoc.jpg', 0, 5],
        [22, 9, 'uploads/products/ao-so-mi-ke-soc-do-mat-sau.jpg', 0, 6],

        // SP 10: Áo sơ mi linen
        [23, 10, 'uploads/products/ao-so-mi-linen-tay-ngan-1.jpg', 1, 1],
        [24, 10, 'uploads/products/ao-so-mi-linen-tay-ngan-2.jpg', 0, 2],

        // SP 11: Áo khoác denim
        [25, 11, 'uploads/products/Ao-khoac-denim-mat-truoc.jpg', 1, 1],
        [26, 11, 'uploads/products/Ao-khoac-denim-mat-sau.webp', 0, 2],

        // SP 12: Áo varsity bóng chày
        [27, 12, 'uploads/products/Ao-khoac-varsity-mat-truoc.jpg', 1, 1],
        [28, 12, 'uploads/products/Ao-khoac-varsity-mat-sau.webp', 0, 2],

        // SP 13: Bomber dù 2 lớp
        [29, 13, 'uploads/products/ao-bomber-du-2-lop-1.jpg', 1, 1],
        [30, 13, 'uploads/products/ao-bomber-du-2-lop-2.jpg', 0, 2],

        // SP 14: Bomber da PU
        [31, 14, 'uploads/products/ao-bomber-da-pu-1.jpg', 1, 1],
        [32, 14, 'uploads/products/ao-bomber-da-pu-2.jpg', 0, 2],

        // SP 15: Áo khoác jean basic
        [33, 15, 'uploads/products/ao-khoac-jean-unisex-1.jpg', 1, 1],
        [34, 15, 'uploads/products/ao-khoac-jean-unisex-2.jpg', 0, 2],

        // SP 16: Hoodie nỉ bông
        [35, 16, 'uploads/products/Ao-khoac-hoodie-oversize-mat-truoc.jpg', 1, 1],
        [36, 16, 'uploads/products/Ao-khoac-hoodie-oversize-mat-sau.webp', 0, 2],

        // SP 17: Hoodie zip khóa kéo
        [37, 17, 'uploads/products/Ao-khoac-hoodie-zip-mat-truoc.jpg', 1, 1],
        [38, 17, 'uploads/products/Ao-khoac-hoodie-zip-mat-sau.webp', 0, 2],

        // SP 18: Hoodie in graphic
        [39, 18, 'uploads/products/hoodie-ni-bong-form-rong-1.jpg', 1, 1],
        [40, 18, 'uploads/products/hoodie-ni-bong-form-rong-2.jpg', 0, 2],

        // SP 19: Hoodie zip basic
        [41, 19, 'uploads/products/hoodie-zip-khoa-keo-1.jpg', 1, 1],
        [42, 19, 'uploads/products/hoodie-zip-khoa-keo-2.jpg', 0, 2],

        // SP 20: Áo bomber thể thao
        [43, 20, 'uploads/products/ao-bomber-du-2-lop-3.jpg', 1, 1],
        [44, 20, 'uploads/products/ao-bomber-da-pu-3.jpg', 0, 2],

        // SP 21: Quần jean slimfit
        [45, 21, 'uploads/products/Quan-jeans-straight-mat-truoc.jpg', 1, 1],
        [46, 21, 'uploads/products/Quan-jeans-straight-mat-sau.webp', 0, 2],

        // SP 22: Quần jean ống rộng
        [47, 22, 'uploads/products/Quan-jeans-ong-rong-mat-truoc.jpg', 1, 1],
        [48, 22, 'uploads/products/Quan-jeans-ong-rong-mat-sau.webp', 0, 2],

        // SP 23: Quần jean rách gối
        [49, 23, 'uploads/products/quan-jean-rach-goi-1.jpg', 1, 1],
        [50, 23, 'uploads/products/quan-jean-rach-goi-2.jpg', 0, 2],

        // SP 24: Quần short jean
        [51, 24, 'uploads/products/quan-short-jean-nam-1.jpg', 1, 1],
        [52, 24, 'uploads/products/quan-short-jean-nam-2.jpg', 0, 2],

        // SP 25: Quần short thể thao
        [53, 25, 'uploads/products/Quan-short-the-thao-mat-truoc.jpg', 1, 1],
        [54, 25, 'uploads/products/Quan-short-the-thao-mat-sau.webp', 0, 2],

        // SP 26: Váy midi hoa nhí
        [55, 26, 'uploads/products/Chan-vay-midi-mat-truoc.jpg', 1, 1],
        [56, 26, 'uploads/products/Chan-vay-midi-mat-sau.webp', 0, 2],

        // SP 27: Đầm suông công sở
        [57, 27, 'uploads/products/dam-suong-cong-so-1.jpg', 1, 1],
        [58, 27, 'uploads/products/dam-suong-cong-so-2.jpg', 0, 2],

        // SP 28: Đầm maxi dự tiệc
        [59, 28, 'uploads/products/dam-maxi-du-tiec-1.jpg', 1, 1],
        [60, 28, 'uploads/products/dam-maxi-du-tiec-2.jpg', 0, 2],

        // SP 29: Chân váy chữ A
        [61, 29, 'uploads/products/chan-vay-chu-a-1.jpg', 1, 1],
        [62, 29, 'uploads/products/chan-vay-chu-a-2.jpg', 0, 2],

        // SP 30: Chân váy xếp ly dáng dài
        [63, 30, 'uploads/products/Chan-vay-xep-ly-mat-truoc.jpg', 1, 1],
        [64, 30, 'uploads/products/Chan-vay-xep-li-mat-sau.webp', 0, 2],
    ];

    $imgStmt = $pdo->prepare("INSERT INTO product_images (id, product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($images as $img) {
        $imgStmt->execute($img);
    }
    echo "3. [OK] Đã nạp " . count($images) . " ảnh sản phẩm (chuẩn ảnh mặt trước & mặt sau thực tế).\n";

    // 4. Biến thể sản phẩm (Mỗi sản phẩm đều có từ 2 - 3 màu khác nhau + các kích thước)
    $pdo->exec("TRUNCATE TABLE product_variants;");
    $productConfig = [
        // 1. Áo thun & Croptop
        1  => ['colors' => ['Trắng', 'Đen', 'Xám'], 'sizes' => ['S', 'M', 'L']],
        2  => ['colors' => ['Trắng', 'Be', 'Xám'], 'sizes' => ['S', 'M', 'L']],
        3  => ['colors' => ['Đen', 'Trắng'], 'sizes' => ['S', 'M', 'L']],
        4  => ['colors' => ['Xám', 'Đen', 'Xanh rêu'], 'sizes' => ['S', 'M', 'L']],
        5  => ['colors' => ['Đen', 'Trắng', 'Hồng'], 'sizes' => ['S', 'M', 'L']],

        // 2. Áo sơ mi & Polo
        6  => ['colors' => ['Xanh navy', 'Trắng', 'Đen'], 'sizes' => ['S', 'M', 'L', 'XL']],
        7  => ['colors' => ['Xám', 'Đen', 'Trắng'], 'sizes' => ['S', 'M', 'L', 'XL']],
        8  => ['colors' => ['Trắng', 'Xanh nhạt'], 'sizes' => ['S', 'M', 'L', 'XL']],
        9  => ['colors' => ['Kẻ sọc xanh', 'Kẻ sọc đen', 'Kẻ sọc đỏ đô'], 'sizes' => ['S', 'M', 'L', 'XL']],
        10 => ['colors' => ['Be', 'Trắng', 'Xanh rêu'], 'sizes' => ['S', 'M', 'L', 'XL']],

        // 3. Áo khoác
        11 => ['colors' => ['Xanh nhạt', 'Xanh đậm'], 'sizes' => ['S', 'M', 'L', 'XL']],
        12 => ['colors' => ['Đen phối trắng', 'Xanh navy phối trắng'], 'sizes' => ['S', 'M', 'L', 'XL']],
        13 => ['colors' => ['Đen', 'Xanh rêu'], 'sizes' => ['S', 'M', 'L', 'XL']],
        14 => ['colors' => ['Đen', 'Nâu bò'], 'sizes' => ['S', 'M', 'L', 'XL']],
        15 => ['colors' => ['Xanh wash', 'Đen denim'], 'sizes' => ['S', 'M', 'L', 'XL']],

        // 4. Bomber & Hoodie
        16 => ['colors' => ['Xám tiêu', 'Đen', 'Xanh pastel'], 'sizes' => ['S', 'M', 'L', 'XL']],
        17 => ['colors' => ['Xám chì', 'Đen', 'Be'], 'sizes' => ['S', 'M', 'L', 'XL']],
        18 => ['colors' => ['Đen', 'Trắng kem'], 'sizes' => ['S', 'M', 'L', 'XL']],
        19 => ['colors' => ['Đen', 'Xám ghi'], 'sizes' => ['S', 'M', 'L', 'XL']],
        20 => ['colors' => ['Đen', 'Xanh than'], 'sizes' => ['S', 'M', 'L', 'XL']],

        // 5. Quần jean & Quần short
        21 => ['colors' => ['Xanh đậm', 'Đen'], 'sizes' => ['29', '30', '31', '32']],
        22 => ['colors' => ['Xanh nhạt', 'Xanh retro'], 'sizes' => ['29', '30', '31', '32']],
        23 => ['colors' => ['Xanh wash rách', 'Đen wash rách'], 'sizes' => ['29', '30', '31', '32']],
        24 => ['colors' => ['Xanh nhạt', 'Đen denim'], 'sizes' => ['29', '30', '31', '32']],
        25 => ['colors' => ['Đen', 'Xám', 'Xanh than'], 'sizes' => ['M', 'L', 'XL']],

        // 6. Váy, Đầm & Chân váy
        26 => ['colors' => ['Hoa vàng vintage', 'Hoa xanh pastel'], 'sizes' => ['S', 'M', 'L']],
        27 => ['colors' => ['Đen', 'Be cát', 'Hồng phấn'], 'sizes' => ['S', 'M', 'L']],
        28 => ['colors' => ['Đỏ rượu', 'Trắng ngọc', 'Đen tuyền'], 'sizes' => ['S', 'M', 'L']],
        29 => ['colors' => ['Đen', 'Xám ghi', 'Nâu tây'], 'sizes' => ['S', 'M', 'L']],
        30 => ['colors' => ['Kem be', 'Hồng đất'], 'sizes' => ['S', 'M', 'L']],
    ];

    $varStmt = $pdo->prepare("INSERT INTO product_variants (id, product_id, size, color, stock) VALUES (?, ?, ?, ?, ?)");
    $vId = 1;
    foreach ($productConfig as $productId => $cfg) {
        foreach ($cfg['colors'] as $cIdx => $color) {
            foreach ($cfg['sizes'] as $sIdx => $size) {
                $stock = ($productId === 13 && $cIdx === 0 && $sIdx === 0) ? 2 : (($productId === 5 && $cIdx === 0 && $sIdx === 0) ? 0 : (10 + (($vId * 7) % 25)));
                $varStmt->execute([$vId, $productId, $size, $color, $stock]);
                $vId++;
            }
        }
    }
    echo "4. [OK] Đã nạp " . ($vId - 1) . " biến thể (mỗi sản phẩm có 2-3 màu khác nhau).\n";

    // 5. Cập nhật các bảng liên kết
    $pdo->exec("TRUNCATE TABLE order_items;");
    $pdo->exec("INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, price) VALUES
        (1, 1, 1, 2, 2, 149000),
        (2, 1, 10, 40, 1, 369000),
        (3, 2, 16, 65, 1, 449000),
        (4, 2, 21, 85, 1, 549000),
        (5, 3, 26, 105, 1, 459000),
        (6, 3, 5, 14, 1, 129000),
        (7, 4, 6, 25, 1, 259000),
        (8, 5, 24, 95, 1, 399000),
        (9, 5, 1, 4, 2, 149000);");

    $pdo->exec("TRUNCATE TABLE wishlists;");
    $pdo->exec("INSERT INTO wishlists (id, user_id, product_id) VALUES
        (1, 2, 16),
        (2, 2, 21),
        (3, 3, 11),
        (4, 3, 26);");

    $pdo->exec("TRUNCATE TABLE reviews;");
    $pdo->exec("INSERT INTO reviews (id, product_id, user_id, rating, comment, status, created_at) VALUES
        (1, 1, 2, 5, 'Vải mềm mịn, mặc rất thoải mái, chuẩn ảnh mặt trước sau.', 1, '2026-10-04 20:00:00'),
        (2, 9, 2, 4, 'Áo kẻ sọc đường may đẹp, màu đỏ đô và sọc đen mặc rất tôn da.', 1, '2026-10-05 21:10:00'),
        (3, 16, 3, 5, 'Hoodie nỉ bông dày dặn ấm cực kỳ, form unisex rất ưng.', 1, '2026-10-18 09:00:00'),
        (4, 6, 3, 4, 'Áo polo đẹp, vải cá sấu mặc thoáng khí.', 1, '2026-10-10 15:30:00'),
        (5, 21, 3, 3, 'Quần jean rách cá tính, hơi dài một chút.', 1, '2026-10-19 12:45:00');");

    $pdo->exec("TRUNCATE TABLE cart_items;");
    $pdo->exec("INSERT INTO cart_items (id, cart_id, variant_id, quantity) VALUES
        (1, 1, 1, 1),
        (2, 1, 25, 2);");

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "========================================================\n";
    echo " CẬP NHẬT HOÀN TẤT: ĐÃ CÓ ĐỦ 30 SẢN PHẨM MỚI TRONG CSDL!\n";
    echo "========================================================\n";
} catch (Throwable $e) {
    echo "LỖI KHI CẬP NHẬT: " . $e->getMessage() . "\n";
}
