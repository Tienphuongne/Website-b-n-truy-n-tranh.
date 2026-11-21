<?php
$resourceKey = (string) ($_GET['resource'] ?? '');
$columnLabels = [
    'id' => 'Mã',
    'name' => 'Tên',
    'sale_price' => 'Giá bán',
    'stock_quantity' => 'Tồn kho',
    'status' => 'Trạng thái',
    'is_active' => 'Trạng thái',
    'customer_name' => 'Khách hàng',
    'total_amount' => 'Tổng tiền',
    'created_at' => 'Ngày tạo',
    'full_name' => 'Họ và tên',
    'email' => 'Email',
    'phone' => 'Số điện thoại',
    'role' => 'Vai trò',
    'product_id' => 'Mã sản phẩm',
    'user_id' => 'Mã người dùng',
    'order_id' => 'Mã đơn hàng',
    'rating' => 'Điểm đánh giá',
    'reason' => 'Lý do trả hàng',
    'code' => 'Mã giảm giá',
    'discount_type' => 'Kiểu giảm giá',
    'discount_value' => 'Giá trị giảm',
    'starts_at' => 'Ngày bắt đầu',
    'ends_at' => 'Ngày kết thúc',
    'usage_limit' => 'Giới hạn lượt dùng',
    'used_count' => 'Số lượt đã dùng',
    'available_count' => 'Số lượt còn lại',
    'message' => 'Nội dung',
];
$valueLabels = [
    'products' => [
        'status' => ['active' => 'Đang bán', 'draft' => 'Bản nháp', 'archived' => 'Ngừng kinh doanh'],
    ],
    'categories' => [
        'is_active' => ['0' => 'Đã tắt', '1' => 'Đang hoạt động'],
    ],
    'orders' => [
        'status' => [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
        ],
    ],
    'returns' => [
        'status' => ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'],
        'reason' => [
            'defective' => 'Hàng bị lỗi',
            'damaged' => 'Hàng bị hỏng',
            'wrong_item' => 'Giao sai hàng',
            'missing_item' => 'Thiếu sản phẩm',
            'other' => 'Lý do khác',
        ],
    ],
    'contacts' => [
        'status' => ['new' => 'Mới', 'processing' => 'Đang xử lý', 'closed' => 'Đã đóng'],
    ],
    'users' => [
        'role' => ['customer' => 'Khách hàng', 'admin' => 'Quản trị viên'],
    ],
    'reviews' => [
        'status' => ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'],
    ],
    'coupons' => [
        'discount_type' => ['fixed' => 'Số tiền cố định', 'percent' => 'Phần trăm'],
        'is_active' => ['0' => 'Đã tắt', '1' => 'Đang hoạt động'],
    ],
];
$formatCurrency = static function ($value) {
    return number_format((float) $value, 0, ',', '.') . '₫';
};
$formatPercent = static function ($value) {
    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',') . '%';
};
?>
<section class="admin-panel">
    <div class="admin-panel-heading"><div><h2><?= e($resource['title'] ?? 'Danh sách') ?></h2><p>Danh sách dữ liệu hiện có trong cửa hàng.</p></div>
        <?php if (!empty($definition['create'])): ?><a class="btn btn-primary btn-sm" href="<?= e(url('admin/' . ($_GET['resource'] ?? '') . '/create')) ?>">+ Thêm mới</a><?php endif; ?>
    </div>
    <div class="admin-list-search"><input type="search" class="form-control" placeholder="Tìm trong danh sách..." data-admin-search><span><?= count($resource['rows'] ?? []) ?> bản ghi</span></div>
    <?php if (!empty($resource['rows'])): ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><?php foreach (array_keys($resource['rows'][0]) as $column): ?><th><?= e($columnLabels[$column] ?? $column) ?></th><?php endforeach; ?><th>Thao tác</th></tr></thead><tbody>
            <?php foreach ($resource['rows'] as $row): ?><tr><?php foreach ($row as $column => $value): ?><td><?php
                if ($resourceKey === 'coupons' && $column === 'available_count' && $value === null) {
                    $displayValue = 'Không giới hạn';
                } elseif ($value === null) {
                    $displayValue = '—';
                } elseif ($resourceKey === 'coupons' && $column === 'discount_value') {
                    $displayValue = $row['discount_type'] === 'fixed' ? $formatCurrency($value) : $formatPercent($value);
                } elseif (in_array($column, ['sale_price', 'total_amount'], true)) {
                    $displayValue = $formatCurrency($value);
                } else {
                    $displayValue = $valueLabels[$resourceKey][$column][(string) $value] ?? $value;
                }
                echo e($displayValue);
                ?></td><?php endforeach; ?><td class="admin-row-actions">
                <?php
                $couponExpired = $resourceKey === 'coupons'
                    && !empty($row['ends_at'])
                    && strtotime((string) $row['ends_at']) < time();
                ?>
                <?php if ($couponExpired): ?>
                    <span class="badge bg-secondary">Ngừng hoạt động</span>
                <?php else: ?>
                <?php if (!empty($definition['detail'])): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/' . ($_GET['resource'] ?? '') . '/detail?id=' . (int) $row['id'])) ?>">Xem</a><?php endif; ?>
                <?php if (!empty($definition['edit'])): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/' . ($_GET['resource'] ?? '') . '/edit?id=' . (int) $row['id'])) ?>">Sửa</a><?php endif; ?>
                <?php if (!empty($definition['delete'])): ?><form method="post" action="<?= e(url('admin/' . ($_GET['resource'] ?? '') . '/delete')) ?>" data-confirm="Bạn có chắc muốn xóa bản ghi này?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button></form><?php endif; ?>
                <?php endif; ?>
            </td></tr><?php endforeach; ?>
        </tbody></table></div>
        <p class="small text-muted mb-0">Đang hiển thị tối đa 100 bản ghi gần nhất.</p>
    <?php else: ?><div class="empty-state compact-empty"><div class="empty-icon">⌕</div><h3>Chưa có dữ liệu</h3><p>Dữ liệu mục này sẽ xuất hiện tại đây sau khi được thêm vào cơ sở dữ liệu.</p></div><?php endif; ?>
</section>
