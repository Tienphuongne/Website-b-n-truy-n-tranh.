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
                ['name' => 'cover_upload', 'label' => 'Hoặc tải ảnh bìa lên', 'type' => 'file', 'column' => 'cover_image', 'accept' => 'image/jpeg,image/png,image/webp,image/gif'],
                ['name' => 'genres', 'label' => 'Thể loại', 'type' => 'multiselect', 'options_table' => 'genres'],
                ['name' => 'existing_images', 'label' => 'Ảnh trong thư viện', 'type' => 'images'],
                ['name' => 'gallery_upload', 'label' => 'Tải thêm ảnh vào thư viện', 'type' => 'file-multiple', 'accept' => 'image/jpeg,image/png,image/webp,image/gif'],
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
            'table' => 'orders', 'create' => false, 'edit' => true, 'delete' => false, 'detail' => true,
            'fields' => [['name' => 'status', 'label' => 'Trạng thái đơn hàng', 'type' => 'select', 'options' => ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy']]],
        ],
        'contacts' => [
            'title' => 'Liên hệ', 'query' => 'SELECT id, full_name, email, phone, message, status, created_at FROM contacts ORDER BY created_at DESC LIMIT 100',
            'table' => 'contacts', 'create' => false, 'edit' => true, 'delete' => true,
            'fields' => [['name' => 'status', 'label' => 'Trạng thái liên hệ', 'type' => 'select', 'options' => ['new' => 'Mới', 'processing' => 'Đang xử lý', 'closed' => 'Đã đóng']]],
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
        $dailyRows = $this->execute(
            "SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount), 0) AS revenue
             FROM orders
             WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)"
        )->fetchAll();
        $revenueByDay = [];
        foreach ($dailyRows as $row) {
            $revenueByDay[(string) $row['day']] = (float) $row['revenue'];
        }
        $dailyRevenue = [];
        for ($offset = 6; $offset >= 0; $offset--) {
            $timestamp = strtotime('-' . $offset . ' days');
            $day = date('Y-m-d', $timestamp);
            $dailyRevenue[] = [
                'label' => date('d/m', $timestamp),
                'value' => $revenueByDay[$day] ?? 0,
            ];
        }

        $statusRows = $this->execute(
            'SELECT status, COUNT(*) AS total FROM orders GROUP BY status'
        )->fetchAll();
        $statusCounts = [];
        foreach ($statusRows as $row) {
            $statusCounts[(string) $row['status']] = (int) $row['total'];
        }

        return [
            'products' => (int) $this->execute('SELECT COUNT(*) FROM products')->fetchColumn(),
            'orders' => (int) $this->execute('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'users' => (int) $this->execute("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
            'revenue' => (float) $this->execute(
                "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed'"
            )->fetchColumn(),
            'month_revenue' => (float) $this->execute(
                "SELECT COALESCE(SUM(total_amount), 0) FROM orders
                 WHERE status = 'completed' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
            )->fetchColumn(),
            'daily_revenue' => $dailyRevenue,
            'status_counts' => $statusCounts,
            'recent_orders' => $this->execute(
                'SELECT id, customer_name, total_amount, status, created_at
                 FROM orders ORDER BY created_at DESC LIMIT 8'
            )->fetchAll(),
            'best_sellers' => $this->execute(
                'SELECT name, sold_quantity, sale_price FROM products ORDER BY sold_quantity DESC LIMIT 5'
            )->fetchAll(),
            'low_stock' => $this->execute(
                "SELECT id, name, stock_quantity FROM products
                 WHERE status = 'active' AND stock_quantity <= 5
                 ORDER BY stock_quantity ASC, name ASC LIMIT 5"
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
            'genres' => $this->execute('SELECT id, name FROM genres ORDER BY name')->fetchAll(),
        ];
    }

    public function getProductGenreIds($productId)
    {
        return array_map('intval', $this->execute(
            'SELECT genre_id FROM product_genres WHERE product_id = :product_id',
            ['product_id' => (int) $productId]
        )->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getProductImages($productId)
    {
        return $this->execute(
            'SELECT id, image_path FROM product_images WHERE product_id = :product_id ORDER BY sort_order, id',
            ['product_id' => (int) $productId]
        )->fetchAll();
    }

    public function getOrderDetail($orderId)
    {
        $order = $this->execute(
            'SELECT o.*, c.code AS coupon_code
             FROM orders o
             LEFT JOIN coupons c ON c.id = o.coupon_id
             WHERE o.id = :id LIMIT 1',
            ['id' => (int) $orderId]
        )->fetch();

        if (!$order) {
            return null;
        }

        return [
            'order' => $order,
            'items' => $this->execute(
                'SELECT product_id, product_name, unit_price, quantity
                 FROM order_details WHERE order_id = :order_id ORDER BY id',
                ['order_id' => (int) $orderId]
            )->fetchAll(),
        ];
    }

    public function getReport($from, $to)
    {
        $toExclusive = date('Y-m-d', strtotime($to . ' +1 day'));
        $params = ['from' => $from . ' 00:00:00', 'to' => $toExclusive . ' 00:00:00'];
        $range = 'o.created_at >= :from AND o.created_at < :to';

        $summary = $this->execute(
            "SELECT COUNT(*) AS total_orders,
                    COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN o.total_amount ELSE 0 END), 0) AS gross_revenue,
                    COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END), 0) AS completed_revenue,
                    COALESCE(AVG(CASE WHEN o.status <> 'cancelled' THEN o.total_amount END), 0) AS average_order,
                    COUNT(CASE WHEN o.status = 'pending' THEN 1 END) AS pending_orders
             FROM orders o WHERE $range",
            $params
        )->fetch();

        $statusRows = $this->execute(
            "SELECT o.status, COUNT(*) AS total, COALESCE(SUM(o.total_amount), 0) AS revenue
             FROM orders o WHERE $range GROUP BY o.status",
            $params
        )->fetchAll();

        $dayCount = (int) floor((strtotime($to) - strtotime($from)) / 86400) + 1;
        $granularity = $dayCount > 62 ? 'month' : 'day';

        if ($granularity === 'month') {
            $rows = $this->execute(
                "SELECT DATE_FORMAT(o.created_at, '%Y-%m') AS period, COUNT(*) AS total_orders,
                        COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN o.total_amount ELSE 0 END), 0) AS revenue
                 FROM orders o WHERE $range
                 GROUP BY period ORDER BY period",
                $params
            )->fetchAll();
        } else {
            $rows = $this->execute(
                "SELECT DATE(o.created_at) AS period, COUNT(*) AS total_orders,
                        COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN o.total_amount ELSE 0 END), 0) AS revenue
                 FROM orders o WHERE $range
                 GROUP BY DATE(o.created_at) ORDER BY period",
                $params
            )->fetchAll();
        }

        $periodMap = [];
        foreach ($rows as $row) {
            $periodMap[(string) $row['period']] = $row;
        }

        $daily = [];
        $guard = 0;
        if ($granularity === 'month') {
            $cursor = strtotime(date('Y-m-01', strtotime($from)));
            $last = strtotime(date('Y-m-01', strtotime($to)));
            while ($cursor !== false && $last !== false && $cursor <= $last && $guard < 600) {
                $period = date('Y-m', $cursor);
                $daily[] = [
                    'day' => $period . '-01',
                    'period' => $period,
                    'total_orders' => (int) ($periodMap[$period]['total_orders'] ?? 0),
                    'revenue' => (float) ($periodMap[$period]['revenue'] ?? 0),
                ];
                $cursor = strtotime('+1 month', $cursor);
                $guard++;
            }
        } else {
            $cursor = strtotime($from);
            $last = strtotime($to);
            while ($cursor !== false && $last !== false && $cursor <= $last && $guard < 62) {
                $period = date('Y-m-d', $cursor);
                $daily[] = [
                    'day' => $period,
                    'period' => $period,
                    'total_orders' => (int) ($periodMap[$period]['total_orders'] ?? 0),
                    'revenue' => (float) ($periodMap[$period]['revenue'] ?? 0),
                ];
                $cursor = strtotime('+1 day', $cursor);
                $guard++;
            }
        }

        $topProducts = $this->execute(
            "SELECT od.product_name, SUM(od.quantity) AS quantity,
                    SUM(od.unit_price * od.quantity) AS revenue
             FROM order_details od
             INNER JOIN orders o ON o.id = od.order_id
             WHERE $range AND o.status <> 'cancelled'
             GROUP BY od.product_name
             ORDER BY quantity DESC, revenue DESC LIMIT 8",
            $params
        )->fetchAll();

        $payments = $this->execute(
            "SELECT o.payment_method, COUNT(*) AS total, COALESCE(SUM(o.total_amount), 0) AS revenue
             FROM orders o WHERE $range GROUP BY o.payment_method",
            $params
        )->fetchAll();

        return [
            'from' => $from,
            'to' => $to,
            'granularity' => $granularity,
            'summary' => $summary,
            'statuses' => $statusRows,
            'daily' => $daily,
            'top_products' => $topProducts,
            'payments' => $payments,
        ];
    }

    public function saveRecord($key, $id, array $input)
    {
        $definition = $this->getDefinition($key);
        if (!$definition || ($id ? empty($definition['edit']) : empty($definition['create']))) {
            throw new DomainException('Thao tác này không được hỗ trợ.');
        }

        $values = [];
        $specialFields = [];
        foreach ($definition['fields'] as $field) {
            if (in_array($field['type'], ['file', 'file-multiple', 'multiselect', 'images'], true)) {
                $specialFields[] = $field;
                continue;
            }

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

        $galleryPaths = [];
        $uploadedPaths = [];
        $filesToDelete = [];
        $genreIds = null;
        $removeImageIds = [];
        $coverColumn = null;
        $oldCoverPath = null;

        try {
            foreach ($specialFields as $field) {
                if ($field['type'] === 'file') {
                    if (!empty($field['column'])) {
                        $coverColumn = $field['column'];
                    }
                    $file = $_FILES[$field['name']] ?? null;
                    if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $uploadedPath = save_uploaded_image($file, 'products');
                        if ($uploadedPath !== null) {
                            $values[$field['column']] = $uploadedPath;
                            $uploadedPaths[] = $uploadedPath;
                        }
                    }
                    continue;
                }

                if ($field['type'] === 'file-multiple') {
                    $files = $_FILES[$field['name']] ?? null;
                    if (!is_array($files) || !is_array($files['name'] ?? null)) {
                        continue;
                    }
                    foreach ($files['name'] as $index => $fileName) {
                        $upload = [
                            'name' => $fileName,
                            'type' => $files['type'][$index] ?? '',
                            'tmp_name' => $files['tmp_name'][$index] ?? '',
                            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                            'size' => $files['size'][$index] ?? 0,
                        ];
                        if ((int) $upload['error'] === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        $uploadedPath = save_uploaded_image($upload, 'products');
                        if ($uploadedPath !== null) {
                            $galleryPaths[] = $uploadedPath;
                            $uploadedPaths[] = $uploadedPath;
                        }
                    }
                    continue;
                }

                if ($field['type'] === 'multiselect') {
                    $rawValues = $input[$field['name']] ?? [];
                    if (!is_array($rawValues)) {
                        $rawValues = [];
                    }
                    $genreIds = [];
                    foreach ($rawValues as $rawValue) {
                        if (!is_scalar($rawValue) || !ctype_digit((string) $rawValue)) {
                            throw new DomainException('Giá trị "' . $field['label'] . '" không hợp lệ.');
                        }
                        $genreIds[] = (int) $rawValue;
                    }
                    $genreIds = array_values(array_unique($genreIds));
                    if ($genreIds) {
                        $table = (string) ($field['options_table'] ?? '');
                        if (!in_array($table, ['genres'], true)) {
                            throw new DomainException('Tham chiếu dữ liệu không hợp lệ.');
                        }
                        $placeholders = implode(',', array_fill(0, count($genreIds), '?'));
                        $found = (int) $this->execute(
                            'SELECT COUNT(*) FROM ' . $table . ' WHERE id IN (' . $placeholders . ')',
                            $genreIds
                        )->fetchColumn();
                        if ($found !== count($genreIds)) {
                            throw new DomainException('Có "' . $field['label'] . '" không tồn tại.');
                        }
                    }
                    continue;
                }

                if ($field['type'] === 'images') {
                    $rawIds = $input['remove_images'] ?? [];
                    if (is_array($rawIds)) {
                        foreach ($rawIds as $rawId) {
                            if (is_scalar($rawId) && ctype_digit((string) $rawId)) {
                                $removeImageIds[] = (int) $rawId;
                            }
                        }
                    }
                }
            }

            $isProduct = $key === 'products';
            $productId = $id ? (int) $id : null;

            if ($isProduct && $productId && $coverColumn) {
                $oldCoverPath = $this->execute(
                    'SELECT ' . $coverColumn . ' FROM products WHERE id = :id LIMIT 1',
                    ['id' => $productId]
                )->fetchColumn();
            }

            if ($isProduct) {
                $this->pdo->beginTransaction();
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
                $productId = (int) $this->pdo->lastInsertId();
            }

            if ($isProduct && $productId) {
                if ($genreIds !== null) {
                    $this->execute(
                        'DELETE FROM product_genres WHERE product_id = :product_id',
                        ['product_id' => $productId]
                    );
                    foreach ($genreIds as $genreId) {
                        $this->execute(
                            'INSERT INTO product_genres (product_id, genre_id) VALUES (:product_id, :genre_id)',
                            ['product_id' => $productId, 'genre_id' => $genreId]
                        );
                    }
                }

                foreach ($removeImageIds as $imageId) {
                    $image = $this->execute(
                        'SELECT image_path FROM product_images WHERE id = :id AND product_id = :product_id LIMIT 1',
                        ['id' => $imageId, 'product_id' => $productId]
                    )->fetch();
                    if ($image) {
                        $this->execute(
                            'DELETE FROM product_images WHERE id = :id',
                            ['id' => $imageId]
                        );
                        $filesToDelete[] = $image['image_path'];
                    }
                }

                foreach ($galleryPaths as $sortOrder => $imagePath) {
                    $this->execute(
                        'INSERT INTO product_images (product_id, image_path, sort_order)
                         VALUES (:product_id, :image_path, :sort_order)',
                        [
                            'product_id' => $productId,
                            'image_path' => $imagePath,
                            'sort_order' => $sortOrder,
                        ]
                    );
                }
            }

            if ($isProduct) {
                $this->pdo->commit();
            }

            if ($isProduct) {
                if ($oldCoverPath && array_key_exists($coverColumn, $values) && $values[$coverColumn] !== $oldCoverPath) {
                    delete_upload_file($oldCoverPath);
                }
                foreach ($filesToDelete as $filePath) {
                    delete_upload_file($filePath);
                }
            }
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            foreach ($uploadedPaths as $uploadedPath) {
                delete_upload_file($uploadedPath);
            }
            throw $exception;
        }
    }

    public function deleteRecord($key, $id)
    {
        $definition = $this->getDefinition($key);
        if (!$definition || empty($definition['delete']) || (int) $id < 1) {
            throw new DomainException('Không thể xóa mục dữ liệu này.');
        }

        $uploadedFiles = [];
        if ($definition['table'] === 'products') {
            $cover = $this->execute(
                'SELECT cover_image FROM products WHERE id = :id LIMIT 1',
                ['id' => (int) $id]
            )->fetchColumn();
            if ($cover) {
                $uploadedFiles[] = (string) $cover;
            }
            $gallery = $this->execute(
                'SELECT image_path FROM product_images WHERE product_id = :product_id',
                ['product_id' => (int) $id]
            )->fetchAll(PDO::FETCH_COLUMN);
            $uploadedFiles = array_merge($uploadedFiles, $gallery);
        }

        $this->execute(
            'DELETE FROM ' . $definition['table'] . ' WHERE id = :id',
            ['id' => (int) $id]
        );

        foreach ($uploadedFiles as $uploadedFile) {
            delete_upload_file($uploadedFile);
        }
    }
}
