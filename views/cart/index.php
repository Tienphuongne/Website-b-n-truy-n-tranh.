<section class="container section-space">
    <div class="page-heading"><div><span class="eyebrow">GIỎ HÀNG CỦA BẠN</span><h1>Giỏ hàng</h1></div></div>
    <?php if (!empty($items)): ?>
        <?php $subtotal = 0; foreach ($items as $item) { $subtotal += (float) $item['sale_price'] * (int) $item['quantity']; } $shipping = 30000; ?>
        <div class="cart-layout">
            <div class="cart-items">
                <?php foreach ($items as $item): ?>
                    <article class="cart-item">
                        <a class="cart-thumb" href="<?= e(url('product/detail/' . (int) $item['id'])) ?>">
                            <?php if (!empty($item['cover_image'])): ?><img src="<?= e(BASE_ASSETS_UPLOADS . ltrim($item['cover_image'], '/\\')) ?>" alt="<?= e($item['name']) ?>">
                            <?php else: ?><span class="cover-placeholder"><span>✦</span></span><?php endif; ?>
                        </a>
                        <div class="cart-item-info">
                            <p class="product-author"><?= e($item['author_name'] ?? 'Đang cập nhật') ?></p>
                            <h2><a href="<?= e(url('product/detail/' . (int) $item['id'])) ?>"><?= e($item['name']) ?></a></h2>
                            <strong><?= number_format((float) $item['sale_price'], 0, ',', '.') ?>₫</strong>
                            <div class="cart-item-controls">
                                <form action="<?= e(url('cart/update')) ?>" method="post" class="quantity-control">
                                    <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                    <label class="visually-hidden" for="qty-<?= (int) $item['id'] ?>">Số lượng</label>
                                    <input id="qty-<?= (int) $item['id'] ?>" type="number" name="quantity" min="1" max="<?= max(1, (int) $item['stock_quantity']) ?>" value="<?= (int) $item['quantity'] ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Cập nhật</button>
                                </form>
                                <form action="<?= e(url('cart/remove')) ?>" method="post" data-confirm="Bạn có chắc muốn xóa truyện này khỏi giỏ hàng?">
                                    <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                    <button class="btn-remove" type="submit">Xóa</button>
                                </form>
                            </div>
                        </div>
                        <strong class="cart-line-total"><?= number_format((float) $item['sale_price'] * (int) $item['quantity'], 0, ',', '.') ?>₫</strong>
                    </article>
                <?php endforeach; ?>
                <a class="text-link" href="<?= e(url('products')) ?>">← Tiếp tục mua sắm</a>
            </div>
            <aside class="summary-card">
                <h2>Tóm tắt đơn hàng</h2>
                <div class="summary-row"><span>Tạm tính</span><strong><?= number_format($subtotal, 0, ',', '.') ?>₫</strong></div>
                <div class="summary-row"><span>Phí vận chuyển</span><strong><?= number_format($shipping, 0, ',', '.') ?>₫</strong></div>
                <div class="summary-row summary-total"><span>Tổng thanh toán</span><strong><?= number_format($subtotal + $shipping, 0, ',', '.') ?>₫</strong></div>
                <a class="btn btn-primary w-100" href="<?= e(url('checkout')) ?>">Tiến hành thanh toán</a>
                <p class="secure-note">Thanh toán an toàn · Hỗ trợ COD</p>
            </aside>
        </div>
    <?php else: ?>
        <?php
        $emptyTitle = 'Giỏ hàng của bạn đang trống';
        $emptyMessage = $databaseError ?: 'Thêm những câu chuyện bạn yêu thích vào giỏ để bắt đầu mua sắm.';
        $emptyLink = 'products';
        $emptyAction = 'Khám phá truyện';
        require PATH_VIEW . 'components/empty-state.php';
        ?>
    <?php endif; ?>
</section>
