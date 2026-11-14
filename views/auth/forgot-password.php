<section class="container section-space auth-section">
    <div class="auth-card">
        <div class="auth-intro"><img class="brand-mark" src="<?= e(PATH_ASSETS . 'images/logo-mark.svg') ?>" alt="" width="38" height="38"><span class="eyebrow">KHÔI PHỤC TÀI KHOẢN</span><h1>Quên mật khẩu?</h1><p>Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết để bạn tạo mật khẩu mới.</p></div>
        <?php if (!empty($developmentResetLink)): ?>
            <div class="alert alert-info">Môi trường phát triển (chưa gửi được email): <a href="<?= e($developmentResetLink) ?>">bấm vào đây để đặt lại mật khẩu</a>.</div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('forgot-password')) ?>">
            <?= csrf_field() ?>
            <div class="mb-3"><label class="form-label" for="forgot-email">Email</label><input class="form-control" id="forgot-email" name="email" type="email" required autocomplete="email" autofocus></div>
            <button class="btn btn-primary w-100" type="submit">Gửi hướng dẫn đặt lại</button>
        </form>
        <p class="auth-switch"><a href="<?= e(url('login')) ?>">Quay lại đăng nhập</a></p>
    </div>
</section>
