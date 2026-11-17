<?php
$cssVersion = is_file(PATH_ROOT . 'assets/css/app.css') ? (string) filemtime(PATH_ROOT . 'assets/css/app.css') : '1';
$uiCssVersion = is_file(PATH_ROOT . 'assets/css/ui-polish.css') ? (string) filemtime(PATH_ROOT . 'assets/css/ui-polish.css') : '1';
$jsVersion = is_file(PATH_ROOT . 'assets/js/app.js') ? (string) filemtime(PATH_ROOT . 'assets/js/app.js') : '1';
$themeJsVersion = is_file(PATH_ROOT . 'assets/js/theme.js') ? (string) filemtime(PATH_ROOT . 'assets/js/theme.js') : '1';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f05a35">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('ttv-theme');
                var dark = saved ? saved === 'dark' : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.dataset.theme = dark ? 'dark' : 'light';
            } catch (error) {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>
    <title><?= e($title ?? 'Quản trị') ?> | TruyenThuVi Admin</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(PATH_ASSETS . 'images/favicon.svg') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(PATH_CSS) ?>app.css?v=<?= e($cssVersion) ?>" rel="stylesheet">
    <link href="<?= e(PATH_CSS) ?>ui-polish.css?v=<?= e($uiCssVersion) ?>" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a class="brand admin-brand" href="<?= e(url('admin')) ?>"><img class="brand-mark" src="<?= e(PATH_ASSETS . 'images/logo-mark.svg') ?>" alt="" width="38" height="38"><span>Truyen<span class="brand-accent">ThuVi</span></span></a>
            <p class="admin-label">QUẢN TRỊ CỬA HÀNG</p>
            <nav class="admin-nav">
                <a class="<?= !isset($_GET['resource']) ? 'active' : '' ?>" href="<?= e(url('admin')) ?>">▦ <span>Tổng quan</span></a>
                <?php foreach (AdminModel::resources() as $item): ?>
                    <a class="<?= ($_GET['resource'] ?? '') === $item['key'] ? 'active' : '' ?>" href="<?= e(url('admin/' . $item['key'])) ?>">◦ <span><?= e($item['title']) ?></span></a>
                <?php endforeach; ?>
            </nav>
            <a class="admin-store-link" href="<?= e(url('')) ?>">← Về cửa hàng</a>
        </aside>
        <main class="admin-main">
            <header class="admin-topbar d-flex justify-content-between align-items-center gap-3 mb-4 flex-wrap"><div><span class="eyebrow">TRUYENTHUVI / ADMIN</span><h1><?= e($title ?? 'Quản trị') ?></h1></div><div class="admin-topbar-actions d-flex align-items-center gap-3"><button class="theme-toggle" type="button" data-theme-toggle aria-label="Chuyển chế độ tối" title="Chế độ tối" aria-pressed="false"><span class="theme-toggle-icon" aria-hidden="true">☾</span></button><a class="btn btn-outline-primary btn-sm" href="<?= e(url('')) ?>">Xem cửa hàng</a><div class="admin-user"><?= e($_SESSION['user']['name'] ?? 'Quản trị viên') ?></div></div></header>
            <div class="admin-content">
                <?php foreach (take_flash_messages() as $notice): ?><div class="alert alert-<?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endforeach; ?>
                <?php if (!empty($databaseError)): ?><div class="alert alert-warning"><?= e($databaseError) ?></div><?php endif; ?>
                <?php require PATH_VIEW . $view . '.php'; ?>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(PATH_JS) ?>app.js?v=<?= e($jsVersion) ?>"></script>
    <script src="<?= e(PATH_JS) ?>theme.js?v=<?= e($themeJsVersion) ?>"></script>
</body>
</html>
