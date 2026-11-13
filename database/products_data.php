<?php
/**
 * Dữ liệu 30 sản phẩm thời trang chuẩn đồ án TV3
 */

$categories = [
    [1, 'Áo Thun & Croptop', 'ao-thun-croptop', 'Áo thun nam nữ cotton, oversize và áo croptop tôn dáng', null, 1],
    [2, 'Áo Sơ Mi & Polo', 'ao-so-mi-polo', 'Áo sơ mi công sở thanh lịch, sơ mi dạo phố và áo polo thể thao', null, 1],
    [3, 'Áo Khoác, Bomber & Hoodie', 'ao-khoac-bomber-hoodie', 'Áo khoác denim, varsity, bomber và hoodie nỉ ấm áp', null, 1],
    [4, 'Quần Jean & Quần Short', 'quan-jean-short', 'Quần jean slimfit, ống rộng, baggy và quần short năng động', null, 1],
    [5, 'Váy, Đầm & Chân Váy', 'vay-dam-chan-vay', 'Váy hoa nhí, đầm suông, đầm maxi dự tiệc và chân váy xếp ly', null, 1],
    [7, 'Áo khoác bomber', 'ao-khoac-bomber', 'Bomber dù, bomber da thời trang', 3, 1],
    [8, 'Hoodie', 'hoodie', 'Áo hoodie nỉ bông, hoodie zip', 3, 1],
];

