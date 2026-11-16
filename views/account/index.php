<?php
$orderStatusLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
];
$shippingLabels = [
    'standard' => 'Tiêu chuẩn',
    'express' => 'Nhanh',
];
$selectedOrderId = (int) ($_GET['order'] ?? 0);
$selectedOrder = null;
foreach ($orders as $orderRow) {
    if ((int) $orderRow['id'] === $selectedOrderId) {
        $selectedOrder = $orderRow;
        break;
    }
}
?>
<section class="container section-space">
    <div class="page-heading"><div><span class="eyebrow">TRUYENTHUVI</span><h1>Tài khoản của tôi</h1></div>
        <form action="<?= e(url('logout')) ?>" method="post"><?= csrf_field() ?><button class="btn btn-outline-secondary" type="submit">Đăng xuất</button></form>
    </div>
    <div class="account-layout">
        <nav class="account-nav" aria-label="Tài khoản">
            <a class="<?= $tab === 'profile' ? 'active' : '' ?>" href="<?= e(url('account')) ?>">Thông tin cá nhân</a>
            <a class="<?= $tab === 'addresses' ? 'active' : '' ?>" href="<?= e(url('account?tab=addresses')) ?>">Sổ địa chỉ</a>
            <a class="<?= $tab === 'password' ? 'active' : '' ?>" href="<?= e(url('account?tab=password')) ?>">Đổi mật khẩu</a>
            <a class="<?= $tab === 'orders' ? 'active' : '' ?>" href="<?= e(url('account?tab=orders')) ?>">Lịch sử đơn hàng</a>
            <a href="<?= e(url('wishlist')) ?>">Truyện yêu thích</a>
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

            <?php elseif ($tab === 'addresses'): ?>
                <h2>Sổ địa chỉ nhận hàng</h2>
                <p class="text-muted" style="font-size:10px;margin-bottom:14px">Lưu địa chỉ để thanh toán nhanh hơn. Bạn có thể thêm mới trực tiếp khi đặt hàng (chọn “Lưu địa chỉ này”).</p>
                <?php if (!empty($databaseError)): ?><p class="text-muted"><?= e($databaseError) ?></p>
                <?php elseif (!empty($addresses)): ?>
                    <div class="address-book-grid">
                        <?php foreach ($addresses as $addr): ?>
                            <div class="address-book-card <?= !empty($addr['is_default']) ? 'is-default' : '' ?>">
                                <div class="address-book-head">
                                    <strong><?= e($addr['label'] ?: 'Địa chỉ đã lưu') ?></strong>
                                    <?php if (!empty($addr['is_default'])): ?><span class="saved-address-badge">Mặc định</span><?php endif; ?>
                                </div>
                                <div class="address-book-line"><strong><?= e($addr['full_name']) ?></strong> · <?= e($addr['phone']) ?></div>
                                <div class="address-book-line"><?= e(full_address_line($addr)) ?></div>
                                <div class="address-book-actions">
                                    <?php if (empty($addr['is_default'])): ?>
                                        <form method="post" action="<?= e(url('account/address/default')) ?>"><?= csrf_field() ?><input type="hidden" name="address_id" value="<?= (int) $addr['id'] ?>"><button class="btn btn-sm btn-outline-primary" type="submit">Đặt mặc định</button></form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(url('account/address/delete')) ?>" data-confirm="Xóa địa chỉ này?"><?= csrf_field() ?><input type="hidden" name="address_id" value="<?= (int) $addr['id'] ?>"><button class="btn btn-sm btn-outline-secondary" type="submit">Xóa</button></form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="muted-panel">Bạn chưa có địa chỉ nào được lưu. Khi thanh toán, hãy chọn “Lưu địa chỉ này vào sổ địa chỉ”.</div>
                <?php endif; ?>

            <?php elseif ($tab === 'orders'): ?>
                <h2>Lịch sử đơn hàng</h2>
                <?php if ($databaseError): ?><p class="text-muted"><?= e($databaseError) ?></p>
                <?php elseif ($orders): ?>
                    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead><tbody>
                    <?php foreach ($orders as $order): ?><tr><td>#<?= (int) $order['id'] ?></td><td><?= e($order['created_at']) ?></td><td><?= number_format((float) $order['total_amount'], 0, ',', '.') ?>₫</td><td><span class="order-status order-status-<?= e($order['status']) ?>"><?= e($orderStatusLabels[$order['status']] ?? $order['status']) ?></span></td><td class="order-row-actions"><a href="<?= e(url('account?tab=orders&order=' . (int) $order['id'])) ?>">Chi tiết</a>
                        <?php if (in_array($order['status'], ['pending', 'confirmed'], true)): ?>
                            <form method="post" action="<?= e(url('account/order/cancel')) ?>" data-confirm="Bạn chắc chắn muốn hủy đơn hàng #<?= (int) $order['id'] ?>?"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>"><button class="link-button text-danger" type="submit">Hủy đơn</button></form>
                        <?php endif; ?>
                    </td></tr><?php endforeach; ?>
                    </tbody></table></div>
                    <?php if ($selectedOrder): ?>
                        <div class="order-detail-box"><h3>Chi tiết đơn #<?= (int) $selectedOrder['id'] ?> · <span class="order-status order-status-<?= e($selectedOrder['status']) ?>"><?= e($orderStatusLabels[$selectedOrder['status']] ?? $selectedOrder['status']) ?></span></h3>
                            <?php if ($orderDetails): ?><ul><?php foreach ($orderDetails as $detail): ?><li><?php if (!empty($detail['product_id'])): ?><a href="<?= e(url('product/detail/' . (int) $detail['product_id'])) ?>"><?= e($detail['product_name']) ?></a><?php else: ?><?= e($detail['product_name']) ?><?php endif; ?> × <?= (int) $detail['quantity'] ?> — <?= number_format((float) $detail['unit_price'], 0, ',', '.') ?>₫</li><?php endforeach; ?></ul><?php else: ?><p>Không tìm thấy sản phẩm trong đơn này.</p><?php endif; ?>
                            <div class="order-detail-actions">
                                <form method="post" action="<?= e(url('account/order/reorder')) ?>"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $selectedOrder['id'] ?>"><button class="btn btn-sm btn-primary" type="submit">Mua lại đơn này</button></form>
                                <?php if (in_array($selectedOrder['status'], ['pending', 'confirmed'], true)): ?>
                                    <form method="post" action="<?= e(url('account/order/cancel')) ?>" data-confirm="Bạn chắc chắn muốn hủy đơn hàng #<?= (int) $selectedOrder['id'] ?>?"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $selectedOrder['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Hủy đơn</button></form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($selectedOrderId > 0): ?>
                        <div class="muted-panel">Không tìm thấy đơn hàng #<?= (int) $selectedOrderId ?> trong tài khoản của bạn.</div>
                    <?php endif; ?>
                <?php else: ?><div class="muted-panel">Bạn chưa có đơn hàng nào.</div><?php endif; ?>

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
