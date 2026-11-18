<section class="container section-space">
    <div class="page-heading"><div><span class="eyebrow">TRUYENTHUVI</span><h1>Tài khoản của tôi</h1></div>
        <form action="<?= e(url('logout')) ?>" method="post"><?= csrf_field() ?><button class="btn btn-outline-secondary" type="submit">Đăng xuất</button></form>
    </div>
    <div class="account-layout">
        <nav class="account-nav" aria-label="Tài khoản">
            <a class="<?= $tab === 'profile' ? 'active' : '' ?>" href="<?= e(url('account')) ?>">Thông tin cá nhân</a>
            <a class="<?= $tab === 'password' ? 'active' : '' ?>" href="<?= e(url('account?tab=password')) ?>">Đổi mật khẩu</a>
            <a class="<?= $tab === 'orders' ? 'active' : '' ?>" href="<?= e(url('account?tab=orders')) ?>">Lịch sử đơn hàng</a>

            <div class="account-voucher-card" title="Số mã ưu đãi còn dùng được, đã trừ mã đã sử dụng">
                <span class="voucher-mini-icon" aria-hidden="true">✦</span>
                <div>
                    <span class="account-voucher-label">Mã ưu đãi của bạn</span>
                    <strong><?= (int) $voucherSummary['available'] ?> <small>có thể dùng</small></strong>
                    <small class="account-voucher-used">Đã dùng <?= (int) $voucherSummary['used'] ?> mã ưu đãi</small>
                </div>
            </div>
        </nav>

        <div class="account-content">
            <?php if ($tab === 'password'): ?>
                <h2>Đổi mật khẩu</h2>
                <form method="post" action="<?= e(url('account?tab=password')) ?>" class="account-form">
                    <?= csrf_field() ?><input type="hidden" name="form_type" value="password">
                    <label class="form-label" for="current-password">Mật khẩu hiện tại</label><input class="form-control mb-3" id="current-password" name="current_password" type="password" required autocomplete="current-password">
                    <label class="form-label" for="new-password">Mật khẩu mới</label><input class="form-control mb-3" id="new-password" name="new_password" type="password" required minlength="8" autocomplete="new-password">
                    <label class="form-label" for="confirm-password">Xác nhận mật khẩu mới</label><input class="form-control mb-3" id="confirm-password" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
                    <button class="btn btn-primary" type="submit">Cập nhật mật khẩu</button>
                </form>
            <?php elseif ($tab === 'orders'): ?>
                <?php
                $statusLabels = [
                    'pending' => 'Chờ xác nhận',
                    'confirmed' => 'Đã xác nhận',
                    'shipping' => 'Đang giao',
                    'completed' => 'Hoàn tất',
                    'cancelled' => 'Đã hủy',
                ];
                $paymentLabels = [
                    'cod' => 'Thanh toán khi nhận hàng (COD)',
                    'bank_transfer' => 'Chuyển khoản ngân hàng',
                    'vnpay' => 'VNPAY',
                    'momo' => 'MoMo',
                    'card' => 'Thẻ Visa / Master / JCB / Napas',
                ];
                $formatOrderDate = static function ($value) {
                    return !empty($value) ? date('d/m/Y H:i', strtotime((string) $value)) : '—';
                };
                ?>
                <div class="account-section-head">
                    <div><h2>Lịch sử đơn hàng</h2><p>Đơn đặt bằng tài khoản hoặc bằng cùng email đều hiển thị tại đây.</p></div>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('account?tab=orders')) ?>">Làm mới</a>
                </div>
                <?php if ($databaseError): ?>
                    <p class="text-muted"><?= e($databaseError) ?></p>
                <?php elseif ($orders): ?>
                    <div class="table-responsive account-orders-table">
                        <table class="table align-middle">
                            <thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $orderStatus = (string) $order['status'];
                                $isSelected = $selectedOrder && (int) $selectedOrder['id'] === (int) $order['id'];
                                ?>
                                <tr class="<?= $isSelected ? 'is-selected' : '' ?>">
                                    <td>
                                        <strong>#<?= (int) $order['id'] ?></strong>
                                        <small><?= (int) $order['item_quantity'] ?> cuốn · <?= (int) $order['item_count'] ?> đầu truyện</small>
                                    </td>
                                    <td><?= e($formatOrderDate($order['created_at'])) ?></td>
                                    <td><strong><?= number_format((float) $order['total_amount'], 0, ',', '.') ?>₫</strong></td>
                                    <td><span class="order-status-badge status-<?= e($orderStatus) ?>"><?= e($statusLabels[$orderStatus] ?? $orderStatus) ?></span></td>
                                    <td><a href="<?= e(url('account?tab=orders&order=' . (int) $order['id'])) ?>"><?= $isSelected ? 'Đang xem' : 'Chi tiết' ?> <span aria-hidden="true">→</span></a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($orderNotFound && !$selectedOrder): ?>
                        <div class="account-alert">Không tìm thấy đơn hàng #<?= $requestedOrderId ?> trong lịch sử của bạn.</div>
                    <?php endif; ?>

                    <?php if ($selectedOrder): ?>
                        <?php
                        $detailSubtotal = 0;
                        foreach ($orderDetails as $detail) {
                            $detailSubtotal += (float) $detail['unit_price'] * (int) $detail['quantity'];
                        }
                        $detailStatus = (string) $selectedOrder['status'];
                        $addressParts = array_filter([
                            $selectedOrder['address'] ?? '',
                            $selectedOrder['district'] ?? '',
                            $selectedOrder['city'] ?? '',
                        ]);
                        $addressLabel = $addressParts ? implode(', ', array_map('strval', $addressParts)) : '—';
                        ?>
                        <div class="order-detail-box account-order-detail">
                            <div class="order-detail-head">
                                <div><h3>Chi tiết đơn #<?= (int) $selectedOrder['id'] ?></h3><p>Đặt lúc <?= e($formatOrderDate($selectedOrder['created_at'])) ?></p></div>
                                <span class="order-status-badge status-<?= e($detailStatus) ?>"><?= e($statusLabels[$detailStatus] ?? $detailStatus) ?></span>
                            </div>

                            <div class="account-order-meta">
                                <div><span>Người nhận</span><strong><?= e($selectedOrder['customer_name']) ?></strong></div>
                                <div><span>Điện thoại</span><strong><?= e($selectedOrder['phone']) ?></strong></div>
                                <div><span>Thanh toán</span><strong><?= e($paymentLabels[$selectedOrder['payment_method']] ?? $selectedOrder['payment_method']) ?></strong></div>
                                <div class="is-wide"><span>Địa chỉ giao hàng</span><strong><?= e($addressLabel) ?></strong></div>
                            </div>

                            <?php if ($orderDetails): ?>
                                <div class="table-responsive account-order-items">
                                    <table class="table align-middle">
                                        <thead><tr><th>Truyện</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($orderDetails as $detail): ?>
                                            <tr>
                                                <td><?= e($detail['product_name']) ?></td>
                                                <td><?= number_format((float) $detail['unit_price'], 0, ',', '.') ?>₫</td>
                                                <td>× <?= (int) $detail['quantity'] ?></td>
                                                <td><strong><?= number_format((float) $detail['unit_price'] * (int) $detail['quantity'], 0, ',', '.') ?>₫</strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="account-alert">Đơn hàng này chưa có thông tin sản phẩm.</div>
                            <?php endif; ?>

                            <div class="account-order-totals">
                                <div><span>Tạm tính</span><strong><?= number_format($detailSubtotal, 0, ',', '.') ?>₫</strong></div>
                                <div><span>Giảm giá<?= !empty($selectedOrder['coupon_code']) ? ' (' . e($selectedOrder['coupon_code']) . ')' : '' ?></span><strong>-<?= number_format((float) $selectedOrder['discount_amount'], 0, ',', '.') ?>₫</strong></div>
                                <div><span>Phí vận chuyển</span><strong><?= number_format((float) $selectedOrder['shipping_fee'], 0, ',', '.') ?>₫</strong></div>
                                <div class="is-total"><span>Tổng thanh toán</span><strong><?= number_format((float) $selectedOrder['total_amount'], 0, ',', '.') ?>₫</strong></div>
                            </div>
                            <a class="text-link" href="<?= e(url('account?tab=orders')) ?>">← Ẩn chi tiết</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="muted-panel">Bạn chưa có đơn hàng nào. <a href="<?= e(url('products')) ?>">Khám phá truyện ngay</a>.</div>
                <?php endif; ?>
            <?php else: ?>
                <h2>Thông tin cá nhân</h2>
                <form method="post" action="<?= e(url('account')) ?>" class="account-form">
                    <?= csrf_field() ?><input type="hidden" name="form_type" value="profile">
                    <label class="form-label" for="account-name">Họ và tên</label><input class="form-control mb-3" id="account-name" name="full_name" required value="<?= e($_SESSION['user']['name']) ?>">
                    <label class="form-label" for="account-email">Email</label><input class="form-control mb-3" id="account-email" value="<?= e($_SESSION['user']['email']) ?>" disabled>
                    <label class="form-label" for="account-phone">Số điện thoại</label><input class="form-control mb-3" id="account-phone" name="phone" value="<?= e($_SESSION['user']['phone'] ?? '') ?>">
                    <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
