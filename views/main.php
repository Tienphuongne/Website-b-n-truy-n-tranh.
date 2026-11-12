<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f05a35">
    <title><?= e($title ?? 'Manga House') ?> | Manga House</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(PATH_CSS) ?>app.css" rel="stylesheet">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(PATH_JS) ?>app.js"></script>
</body>
</html>
