</main>

<style>
    .footer-policy-list {
        list-style: none;
        padding: 0;
        margin: 0;
        line-height: 2.3;
        font-size: 14px;
    }

    .footer-policy-list li a {
        color: #aaa;
        text-decoration: none;
        transition: color 0.2s ease, padding-left 0.2s ease;
        display: inline-block;
        cursor: pointer;
    }

    .footer-policy-list li a:hover {
        color: #e60023;
        padding-left: 6px;
    }

    .policy-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.65);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
    }

    .policy-modal-overlay.active {
        display: flex;
    }

    .policy-modal-box {
        background: #ffffff;
        color: #111111;
        width: 100%;
        max-width: 540px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        animation: policyFadeIn 0.2s ease;
    }

    @keyframes policyFadeIn {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .policy-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 22px;
        background: #111111;
        color: #ffffff;
        border-bottom: 3px solid #e60023;
    }

    .policy-modal-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .policy-modal-close {
        background: none;
        border: none;
        color: #ffffff;
        font-size: 20px;
        cursor: pointer;
        line-height: 1;
    }

    .policy-modal-close:hover {
        color: #e60023;
    }

    .policy-modal-body {
        padding: 22px;
        font-size: 14px;
        line-height: 1.65;
        color: #333333;
        max-height: 75vh;
        overflow-y: auto;
    }

    html,
    body {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        overflow-x: hidden !important;
    }

    @media (max-width: 1024px) {

        html body header,
        html body .site-header,
        html body .site-header>div,
        html body .header-inner {
            height: auto !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            padding: 10px 14px !important;
            gap: 10px !important;
            max-width: 100% !important;
        }

        html body .home-main-wrap .product-grid,
        html body .catalog-wrap .product-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 16px !important;
        }

        html body .wf-detail-wrap .wf-top-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 767px) {

        html body header>div,
        html body .site-header .header-inner {
            flex-direction: column !important;
            align-items: center !important;
            height: auto !important;
            padding: 10px 12px !important;
            gap: 8px !important;
            width: 100% !important;
        }

        html body header nav,
        html body .site-header .main-nav,
        html body .site-header .main-nav ul {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            gap: 12px !important;
            width: 100% !important;
            height: auto !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        html body .site-header .nav-link,
        html body .site-header .main-nav a {
            font-size: 13px !important;
            padding: 4px 2px !important;
        }

        html body .site-header .header-actions,
        html body .site-header .header-icons {
            width: 100% !important;
            justify-content: center !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
        }

        html body .site-header .search-box,
        html body .site-header form {
            flex: 1 1 auto !important;
            max-width: 230px !important;
        }

        html body .site-header .search-box input,
        html body .site-header form input {
            width: 100% !important;
            min-width: 0 !important;
        }

        html body .home-main-wrap .hero-banner-img-wrap {
            margin-bottom: 24px !important;
        }

        html body .home-main-wrap .hero-cta-btn {
            font-size: 9.5px !important;
            padding: 6px 11px !important;
            bottom: 8% !important;
            left: 5% !important;
            border-radius: 4px !important;
            gap: 4px !important;
        }

        html body .home-main-wrap .product-grid,
        html body .catalog-wrap .product-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 10px !important;
        }

        html body .home-main-wrap .prod-body,
        html body .catalog-wrap .prod-body {
            padding: 10px 8px !important;
        }

        html body .home-main-wrap .prod-title,
        html body .catalog-wrap .prod-title {
            font-size: 13px !important;
            min-height: 36px !important;
        }

        html body .home-main-wrap .card-action-top,
        html body .catalog-wrap .card-action-top {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 5px !important;
            width: 100% !important;
        }

        html body .home-main-wrap .card-qty-pill,
        html body .catalog-wrap .card-qty-pill {
            width: 68px !important;
            height: 34px !important;
            padding: 0 3px !important;
            flex-shrink: 0 !important;
        }

        html body .home-main-wrap .card-qty-pill button,
        html body .catalog-wrap .card-qty-pill button {
            width: 20px !important;
            height: 20px !important;
            font-size: 13px !important;
        }

        html body .home-main-wrap .card-qty-pill input,
        html body .catalog-wrap .card-qty-pill input {
            width: 22px !important;
            height: 24px !important;
            font-size: 12px !important;
        }

        html body .home-main-wrap .card-btn-addcart,
        html body .catalog-wrap .card-btn-addcart {
            flex: 1 1 auto !important;
            width: auto !important;
            min-width: 0 !important;
            height: 34px !important;
            font-size: 9px !important;
            padding: 0 4px !important;
        }

        html body .home-main-wrap .card-btn-buynow,
        html body .catalog-wrap .card-btn-buynow {
            width: 100% !important;
            height: 34px !important;
            font-size: 11px !important;
        }

        /* 4. Trang Chi tiết sản phẩm: Giữ Số lượng + Thêm giỏ hàng trên 1 dòng */
        html body .wf-detail-wrap {
            margin: 20px auto 40px !important;
            padding: 0 12px !important;
        }

        html body .wf-detail-wrap .wf-top-grid {
            grid-template-columns: 1fr !important;
            padding: 16px !important;
            gap: 24px !important;
        }

        html body .wf-detail-wrap .wf-cart-row {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 10px !important;
        }

        html body .wf-detail-wrap .wf-qty-pill {
            width: 115px !important;
            height: 44px !important;
            flex-shrink: 0 !important;
        }

        html body .wf-detail-wrap .wf-btn-addcart {
            flex: 1 1 auto !important;
            width: auto !important;
            max-width: none !important;
            height: 44px !important;
            font-size: 13px !important;
        }

        html body .wf-detail-wrap .wf-btn-buynow {
            width: 100% !important;
            max-width: 100% !important;
            height: 46px !important;
        }
    }
    
    html body .home-main-wrap img,
    html body .catalog-wrap img {
        object-fit: contain !important;
        transform: none !important;
    }
