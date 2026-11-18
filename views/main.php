<?php
$pageTitle = ($title ?? 'TruyenThuVi') . ' | TruyenThuVi';
$ogImage = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . PATH_ASSETS . 'images/og-image.png';
$ogDescription = 'TruyenThuVi - truyện hay mỗi ngày. Khám phá truyện tranh tuyển chọn, giao hàng toàn quốc.';
$cssVersion = is_file(PATH_ROOT . 'assets/css/app.css') ? (string) filemtime(PATH_ROOT . 'assets/css/app.css') : '1';
$uiCssVersion = is_file(PATH_ROOT . 'assets/css/ui-polish.css') ? (string) filemtime(PATH_ROOT . 'assets/css/ui-polish.css') : '1';
$chatbotCssVersion = is_file(PATH_ROOT . 'assets/css/chatbot.css') ? (string) filemtime(PATH_ROOT . 'assets/css/chatbot.css') : '1';
$jsVersion = is_file(PATH_ROOT . 'assets/js/app.js') ? (string) filemtime(PATH_ROOT . 'assets/js/app.js') : '1';
$chatbotJsVersion = is_file(PATH_ROOT . 'assets/js/chatbot.js') ? (string) filemtime(PATH_ROOT . 'assets/js/chatbot.js') : '1';
$heroJsVersion = is_file(PATH_ROOT . 'assets/js/hero-carousel.js') ? (string) filemtime(PATH_ROOT . 'assets/js/hero-carousel.js') : '1';
$addressJsVersion = is_file(PATH_ROOT . 'assets/js/vietnam-addresses.js') ? (string) filemtime(PATH_ROOT . 'assets/js/vietnam-addresses.js') : '1';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f05a35">
    <title><?= e($pageTitle) ?></title>
    <script>
        function showCoverFallback(img) {
            if (!img || img.dataset.fallbackApplied) {
                return;
            }
            img.dataset.fallbackApplied = '1';
            var placeholder = document.createElement('span');
            placeholder.className = 'cover-placeholder';
            placeholder.setAttribute('aria-hidden', 'true');
            placeholder.innerHTML = '<span>✦</span><small>TRUYENTHUVI</small>';
            img.replaceWith(placeholder);
        }
    </script>
    <meta name="description" content="<?= e($ogDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="TruyenThuVi">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($ogDescription) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($ogDescription) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(PATH_ASSETS . 'images/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= e(PATH_ASSETS . 'images/logo-512.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(PATH_CSS) ?>app.css?v=<?= e($cssVersion) ?>" rel="stylesheet">
    <link href="<?= e(PATH_CSS) ?>ui-polish.css?v=<?= e($uiCssVersion) ?>" rel="stylesheet">
    <link href="<?= e(PATH_CSS) ?>chatbot.css?v=<?= e($chatbotCssVersion) ?>" rel="stylesheet">
</head>
<body>
    <?php require PATH_VIEW . 'components/header.php'; ?>
    <main>
        <div class="container">
            <?php foreach (take_flash_messages() as $notice): ?>
                <div class="alert alert-<?= e($notice['type']) ?> alert-dismissible fade show mt-3" role="alert">
                    <?= e($notice['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
                </div>
            <?php endforeach; ?>
            <?php if (!empty($databaseError)): ?>
                <div class="alert alert-warning mt-3" role="status"><?= e($databaseError) ?></div>
            <?php endif; ?>
        </div>
        <?php require PATH_VIEW . $view . '.php'; ?>
    </main>
    <?php require PATH_VIEW . 'components/footer.php'; ?>
    <?php require PATH_VIEW . 'components/chatbot.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(PATH_JS) ?>app.js?v=<?= e($jsVersion) ?>"></script>
    <script src="<?= e(PATH_JS) ?>chatbot.js?v=<?= e($chatbotJsVersion) ?>"></script>
    <script src="<?= e(PATH_JS) ?>hero-carousel.js?v=<?= e($heroJsVersion) ?>"></script>
</body>
</html>
