<section class="admin-panel">
    <div class="admin-panel-heading"><div><h2><?= e($resource['title'] ?? 'Danh sách') ?></h2><p>Danh sách dữ liệu hiện có trong cửa hàng.</p></div>
        <?php if (!empty($definition['create'])): ?><a class="btn btn-primary btn-sm" href="<?= e(url('admin/' . ($_GET['resource'] ?? '') . '/create')) ?>">+ Thêm mới</a><?php endif; ?>
    </div>
    <div class="admin-list-search"><input type="search" class="form-control" placeholder="Tìm trong danh sách..." data-admin-search><span><?= count($resource['rows'] ?? []) ?> bản ghi</span></div>
    <?php if (!empty($resource['rows'])): ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><?php foreach (array_keys($resource['rows'][0]) as $column): ?><th><?= e(ucwords(str_replace('_', ' ', $column))) ?></th><?php endforeach; ?><th>Thao tác</th></tr></thead><tbody>
            <?php foreach ($resource['rows'] as $row): ?><tr><?php foreach ($row as $value): ?><td><?= e($value === null ? '—' : $value) ?></td><?php endforeach; ?><td class="admin-row-actions">
                <?php if (!empty($definition['detail'])): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/' . ($_GET['resource'] ?? '') . '/detail?id=' . (int) $row['id'])) ?>">Xem</a><?php endif; ?>
                <?php if (!empty($definition['edit'])): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/' . ($_GET['resource'] ?? '') . '/edit?id=' . (int) $row['id'])) ?>">Sửa</a><?php endif; ?>
                <?php if (!empty($definition['delete'])): ?><form method="post" action="<?= e(url('admin/' . ($_GET['resource'] ?? '') . '/delete')) ?>" data-confirm="Bạn có chắc muốn xóa bản ghi này?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button></form><?php endif; ?>
            </td></tr><?php endforeach; ?>
        </tbody></table></div>
        <p class="small text-muted mb-0">Đang hiển thị tối đa 100 bản ghi gần nhất.</p>
    <?php else: ?><div class="empty-state compact-empty"><div class="empty-icon">⌕</div><h3>Chưa có dữ liệu</h3><p>Dữ liệu mục này sẽ xuất hiện tại đây sau khi được thêm vào cơ sở dữ liệu.</p></div><?php endif; ?>
</section>