$products = [
    // Nhóm 1: Áo Thun & Croptop (Cat 1)
    [1, 'Áo thun basic cotton cổ tròn', 'ao-thun-basic-cotton-co-tron', 189000, 149000, '<p>Áo thun cotton 100% mềm mịn, form regular dễ phối đồ.</p><ul><li>Chất liệu: Cotton 100% 4 chiều</li><li>Thấm hút mồ hôi tốt, không bai xù</li><li>Đổi trả 7 ngày nếu có lỗi</li></ul>', 1, 1, 120, '2026-09-04 10:00:00'],
    [2, 'Áo thun oversize form rộng', 'ao-thun-oversize-form-rong', 249000, null, '<p>Form oversize phong cách streetwear Hàn Quốc, vải dày dặn.</p><ul><li>Chất liệu: Cotton 250gsm dày dặn</li><li>Form rộng unisex nam nữ đều mặc đẹp</li></ul>', 1, 1, 95, '2026-09-07 10:00:00'],
    [3, 'Áo thun in hình graphic streetwear', 'ao-thun-in-hinh-graphic', 229000, null, '<p>Họa tiết in lụa cao cấp sắc nét, cá tính, giặt không bong tróc.</p><ul><li>Mực in cao cấp chuẩn quốc tế</li><li>Phong cách trẻ trung năng động</li></ul>', 1, 1, 168, '2026-09-13 10:00:00'],
    [4, 'Áo thun tay dài thu đông', 'ao-thun-tay-dai-thu-dong', 279000, null, '<p>Chất liệu cotton dày vừa, giữ ấm nhẹ nhàng cho thời tiết se lạnh.</p><ul><li>Dễ phối cùng áo khoác hoặc mặc đơn</li><li>Bo cổ tay co giãn thoải mái</li></ul>', 1, 1, 242, '2026-09-19 10:00:00'],
    [5, 'Áo croptop nữ ôm body tôn dáng', 'ao-croptop-nu-om-body', 169000, 129000, '<p>Croptop co giãn 4 chiều, ôm sát tôn đường cong quyến rũ.</p><ul><li>Mix hoàn hảo với quần cạp cao hoặc chân váy</li><li>Vải borip gân co giãn tối ưu</li></ul>', 1, 1, 205, '2026-09-16 10:00:00'],
    [6, 'Áo croptop cổ vuông tay ngắn', 'ao-croptop-co-vuong-tay-ngan', 179000, null, '<p>Thiết kế cổ vuông khoe xương quai xanh thanh thoát, phong cách tiểu thư.</p><ul><li>Vải thun cotton mịn mát</li><li>Tạo cảm giác thon gọn phần thân trên</li></ul>', 1, 1, 88, '2026-09-20 10:00:00'],

    // Nhóm 2: Áo Sơ Mi & Polo (Cat 2)
    [7, 'Áo polo nam vải cá sấu pique', 'ao-polo-nam-vai-ca-sau', 299000, 259000, '<p>Polo pique dệt tổ ong thoáng khí, cổ dệt bền form, hợp đi làm và đi chơi.</p><ul><li>Chất vải dệt pique thấm hút tốt</li><li>Đường may tỉ mỉ, tôn dáng người mặc</li></ul>', 2, 1, 131, '2026-09-10 10:00:00'],
    [8, 'Áo polo cổ bẻ thể thao năng động', 'ao-polo-co-be-the-thao', 319000, null, '<p>Thiết kế năng động với viền cổ cách điệu, chất thun co giãn vận động thoải mái.</p><ul><li>Phù hợp thể thao, dạo phố và du lịch</li><li>Kháng khuẩn, khử mùi hiệu quả</li></ul>', 2, 1, 110, '2026-09-12 10:00:00'],
    [9, 'Áo sơ mi trắng công sở chống nhăn', 'ao-so-mi-trang-cong-so', 349000, null, '<p>Sơ mi trắng chống nhăn cao cấp, phù hợp môi trường văn phòng chuyên nghiệp.</p><ul><li>Vải Bamboo tự nhiên chống nhăn vượt trội</li><li>Form slimfit thanh lịch</li></ul>', 2, 1, 145, '2026-09-22 10:00:00'],
    [10, 'Áo sơ mi kẻ sọc cao cấp', 'ao-so-mi-ke-soc', 329000, 279000, '<p>Họa tiết kẻ sọc thanh lịch, chất vải cotton pha thoáng mát cả ngày dài.</p><ul><li>3 phiên bản màu kẻ sọc: Sọc xanh biển, Sọc đen thanh lịch, Sọc đỏ đô trẻ trung</li><li>Tạo hiệu ứng thon gọn dáng vóc</li><li>Đầy đủ ảnh chi tiết mặt trước và mặt sau</li></ul>', 2, 1, 66, '2026-09-25 10:00:00'],
    [11, 'Áo sơ mi linen tay ngắn thoáng mát', 'ao-so-mi-linen-tay-ngan', 369000, null, '<p>Vải linen tự nhiên, thấm hút mồ hôi tối ưu, đem lại cảm giác tự do phóng khoáng.</p><ul><li>100% Linen nguồn gốc thực vật</li><li>Màu sắc mộc mạc tinh tế</li></ul>', 2, 1, 103, '2026-09-28 10:00:00'],
    [12, 'Áo sơ mi flannel caro thời thượng', 'ao-so-mi-flannel-caro', 399000, null, '<p>Flannel caro chất vải dày dặn ấm áp, có thể mặc riêng hoặc khoác ngoài áo thun.</p><ul><li>Họa tiết caro cá tính, không bao giờ lỗi mốt</li><li>Chất nỉ bông mềm mại</li></ul>', 2, 1, 140, '2026-09-03 10:00:00'],

    // Nhóm 3: Áo Khoác, Bomber & Hoodie (Cat 3, 7, 8)
    [13, 'Áo khoác denim jean unisex', 'ao-khoac-jean-unisex', 649000, null, '<p>Jacket denim wash nhẹ, form rộng unisex, phong cách bụi bặm đường phố.</p><ul><li>Vải denim 14oz chắc chắn</li><li>Khuy kim loại đúc bền đẹp</li></ul>', 3, 1, 84, '2026-09-11 10:00:00'],
    [14, 'Áo bomber dù 2 lớp chống gió', 'ao-bomber-du-2-lop', 589000, 499000, '<p>Bomber vải dù trượt nước, cản gió tốt, lót trong mềm mại giữ ấm.</p><ul><li>Vải dù micro-polyester cao cấp</li><li>Túi tay tiện lợi phong cách phi công</li></ul>', 7, 1, 121, '2026-09-14 10:00:00'],
    [15, 'Áo bomber da PU cao cấp', 'ao-bomber-da-pu', 749000, null, '<p>Chất da PU cao cấp không bong nổ, lót lụa sang trọng, đường may chắc chắn.</p><ul><li>Chống thấm nước nhẹ, dễ lau chùi</li><li>Kiểu dáng nam tính, thời thượng</li></ul>', 7, 1, 158, '2026-09-17 10:00:00'],
    [16, 'Áo khoác varsity bóng chày', 'ao-khoac-varsity-bong-chay', 629000, 549000, '<p>Áo khoác varsity phong cách học đường năng động, phối màu tương phản cá tính.</p><ul><li>Chất vải dạ phối tay da cao cấp</li><li>Bo dệt cổ áo và gấu áo dày dặn</li></ul>', 3, 1, 175, '2026-09-18 10:00:00'],
    [17, 'Hoodie nỉ bông form rộng', 'hoodie-ni-bong-form-rong', 449000, null, '<p>Nỉ bông dày ấm 380gsm, mũ 2 lớp đứng form, túi kangaroo rộng rãi.</p><ul><li>Phong cách oversize thoải mái</li><li>Không xù lông sau khi giặt máy</li></ul>', 8, 1, 195, '2026-09-20 10:00:00'],
    [18, 'Hoodie zip khóa kéo nỉ chân cua', 'hoodie-zip-khoa-keo', 479000, 419000, '<p>Hoodie khóa kéo dễ mặc, vải nỉ da cá thoáng khí, khóa kéo kim loại mượt mà.</p><ul><li>Tiện dụng khoác ngoài đi học, đi chơi</li><li>Form dáng unisex thời trang</li></ul>', 8, 1, 232, '2026-09-23 10:00:00'],

    // Nhóm 4: Quần Jean & Quần Short (Cat 4)
    [19, 'Quần jean slim fit wash xanh đậm', 'quan-jean-slim-fit-xanh-dam', 549000, null, '<p>Jean co giãn nhẹ, ôm vừa vặn bắp chân, wash màu xanh đậm lịch lãm.</p><ul><li>Vải denim co giãn 2% spandex</li><li>Không phai màu, bền bỉ theo thời gian</li></ul>', 4, 1, 214, '2026-09-09 10:00:00'],
    [20, 'Quần jean ống rộng cạp cao tôn dáng', 'quan-jean-ong-rong-nu', 529000, 459000, '<p>Ống rộng suông dài cạp cao, tôn chân dài và che khuyết điểm chân cực tốt.</p><ul><li>Chất jean cotton không giãn đứng form</li><li>Hack dáng hoàn hảo cho phái nữ</li></ul>', 4, 1, 251, '2026-09-12 10:00:00'],
    [21, 'Quần jean rách gối bụi bặm cá tính', 'quan-jean-rach-goi', 579000, null, '<p>Chi tiết cào rách gối thủ công cá tính, chuẩn phong cách streetwear bụi bặm.</p><ul><li>Đường may gia cố chắc chắn ở vết rách</li><li>Wash màu xám xanh độc đáo</li></ul>', 4, 1, 138, '2026-09-15 10:00:00'],
    [22, 'Quần jean baggy thời trang trẻ trung', 'quan-jean-baggy', 559000, null, '<p>Form baggy rộng rãi ở đùi và thon dần xuống gấu, tạo sự năng động thoải mái.</p><ul><li>Vải denim dày 13oz bền chắc</li><li>Phù hợp cho cả nam và nữ</li></ul>', 4, 1, 75, '2026-09-18 10:00:00'],
    [23, 'Quần short jean rách gấu năng động', 'quan-short-jean-nam', 399000, 349000, '<p>Short jean mùa hè thoáng mát, gấu quần xước nhẹ phong cách trẻ trung.</p><ul><li>Chất vải mềm mát có độ co giãn nhẹ</li><li>Túi sâu tiện lợi để điện thoại, ví</li></ul>', 4, 1, 112, '2026-09-21 10:00:00'],
    [24, 'Quần short thể thao co giãn thoáng khí', 'quan-short-the-thao-nam-nu', 289000, 249000, '<p>Quần short thể thao vải dù co giãn 4 chiều, siêu nhẹ và nhanh khô.</p><ul><li>Lưng thun kèm dây rút tiện lợi</li><li>Thích hợp tập gym, chạy bộ, mặc ở nhà</li></ul>', 4, 1, 95, '2026-09-22 10:00:00'],

    // Nhóm 5: Váy, Đầm & Chân Váy (Cat 5)
    [25, 'Váy midi hoa nhí vintage', 'vay-midi-hoa-nhi', 459000, null, '<p>Váy midi họa tiết hoa nhí vintage, chất voan tơ mềm mại kèm lớp lót kín đáo.</p><ul><li>Chiết eo nhẹ nhàng tôn dáng</li><li>Vạt xòe bay bổng khi bước đi</li></ul>', 5, 1, 149, '2026-09-24 10:00:00'],
    [26, 'Đầm suông công sở thanh lịch', 'dam-suong-cong-so', 489000, 429000, '<p>Đầm suông thiết kế tối giản, che khuyết điểm eo và bắp tay hiệu quả.</p><ul><li>Chất vải tuyết mưa cao cấp chống nhăn</li><li>Mặc thoải mái cả ngày làm việc</li></ul>', 5, 1, 186, '2026-09-27 10:00:00'],
    [27, 'Đầm maxi dự tiệc chiết eo dáng dài', 'dam-maxi-du-tiec', 699000, null, '<p>Đầm maxi dáng dài thướt tha, đường cắt cúp tôn vòng eo, hợp đi tiệc và du lịch.</p><ul><li>Chất liệu lụa satin mượt mà</li><li>Thiết kế quyến rũ, nổi bật giữa đám đông</li></ul>', 5, 1, 260, '2026-09-05 10:00:00'],
    [28, 'Chân váy chữ A lưng cao tôn dáng', 'chan-vay-chu-a', 289000, null, '<p>Chân váy chữ A cạp cao có quần bảo hộ bên trong, dễ mix cùng sơ mi hoặc áo thun.</p><ul><li>Chất vải kaki đứng form</li><li>Khóa kéo sau ẩn tinh tế</li></ul>', 5, 1, 223, '2026-09-02 10:00:00'],
    [29, 'Chân váy xếp ly dáng dài thanh lịch', 'chan-vay-xep-ly-dang-dai', 379000, 329000, '<p>Chân váy xếp ly ly nhỏ ép nhiệt vĩnh viễn, không bung nếp sau nhiều lần giặt.</p><ul><li>Chất liệu voan cát 2 lớp dày dặn</li><li>Dễ phối đồ theo phong cách vintage Hàn Quốc</li></ul>', 5, 1, 147, '2026-09-08 10:00:00'],
    [30, 'Chân váy tennis xếp ly ngắn năng động', 'chan-vay-tennis-xep-ly-ngan', 299000, 259000, '<p>Chân váy tennis phong cách Y2K trẻ trung, tích hợp quần lót an toàn bên trong.</p><ul><li>Form chữ A xòe nhẹ năng động</li><li>Vải tuyết mưa đứng dáng, cạp bản to thon eo</li></ul>', 5, 1, 192, '2026-09-10 10:00:00']
];

