<section class="container section-space auth-section">
    <div class="auth-card">
        <div class="auth-intro"><img class="brand-mark" src="<?= e(PATH_ASSETS . 'images/logo-mark.svg') ?>" alt="" width="38" height="38"><span class="eyebrow">TẠO MẬT KHẨU MỚI</span><h1>Đặt lại mật khẩu</h1><p>Tài khoản: <?= e($reset['email']) ?>. Liên kết này chỉ dùng được một lần.</p></div>
        <form method="post" action="<?= e(url('reset-password?token=' . urlencode($token))) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="mb-3"><label class="form-label" for="reset-password">Mật khẩu mới</label><div class="password-field"><input class="form-control" id="reset-password" name="password" type="password" required minlength="8" autocomplete="new-password"><button type="button" class="password-toggle" data-toggle-password="reset-password">Hiện</button></div></div>
            <div class="mb-3"><label class="form-label" for="reset-password-confirm">Xác nhận mật khẩu mới</label><div class="password-field"><input class="form-control" id="reset-password-confirm" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"><button type="button" class="password-toggle" data-toggle-password="reset-password-confirm">Hiện</button></div></div>
            <button class="btn btn-primary w-100" type="submit">Lưu mật khẩu mới</button>
        </form>
        <p class="auth-switch"><a href="<?= e(url('login')) ?>">Quay lại đăng nhập</a></p>
    </div>
</section>
