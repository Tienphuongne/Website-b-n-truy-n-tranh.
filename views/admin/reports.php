<?php
$summary = $report['summary'] ?? [];
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
$daily = $report['daily'] ?? [];
$maxDaily = 0;
foreach ($daily as $day) {
    $maxDaily = max($maxDaily, (float) $day['revenue']);
}
$maxDaily = $maxDaily > 0 ? $maxDaily : 1;
$granularity = $report['granularity'] ?? 'day';
$isMonthly = $granularity === 'month';
$periodHeading = $isMonthly ? 'Doanh thu theo tháng' : 'Doanh thu theo ngày';
?>
<section class="admin-panel">
    <div class="admin-panel-heading"><div><h2>Thống kê và báo cáo</h2><p>Tổng hợp doanh thu, đơn hàng và sản phẩm bán chạy theo khoảng ngày.</p></div></div>
    <form class="admin-report-filter" method="get" action="<?= e(url('admin/reports')) ?>">
        <div><label class="form-label" for="report-from">Từ ngày</label><input class="form-control" id="report-from" type="date" name="from" value="<?= e($report['from']) ?>"></div>
        <div><label class="form-label" for="report-to">Đến ngày</label><input class="form-control" id="report-to" type="date" name="to" value="<?= e($report['to']) ?>"></div>
        <button class="btn btn-primary" type="submit">Xem báo cáo</button>
    </form>

    <div class="admin-metric-grid admin-report-metrics">
        <article class="admin-metric"><span>Tổng đơn hàng</span><strong><?= number_format((int) ($summary['total_orders'] ?? 0)) ?></strong><small>Trong kỳ đã chọn</small></article>
        <article class="admin-metric"><span>Doanh thu (trừ đơn hủy)</span><strong><?= number_format((float) ($summary['gross_revenue'] ?? 0), 0, ',', '.') ?>₫</strong><small>Tất cả đơn không bị hủy</small></article>
        <article class="admin-metric metric-revenue"><span>Doanh thu hoàn tất</span><strong><?= number_format((float) ($summary['completed_revenue'] ?? 0), 0, ',', '.') ?>₫</strong><small>Chỉ tính đơn hoàn tất</small></article>
        <article class="admin-metric"><span>Giá trị đơn trung bình</span><strong><?= number_format((float) ($summary['average_order'] ?? 0), 0, ',', '.') ?>₫</strong><small>Đơn không bị hủy</small></article>
    </div>

    <div class="admin-report-grid">
        <section class="admin-report-card">
            <h3><?= e($periodHeading) ?></h3>
            <div class="admin-report-chart-wrap">
                <div class="admin-report-chart">
                    <?php foreach ($daily as $day): ?>
                        <?php $height = $maxDaily > 0 ? max(3, (int) round((float) $day['revenue'] / $maxDaily * 100)) : 3; ?>
                        <?php $periodLabel = $isMonthly ? date('m/Y', strtotime($day['day'])) : date('d/m', strtotime($day['day'])); ?>
                        <div class="admin-report-bar" title="<?= e($periodLabel) ?>: <?= number_format((float) $day['revenue'], 0, ',', '.') ?>₫">
                            <span style="height: <?= $height ?>%"></span>
                            <small><?= e($periodLabel) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="table-responsive admin-report-table-scroll">
                <table class="table">
                    <thead><tr><th><?= $isMonthly ? 'Tháng' : 'Ngày' ?></th><th>Đơn hàng</th><th>Doanh thu</th></tr></thead>
                    <tbody>
                        <?php foreach ($daily as $day): ?><tr><td><?= e($isMonthly ? date('m/Y', strtotime($day['day'])) : date('d/m/Y', strtotime($day['day']))) ?></td><td><?= (int) $day['total_orders'] ?></td><td><?= number_format((float) $day['revenue'], 0, ',', '.') ?>₫</td></tr><?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-report-card">
            <h3>Đơn hàng theo trạng thái</h3>
            <?php if ($report['statuses']): ?>
                <table class="table">
                    <thead><tr><th>Trạng thái</th><th>Số đơn</th><th>Tổng tiền</th></tr></thead>
                    <tbody>
                        <?php foreach ($report['statuses'] as $row): ?><tr><td><?= e($statusLabels[$row['status']] ?? $row['status']) ?></td><td><?= (int) $row['total'] ?></td><td><?= number_format((float) $row['revenue'], 0, ',', '.') ?>₫</td></tr><?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?><p class="text-muted">Chưa có đơn hàng trong khoảng ngày này.</p><?php endif; ?>

            <h3>Sản phẩm bán chạy</h3>
            <?php if ($report['top_products']): ?>
                <table class="table">
                    <thead><tr><th>Sản phẩm</th><th>Đã bán</th><th>Doanh thu</th></tr></thead>
                    <tbody>
                        <?php foreach ($report['top_products'] as $row): ?><tr><td><?= e($row['product_name']) ?></td><td><?= (int) $row['quantity'] ?></td><td><?= number_format((float) $row['revenue'], 0, ',', '.') ?>₫</td></tr><?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?><p class="text-muted">Chưa có sản phẩm nào được bán trong khoảng ngày này.</p><?php endif; ?>

            <h3>Phương thức thanh toán</h3>
            <?php if ($report['payments']): ?>
                <table class="table mb-0">
                    <thead><tr><th>Phương thức</th><th>Số đơn</th><th>Tổng tiền</th></tr></thead>
                    <tbody>
                        <?php foreach ($report['payments'] as $row): ?><tr><td><?= e($paymentLabels[$row['payment_method']] ?? $row['payment_method']) ?></td><td><?= (int) $row['total'] ?></td><td><?= number_format((float) $row['revenue'], 0, ',', '.') ?>₫</td></tr><?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?><p class="text-muted mb-0">Chưa có dữ liệu thanh toán.</p><?php endif; ?>
        </section>
    </div>
</section>
