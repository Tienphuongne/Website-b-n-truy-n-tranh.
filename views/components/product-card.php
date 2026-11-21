<?php
$isOutOfStock = (int) ($product['stock_quantity'] ?? 0) < 1;
$isWished = in_array((int) ($product['id'] ?? 0), wishlist_ids(), true);
?>
<article class="product-card<?= $isOutOfStock ? ' is-out-of-stock' : '' ?>">
    <?php if (!empty($_SESSION['user'])): ?>
        <form class="wishlist-form" action="<?= e(url('wishlist/toggle')) ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="return" value="<?= e(current_path()) ?>">
            <button class="wishlist-btn<?= $isWished ? ' is-active' : '' ?>" type="submit" aria-label="<?= $isWished ? 'Bỏ khỏi yêu thích' : 'Thêm vào yêu thích' ?>" title="<?= $isWished ? 'Bỏ khỏi yêu thích' : 'Thêm vào yêu thích' ?>"><?= $isWished ? '♥' : '♡' ?></button>
        </form>
    <?php else: ?>
        <a class="wishlist-btn" href="<?= e(url('login')) ?>" aria-label="Đăng nhập để lưu yêu thích" title="Đăng nhập để lưu yêu thích">♡</a>
    <?php endif; ?>
    <a class="product-card-link" href="<?= e(url('product/detail/' . (int) $product['id'])) ?>" aria-label="Xem chi tiết <?= e($product['name']) ?>">
        <div class="product-cover">
            <?php if (!empty($product['cover_image'])): ?>
                <img src="<?= e(product_image_url($product['cover_image'])) ?>" alt="Bìa truyện <?= e($product['name']) ?>" loading="lazy" onerror="showCoverFallback(this)">
            <?php else: ?>
                <span class="cover-placeholder" aria-hidden="true"><span>✦</span><small>TRUYENTHUVI</small></span>
            <?php endif; ?>
            <?php if (!empty($product['is_featured'])): ?><span class="product-badge">Nổi bật</span><?php endif; ?>
            <?php if (!empty($product['original_price']) && $product['original_price'] > $product['sale_price']): ?>
                <span class="product-badge discount-badge">-<?= (int) round((1 - $product['sale_price'] / $product['original_price']) * 100) ?>%</span>
            <?php endif; ?>
            <?php if ($isOutOfStock): ?><span class="product-badge stock-badge">Hết hàng</span><?php endif; ?>
        </div>
        <div class="product-info">
            <p class="product-author"><?= e($product['author_name'] ?? 'Đang cập nhật tác giả') ?></p>
            <div class="product-rating-slot">
                <?php if (!empty($product['review_count'])): ?>
                    <?php $cardRating = (int) round((float) $product['avg_rating']); ?>
                    <div class="product-rating">
                        <span class="rating-stars" aria-hidden="true"><?= str_repeat('★', $cardRating) ?><?= str_repeat('☆', 5 - $cardRating) ?></span>
                        <small>(<?= (int) $product['review_count'] ?>)</small>
                    </div>
                <?php endif; ?>
            </div>
            <h3><?= e($product['name']) ?></h3>
            <div class="product-prices">
                <strong><?= number_format((float) $product['sale_price'], 0, ',', '.') ?>₫</strong>
                <?php if (!empty($product['original_price']) && $product['original_price'] > $product['sale_price']): ?>
                    <del><?= number_format((float) $product['original_price'], 0, ',', '.') ?>₫</del>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <form class="product-card-cart-form" action="<?= e(url('cart/add')) ?>" method="post" data-cart-add-form>
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <input type="hidden" name="quantity" value="1">
        <input type="hidden" name="return_url" value="<?= e($_SERVER['REQUEST_URI'] ?? '/products') ?>">
        <button class="btn btn-add-cart w-100" type="submit" <?= $isOutOfStock ? 'disabled' : '' ?>>
            <?= $isOutOfStock ? 'Hết hàng' : 'Thêm vào giỏ' ?>
        </button>
    </form>
</article>
