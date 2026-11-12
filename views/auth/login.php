<section class="container section-space auth-section">
    <div class="auth-card">
        <div class="auth-intro"><span class="brand-mark">M</span><span class="eyebrow">CHÀO MỪNG TRỞ LẠI</span><h1>Đăng nhập</h1><p>Tiếp tục hành trình khám phá những câu chuyện mới.</p></div>
        <form method="post" action="<?= e(url('login')) ?>">
            <?= csrf_field() ?>
            <div class="mb-3"><label class="form-label" for="login">Email hoặc tên đăng nhập</label><input class="form-control" id="login" name="login" required autocomplete="username" value="<?= e($_POST['login'] ?? '') ?>"></div>
            <div class="mb-2"><label class="form-label" for="password">Mật khẩu</label><div class="password-field"><input class="form-control" id="password" name="password" type="password" required autocomplete="current-password"><button type="button" class="password-toggle" data-toggle-password="password">Hiện</button></div></div>
            <div class="auth-options"><label class="form-check"><input class="form-check-input" type="checkbox" name="remember" value="1"><span class="form-check-label">Ghi nhớ đăng nhập</span></label><a href="<?= e(url('contact')) ?>">Quên mật khẩu?</a></div>
            <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
        </form>
        <p class="auth-switch">Chưa có tài khoản? <a href="<?= e(url('register')) ?>">Đăng ký ngay</a></p>
    </div>
</section>
