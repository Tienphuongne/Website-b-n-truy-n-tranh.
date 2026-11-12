<section class="container section-space">
    <div class="empty-state">
        <div class="empty-icon">404</div>
        <h1><?= e($title ?? 'Không tìm thấy trang') ?></h1>
        <p><?= e($message ?? 'Trang bạn đang tìm không tồn tại.') ?></p>
        <a class="btn btn-primary" href="<?= e(url('')) ?>">Về trang chủ</a>
    </div>
</section>
