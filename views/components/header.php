<?php $cartCount = array_sum(array_map('intval', $_SESSION['cart'] ?? [])); ?>
<header class="site-header">
    <div class="container header-main">
        <a class="brand" href="<?= e(url('')) ?>" aria-label="Truyện Thú Vị - trang chủ">
            <span class="brand-mark">M</span><span>Truyện Thú Vị</span>
        </a>
        <form class="header-search" action="<?= e(url('products')) ?>" method="get" role="search">
            <label class="visually-hidden" for="site-search">Tìm truyện tranh</label>
            <input id="site-search" type="search" name="q" placeholder="Tìm tên truyện, tác giả..." value="<?= e($_GET['q'] ?? '') ?>">
            <button type="submit" aria-label="Tìm kiếm">⌕</button>
        </form>
        <div class="header-actions">
            <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                <a class="account-link" href="<?= e(url('admin')) ?>"><span>Quản trị</span></a>
            <?php endif; ?>
            <?php if (!empty($_SESSION['user'])): ?>
                <a class="account-link" href="<?= e(url('account')) ?>"><span class="header-icon">♙</span><span><?= e($_SESSION['user']['name']) ?></span></a>
            <?php else: ?>
                <a class="account-link" href="<?= e(url('login')) ?>"><span class="header-icon">♙</span><span>Tài khoản</span></a>
            <?php endif; ?>
            <a class="cart-link" href="<?= e(url('cart')) ?>"><span class="header-icon">▱</span><span>Giỏ hàng</span><b class="cart-count"><?= (int) $cartCount ?></b></a>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg site-nav">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Mở menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('')) ?>">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('products')) ?>">Truyện tranh</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('about')) ?>">Giới thiệu</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('contact')) ?>">Liên hệ</a></li>
                </ul>
                <span class="nav-note">Đọc thế giới qua từng trang truyện</span>
            </div>
        </div>
    </nav>
</header>
