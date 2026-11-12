<section class="container section-space">
    <?php if ($product): ?>
        <nav class="breadcrumb-line"><a href="<?= e(url('')) ?>">Trang chủ</a><span>/</span><a href="<?= e(url('products')) ?>">Truyện tranh</a><span>/</span><span><?= e($product['name']) ?></span></nav>
        <div class="detail-layout">
            <div class="detail-cover">
                <?php if (!empty($product['cover_image'])): ?>
                    <img src="<?= e(product_image_url($product['cover_image'])) ?>" alt="Bìa truyện <?= e($product['name']) ?>">
                <?php else: ?>
                    <div class="cover-placeholder detail-placeholder"><span>✦</span><small>TRUYENTHUVI</small></div>
                <?php endif; ?>
            </div>
            <div class="detail-info">
                <span class="eyebrow"><?= e($product['category_name'] ?? 'TRUYỆN TRANH') ?></span>
                <h1><?= e($product['name']) ?></h1>
                <p class="detail-byline">Tác giả: <strong><?= e($product['author_name'] ?? 'Đang cập nhật') ?></strong></p>
                <div class="detail-price"><strong><?= number_format((float) $product['sale_price'], 0, ',', '.') ?>₫</strong>
                    <?php if (!empty($product['original_price']) && $product['original_price'] > $product['sale_price']): ?><del><?= number_format((float) $product['original_price'], 0, ',', '.') ?>₫</del><?php endif; ?>
                </div>
                <p class="stock-status <?= (int) $product['stock_quantity'] > 0 ? 'in-stock' : 'out-stock' ?>">
                    <?= (int) $product['stock_quantity'] > 0 ? '● Còn hàng (' . (int) $product['stock_quantity'] . ' sản phẩm)' : '● Tạm hết hàng' ?>
                </p>
                <p class="detail-description"><?= nl2br(e($product['short_description'] ?? 'Thông tin nội dung truyện đang được cập nhật.')) ?></p>
                <div class="detail-facts">
                    <div><span>Nhà xuất bản</span><strong><?= e($product['publisher_name'] ?? 'Đang cập nhật') ?></strong></div>
                    <div><span>Thể loại</span><strong><?= e($genres ? implode(', ', $genres) : 'Đang cập nhật') ?></strong></div>
                    <div><span>Mã truyện</span><strong>#<?= (int) $product['id'] ?></strong></div>
                </div>
                <form action="<?= e(url('cart/add')) ?>" method="post" class="detail-actions">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <label class="visually-hidden" for="quantity">Số lượng</label>
                    <input class="form-control quantity-input" id="quantity" type="number" name="quantity" value="1" min="1" max="<?= max(1, (int) $product['stock_quantity']) ?>" <?= (int) $product['stock_quantity'] < 1 ? 'disabled' : '' ?>>
                    <button class="btn btn-primary" type="submit" <?= (int) $product['stock_quantity'] < 1 ? 'disabled' : '' ?>>Thêm vào giỏ hàng</button>
                    <button class="btn btn-outline-primary" type="submit" name="buy_now" value="1" <?= (int) $product['stock_quantity'] < 1 ? 'disabled' : '' ?>>Mua ngay</button>
                </form>
            </div>
        </div>
        <section class="detail-description-panel">
            <h2>Mô tả chi tiết</h2>
            <p><?= nl2br(e($product['description'] ?? $product['short_description'] ?? 'Thông tin chi tiết đang được cập nhật.')) ?></p>
        </section>
        <?php if (!empty($images)): ?>
            <section class="product-gallery">
                <?php foreach ($images as $image): ?><img src="<?= e(product_image_url($image)) ?>" alt="Ảnh truyện <?= e($product['name']) ?>" loading="lazy"><?php endforeach; ?>
            </section>
        <?php endif; ?>
        <section class="reviews-section">
            <div class="section-heading"><div><span class="eyebrow">CỘNG ĐỒNG ĐỘC GIẢ</span><h2>Đánh giá và nhận xét</h2></div></div>
            <?php if ($reviews): ?>
                <div class="review-list"><?php foreach ($reviews as $review): ?><article class="review-item"><div class="review-heading"><strong><?= e($review['full_name']) ?></strong><span><?= str_repeat('★', (int) $review['rating']) ?><?= str_repeat('☆', 5 - (int) $review['rating']) ?></span></div><p><?= nl2br(e($review['content'] ?? '')) ?></p><small><?= e($review['created_at']) ?></small></article><?php endforeach; ?></div>
            <?php else: ?><div class="muted-panel">Chưa có đánh giá được duyệt cho truyện này.</div><?php endif; ?>
            <?php if (!empty($_SESSION['user'])): ?>
                <form class="review-form" action="<?= e(url('product/review')) ?>" method="post">
                    <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <h3>Chia sẻ cảm nhận của bạn</h3>
                    <label class="form-label" for="review-rating">Đánh giá</label><select class="form-select mb-3" id="review-rating" name="rating"><option value="5">5 sao</option><option value="4">4 sao</option><option value="3">3 sao</option><option value="2">2 sao</option><option value="1">1 sao</option></select>
                    <label class="form-label" for="review-content">Nhận xét</label><textarea class="form-control mb-3" id="review-content" name="content" rows="3" maxlength="3000"></textarea>
                    <button class="btn btn-primary" type="submit">Gửi đánh giá</button>
                </form>
            <?php else: ?><p class="review-login-prompt"><a href="<?= e(url('login')) ?>">Đăng nhập</a> để gửi đánh giá.</p><?php endif; ?>
        </section>
        <?php if ($related): ?>
            <section class="related-section">
                <div class="section-heading"><div><span class="eyebrow">CÓ THỂ BẠN SẼ THÍCH</span><h2>Truyện cùng danh mục</h2></div></div>
                <div class="product-grid"><?php foreach ($related as $product): require PATH_VIEW . 'components/product-card.php'; endforeach; ?></div>
            </section>
        <?php endif; ?>
    <?php else: ?>
        <?php
        $emptyTitle = 'Không tìm thấy truyện';
        $emptyMessage = $databaseError ?: 'Sản phẩm không tồn tại hoặc hiện không được mở bán.';
        $emptyLink = 'products';
        $emptyAction = 'Xem danh sách truyện';
        require PATH_VIEW . 'components/empty-state.php';
        ?>
    <?php endif; ?>
</section>
