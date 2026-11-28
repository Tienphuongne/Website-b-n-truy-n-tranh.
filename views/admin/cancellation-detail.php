<?php
$request = $detail;
$statusLabels = [
    'pending' => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Đã từ chối',
];
$reasons = OrderModel::cancellationReasons();
$orderStatusLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Chờ lấy hàng',
    'picked_up' => 'Lấy hàng thành công',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
];
$subtotal = 0;
foreach ($request['items'] as $item) {
    $subtotal += (float) $item['unit_price'] * (int) $item['quantity'];
}
?>
<section class="admin-panel">
    <div class="admin-panel-heading">
        <div><h2>Yêu cầu hủy đơn #<?= (int) $request['order_id'] ?></h2><p>Mã yêu cầu #<?= (int) $request['id'] ?></p></div>
        <a href="<?= e(url('admin/cancellations')) ?>">← Danh sách yêu cầu hủy</a>
    </div>

    <div class="admin-order-grid">
        <div class="admin-order-box">
            <h3>Thông tin khách hàng</h3>
            <p><span>Họ tên</span><strong><?= e($request['customer_name']) ?></strong></p>
            <p><span>Điện thoại</span><strong><?= e($request['phone']) ?></strong></p>
            <p><span>Email</span><strong><?= e($request['email'] ?: '—') ?></strong></p>
        </div>
        <div class="admin-order-box">
            <h3>Thông tin yêu cầu</h3>
            <p><span>Trạng thái yêu cầu</span><strong><?= e($statusLabels[$request['status']] ?? $request['status']) ?></strong></p>
            <p><span>Trạng thái đơn</span><strong><?= e($orderStatusLabels[$request['order_status']] ?? $request['order_status']) ?></strong></p>
            <p><span>Lý do</span><strong><?= e($reasons[$request['reason']] ?? $request['reason']) ?></strong></p>
            <p><span>Chi tiết</span><strong><?= e($request['description'] ?: '—') ?></strong></p>
            <p><span>Gửi lúc</span><strong><?= e($request['created_at']) ?></strong></p>
        </div>
    </div>

    <div class="table-responsive admin-order-items">
        <table class="table align-middle">
            <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead>
            <tbody>
                <?php foreach ($request['items'] as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?></td>
                        <td><?= number_format((float) $item['unit_price'], 0, ',', '.') ?>₫</td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= number_format((float) $item['unit_price'] * (int) $item['quantity'], 0, ',', '.') ?>₫</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-order-summary">
        <div><span>Tạm tính</span><strong><?= number_format($subtotal, 0, ',', '.') ?>₫</strong></div>
        <div class="admin-order-total"><span>Tổng cộng</span><strong><?= number_format((float) $request['total_amount'], 0, ',', '.') ?>₫</strong></div>
    </div>

    <?php if ($request['status'] === 'pending'): ?>
        <form class="admin-order-status-form" method="post" action="<?= e(url('admin/cancellations/review')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
            <label class="form-label" for="cancel-status">Phê duyệt yêu cầu hủy đơn</label>
            <div class="admin-order-status-row">
                <select class="form-select" id="cancel-status" name="status">
                    <option value="approved">Duyệt hủy đơn</option>
                    <option value="rejected">Từ chối hủy đơn</option>
                </select>
                <button class="btn btn-primary" type="submit">Cập nhật quyết định</button>
            </div>
        </form>
    <?php endif; ?>
</section>