$images = [
    // SP 1: Áo thun basic cotton
    [1, 1, 'uploads/products/Ao-thun-basic-co-tron-den-mat-truoc.jpg', 1, 1],
    [2, 1, 'uploads/products/Ao-thun-basic-co-tron-den-mat-sau.jpg', 0, 2],
    [3, 1, 'uploads/products/ao-thun-basic-cotton-co-tron-1.jpg', 0, 3],

    // SP 2: Áo thun oversize
    [4, 2, 'uploads/products/Ao-thun-oversize-basic-mat-truoc.jpg', 1, 1],
    [5, 2, 'uploads/products/Ao-thun-oversize-basic-mat-sau.webp', 0, 2],
    [6, 2, 'uploads/products/ao-thun-oversize-form-rong-1.jpg', 0, 3],

    // SP 3: Áo thun in graphic
    [7, 3, 'uploads/products/Ao-thun-in-logo-mat-truoc.jpg', 1, 1],
    [8, 3, 'uploads/products/Ao-thun-in-logo-mat-sau.webp', 0, 2],
    [9, 3, 'uploads/products/ao-thun-in-hinh-graphic-1.jpg', 0, 3],

    // SP 4: Áo thun tay dài
    [10, 4, 'uploads/products/ao-thun-tay-dai-thu-dong-1.jpg', 1, 1],
    [11, 4, 'uploads/products/ao-thun-tay-dai-thu-dong-2.jpg', 0, 2],
    [12, 4, 'uploads/products/ao-thun-tay-dai-thu-dong-3.jpg', 0, 3],

    // SP 5: Croptop ôm body
    [13, 5, 'uploads/products/Ao-croptop-mat-truoc.jpg', 1, 1],
    [14, 5, 'uploads/products/Ao-croptop-mat-sau.webp', 0, 2],
    [15, 5, 'uploads/products/ao-croptop-nu-om-body-1.jpg', 0, 3],

    // SP 6: Croptop cổ vuông
    [16, 6, 'uploads/products/ao-croptop-nu-om-body-2.jpg', 1, 1],
    [17, 6, 'uploads/products/ao-croptop-nu-om-body-3.jpg', 0, 2],

    // SP 7: Áo polo nam cá sấu
    [18, 7, 'uploads/products/Ao-polo-tron-mat-truoc.jpg', 1, 1],
    [19, 7, 'uploads/products/Ao-polo-tron-mat-sau.webp', 0, 2],
    [20, 7, 'uploads/products/ao-polo-nam-vai-ca-sau-1.jpg', 0, 3],

    // SP 8: Áo polo cổ bẻ thể thao
    [21, 8, 'uploads/products/Ao-polo-co-tron-mat-truoc.jpg', 1, 1],
    [22, 8, 'uploads/products/ao-polo-nam-vai-ca-sau-2.jpg', 0, 2],
    [23, 8, 'uploads/products/ao-polo-nam-vai-ca-sau-3.jpg', 0, 3],

    // SP 9: Áo sơ mi trắng
    [24, 9, 'uploads/products/ao-so-mi-trang-cong-so-1.jpg', 1, 1],
    [25, 9, 'uploads/products/ao-so-mi-trang-cong-so-2.jpg', 0, 2],
    [26, 9, 'uploads/products/ao-so-mi-trang-cong-so-3.jpg', 0, 3],

    // SP 10: Áo sơ mi kẻ sọc (Đủ 3 màu kẻ sọc: Xanh, Đen, Đỏ đô - Có mặt trước & mặt sau)
    [27, 10, 'uploads/products/ao-so-mi-ke-soc-xanh-1.jpg', 1, 1],
    [28, 10, 'uploads/products/ao-so-mi-ke-soc-xanh-2.jpg', 0, 2],
    [29, 10, 'uploads/products/ao-so-mi-ke-soc-xanh-3.jpg', 0, 3],
    [89, 10, 'uploads/products/ao-so-mi-ke-soc-den-mat-truoc.jpg', 0, 4],
    [90, 10, 'uploads/products/ao-so-mi-ke-soc-den-mat-sau.jpg', 0, 5],
    [91, 10, 'uploads/products/ao-so-mi-ke-soc-do-mat-truoc.jpg', 0, 6],
    [92, 10, 'uploads/products/ao-so-mi-ke-soc-do-mat-sau.jpg', 0, 7],

    // SP 11: Áo sơ mi linen
    [30, 11, 'uploads/products/ao-so-mi-linen-tay-ngan-1.jpg', 1, 1],
    [31, 11, 'uploads/products/ao-so-mi-linen-tay-ngan-2.jpg', 0, 2],
    [32, 11, 'uploads/products/Ao-so-mi-linen-nu-tay-dai-mat-sau.webp', 0, 3],

    // SP 12: Áo sơ mi flannel caro
    [33, 12, 'uploads/products/ao-so-mi-flannel-caro-1.jpg', 1, 1],
    [34, 12, 'uploads/products/ao-so-mi-flannel-caro-2.jpg', 0, 2],
    [35, 12, 'uploads/products/ao-so-mi-flannel-caro-3.jpg', 0, 3],

    // SP 13: Áo khoác denim
    [36, 13, 'uploads/products/Ao-khoac-denim-mat-truoc.jpg', 1, 1],
    [37, 13, 'uploads/products/Ao-khoac-denim-mat-sau.webp', 0, 2],
    [38, 13, 'uploads/products/ao-khoac-jean-unisex-1.jpg', 0, 3],

    // SP 14: Bomber dù 2 lớp
    [39, 14, 'uploads/products/ao-bomber-du-2-lop-1.jpg', 1, 1],
    [40, 14, 'uploads/products/ao-bomber-du-2-lop-2.jpg', 0, 2],
    [41, 14, 'uploads/products/ao-bomber-du-2-lop-3.jpg', 0, 3],

    // SP 15: Bomber da PU
    [42, 15, 'uploads/products/ao-bomber-da-pu-1.jpg', 1, 1],
    [43, 15, 'uploads/products/ao-bomber-da-pu-2.jpg', 0, 2],
    [44, 15, 'uploads/products/ao-bomber-da-pu-3.jpg', 0, 3],

    // SP 16: Áo varsity bóng chày
    [45, 16, 'uploads/products/Ao-khoac-varsity-mat-truoc.jpg', 1, 1],
    [46, 16, 'uploads/products/Ao-khoac-varsity-mat-sau.webp', 0, 2],

    // SP 17: Hoodie nỉ bông
    [47, 17, 'uploads/products/Ao-khoac-hoodie-oversize-mat-truoc.jpg', 1, 1],
    [48, 17, 'uploads/products/Ao-khoac-hoodie-oversize-mat-sau.webp', 0, 2],
    [49, 17, 'uploads/products/hoodie-ni-bong-form-rong-1.jpg', 0, 3],

    // SP 18: Hoodie zip
    [50, 18, 'uploads/products/Ao-khoac-hoodie-zip-mat-truoc.jpg', 1, 1],
    [51, 18, 'uploads/products/Ao-khoac-hoodie-zip-mat-sau.webp', 0, 2],
    [52, 18, 'uploads/products/hoodie-zip-khoa-keo-1.jpg', 0, 3],

    // SP 19: Quần jean slimfit
    [53, 19, 'uploads/products/Quan-jeans-straight-mat-truoc.jpg', 1, 1],
    [54, 19, 'uploads/products/Quan-jeans-straight-mat-sau.webp', 0, 2],
    [55, 19, 'uploads/products/quan-jean-slim-fit-xanh-dam-1.jpg', 0, 3],

    // SP 20: Quần jean ống rộng
    [56, 20, 'uploads/products/Quan-jeans-ong-rong-mat-truoc.jpg', 1, 1],
    [57, 20, 'uploads/products/Quan-jeans-ong-rong-mat-sau.webp', 0, 2],
    [58, 20, 'uploads/products/quan-jean-ong-rong-nu-1.jpg', 0, 3],

    // SP 21: Quần jean rách gối
    [59, 21, 'uploads/products/quan-jean-rach-goi-1.jpg', 1, 1],
    [60, 21, 'uploads/products/quan-jean-rach-goi-2.jpg', 0, 2],
    [61, 21, 'uploads/products/quan-jean-rach-goi-3.jpg', 0, 3],

    // SP 22: Quần jean baggy
    [62, 22, 'uploads/products/quan-jean-baggy-1.jpg', 1, 1],
    [63, 22, 'uploads/products/quan-jean-baggy-2.jpg', 0, 2],
    [64, 22, 'uploads/products/quan-jean-baggy-3.jpg', 0, 3],

    // SP 23: Quần short jean
    [65, 23, 'uploads/products/quan-short-jean-nam-1.jpg', 1, 1],
    [66, 23, 'uploads/products/quan-short-jean-nam-2.jpg', 0, 2],
    [67, 23, 'uploads/products/Quan-short-nu-mat-truoc.jpg', 0, 3],

    // SP 24: Quần short thể thao
    [68, 24, 'uploads/products/Quan-short-the-thao-mat-truoc.jpg', 1, 1],
    [69, 24, 'uploads/products/Quan-short-the-thao-mat-sau.webp', 0, 2],
    [70, 24, 'uploads/products/Quan-short-nu-mat-sau.webp', 0, 3],

    // SP 25: Váy midi hoa nhí
    [71, 25, 'uploads/products/Chan-vay-midi-mat-truoc.jpg', 1, 1],
    [72, 25, 'uploads/products/Chan-vay-midi-mat-sau.webp', 0, 2],
    [73, 25, 'uploads/products/vay-midi-hoa-nhi-1.jpg', 0, 3],

    // SP 26: Đầm suông công sở
    [74, 26, 'uploads/products/dam-suong-cong-so-1.jpg', 1, 1],
    [75, 26, 'uploads/products/dam-suong-cong-so-2.jpg', 0, 2],
    [76, 26, 'uploads/products/dam-suong-cong-so-3.jpg', 0, 3],

    // SP 27: Đầm maxi dự tiệc
    [77, 27, 'uploads/products/dam-maxi-du-tiec-1.jpg', 1, 1],
    [78, 27, 'uploads/products/dam-maxi-du-tiec-2.jpg', 0, 2],
    [79, 27, 'uploads/products/dam-maxi-du-tiec-3.jpg', 0, 3],

    // SP 28: Chân váy chữ A
    [80, 28, 'uploads/products/chan-vay-chu-a-1.jpg', 1, 1],
    [81, 28, 'uploads/products/chan-vay-chu-a-2.jpg', 0, 2],
    [82, 28, 'uploads/products/chan-vay-chu-a-3.jpg', 0, 3],

    // SP 29: Chân váy xếp ly dáng dài
    [83, 29, 'uploads/products/Chan-vay-xep-ly-mat-truoc.jpg', 1, 1],
    [84, 29, 'uploads/products/Chan-vay-xep-li-mat-sau.webp', 0, 2],
    [85, 29, 'uploads/products/vay-xep-ly-1.jpg', 0, 3],

    // SP 30: Chân váy tennis ngắn
    [86, 30, 'uploads/products/vay-xep-ly-2.jpg', 1, 1],
    [87, 30, 'uploads/products/vay-xep-ly-3.jpg', 0, 2],
    [88, 30, 'uploads/products/Quan-kaki-mat-truoc.jpg', 0, 3],
];

