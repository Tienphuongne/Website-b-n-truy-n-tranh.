<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Quản trị') ?> | Manga House Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(PATH_CSS) ?>app.css" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a class="brand admin-brand" href="<?= e(url('admin')) ?>"><span class="brand-mark">M</span><span>Manga<span class="brand-accent">House</span></span></a>
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
            <header class="admin-topbar"><div><span class="eyebrow">MANGA HOUSE / ADMIN</span><h1><?= e($title ?? 'Quản trị') ?></h1></div><div class="admin-user"><?= e($_SESSION['user']['name'] ?? 'Quản trị viên') ?></div></header>
            <div class="admin-content">
                <?php foreach (take_flash_messages() as $notice): ?><div class="alert alert-<?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endforeach; ?>
                <?php if (!empty($databaseError)): ?><div class="alert alert-warning"><?= e($databaseError) ?></div><?php endif; ?>
                <?php require PATH_VIEW . $view . '.php'; ?>
            </div>
        </main>
    </div>
</body>
</html>
