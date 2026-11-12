<section class="container section-space">
    <?php if (!empty($orderComplete)): ?>
        <div class="order-success">
            <span class="success-mark">✓</span><span class="eyebrow">ĐẶT HÀNG THÀNH CÔNG</span>
            <h1>Cảm ơn bạn đã đặt hàng!</h1>
            <p>Mã đơn hàng của bạn là <strong>#<?= (int) $orderComplete ?></strong>. Chúng tôi sẽ liên hệ xác nhận sớm nhất.</p>
            <a class="btn btn-primary" href="<?= e(url('products')) ?>">Tiếp tục khám phá</a>
        </div>
    <?php elseif (!empty($items)): ?>
        <?php $subtotal = 0; foreach ($items as $item) { $subtotal += (float) $item['sale_price'] * (int) $item['quantity']; } ?>
        <div class="page-heading"><div><span class="eyebrow">SẮP HOÀN TẤT</span><h1>Thanh toán</h1></div></div>
        <form action="<?= e(url('checkout')) ?>" method="post" class="checkout-layout">
            <?= csrf_field() ?>
            <section class="checkout-card">
                <h2>Thông tin nhận hàng</h2>
                <div class="row g-3">
                    <div class="col-12"><label class="form-label" for="full-name">Họ và tên *</label><input class="form-control" id="full-name" name="full_name" required autocomplete="name" value="<?= e($_SESSION['user']['name'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="phone">Số điện thoại *</label><input class="form-control" id="phone" name="phone" required autocomplete="tel"></div>
                    <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" autocomplete="email" value="<?= e($_SESSION['user']['email'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="city">Tỉnh / thành phố *</label><input class="form-control" id="city" name="city" required autocomplete="address-level1"></div>
                    <div class="col-md-6"><label class="form-label" for="district">Quận / huyện *</label><input class="form-control" id="district" name="district" required autocomplete="address-level2"></div>
                    <div class="col-12"><label class="form-label" for="address">Địa chỉ nhận hàng *</label><input class="form-control" id="address" name="address" required autocomplete="street-address"></div>
                    <div class="col-12"><label class="form-label" for="note">Ghi chú đơn hàng</label><textarea class="form-control" id="note" name="note" rows="3"></textarea></div>
                </div>
                <h2 class="checkout-subheading">Phương thức thanh toán</h2>
                <label class="payment-option"><input type="radio" name="payment_method" value="cod" checked><span><strong>Thanh toán khi nhận hàng (COD)</strong><small>Thanh toán trực tiếp khi nhận được truyện.</small></span></label>
                <label class="payment-option"><input type="radio" name="payment_method" value="bank_transfer"><span><strong>Chuyển khoản ngân hàng</strong><small>Thông tin chuyển khoản sẽ được gửi sau khi xác nhận đơn.</small></span></label>
            </section>
            <aside class="summary-card checkout-summary">
                <h2>Đơn hàng của bạn</h2>
                <?php foreach ($items as $item): ?>
                    <div class="checkout-product"><span><?= e($item['name']) ?> <small>× <?= (int) $item['quantity'] ?></small></span><strong><?= number_format((float) $item['sale_price'] * (int) $item['quantity'], 0, ',', '.') ?>₫</strong></div>
                <?php endforeach; ?>
                <div class="coupon-entry">
                    <label class="form-label" for="coupon">Mã giảm giá</label>
                    <div class="coupon-input-row"><input class="form-control" id="coupon" name="coupon" placeholder="Nhập mã giảm giá" value="<?= e($_SESSION['coupon'] ?? '') ?>"><button class="btn btn-outline-primary" type="submit" name="apply_coupon" value="1" formnovalidate>Áp dụng</button></div>
                    <?php if ($coupon): ?><small class="coupon-applied">Đã áp dụng <?= e($coupon['code']) ?></small><?php endif; ?>
                </div>
                <div class="summary-row"><span>Tạm tính</span><strong><?= number_format($subtotal, 0, ',', '.') ?>₫</strong></div>
                <div class="summary-row"><span>Phí vận chuyển</span><strong>30.000₫</strong></div>
                <?php if ($discount > 0): ?><div class="summary-row"><span>Giảm giá</span><strong>-<?= number_format($discount, 0, ',', '.') ?>₫</strong></div><?php endif; ?>
                <div class="summary-row summary-total"><span>Tổng thanh toán</span><strong><?= number_format($subtotal + 30000 - $discount, 0, ',', '.') ?>₫</strong></div>
                <button class="btn btn-primary w-100" type="submit">Xác nhận đặt hàng</button>
                <p class="secure-note">Bằng việc đặt hàng, bạn đồng ý với chính sách mua hàng.</p>
            </aside>
        </form>
    <?php else: ?>
        <?php
        $emptyTitle = 'Chưa có sản phẩm để thanh toán';
        $emptyMessage = $databaseError ?: 'Hãy thêm truyện vào giỏ hàng trước khi đặt hàng.';
        $emptyLink = 'products';
        $emptyAction = 'Tìm truyện';
        require PATH_VIEW . 'components/empty-state.php';
        ?>
    <?php endif; ?>
</section>
