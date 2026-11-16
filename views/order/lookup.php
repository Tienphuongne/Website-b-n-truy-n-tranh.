<?php
$status = $order['status'] ?? '';
$steps = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn tất',
];
$statusOrder = array_keys($steps);
$currentIndex = array_search($status, $statusOrder, true);
$subtotal = 0.0;
foreach ($items as $item) {
    $subtotal += (float) $item['unit_price'] * (int) $item['quantity'];
}
?>
<section class="container section-space">
    <div class="page-heading"><div><span class="eyebrow">HỖ TRỢ KHÁCH HÀNG</span><h1>Tra cứu đơn hàng</h1></div></div>

    <div class="lookup-layout">
        <section class="checkout-card">
            <h2>Thông tin tra cứu</h2>
            <p class="text-muted" style="font-size:11px;line-height:1.7">Nhập <strong>mã đơn hàng</strong> và <strong>số điện thoại</strong> bạn đã dùng khi đặt hàng. Bạn không cần đăng nhập.</p>
            <form method="post" action="<?= e(url('order/lookup')) ?>" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-md-5">
                    <label class="form-label" for="order-code">Mã đơn hàng *</label>
                    <input class="form-control" id="order-code" name="order_code" required placeholder="VD: 12 hoặc #12" value="<?= e($orderCode) ?>" inputmode="numeric" autocomplete="off">
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="lookup-phone">Số điện thoại đặt hàng *</label>
                    <input class="form-control" id="lookup-phone" name="phone" required placeholder="0xxxxxxxxx" inputmode="tel" autocomplete="tel">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" type="submit">Tra cứu</button>
                </div>
            </form>
            <?php if ($error): ?><div class="alert alert-warning mt-3 mb-0"><?= e($error) ?></div><?php endif; ?>
            <p class="secure-note mt-3">Mẹo: mã đơn nằm trong email xác nhận hoặc trang đặt hàng thành công.</p>
        </section>

        <?php if ($order): ?>
            <section class="checkout-card lookup-result">
                <div class="checkout-card-head">
                    <h2>Đơn hàng #<?= (int) $order['id'] ?></h2>
                    <span class="order-status order-status-<?= e($status) ?>"><?= e(order_status_label($status)) ?></span>
                </div>

                <?php if ($status === 'cancelled'): ?>
                    <div class="alert alert-secondary">Đơn hàng này đã bị hủy. Nếu bạn cần hỗ trợ, vui lòng liên hệ cửa hàng.</div>
                <?php else: ?>
                    <ol class="order-timeline" aria-label="Tiến trình đơn hàng">
                        <?php foreach ($steps as $key => $label): $index = array_search($key, $statusOrder, true); ?>
                            <li class="<?= $currentIndex !== false && $index <= $currentIndex ? 'done' : '' ?> <?= $status === $key ? 'current' : '' ?>">
                                <span class="order-timeline-dot"></span><strong><?= e($label) ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <div class="lookup-meta">
                    <p><span>Ngày đặt</span><strong><?= e($order['created_at']) ?></strong></p>
                    <p><span>Người nhận</span><strong><?= e($order['customer_name']) ?> · <?= e($order['phone']) ?></strong></p>
                    <p><span>Địa chỉ</span><strong><?= e(full_address_line($order)) ?></strong></p>
                    <p><span>Giao hàng</span><strong><?= e(shipping_method_label($order['shipping_method'] ?? 'standard')) ?> · dự kiến <?= e(delivery_window($order['shipping_method'] ?? 'standard', strtotime($order['created_at'] ?? 'now'))) ?></strong></p>
                    <p><span>Thanh toán</span><strong><?= e(payment_method_label($order['payment_method'] ?? 'cod')) ?></strong></p>
                    <?php if (!empty($order['note'])): ?><p><span>Ghi chú</span><strong><?= e($order['note']) ?></strong></p><?php endif; ?>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table align-middle">
                        <thead><tr><th>Sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= e($item['product_name']) ?></td>
                                    <td><?= (int) $item['quantity'] ?></td>
                                    <td><?= number_format((float) $item['unit_price'], 0, ',', '.') ?>₫</td>
                                    <td><?= number_format((float) $item['unit_price'] * (int) $item['quantity'], 0, ',', '.') ?>₫</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="lookup-totals">
                    <div><span>Tạm tính</span><strong><?= number_format($subtotal, 0, ',', '.') ?>₫</strong></div>
                    <div><span>Phí vận chuyển</span><strong><?= (float) $order['shipping_fee'] <= 0 ? 'Miễn phí' : number_format((float) $order['shipping_fee'], 0, ',', '.') . '₫' ?></strong></div>
                    <?php if ((float) $order['discount_amount'] > 0): ?><div><span>Giảm giá</span><strong>-<?= number_format((float) $order['discount_amount'], 0, ',', '.') ?>₫</strong></div><?php endif; ?>
                    <div class="lookup-total"><span>Tổng thanh toán</span><strong><?= number_format((float) $order['total_amount'], 0, ',', '.') ?>₫</strong></div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>
