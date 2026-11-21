<section class="container section-space auth-section">
    <div class="auth-card">
        <div class="auth-intro"><img class="brand-mark" src="<?= e(PATH_ASSETS . 'images/logo-mark.svg') ?>" alt="" width="38" height="38"><span class="eyebrow">MẬT KHẨU MỚI</span><h1>Đặt lại mật khẩu</h1><p>Chọn mật khẩu mới có ít nhất 8 ký tự cho tài khoản <strong><?= e($record['email'] ?? '') ?></strong>.</p></div>
        <form method="post" action="<?= e(url('reset-password?token=' . urlencode($token))) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="mb-3"><label class="form-label" for="new-password">Mật khẩu mới</label><div class="password-field"><input class="form-control" id="new-password" name="password" type="password" required minlength="8" autocomplete="new-password"><button type="button" class="password-toggle" data-toggle-password="new-password">Hiện</button></div></div>
            <div class="mb-3"><label class="form-label" for="confirm-password">Xác nhận mật khẩu mới</label><div class="password-field"><input class="form-control" id="confirm-password" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"><button type="button" class="password-toggle" data-toggle-password="confirm-password">Hiện</button></div></div>
            <button class="btn btn-primary w-100" type="submit">Cập nhật mật khẩu</button>
        </form>
        <p class="auth-switch"><a href="<?= e(url('login')) ?>">← Quay lại đăng nhập</a></p>
    </div>
</section>