</style>

<footer class="site-footer"
    style="background:#111; color:#fff; padding:55px 20px 25px; margin-top:60px; border-top:4px solid #e60023;">
    <div class="container footer-grid"
        style="max-width:1240px; margin:0 auto; display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:38px;">
        <div class="footer-col">
            <h3 style="font-size:24px; font-weight:900; margin-bottom:15px; color:#fff; letter-spacing:-0.5px;">
                VIBEZ<span style="color:#e60023;">.</span>CLUB
            </h3>
            <p style="color:#aaa; font-size:14px; line-height:1.6; margin-bottom:15px;">
                Local Brand Unisex Streetwear dành riêng cho thế hệ Gen Z. Định hình phong cách cá tính với chất liệu
                Cotton 250gsm cao cấp và form dáng Boxy/Oversize chuẩn chất đường phố.
            </p>
        </div>

        <div class="footer-col">
            <h4 style="font-size:15px; font-weight:800; text-transform:uppercase; margin-bottom:15px; color:#fff;">Hỗ
                trợ khách hàng</h4>
            <ul class="footer-policy-list">
                <li><a href="javascript:void(0)" onclick="openPolicyModal('return')">Chính sách đổi trả trong 7 ngày</a>
                </li>
                <li><a href="javascript:void(0)" onclick="openPolicyModal('inspect')">Đồng kiểm hàng trước khi thanh
                        toán</a></li>
                <li><a href="javascript:void(0)" onclick="openPolicyModal('privacy')">Chính sách bảo mật thông tin</a>
                </li>
            </ul>
        </div>

        <div class="footer-col">
            <h4 style="font-size:15px; font-weight:800; text-transform:uppercase; margin-bottom:15px; color:#fff;">Thông
                tin liên hệ</h4>
            <p style="color:#aaa; font-size:14px; line-height:2; margin:0;">
                <i class="fa-solid fa-location-dot" style="color:#e60023; width:20px;"></i> Quận Bình Thạnh, TP. Hồ Chí
                Minh<br>
                <i class="fa-solid fa-phone" style="color:#e60023; width:20px;"></i> 0909 123 456<br>
                <i class="fa-solid fa-envelope" style="color:#e60023; width:20px;"></i> support@vibezclub.vn
            </p>
        </div>
    </div>

    <div class="footer-bottom"
        style="max-width:1240px; margin:35px auto 0; padding-top:20px; border-top:1px solid #222; text-align:center; color:#777; font-size:13px;">
        © <?= date('Y') ?> VIBEZ.CLUB GenZ Streetwear. Thiết kế giao diện bởi Thành viên 4 (Anh Thư).
    </div>
