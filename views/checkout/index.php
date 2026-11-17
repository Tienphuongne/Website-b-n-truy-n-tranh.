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
                <?php if (!empty($_SESSION['user']) || !empty($savedAddresses)): ?>
                    <?php
                    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP;
                    $accountFill = json_encode([
                        'full_name' => $_SESSION['user']['name'] ?? '',
                        'phone' => $_SESSION['user']['phone'] ?? '',
                        'email' => $_SESSION['user']['email'] ?? '',
                    ], $jsonFlags);
                    ?>
                    <div class="quick-address">
                        <span class="quick-address-label">Chọn nhanh:</span>
                        <div class="quick-address-list">
                            <?php if (!empty($_SESSION['user'])): ?>
                                <button type="button" class="quick-address-chip" data-fill="<?= e($accountFill) ?>">👤 Thông tin tài khoản</button>
                            <?php endif; ?>
                            <?php foreach ($savedAddresses as $address): ?>
                                <?php
                                $addressFill = json_encode([
                                    'full_name' => $address['customer_name'],
                                    'phone' => $address['phone'],
                                    'email' => $address['email'],
                                    'city' => $address['city'],
                                    'district' => $address['district'],
                                    'address' => $address['address'],
                                ], $jsonFlags);
                                ?>
                                <button type="button" class="quick-address-chip" data-fill="<?= e($addressFill) ?>">
                                    📍 <?= e($address['district'] . ' · ' . $address['city']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-12"><label class="form-label" for="full-name">Họ và tên *</label><input class="form-control" id="full-name" name="full_name" required autocomplete="name" value="<?= e($customer['full_name']) ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="phone">Số điện thoại *</label><input class="form-control" id="phone" name="phone" required autocomplete="tel" value="<?= e($customer['phone']) ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" autocomplete="email" value="<?= e($customer['email']) ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="city">Tỉnh / thành phố *</label><select class="form-select" id="city" name="city" required autocomplete="address-level1"><option value="">-- Chọn tỉnh / thành phố --</option></select></div>
                    <div class="col-md-6"><label class="form-label" for="district">Quận / huyện *</label><select class="form-select" id="district" name="district" required autocomplete="address-level2"><option value="">-- Chọn quận / huyện --</option></select></div>
                    <div class="col-12"><label class="form-label" for="address">Địa chỉ nhận hàng *</label><input class="form-control" id="address" name="address" required autocomplete="street-address" value="<?= e($customer['address']) ?>"></div>
                    <div class="col-12"><label class="form-label" for="note">Ghi chú đơn hàng</label><textarea class="form-control" id="note" name="note" rows="3"><?= e($customer['note']) ?></textarea></div>
                </div>

                <h2 class="checkout-subheading">Phương thức thanh toán</h2>
                <div class="payment-methods">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <span class="payment-option-content">
                            <span class="payment-icon">🏠</span>
                            <span>
                                <strong>Thanh toán khi nhận hàng (COD)</strong>
                                <small>Thanh toán trực tiếp khi nhận được truyện.</small>
                            </span>
                        </span>
                    </label>

                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="bank_transfer">
                        <span class="payment-option-content">
                            <span class="payment-icon">🏦</span>
                            <span>
                                <strong>Chuyển khoản ngân hàng qua mã QR</strong>
                                <small>Thông tin chuyển khoản / QR sẽ được gửi sau khi xác nhận đơn.</small>
                            </span>
                        </span>
                    </label>

                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="vnpay">
                        <span class="payment-option-content">
                            <span class="payment-icon payment-icon-vnpay">VNPAY</span>
                            <span>
                                <strong>VNPAY</strong>
                                <small>Thanh toán nhanh qua cổng VNPAY (ATM / Internet Banking / QR).</small>
                            </span>
                        </span>
                    </label>

                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="momo">
                        <span class="payment-option-content">
                            <span class="payment-icon payment-icon-momo">MoMo</span>
                            <span>
                                <strong>MoMo</strong>
                                <small>Thanh toán qua ví MoMo.</small>
                            </span>
                        </span>
                    </label>

                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="card">
                        <span class="payment-option-content">
                            <span class="payment-icon">💳</span>
                            <span>
                                <strong>Qua thẻ Visa / Master / JCB / Napas</strong>
                                <small>Thanh toán bằng thẻ nội địa hoặc quốc tế.</small>
                            </span>
                        </span>
                    </label>
                </div>
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

    <?php if (!empty($items) && empty($orderComplete)): ?>
        <?php $jsFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT; ?>
        <script src="<?= e(PATH_JS) ?>vietnam-addresses.js"></script>
        <script>
            (function () {
                var addressData = window.VIETNAM_ADDRESSES || {};
                var citySelect = document.getElementById('city');
                var districtSelect = document.getElementById('district');
                if (!citySelect || !districtSelect) {
                    return;
                }

                var cleanName = function (name) {
                    return String(name || '').replace(/^(Thành phố|Thị xã|Quận|Huyện|Tỉnh)\s+/, '').trim();
                };

                function matchCity(value) {
                    var name = cleanName(value);
                    var keys = Object.keys(addressData);
                    for (var i = 0; i < keys.length; i++) {
                        if (keys[i].localeCompare(name, 'vi') === 0) {
                            return keys[i];
                        }
                    }
                    return '';
                }

                function renderDistricts(preselect) {
                    var districts = addressData[citySelect.value] || [];
                    districtSelect.innerHTML = '<option value="">-- Chọn quận / huyện --</option>';
                    districts.forEach(function (name) {
                        var option = document.createElement('option');
                        option.value = name;
                        option.textContent = name;
                        districtSelect.appendChild(option);
                    });
                    var wanted = cleanName(preselect);
                    if (wanted && districts.indexOf(wanted) === -1) {
                        var extra = document.createElement('option');
                        extra.value = wanted;
                        extra.textContent = wanted;
                        districtSelect.appendChild(extra);
                    }
                    districtSelect.value = wanted || '';
                }

                function applyCity(city, district) {
                    var matched = matchCity(city);
                    if (!matched && city) {
                        var option = document.createElement('option');
                        option.value = city;
                        option.textContent = city;
                        citySelect.appendChild(option);
                        matched = city;
                    }
                    citySelect.value = matched || '';
                    renderDistricts(district);
                }

                Object.keys(addressData).sort(function (a, b) {
                    return a.localeCompare(b, 'vi');
                }).forEach(function (name) {
                    var option = document.createElement('option');
                    option.value = name;
                    option.textContent = name;
                    citySelect.appendChild(option);
                });

                applyCity(<?= json_encode($customer['city'] ?? '', $jsFlags) ?>, <?= json_encode($customer['district'] ?? '', $jsFlags) ?>);

                citySelect.addEventListener('change', function () {
                    renderDistricts('');
                });

                document.querySelectorAll('.quick-address-chip').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var data;
                        try {
                            data = JSON.parse(chip.getAttribute('data-fill') || '{}');
                        } catch (error) {
                            return;
                        }
                        Object.keys(data).forEach(function (key) {
                            var field = document.getElementById(key === 'full_name' ? 'full-name' : key);
                            if (field) {
                                field.value = data[key] || '';
                            }
                        });
                        if (data.city) {
                            applyCity(data.city, data.district || '');
                        }
                        document.querySelectorAll('.quick-address-chip').forEach(function (item) {
                            item.classList.remove('active');
                        });
                        chip.classList.add('active');
                    });
                });
            })();
        </script>
    <?php endif; ?>
</section>
