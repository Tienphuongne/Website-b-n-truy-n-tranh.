<?php if ($metrics): ?>
    <?php
    $statusLabels = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
    ];
    $dailyRevenue = $metrics['daily_revenue'] ?? [];
    $maxRevenue = 0;
    foreach ($dailyRevenue as $day) {
        $maxRevenue = max($maxRevenue, (float) $day['value']);
    }
    $maxRevenue = $maxRevenue > 0 ? $maxRevenue : 1;
    ?>
    <div class="admin-metric-grid">
        <article class="admin-metric"><span>Sản phẩm</span><strong><?= number_format($metrics['products']) ?></strong><small>Trong cửa hàng</small></article>
        <article class="admin-metric"><span>Đơn hàng</span><strong><?= number_format($metrics['orders']) ?></strong><small>Tất cả trạng thái</small></article>
        <article class="admin-metric"><span>Khách hàng</span><strong><?= number_format($metrics['users']) ?></strong><small>Tài khoản khách hàng</small></article>
        <article class="admin-metric metric-revenue"><span>Doanh thu hoàn tất</span><strong><?= number_format($metrics['revenue'], 0, ',', '.') ?>₫</strong><small>Tháng này: <?= number_format($metrics['month_revenue'], 0, ',', '.') ?>₫</small></article>
    </div>
    <div class="admin-panels">
        <section class="admin-panel"><div class="admin-panel-heading"><h2>Đơn hàng mới</h2><a href="<?= e(url('admin/orders')) ?>">Tất cả đơn hàng →</a></div>
            <?php if ($metrics['recent_orders']): ?><div class="table-responsive"><table class="table"><thead><tr><th>Mã</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead><tbody><?php foreach ($metrics['recent_orders'] as $order): ?><tr><td>#<?= (int) $order['id'] ?></td><td><?= e($order['customer_name']) ?></td><td><?= number_format((float) $order['total_amount'], 0, ',', '.') ?>₫</td><td><?= e($statusLabels[$order['status']] ?? $order['status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
            <?php else: ?><p class="text-muted">Chưa có đơn hàng.</p><?php endif; ?>
        </section>
        <section class="admin-panel"><div class="admin-panel-heading"><h2>Sản phẩm bán chạy</h2><a href="<?= e(url('admin/products')) ?>">Quản lý sản phẩm →</a></div>
            <?php if ($metrics['best_sellers']): ?><ul class="admin-best-sellers"><?php foreach ($metrics['best_sellers'] as $product): ?><li><span><?= e($product['name']) ?><small><?= (int) $product['sold_quantity'] ?> lượt bán</small></span><strong><?= number_format((float) $product['sale_price'], 0, ',', '.') ?>₫</strong></li><?php endforeach; ?></ul>
            <?php else: ?><p class="text-muted">Chưa có sản phẩm bán ra.</p><?php endif; ?>
        </section>
    </div>
    <div class="admin-panels">
        <section class="admin-panel">
            <div class="admin-panel-heading"><h2>Doanh thu 7 ngày gần nhất</h2><a href="<?= e(url('admin/reports')) ?>">Báo cáo chi tiết →</a></div>
            <div class="admin-daily-chart">
                <?php foreach ($dailyRevenue as $day): ?>
                    <?php $height = max(3, (int) round((float) $day['value'] / $maxRevenue * 100)); ?>
                    <div class="admin-daily-bar" title="<?= e($day['label']) ?>: <?= number_format((float) $day['value'], 0, ',', '.') ?>₫">
                        <span style="height: <?= $height ?>%"></span>
                        <small><?= e($day['label']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="admin-chart-note">Chỉ tính các đơn đã hoàn tất.</p>
        </section>
        <section class="admin-panel">
            <div class="admin-panel-heading"><h2>Trạng thái đơn hàng</h2></div>
            <ul class="admin-status-list">
                <?php foreach ($statusLabels as $status => $label): ?>
                    <li><span><?= e($label) ?></span><strong><?= number_format((int) ($metrics['status_counts'][$status] ?? 0)) ?></strong></li>
                <?php endforeach; ?>
            </ul>
            <div class="admin-panel-heading admin-subheading"><h2>Sắp hết hàng</h2><a href="<?= e(url('admin/products')) ?>">Kho hàng →</a></div>
            <?php if ($metrics['low_stock']): ?>
                <ul class="admin-best-sellers">
                    <?php foreach ($metrics['low_stock'] as $product): ?><li><span><?= e($product['name']) ?><small>Mã #<?= (int) $product['id'] ?></small></span><strong><?= (int) $product['stock_quantity'] ?> còn lại</strong></li><?php endforeach; ?>
                </ul>
            <?php else: ?><p class="text-muted mb-0">Không có sản phẩm nào sắp hết hàng.</p><?php endif; ?>
        </section>
    </div>
<?php else: ?><div class="admin-panel"><p>Chưa có dữ liệu để hiển thị.</p></div><?php endif; ?>
