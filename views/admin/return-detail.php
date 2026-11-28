<?php
$statusLabels = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối', 'cancelled' => 'Đã hủy bởi khách hàng'];
$reasonLabels = [
    'defective' => 'Hàng bị lỗi',
    'damaged' => 'Hàng bị hỏng',
    'wrong_item' => 'Giao sai hàng',
    'missing_item' => 'Thiếu sản phẩm',
    'other' => 'Lý do khác',
];
?>
<section class="admin-panel">
    <div class="admin-panel-heading">
        <div><h2>Yêu cầu #<?= (int) $detail['id'] ?></h2><p>Đơn hàng <a href="<?= e(url('admin/orders/detail?id=' . (int) $detail['order_id'])) ?>">#<?= (int) $detail['order_id'] ?></a> · Gửi lúc <?= e($detail['created_at']) ?></p></div>
        <a href="<?= e(url('admin/returns')) ?>">← Danh sách yêu cầu</a>
    </div>
    <div class="admin-order-grid">
        <div class="admin-order-box">
            <h3>Khách hàng</h3>
            <p><span>Họ tên</span><strong><?= e($detail['customer_name']) ?></strong></p>
            <p><span>Điện thoại</span><strong><?= e($detail['phone']) ?></strong></p>
            <p><span>Email</span><strong><?= e($detail['email'] ?: '—') ?></strong></p>
        </div>
        <div class="admin-order-box">
            <h3>Yêu cầu hoàn tiền</h3>
            <p><span>Trạng thái</span><strong><?= e($statusLabels[$detail['status']] ?? $detail['status']) ?></strong></p>
            <p><span>Lý do</span><strong><?= e($reasonLabels[$detail['reason']] ?? $detail['reason']) ?></strong></p>
            <p><span>Tổng tiền đơn</span><strong><?= number_format((float) $detail['total_amount'], 0, ',', '.') ?>₫</strong></p>
            <p><span>Thanh toán</span><strong><?= e($detail['payment_method']) ?></strong></p>
            <?php if ($detail['reviewed_at']): ?><p><span>Đã xử lý lúc</span><strong><?= e($detail['reviewed_at']) ?></strong></p><?php endif; ?>
        </div>
    </div>
    <div class="admin-return-description">
        <h3>Chi tiết từ khách hàng</h3>
        <p><?= nl2br(e($detail['description'])) ?></p>
    </div>
    <div class="admin-return-evidence">
        <h3>Minh chứng</h3>
        <div class="admin-return-images">
            <?php foreach ($detail['media'] as $media): ?>
                <?php $mediaUrl = BASE_ASSETS_UPLOADS . ltrim((string) $media['file_path'], '/\\'); ?>
                <?php if ($media['media_type'] === 'image'): ?>
                    <a href="<?= e($mediaUrl) ?>" target="_blank" rel="noopener"><img src="<?= e($mediaUrl) ?>" alt="Ảnh minh chứng yêu cầu trả hàng"></a>
                <?php else: ?>
                    <video controls preload="metadata"><source src="<?= e($mediaUrl) ?>">Trình duyệt không hỗ trợ phát video.</video>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if ($detail['status'] === 'pending'): ?>
        <div class="admin-return-actions">
            <form method="post" action="<?= e(url('admin/returns/review')) ?>" data-confirm="Duyệt yêu cầu này và ghi nhận hoàn tiền cho đơn hàng?">
                <?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int) $detail['id'] ?>"><input type="hidden" name="status" value="approved">
                <button class="btn btn-primary" type="submit">Duyệt và ghi nhận hoàn tiền</button>
            </form>
            <form method="post" action="<?= e(url('admin/returns/review')) ?>" data-confirm="Từ chối yêu cầu trả hàng này?">
                <?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int) $detail['id'] ?>"><input type="hidden" name="status" value="rejected">
                <button class="btn btn-outline-danger" type="submit">Từ chối yêu cầu</button>
            </form>
        </div>
    <?php endif; ?>
</section>
