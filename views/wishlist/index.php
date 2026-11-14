<section class="container section-space">
    <div class="page-heading"><div><span class="eyebrow">BỘ SƯU TẬP CỦA BẠN</span><h1>Truyện yêu thích</h1></div></div>
    <?php if ($databaseError): ?>
        <div class="muted-panel"><?= e($databaseError) ?></div>
    <?php elseif ($items): ?>
        <div class="product-grid wishlist-grid">
            <?php foreach ($items as $product): ?>
                <div class="wishlist-item">
                    <?php require PATH_VIEW . 'components/product-card.php'; ?>
                    <form class="wishlist-remove" method="post" action="<?= e(url('wishlist/toggle')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <input type="hidden" name="redirect" value="wishlist">
                        <button class="link-button" type="submit">Bỏ khỏi yêu thích</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?php
        $emptyTitle = 'Chưa có truyện yêu thích';
        $emptyMessage = 'Nhấn "Yêu thích" ở trang chi tiết truyện để lưu lại những bộ truyện bạn quan tâm.';
        $emptyLink = 'products';
        $emptyAction = 'Khám phá truyện tranh';
        require PATH_VIEW . 'components/empty-state.php';
        ?>
    <?php endif; ?>
</section>
