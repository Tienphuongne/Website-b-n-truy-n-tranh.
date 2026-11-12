<section class="container section-space">
    <div class="page-heading"><div><span class="eyebrow">MANGA HOUSE</span><h1>Tài khoản của tôi</h1></div>
        <form action="<?= e(url('logout')) ?>" method="post"><?= csrf_field() ?><button class="btn btn-outline-secondary" type="submit">Đăng xuất</button></form>
    </div>
    <div class="account-layout">
        <nav class="account-nav" aria-label="Tài khoản">
            <a class="<?= $tab === 'profile' ? 'active' : '' ?>" href="<?= e(url('account')) ?>">Thông tin cá nhân</a>
            <a class="<?= $tab === 'password' ? 'active' : '' ?>" href="<?= e(url('account?tab=password')) ?>">Đổi mật khẩu</a>
            <a class="<?= $tab === 'orders' ? 'active' : '' ?>" href="<?= e(url('account?tab=orders')) ?>">Lịch sử đơn hàng</a>
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
                <h2>Lịch sử đơn hàng</h2>
                <?php if ($databaseError): ?><p class="text-muted"><?= e($databaseError) ?></p>
                <?php elseif ($orders): ?>
                    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead><tbody>
                    <?php foreach ($orders as $order): ?><tr><td>#<?= (int) $order['id'] ?></td><td><?= e($order['created_at']) ?></td><td><?= number_format((float) $order['total_amount'], 0, ',', '.') ?>₫</td><td><?= e($order['status']) ?></td><td><a href="<?= e(url('account?tab=orders&order=' . (int) $order['id'])) ?>">Chi tiết</a></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                    <?php if (isset($_GET['order'])): ?><div class="order-detail-box"><h3>Chi tiết đơn #<?= (int) $_GET['order'] ?></h3>
                        <?php if ($orderDetails): ?><ul><?php foreach ($orderDetails as $detail): ?><li><?= e($detail['product_name']) ?> × <?= (int) $detail['quantity'] ?> — <?= number_format((float) $detail['unit_price'], 0, ',', '.') ?>₫</li><?php endforeach; ?></ul><?php else: ?><p>Không tìm thấy sản phẩm trong đơn này.</p><?php endif; ?>
                    </div><?php endif; ?>
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
