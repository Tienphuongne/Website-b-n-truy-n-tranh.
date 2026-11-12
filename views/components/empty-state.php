<div class="empty-state">
    <div class="empty-icon" aria-hidden="true">⌕</div>
    <h2><?= e($emptyTitle ?? 'Chưa có dữ liệu') ?></h2>
    <p><?= e($emptyMessage ?? 'Nội dung sẽ được cập nhật sớm.') ?></p>
    <?php if (!empty($emptyLink)): ?><a class="btn btn-primary" href="<?= e(url($emptyLink)) ?>"><?= e($emptyAction ?? 'Khám phá cửa hàng') ?></a><?php endif; ?>
</div>
