<section class="admin-panel admin-form-panel">
    <div class="admin-panel-heading"><div><h2><?= e($title) ?></h2><p>Các trường bắt buộc được đánh dấu theo biểu mẫu.</p></div><a href="<?= e(url('admin/' . ($_GET['resource'] ?? ''))) ?>">← Quay lại danh sách</a></div>
    <?php if (empty($databaseError)): ?>
        <form method="post" action="<?= e(url('admin/' . ($_GET['resource'] ?? '') . '/' . ($_GET['task'] ?? '') . (isset($_GET['id']) ? '?id=' . (int) $_GET['id'] : ''))) ?>" class="admin-data-form">
            <?= csrf_field() ?>
            <?php foreach (($definition['fields'] ?? []) as $field): ?>
                <?php $value = $record[$field['name']] ?? ''; ?>
                <?php if ($field['type'] === 'checkbox'): ?>
                    <label class="form-check admin-checkbox"><input class="form-check-input" type="checkbox" name="<?= e($field['name']) ?>" value="1" <?= !empty($value) ? 'checked' : '' ?>><span class="form-check-label"><?= e($field['label']) ?></span></label>
                <?php elseif ($field['type'] === 'textarea'): ?>
                    <label class="form-label" for="field-<?= e($field['name']) ?>"><?= e($field['label']) ?></label>
                    <textarea class="form-control mb-3" id="field-<?= e($field['name']) ?>" name="<?= e($field['name']) ?>" rows="4" <?= !empty($field['required']) ? 'required' : '' ?>><?= e($value) ?></textarea>
                <?php elseif ($field['type'] === 'foreign'): ?>
                    <label class="form-label" for="field-<?= e($field['name']) ?>"><?= e($field['label']) ?></label>
                    <select class="form-select mb-3" id="field-<?= e($field['name']) ?>" name="<?= e($field['name']) ?>">
                        <option value="">Không chọn</option>
                        <?php foreach (($formOptions[$field['table']] ?? []) as $option): ?><option value="<?= (int) $option['id'] ?>" <?= (string) $value === (string) $option['id'] ? 'selected' : '' ?>><?= e($option['name']) ?></option><?php endforeach; ?>
                    </select>
                <?php elseif ($field['type'] === 'select'): ?>
                    <label class="form-label" for="field-<?= e($field['name']) ?>"><?= e($field['label']) ?></label>
                    <select class="form-select mb-3" id="field-<?= e($field['name']) ?>" name="<?= e($field['name']) ?>" required>
                        <?php foreach ($field['options'] as $option => $label): ?><option value="<?= e($option) ?>" <?= (string) $value === (string) $option ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <?php if ($field['type'] === 'datetime-local' && $value): $value = str_replace(' ', 'T', substr($value, 0, 16)); endif; ?>
                    <label class="form-label" for="field-<?= e($field['name']) ?>"><?= e($field['label']) ?></label>
                    <input class="form-control mb-3" id="field-<?= e($field['name']) ?>" type="<?= e($field['type']) ?>" name="<?= e($field['name']) ?>" value="<?= e($value) ?>" <?= !empty($field['required']) ? 'required' : '' ?> <?= $field['type'] === 'number' ? 'step="any" min="0"' : '' ?>>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="admin-form-actions"><button class="btn btn-primary" type="submit">Lưu thay đổi</button><a class="btn btn-outline-secondary" href="<?= e(url('admin/' . ($_GET['resource'] ?? ''))) ?>">Hủy</a></div>
        </form>
    <?php endif; ?>
</section>
