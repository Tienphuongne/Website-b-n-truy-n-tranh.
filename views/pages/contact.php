<section class="container section-space">
    <div class="page-heading"><div><span class="eyebrow">CHÚNG TÔI LUÔN SẴN SÀNG LẮNG NGHE</span><h1>Liên hệ</h1><p>Cần hỗ trợ? Gửi lời nhắn cho TruyenThuVi.</p></div></div>
    <div class="contact-layout">
        <div class="contact-info">
            <div class="contact-info-card"><span>⌖</span><div><h2>Địa chỉ</h2><p>Thông tin cửa hàng sẽ được cập nhật.</p></div></div>
            <div class="contact-info-card"><span>☎</span><div><h2>Điện thoại</h2><p>1900 0000</p></div></div>
            <div class="contact-info-card"><span>✉</span><div><h2>Email</h2><p>hello@truyenthuvi.vn</p></div></div>
            <p class="small text-muted">Thông tin liên hệ hiện là nội dung mẫu của dự án học tập; hãy cập nhật trước khi đưa website vào sử dụng thực tế.</p>
        </div>
        <form class="contact-form" method="post" action="<?= e(url('contact')) ?>">
            <?= csrf_field() ?><h2>Gửi lời nhắn</h2>
            <label class="form-label" for="contact-name">Họ và tên *</label><input class="form-control mb-3" id="contact-name" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
            <label class="form-label" for="contact-email">Email *</label><input class="form-control mb-3" id="contact-email" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>">
            <label class="form-label" for="contact-phone">Số điện thoại</label><input class="form-control mb-3" id="contact-phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
            <label class="form-label" for="contact-message">Nội dung *</label><textarea class="form-control mb-3" id="contact-message" name="message" rows="5" required><?= e($_POST['message'] ?? '') ?></textarea>
            <button class="btn btn-primary" type="submit">Gửi liên hệ</button>
        </form>
    </div>
</section>
