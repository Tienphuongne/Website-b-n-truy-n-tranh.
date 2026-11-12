<section class="container section-space auth-section">
    <div class="auth-card">
        <div class="auth-intro"><img class="brand-mark" src="<?= e(PATH_ASSETS . 'images/logo-mark.svg') ?>" alt="" width="38" height="38"><span class="eyebrow">THAM GIA CỘNG ĐỒNG</span><h1>Tạo tài khoản</h1><p>Lưu lại bộ sưu tập và theo dõi đơn hàng của bạn.</p></div>
        <form method="post" action="<?= e(url('register')) ?>">
            <?= csrf_field() ?>
            <div class="mb-3"><label class="form-label" for="full-name">Họ và tên</label><input class="form-control" id="full-name" name="full_name" required autocomplete="name" value="<?= e($_POST['full_name'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label" for="phone">Số điện thoại</label><input class="form-control" id="phone" name="phone" autocomplete="tel" value="<?= e($_POST['phone'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label" for="password">Mật khẩu (ít nhất 8 ký tự)</label><div class="password-field"><input class="form-control" id="password" name="password" type="password" required minlength="8" autocomplete="new-password"><button type="button" class="password-toggle" data-toggle-password="password">Hiện</button></div></div>
            <div class="mb-3"><label class="form-label" for="password-confirm">Xác nhận mật khẩu</label><input class="form-control" id="password-confirm" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div>
            <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="terms" value="1" required><span class="form-check-label">Tôi đồng ý với điều khoản sử dụng.</span></label>
            <button class="btn btn-primary w-100" type="submit">Tạo tài khoản</button>
        </form>
        <p class="auth-switch">Đã có tài khoản? <a href="<?= e(url('login')) ?>">Đăng nhập</a></p>
    </div>
</section>
