<?php if ($metrics): ?>
    <div class="admin-metric-grid">
        <article class="admin-metric"><span>Sản phẩm</span><strong><?= number_format($metrics['products']) ?></strong><small>Trong cửa hàng</small></article>
        <article class="admin-metric"><span>Đơn hàng</span><strong><?= number_format($metrics['orders']) ?></strong><small>Tất cả trạng thái</small></article>
        <article class="admin-metric"><span>Khách hàng</span><strong><?= number_format($metrics['users']) ?></strong><small>Tài khoản khách hàng</small></article>
        <article class="admin-metric metric-revenue"><span>Doanh thu hoàn tất</span><strong><?= number_format($metrics['revenue'], 0, ',', '.') ?>₫</strong><small>Các đơn đã hoàn thành</small></article>
    </div>
    <div class="admin-panels">
        <section class="admin-panel"><div class="admin-panel-heading"><h2>Đơn hàng mới</h2><a href="<?= e(url('admin/orders')) ?>">Tất cả đơn hàng →</a></div>
            <?php if ($metrics['recent_orders']): ?><div class="table-responsive"><table class="table"><thead><tr><th>Mã</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead><tbody><?php foreach ($metrics['recent_orders'] as $order): ?><tr><td>#<?= (int) $order['id'] ?></td><td><?= e($order['customer_name']) ?></td><td><?= number_format((float) $order['total_amount'], 0, ',', '.') ?>₫</td><td><?= e($order['status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
            <?php else: ?><p class="text-muted">Chưa có đơn hàng.</p><?php endif; ?>
        </section>
        <section class="admin-panel"><div class="admin-panel-heading"><h2>Sản phẩm bán chạy</h2><a href="<?= e(url('admin/products')) ?>">Quản lý sản phẩm →</a></div>
            <?php if ($metrics['best_sellers']): ?><ul class="admin-best-sellers"><?php foreach ($metrics['best_sellers'] as $product): ?><li><span><?= e($product['name']) ?><small><?= (int) $product['sold_quantity'] ?> lượt bán</small></span><strong><?= number_format((float) $product['sale_price'], 0, ',', '.') ?>₫</strong></li><?php endforeach; ?></ul>
            <?php else: ?><p class="text-muted">Chưa có sản phẩm bán ra.</p><?php endif; ?>
        </section>
    </div>
<?php else: ?><div class="admin-panel"><p>Chưa có dữ liệu để hiển thị.</p></div><?php endif; ?>