</footer>

<div id="policyModal" class="policy-modal-overlay" onclick="if(event.target === this) closePolicyModal()">
    <div class="policy-modal-box">
        <div class="policy-modal-header">
            <h3 id="policyModalTitle">Chính sách hỗ trợ</h3>
            <button type="button" class="policy-modal-close" onclick="closePolicyModal()">&times;</button>
        </div>
        <div id="policyModalBody" class="policy-modal-body"></div>
    </div>
</div>

<script>
    var policyContents = {
        return: {
            title: 'Chính sách đổi trả trong 7 ngày',
            body: '<p><strong>1. Điều kiện áp dụng:</strong></p>' +
                '<ul>' +
                '<li>Sản phẩm còn nguyên tem mác, chưa qua giặt ủi và không bị bám bẩn.</li>' +
                '<li>Hỗ trợ đổi size hoặc đổi mẫu trong vòng <strong>07 ngày</strong> kể từ ngày nhận hàng.</li>' +
                '</ul>' +
                '<p><strong>2. Chi phí vận chuyển:</strong></p>' +
                '<ul>' +
                '<li>Miễn phí 100% phí ship 2 chiều nếu sản phẩm có lỗi từ nhà sản xuất hoặc giao nhầm mẫu.</li>' +
                '</ul>'
        },
        inspect: {
            title: 'Đồng kiểm hàng trước khi thanh toán',
            body: '<p>Nhằm đảm bảo quyền lợi tối đa cho khách hàng mua sắm online tại <strong>VIBEZ.CLUB</strong>:</p>' +
                '<ul>' +
                '<li>Khách hàng được quyền <strong>mở gói hàng kiểm tra mẫu mã, màu sắc, kích cỡ và chất liệu</strong> cùng bưu tá trước khi thanh toán.</li>' +
                '<li>Nếu sản phẩm không đúng đơn đặt hàng, bạn có quyền từ chối nhận hàng và không mất bất kỳ chi phí nào.</li>' +
                '</ul>'
        },
        privacy: {
            title: 'Chính sách bảo mật thông tin',
            body: '<p><strong>VIBEZ.CLUB</strong> cam kết bảo mật tuyệt đối thông tin cá nhân của khách hàng:</p>' +
                '<ul>' +
                '<li>Thông tin Họ tên, Số điện thoại và Địa chỉ giao hàng chỉ được sử dụng duy nhất cho mục đích xử lý đơn hàng và hỗ trợ sau bán hàng.</li>' +
                '<li>Cam kết không chia sẻ hoặc cung cấp dữ liệu khách hàng cho bất kỳ bên thứ ba nào khác.</li>' +
                '</ul>'
        }
    };

    function openPolicyModal(type) {
        var data = policyContents[type];
        if (!data) return;
        document.getElementById('policyModalTitle').textContent = data.title;
        document.getElementById('policyModalBody').innerHTML = data.body;
        document.getElementById('policyModal').classList.add('active');
    }

    function closePolicyModal() {
        document.getElementById('policyModal').classList.remove('active');
    }
</script>

</body>

</html>