<?php
$order = $detail['order'];
$items = $detail['items'];
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
];
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += (float) $item['unit_price'] * (int) $item['quantity'];
}
?>
<section class="admin-panel">
    <div class="admin-panel-heading"><div><h2>Đơn hàng #<?= (int) $order['id'] ?></h2><p>Đặt lúc <?= e($order['created_at']) ?></p></div><a href="<?= e(url('admin/orders')) ?>">← Danh sách đơn hàng</a></div>

    <div class="admin-order-grid">
        <div class="admin-order-box">
            <h3>Thông tin khách hàng</h3>
            <p><span>Họ tên</span><strong><?= e($order['customer_name']) ?></strong></p>
            <p><span>Điện thoại</span><strong><?= e($order['phone']) ?></strong></p>
            <p><span>Email</span><strong><?= e($order['email'] ?: '—') ?></strong></p>
            <p><span>Địa chỉ</span><strong><?= e($order['address']) ?>, <?= e($order['district']) ?>, <?= e($order['city']) ?></strong></p>
            <p><span>Ghi chú</span><strong><?= e($order['note'] ?: '—') ?></strong></p>
        </div>
        <div class="admin-order-box">
            <h3>Thông tin đơn hàng</h3>
            <p><span>Trạng thái</span><strong><?= e($statusLabels[$order['status']] ?? $order['status']) ?></strong></p>
            <p><span>Thanh toán</span><strong><?= e($paymentLabels[$order['payment_method']] ?? $order['payment_method']) ?></strong></p>
            <p><span>Mã giảm giá</span><strong><?= e($order['coupon_code'] ?: '—') ?></strong></p>
            <p><span>Tài khoản</span><strong><?= $order['user_id'] ? '#' . (int) $order['user_id'] : 'Khách vãng lai' ?></strong></p>
            <p><span>Cập nhật</span><strong><?= e($order['updated_at'] ?: '—') ?></strong></p>
        </div>
    </div>

    <div class="table-responsive admin-order-items">
        <table class="table align-middle">
            <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?><?= $item['product_id'] ? ' <small class="text-muted">#' . (int) $item['product_id'] . '</small>' : '' ?></td>
                        <td><?= number_format((float) $item['unit_price'], 0, ',', '.') ?>₫</td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= number_format((float) $item['unit_price'] * (int) $item['quantity'], 0, ',', '.') ?>₫</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$items): ?><tr><td colspan="4" class="text-muted">Đơn hàng chưa có sản phẩm.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-order-summary">
        <div><span>Tạm tính</span><strong><?= number_format($subtotal, 0, ',', '.') ?>₫</strong></div>
        <div><span>Phí vận chuyển</span><strong><?= number_format((float) $order['shipping_fee'], 0, ',', '.') ?>₫</strong></div>
        <div><span>Giảm giá</span><strong>-<?= number_format((float) $order['discount_amount'], 0, ',', '.') ?>₫</strong></div>
        <div class="admin-order-total"><span>Tổng cộng</span><strong><?= number_format((float) $order['total_amount'], 0, ',', '.') ?>₫</strong></div>
    </div>

    <form class="admin-order-status-form" method="post" action="<?= e(url('admin/orders/edit?id=' . (int) $order['id'])) ?>">
        <?= csrf_field() ?>
        <label class="form-label" for="order-status">Cập nhật trạng thái đơn hàng</label>
        <div class="admin-order-status-row">
            <select class="form-select" id="order-status" name="status">
                <?php foreach ($statusLabels as $value => $label): ?><option value="<?= e($value) ?>" <?= $order['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Lưu trạng thái</button>
        </div>
    </form>
</section>
