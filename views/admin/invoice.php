<?php
$order = $detail['order'];
$items = $detail['items'];
$subtotal = 0.0;
foreach ($items as $item) {
    $subtotal += (float) $item['unit_price'] * (int) $item['quantity'];
}
$statusLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hoá đơn #<?= (int) $order['id'] ?> | TruyenThuVi</title>
    <style>
        * { box-sizing: border-box; }
        body { background: #f5f5f5; color: #232326; font-family: "Segoe UI", Arial, sans-serif; margin: 0; padding: 24px; }
        .invoice { background: #fff; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,.08); margin: 0 auto; max-width: 780px; padding: 34px 38px; }
        .invoice-head { align-items: flex-start; border-bottom: 2px solid #f05a35; display: flex; justify-content: space-between; padding-bottom: 16px; }
        .brand-name { color: #f05a35; font-size: 22px; font-weight: 800; letter-spacing: -.5px; }
        .brand-sub { color: #777980; font-size: 11px; margin-top: 3px; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { font-size: 20px; letter-spacing: .5px; margin: 0 0 4px; text-transform: uppercase; }
        .invoice-title p { color: #777980; font-size: 11px; margin: 2px 0; }
        .invoice-meta { display: grid; gap: 18px; grid-template-columns: 1fr 1fr; margin: 22px 0; }
        .box h3 { color: #92939a; font-size: 10px; letter-spacing: .06em; margin: 0 0 8px; text-transform: uppercase; }
        .box p { font-size: 12px; line-height: 1.6; margin: 2px 0; }
        .box strong { font-weight: 600; }
        table { border-collapse: collapse; font-size: 12px; width: 100%; }
        th { background: #faf8f6; border-bottom: 1px solid #e9e7e4; font-size: 10px; letter-spacing: .04em; padding: 9px 8px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #f0efed; padding: 9px 8px; }
        th:last-child, td:last-child { text-align: right; }
        th:nth-child(2), td:nth-child(2), th:nth-child(3), td:nth-child(3) { text-align: center; }
        .totals { margin-left: auto; margin-top: 14px; width: 300px; }
        .totals div { display: flex; font-size: 12px; justify-content: space-between; padding: 5px 0; }
        .totals .grand { border-top: 2px solid #232326; font-size: 15px; font-weight: 700; margin-top: 6px; padding-top: 10px; }
        .note { background: #faf8f6; border-radius: 8px; font-size: 11px; line-height: 1.6; margin-top: 20px; padding: 12px 14px; }
        .signatures { display: flex; gap: 40px; justify-content: flex-end; margin-top: 36px; text-align: center; }
        .signatures div { color: #777980; font-size: 11px; width: 180px; }
        .signatures span { display: block; height: 56px; }
        .invoice-actions { display: flex; gap: 10px; justify-content: center; margin: 20px auto 0; max-width: 780px; }
        .btn { border-radius: 8px; cursor: pointer; display: inline-block; font-size: 13px; font-weight: 600; padding: 9px 18px; text-decoration: none; }
        .btn-primary { background: #f05a35; border: 1px solid #f05a35; color: #fff; }
        .btn-secondary { background: #fff; border: 1px solid #d9d7d4; color: #444; }
        @media print {
            body { background: #fff; padding: 0; }
            .invoice { box-shadow: none; max-width: none; padding: 0; }
            .invoice-actions { display: none !important; }
        }
    </style>
</head>
<body>
    <main class="invoice">
        <header class="invoice-head">
            <div>
                <div class="brand-name">TruyenThuVi</div>
                <div class="brand-sub">Truyện hay mỗi ngày · Hotline 1900 0000 · hello@truyenthuvi.vn</div>
            </div>
            <div class="invoice-title">
                <h1>Hoá đơn bán hàng</h1>
                <p>Mã đơn: <strong>#<?= (int) $order['id'] ?></strong></p>
                <p>Ngày đặt: <?= e($order['created_at']) ?></p>
                <p>Trạng thái: <?= e($statusLabels[$order['status']] ?? $order['status']) ?></p>
            </div>
        </header>

        <div class="invoice-meta">
            <div class="box">
                <h3>Khách hàng</h3>
                <p><strong><?= e($order['customer_name']) ?></strong></p>
                <p>Điện thoại: <?= e($order['phone']) ?></p>
                <?php if (!empty($order['email'])): ?><p>Email: <?= e($order['email']) ?></p><?php endif; ?>
                <p>Tài khoản: <?= $order['user_id'] ? '#' . (int) $order['user_id'] : 'Khách vãng lai' ?></p>
            </div>
            <div class="box">
                <h3>Giao đến</h3>
                <p><strong><?= e(full_address_line($order)) ?></strong></p>
                <p>Phương thức: <?= e(shipping_method_label($order['shipping_method'] ?? 'standard')) ?></p>
                <p>Dự kiến: <?= e(delivery_window($order['shipping_method'] ?? 'standard', strtotime($order['created_at'] ?? 'now'))) ?></p>
                <p>Thanh toán: <?= e(payment_method_label($order['payment_method'] ?? 'cod')) ?></p>
            </div>
        </div>

        <table>
            <thead><tr><th>Sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?><?= $item['product_id'] ? ' <small style="color:#92939a">#' . (int) $item['product_id'] . '</small>' : '' ?></td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= number_format((float) $item['unit_price'], 0, ',', '.') ?>₫</td>
                        <td><?= number_format((float) $item['unit_price'] * (int) $item['quantity'], 0, ',', '.') ?>₫</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$items): ?><tr><td colspan="4" style="color:#92939a">Đơn hàng chưa có sản phẩm.</td></tr><?php endif; ?>
            </tbody>
        </table>

        <div class="totals">
            <div><span>Tạm tính</span><strong><?= number_format($subtotal, 0, ',', '.') ?>₫</strong></div>
            <div><span>Phí vận chuyển</span><strong><?= (float) $order['shipping_fee'] <= 0 ? 'Miễn phí' : number_format((float) $order['shipping_fee'], 0, ',', '.') . '₫' ?></strong></div>
            <?php if ((float) $order['discount_amount'] > 0): ?><div><span>Giảm giá<?= !empty($order['coupon_code']) ? ' (' . e($order['coupon_code']) . ')' : '' ?></span><strong>-<?= number_format((float) $order['discount_amount'], 0, ',', '.') ?>₫</strong></div><?php endif; ?>
            <div class="grand"><span>Tổng cộng</span><strong><?= number_format((float) $order['total_amount'], 0, ',', '.') ?>₫</strong></div>
        </div>

        <?php if (!empty($order['note'])): ?><div class="note"><strong>Ghi chú:</strong> <?= e($order['note']) ?></div><?php endif; ?>

        <div class="signatures">
            <div>Người mua<span></span>(Ký, ghi rõ họ tên)</div>
            <div>Người bán<span></span>(Ký, ghi rõ họ tên)</div>
        </div>
    </main>

    <div class="invoice-actions">
        <button class="btn btn-primary" type="button" onclick="window.print()">In hoá đơn</button>
        <a class="btn btn-secondary" href="<?= e(url('admin/orders/detail?id=' . (int) $order['id'])) ?>">← Về chi tiết đơn</a>
    </div>
</body>
</html>