$productConfig = [
    // Áo thun & Croptop
    1 => ['colors' => ['Trắng', 'Đen', 'Xám'], 'sizes' => ['S', 'M', 'L']],
    2 => ['colors' => ['Be', 'Xám', 'Đen'], 'sizes' => ['S', 'M', 'L']],
    3 => ['colors' => ['Đen', 'Trắng'], 'sizes' => ['S', 'M', 'L']],
    4 => ['colors' => ['Xám', 'Đen', 'Xanh rêu'], 'sizes' => ['S', 'M', 'L']],
    5 => ['colors' => ['Hồng', 'Đen', 'Trắng'], 'sizes' => ['S', 'M', 'L']],
    6 => ['colors' => ['Trắng', 'Đen'], 'sizes' => ['S', 'M', 'L']],

    // Áo sơ mi & Polo
    7 => ['colors' => ['Xanh navy', 'Trắng', 'Đen'], 'sizes' => ['S', 'M', 'L', 'XL']],
    8 => ['colors' => ['Xám', 'Đen', 'Trắng'], 'sizes' => ['S', 'M', 'L', 'XL']],
    9 => ['colors' => ['Trắng', 'Xanh nhạt'], 'sizes' => ['S', 'M', 'L', 'XL']],
    10 => ['colors' => ['Kẻ sọc xanh', 'Kẻ sọc đen', 'Kẻ sọc đỏ đô'], 'sizes' => ['S', 'M', 'L', 'XL']],
    11 => ['colors' => ['Be', 'Trắng', 'Xanh rêu'], 'sizes' => ['S', 'M', 'L', 'XL']],
    12 => ['colors' => ['Đỏ caro', 'Xanh rêu caro'], 'sizes' => ['S', 'M', 'L', 'XL']],

    // Áo khoác, Bomber & Hoodie
    13 => ['colors' => ['Xanh nhạt', 'Xanh đậm'], 'sizes' => ['S', 'M', 'L', 'XL']],
    14 => ['colors' => ['Đen', 'Xanh rêu'], 'sizes' => ['S', 'M', 'L', 'XL']],
    15 => ['colors' => ['Đen', 'Nâu bò'], 'sizes' => ['S', 'M', 'L', 'XL']],
    16 => ['colors' => ['Đen phối trắng', 'Xanh navy phối trắng'], 'sizes' => ['S', 'M', 'L', 'XL']],
    17 => ['colors' => ['Xám', 'Đen', 'Xanh pastel'], 'sizes' => ['S', 'M', 'L', 'XL']],
    18 => ['colors' => ['Be', 'Xanh navy', 'Xám chì'], 'sizes' => ['S', 'M', 'L', 'XL']],

    // Quần jean & Quần short
    19 => ['colors' => ['Xanh đậm', 'Đen'], 'sizes' => ['29', '30', '31', '32']],
    20 => ['colors' => ['Xanh nhạt', 'Xanh đậm'], 'sizes' => ['29', '30', '31', '32']],
    21 => ['colors' => ['Xanh rách', 'Đen rách'], 'sizes' => ['29', '30', '31', '32']],
    22 => ['colors' => ['Đen', 'Xanh khói'], 'sizes' => ['29', '30', '31', '32']],
    23 => ['colors' => ['Xanh nhạt', 'Đen denim'], 'sizes' => ['29', '30', '31', '32']],
    24 => ['colors' => ['Đen', 'Xám', 'Xanh than'], 'sizes' => ['M', 'L', 'XL']],

    // Váy, Đầm & Chân Váy
    25 => ['colors' => ['Vàng hoa', 'Xanh hoa'], 'sizes' => ['S', 'M', 'L']],
    26 => ['colors' => ['Đen', 'Be cát'], 'sizes' => ['S', 'M', 'L']],
    27 => ['colors' => ['Đỏ rượu', 'Xanh navy', 'Trắng ngọc'], 'sizes' => ['S', 'M', 'L']],
    28 => ['colors' => ['Đen', 'Xám ghi', 'Nâu tây'], 'sizes' => ['S', 'M', 'L']],
    29 => ['colors' => ['Kem be', 'Hồng đất'], 'sizes' => ['S', 'M', 'L']],
    30 => ['colors' => ['Trắng', 'Đen', 'Xám ghi'], 'sizes' => ['S', 'M', 'L']],
];
