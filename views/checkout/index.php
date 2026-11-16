<?php
$subtotal = 0;
foreach ($items as $item) { $subtotal += (float) $item['sale_price'] * (int) $item['quantity']; }
$freeRemaining = free_shipping_remaining($subtotal);
$deliveryStandard = delivery_window('standard');
$deliveryExpress = delivery_window('express');
$paymentMethod = request_string($_POST, 'payment_method', 'cod');
if (!in_array($paymentMethod, ['cod', 'bank_transfer'], true)) { $paymentMethod = 'cod'; }
$isNewAddress = $selectedAddressId === 0 && empty($form['address']) && empty($savedAddresses);
?>
<section class="container section-space">
    <?php if (!empty($orderComplete)): ?>
        <div class="order-success">
            <span class="success-mark">✓</span><span class="eyebrow">ĐẶT HÀNG THÀNH CÔNG</span>
            <h1>Cảm ơn bạn đã đặt hàng!</h1>
            <p>Mã đơn hàng của bạn là <strong>#<?= (int) $orderComplete ?></strong>. Chúng tôi sẽ liên hệ xác nhận và giao hàng trong thời gian sớm nhất.</p>
            <div class="order-success-actions">
                <a class="btn btn-primary" href="<?= e(url('products')) ?>">Tiếp tục khám phá</a>
                <?php if (!empty($_SESSION['user'])): ?><a class="btn btn-outline-primary" href="<?= e(url('account?tab=orders&order=' . (int) $orderComplete)) ?>">Theo dõi đơn hàng</a><?php endif; ?>
            </div>
        </div>
    <?php elseif (!empty($items)): ?>
        <div class="page-heading checkout-heading">
            <div><span class="eyebrow">SẮP HOÀN TẤT</span><h1>Thanh toán</h1></div>
            <ol class="checkout-steps" aria-label="Tiến trình">
                <li class="done">Giỏ hàng</li>
                <li class="active">Thông tin nhận hàng</li>
                <li>Xác nhận</li>
            </ol>
        </div>

        <?php if ($freeRemaining > 0): ?>
            <div class="shipping-hint" role="status">Mua thêm <strong><?= number_format($freeRemaining, 0, ',', '.') ?>₫</strong> để được <strong>miễn phí giao tiêu chuẩn</strong>.</div>
        <?php else: ?>
            <div class="shipping-hint is-free" role="status">Đơn hàng của bạn đủ điều kiện <strong>miễn phí giao tiêu chuẩn</strong>.</div>
        <?php endif; ?>

        <form action="<?= e(url('checkout')) ?>" method="post" class="checkout-layout" data-checkout
              data-subtotal="<?= e((string) $subtotal) ?>" data-discount="<?= e((string) $discount) ?>"
              data-free-threshold="<?= e((string) SHIPPING_FREE_THRESHOLD) ?>"
              data-fee-standard="<?= e((string) SHIPPING_STANDARD_FEE) ?>" data-fee-express="<?= e((string) SHIPPING_EXPRESS_FEE) ?>"
              data-eta-standard="<?= e('Dự kiến ' . $deliveryStandard) ?>" data-eta-express="<?= e('Dự kiến ' . $deliveryExpress) ?>">
            <?= csrf_field() ?>
            <section class="checkout-card">
                <div class="checkout-card-head"><h2>Thông tin nhận hàng</h2><span class="checkout-secure">Bảo mật thông tin</span></div>

                <?php if (!empty($savedAddresses)): ?>
                    <div class="saved-address-list" aria-label="Địa chỉ đã lưu">
                        <?php foreach ($savedAddresses as $addr): $addrId = (int) $addr['id']; $checked = $selectedAddressId === $addrId; ?>
                            <label class="saved-address <?= $checked ? 'is-selected' : '' ?>">
                                <input type="radio" name="saved_address_id" value="<?= $addrId ?>" <?= $checked ? 'checked' : '' ?> data-saved-address="<?= e(json_encode([
                                    'full_name' => $addr['full_name'],
                                    'phone' => $addr['phone'],
                                    'city' => $addr['city'],
                                    'district' => $addr['district'],
                                    'ward' => $addr['ward'] ?? '',
                                    'address' => $addr['address'],
                                ], JSON_UNESCAPED_UNICODE)) ?>">
                                <span class="saved-address-body">
                                    <span class="saved-address-head"><strong><?= e($addr['label'] ?: 'Địa chỉ đã lưu') ?></strong><?php if (!empty($addr['is_default'])): ?><span class="saved-address-badge">Mặc định</span><?php endif; ?></span>
                                    <span class="saved-address-line"><strong><?= e($addr['full_name']) ?></strong> · <?= e($addr['phone']) ?></span>
                                    <span class="saved-address-line"><?= e(full_address_line($addr)) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                        <label class="saved-address saved-address-new <?= $isNewAddress || $selectedAddressId === 0 ? 'is-selected' : '' ?>">
                            <input type="radio" name="saved_address_id" value="" <?= ($isNewAddress || $selectedAddressId === 0) ? 'checked' : '' ?> data-new-address>
                            <span class="saved-address-body"><span class="saved-address-head"><strong>Nhập địa chỉ mới</strong></span><span class="saved-address-line">Điền thông tin nhận hàng khác bên dưới.</span></span>
                        </label>
                    </div>
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-12"><label class="form-label" for="full-name">Họ và tên người nhận *</label><input class="form-control" id="full-name" name="full_name" required autocomplete="name" value="<?= e($form['full_name']) ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="phone">Số điện thoại *</label><input class="form-control" id="phone" name="phone" required autocomplete="tel" inputmode="tel" pattern="0[0-9]{9,10}" placeholder="0xxxxxxxxx" data-phone-input value="<?= e($form['phone']) ?>"><div class="form-text">10 số, bắt đầu bằng 0 (hỗ trợ tự chuẩn hóa +84).</div></div>
                    <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" autocomplete="email" value="<?= e($form['email']) ?>"></div>
                    <div class="col-md-6">
                        <label class="form-label" for="city">Tỉnh / Thành phố *</label>
                        <select class="form-select" id="city" name="city" required autocomplete="address-level1">
                            <option value="">-- Chọn tỉnh/thành --</option>
                            <?php foreach ($provinceGroups as $group => $provinces): ?>
                                <optgroup label="<?= e($group) ?>">
                                    <?php foreach ($provinces as $province): ?>
                                        <option value="<?= e($province) ?>" <?= $form['city'] === $province ? 'selected' : '' ?>><?= e($province) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                            <?php if ($form['city'] !== '' && !in_array($form['city'], vietnam_province_names(), true)): ?>
                                <option value="<?= e($form['city']) ?>" selected><?= e($form['city']) ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label" for="district">Quận / Huyện</label><input class="form-control" id="district" name="district" autocomplete="address-level2" placeholder="VD: Quận 1" value="<?= e($form['district']) ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="ward">Phường / Xã</label><input class="form-control" id="ward" name="ward" autocomplete="address-level3" placeholder="VD: Phường Bến Nghé" value="<?= e($form['ward']) ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="address">Số nhà, tên đường *</label><input class="form-control" id="address" name="address" required autocomplete="street-address" placeholder="VD: 123 Nguyễn Huệ" value="<?= e($form['address']) ?>"></div>
                    <div class="col-12">
                        <label class="form-label" for="note">Ghi chú cho đơn hàng</label>
                        <textarea class="form-control" id="note" name="note" rows="3" data-order-note placeholder="Ví dụ: Gọi trước khi giao, giao giờ hành chính..."><?= e($form['note']) ?></textarea>
                        <div class="note-chips" aria-label="Gợi ý ghi chú">
                            <button type="button" class="note-chip" data-note-chip="Gọi trước khi giao">Gọi trước khi giao</button>
                            <button type="button" class="note-chip" data-note-chip="Giao giờ hành chính">Giao giờ hành chính</button>
                            <button type="button" class="note-chip" data-note-chip="Để ở bảo vệ/lễ tân">Để ở bảo vệ/lễ tân</button>
                            <button type="button" class="note-chip" data-note-chip="Giao cuối tuần">Giao cuối tuần</button>
                        </div>
                    </div>
                </div>

                <?php if (!empty($_SESSION['user'])): ?>
                    <div class="save-address-box" data-save-address-wrap>
                        <label class="form-check-label"><input class="form-check-input" type="checkbox" name="save_address" value="1" checked data-save-address> Lưu địa chỉ này vào sổ địa chỉ</label>
                        <input class="form-control form-control-sm" type="text" name="address_label" value="Nhà riêng" maxlength="60" placeholder="Nhãn (Nhà riêng, Cơ quan...)" data-address-label style="max-width:220px">
                    </div>
                <?php endif; ?>

                <h2 class="checkout-subheading">Phương thức giao hàng</h2>
                <div class="delivery-options" role="radiogroup" aria-label="Phương thức giao hàng">
                    <?php foreach ($shippingMethods as $methodKey => $method): $fee = shipping_fee_for($methodKey, $subtotal); ?>
                        <label class="delivery-option <?= $shippingMethod === $methodKey ? 'is-selected' : '' ?>">
                            <input type="radio" name="shipping_method" value="<?= e($methodKey) ?>" <?= $shippingMethod === $methodKey ? 'checked' : '' ?>>
                            <span class="delivery-option-main">
                                <strong><?= e($method['label']) ?></strong>
                                <small><?= e($method['description']) ?> · Dự kiến <?= e(delivery_window($methodKey)) ?></small>
                            </span>
                            <span class="delivery-option-fee"><?= e($fee <= 0 && $methodKey === 'standard' ? 'Miễn phí' : number_format($fee, 0, ',', '.') . '₫') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="form-text mt-2">Thời gian giao dự kiến: <strong data-delivery-eta>Dự kiến <?= e($deliveryEta) ?></strong> (không tính Chủ nhật và ngày lễ).</p>

                <h2 class="checkout-subheading">Phương thức thanh toán</h2>
                <label class="payment-option"><input type="radio" name="payment_method" value="cod" <?= $paymentMethod === 'cod' ? 'checked' : '' ?>><span><strong>Thanh toán khi nhận hàng (COD)</strong><small>Thanh toán trực tiếp cho shipper khi nhận được truyện.</small></span></label>
                <label class="payment-option"><input type="radio" name="payment_method" value="bank_transfer" <?= $paymentMethod === 'bank_transfer' ? 'checked' : '' ?>><span><strong>Chuyển khoản ngân hàng</strong><small>Thông tin chuyển khoản sẽ được gửi sau khi xác nhận đơn.</small></span></label>
            </section>

            <aside class="summary-card checkout-summary">
                <h2>Đơn hàng của bạn</h2>
                <?php foreach ($items as $item): ?>
                    <div class="checkout-product"><span><?= e($item['name']) ?> <small>× <?= (int) $item['quantity'] ?></small></span><strong><?= number_format((float) $item['sale_price'] * (int) $item['quantity'], 0, ',', '.') ?>₫</strong></div>
                <?php endforeach; ?>

                <div class="coupon-entry">
                    <label class="form-label" for="coupon">Mã giảm giá</label>
                    <div class="coupon-input-row"><input class="form-control" id="coupon" name="coupon" placeholder="Nhập mã giảm giá" value="<?= e($_SESSION['coupon'] ?? '') ?>"><button class="btn btn-outline-primary" type="submit" name="apply_coupon" value="1" formnovalidate>Áp dụng</button></div>
                    <?php if ($coupon): ?><small class="coupon-applied">Đã áp dụng mã <?= e($coupon['code']) ?></small><?php endif; ?>
                </div>

                <div class="summary-row"><span>Tạm tính</span><strong><?= number_format($subtotal, 0, ',', '.') ?>₫</strong></div>
                <div class="summary-row"><span>Phí vận chuyển</span><strong id="summary-shipping"><?= e($shippingFee <= 0 ? 'Miễn phí' : number_format($shippingFee, 0, ',', '.') . '₫') ?></strong></div>
                <?php if ($discount > 0): ?><div class="summary-row"><span>Giảm giá</span><strong>-<?= number_format($discount, 0, ',', '.') ?>₫</strong></div><?php endif; ?>
                <div class="summary-row summary-total"><span>Tổng thanh toán</span><strong id="summary-total"><?= number_format(max(0, $subtotal + $shippingFee - $discount), 0, ',', '.') ?>₫</strong></div>
                <button class="btn btn-primary w-100" type="submit">Xác nhận đặt hàng</button>
                <p class="secure-note">Bằng việc đặt hàng, bạn đồng ý với chính sách mua hàng và giao hàng của TruyenThuVi.</p>
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
