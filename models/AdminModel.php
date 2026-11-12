<?php

class AdminModel extends BaseModel
{
    private static $resources = [
        'products' => [
            'title' => 'Sản phẩm',
            'query' => 'SELECT id, name, sale_price, stock_quantity, status FROM products ORDER BY created_at DESC LIMIT 100',
            'table' => 'products', 'create' => true, 'edit' => true, 'delete' => true,
            'fields' => [
                ['name' => 'name', 'label' => 'Tên truyện', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'required' => true],
                ['name' => 'category_id', 'label' => 'Danh mục', 'type' => 'foreign', 'table' => 'categories'],
                ['name' => 'author_id', 'label' => 'Tác giả', 'type' => 'foreign', 'table' => 'authors'],
                ['name' => 'publisher_id', 'label' => 'Nhà xuất bản', 'type' => 'foreign', 'table' => 'publishers'],
                ['name' => 'short_description', 'label' => 'Mô tả ngắn', 'type' => 'textarea'],
                ['name' => 'description', 'label' => 'Mô tả chi tiết', 'type' => 'textarea'],
                ['name' => 'cover_image', 'label' => 'Đường dẫn ảnh bìa', 'type' => 'text'],
                ['name' => 'sale_price', 'label' => 'Giá bán', 'type' => 'number', 'required' => true],
                ['name' => 'original_price', 'label' => 'Giá gốc', 'type' => 'number'],
                ['name' => 'stock_quantity', 'label' => 'Tồn kho', 'type' => 'number', 'required' => true],
                ['name' => 'sold_quantity', 'label' => 'Đã bán', 'type' => 'number'],
                ['name' => 'is_featured', 'label' => 'Sản phẩm nổi bật', 'type' => 'checkbox'],
                ['name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'options' => ['active' => 'Đang bán', 'draft' => 'Bản nháp', 'archived' => 'Lưu trữ']],
            ],
        ],
        'categories' => [
            'title' => 'Danh mục', 'query' => 'SELECT id, name, is_active FROM categories ORDER BY name LIMIT 100',
            'table' => 'categories', 'create' => true, 'edit' => true, 'delete' => true,
            'fields' => [
                ['name' => 'name', 'label' => 'Tên danh mục', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Mô tả', 'type' => 'textarea'],
                ['name' => 'is_active', 'label' => 'Đang hoạt động', 'type' => 'checkbox'],
            ],
        ],
        'genres' => [
            'title' => 'Thể loại', 'query' => 'SELECT id, name FROM genres ORDER BY name LIMIT 100',
            'table' => 'genres', 'create' => true, 'edit' => true, 'delete' => true,
            'fields' => [
                ['name' => 'name', 'label' => 'Tên thể loại', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'required' => true],
            ],
        ],
        'authors' => [
            'title' => 'Tác giả', 'query' => 'SELECT id, name FROM authors ORDER BY name LIMIT 100',
            'table' => 'authors', 'create' => true, 'edit' => true, 'delete' => true,
            'fields' => [
                ['name' => 'name', 'label' => 'Tên tác giả', 'type' => 'text', 'required' => true],
                ['name' => 'biography', 'label' => 'Tiểu sử', 'type' => 'textarea'],
            ],
        ],
        'publishers' => [
            'title' => 'Nhà xuất bản', 'query' => 'SELECT id, name FROM publishers ORDER BY name LIMIT 100',
            'table' => 'publishers', 'create' => true, 'edit' => true, 'delete' => true,
            'fields' => [['name' => 'name', 'label' => 'Tên nhà xuất bản', 'type' => 'text', 'required' => true]],
        ],
        'orders' => [
            'title' => 'Đơn hàng', 'query' => 'SELECT id, customer_name, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 100',
            'table' => 'orders', 'create' => false, 'edit' => true, 'delete' => false,
            'fields' => [['name' => 'status', 'label' => 'Trạng thái đơn hàng', 'type' => 'select', 'options' => ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy']]],
        ],
        'users' => [
            'title' => 'Người dùng', 'query' => 'SELECT id, full_name, email, phone, role, created_at FROM users ORDER BY created_at DESC LIMIT 100',
            'table' => 'users', 'create' => false, 'edit' => true, 'delete' => false,
            'fields' => [['name' => 'role', 'label' => 'Vai trò', 'type' => 'select', 'options' => ['customer' => 'Khách hàng', 'admin' => 'Quản trị viên']]],
        ],
        'reviews' => [
            'title' => 'Đánh giá', 'query' => 'SELECT id, product_id, user_id, rating, status, created_at FROM reviews ORDER BY created_at DESC LIMIT 100',
            'table' => 'reviews', 'create' => false, 'edit' => true, 'delete' => true,
            'fields' => [['name' => 'status', 'label' => 'Trạng thái đánh giá', 'type' => 'select', 'options' => ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối']]],
        ],
        'coupons' => [
            'title' => 'Mã giảm giá', 'query' => 'SELECT id, code, discount_type, discount_value, starts_at, ends_at, is_active FROM coupons ORDER BY created_at DESC LIMIT 100',
            'table' => 'coupons', 'create' => true, 'edit' => true, 'delete' => true,
            'fields' => [
                ['name' => 'code', 'label' => 'Mã giảm giá', 'type' => 'text', 'required' => true],
                ['name' => 'discount_type', 'label' => 'Kiểu giảm giá', 'type' => 'select', 'options' => ['fixed' => 'Số tiền cố định', 'percent' => 'Phần trăm']],
                ['name' => 'discount_value', 'label' => 'Giá trị giảm', 'type' => 'number', 'required' => true],
                ['name' => 'minimum_order', 'label' => 'Giá trị đơn tối thiểu', 'type' => 'number'],
                ['name' => 'starts_at', 'label' => 'Bắt đầu', 'type' => 'datetime-local'],
                ['name' => 'ends_at', 'label' => 'Kết thúc', 'type' => 'datetime-local'],
                ['name' => 'usage_limit', 'label' => 'Giới hạn lượt dùng', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'Đang hoạt động', 'type' => 'checkbox'],
            ],
        ],
        'reports' => ['title' => 'Thống kê và báo cáo', 'query' => 'SELECT id, customer_name, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 100'],
    ];

    public function dashboard()
    {
        return [
            'products' => (int) $this->execute('SELECT COUNT(*) FROM products')->fetchColumn(),
            'orders' => (int) $this->execute('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'users' => (int) $this->execute("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
            'revenue' => (float) $this->execute(
                "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed'"
            )->fetchColumn(),
            'recent_orders' => $this->execute(
                'SELECT id, customer_name, total_amount, status, created_at
                 FROM orders ORDER BY created_at DESC LIMIT 8'
            )->fetchAll(),
            'best_sellers' => $this->execute(
                'SELECT name, sold_quantity, sale_price FROM products ORDER BY sold_quantity DESC LIMIT 5'
            )->fetchAll(),
        ];
    }

    public static function resources()
    {
        return array_map(function ($key, $resource) {
            return ['key' => $key, 'title' => $resource['title']];
        }, array_keys(self::$resources), self::$resources);
    }

    public function getResource($key)
    {
        if (!isset(self::$resources[$key])) {
            return null;
        }

        return [
            'title' => self::$resources[$key]['title'],
            'rows' => $this->execute(self::$resources[$key]['query'])->fetchAll(),
        ];
    }

    public function getDefinition($key)
    {
        return self::$resources[$key] ?? null;
    }

    public function getRecord($key, $id)
    {
        $definition = $this->getDefinition($key);
        if (!$definition || empty($definition['edit'])) {
            return null;
        }

        return $this->execute(
            'SELECT * FROM ' . $definition['table'] . ' WHERE id = :id LIMIT 1',
            ['id' => (int) $id]
        )->fetch();
    }

    public function getForeignOptions()
    {
        return [
            'categories' => $this->execute('SELECT id, name FROM categories ORDER BY name')->fetchAll(),
            'authors' => $this->execute('SELECT id, name FROM authors ORDER BY name')->fetchAll(),
            'publishers' => $this->execute('SELECT id, name FROM publishers ORDER BY name')->fetchAll(),
        ];
    }

    public function saveRecord($key, $id, array $input)
    {
        $definition = $this->getDefinition($key);
        if (!$definition || ($id ? empty($definition['edit']) : empty($definition['create']))) {
            throw new DomainException('Thao tác này không được hỗ trợ.');
        }

        $values = [];
        foreach ($definition['fields'] as $field) {
            $name = $field['name'];
            $rawValue = $input[$name] ?? '';
            $value = $field['type'] === 'checkbox'
                ? (!empty($rawValue) ? 1 : 0)
                : (is_scalar($rawValue) ? trim((string) $rawValue) : '');
            if (!empty($field['required']) && $value === '') {
                throw new DomainException('Vui lòng nhập trường "' . $field['label'] . '".');
            }
            if ($field['type'] === 'number' && $value !== '' && !is_numeric($value)) {
                throw new DomainException('Trường "' . $field['label'] . '" cần là một số hợp lệ.');
            }
            if ($field['type'] === 'number' && $value !== '' && (float) $value < 0) {
                throw new DomainException('Trường "' . $field['label'] . '" không được âm.');
            }
            if ($field['type'] === 'select' && !array_key_exists($value, $field['options'])) {
                throw new DomainException('Giá trị "' . $field['label'] . '" không hợp lệ.');
            }
            if ($field['type'] === 'datetime-local') {
                $value = $value === '' ? null : str_replace('T', ' ', $value) . ':00';
            }
            if ($field['type'] === 'number' && $value === '') {
                $value = null;
            }
            if ($field['type'] === 'foreign') {
                if ($value === '') {
                    $value = null;
                } elseif (!ctype_digit($value)) {
                    throw new DomainException('Vui lòng chọn "' . $field['label'] . '".');
                } else {
                    $table = $field['table'];
                    if (!in_array($table, ['categories', 'authors', 'publishers'], true)) {
                        throw new DomainException('Tham chiếu dữ liệu không hợp lệ.');
                    }
                    $exists = $this->execute(
                        'SELECT 1 FROM ' . $table . ' WHERE id = :id LIMIT 1',
                        ['id' => (int) $value]
                    )->fetchColumn();
                    if (!$exists) {
                        throw new DomainException('Không tìm thấy "' . $field['label'] . '".');
                    }
                    $value = (int) $value;
                }
            }
            $values[$name] = $value;
        }

        if ($id) {
            $assignments = [];
            foreach (array_keys($values) as $name) {
                $assignments[] = "$name = :$name";
            }
            $values['id'] = (int) $id;
            $this->execute(
                'UPDATE ' . $definition['table'] . ' SET ' . implode(', ', $assignments) . ' WHERE id = :id',
                $values
            );
        } else {
            $columns = array_keys($values);
            $placeholders = array_map(function ($name) {
                return ':' . $name;
            }, $columns);
            $this->execute(
                'INSERT INTO ' . $definition['table'] . ' (' . implode(', ', $columns) . ')
                 VALUES (' . implode(', ', $placeholders) . ')',
                $values
            );
        }
    }

    public function deleteRecord($key, $id)
    {
        $definition = $this->getDefinition($key);
        if (!$definition || empty($definition['delete']) || (int) $id < 1) {
            throw new DomainException('Không thể xóa mục dữ liệu này.');
        }

        $this->execute(
            'DELETE FROM ' . $definition['table'] . ' WHERE id = :id',
            ['id' => (int) $id]
        );
    }
}
