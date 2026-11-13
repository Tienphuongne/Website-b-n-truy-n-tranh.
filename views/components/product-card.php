<article class="product-card">
    <a class="product-cover" href="<?= e(url('product/detail/' . (int) $product['id'])) ?>">
        <?php if (!empty($product['cover_image'])): ?>
            <img src="<?= e(BASE_ASSETS_UPLOADS . ltrim($product['cover_image'], '/\\')) ?>" alt="Bìa truyện <?= e($product['name']) ?>" loading="lazy">
        <?php else: ?>
            <span class="cover-placeholder"><span>✦</span><small>MANGA HOUSE</small></span>
        <?php endif; ?>
        <?php if (!empty($product['is_featured'])): ?><span class="product-badge">Nổi bật</span><?php endif; ?>
        <?php if (!empty($product['original_price']) && $product['original_price'] > $product['sale_price']): ?>
            <span class="product-badge discount-badge">-<?= (int) round((1 - $product['sale_price'] / $product['original_price']) * 100) ?>%</span>
        <?php endif; ?>
    </a>
    <div class="product-info">
        <p class="product-author"><?= e($product['author_name'] ?? 'Đang cập nhật tác giả') ?></p>
        <?php if (!empty($product['review_count'])): ?>
            <?php $cardRating = (int) round((float) $product['avg_rating']); ?>
            <div class="product-rating">
                <span class="rating-stars" aria-hidden="true"><?= str_repeat('★', $cardRating) ?><?= str_repeat('☆', 5 - $cardRating) ?></span>
                <small>(<?= (int) $product['review_count'] ?>)</small>
            </div>
        <?php endif; ?>
        <h3><a href="<?= e(url('product/detail/' . (int) $product['id'])) ?>"><?= e($product['name']) ?></a></h3>
        <div class="product-prices">
            <strong><?= number_format((float) $product['sale_price'], 0, ',', '.') ?>₫</strong>
            <?php if (!empty($product['original_price']) && $product['original_price'] > $product['sale_price']): ?>
                <del><?= number_format((float) $product['original_price'], 0, ',', '.') ?>₫</del>
            <?php endif; ?>
        </div>
        <form action="<?= e(url('cart/add')) ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="quantity" value="1">
            <button class="btn btn-add-cart w-100" type="submit" <?= (int) $product['stock_quantity'] < 1 ? 'disabled' : '' ?>>
                <?= (int) $product['stock_quantity'] < 1 ? 'Hết hàng' : 'Thêm vào giỏ' ?>
            </button>
        </form>
    </div>
</article>
