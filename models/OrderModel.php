<?php

class OrderModel extends BaseModel
{
    public static function returnReasons()
    {
        return [
            'defective' => 'Hàng bị lỗi',
            'damaged' => 'Hàng bị hỏng',
            'wrong_item' => 'Giao sai hàng',
            'missing_item' => 'Thiếu sản phẩm',
            'other' => 'Lý do khác',
        ];
    }

    public function createOrder(array $customer, array $cart, $paymentMethod, $shippingFee, $userId = null, $couponId = null, $shippingMethod = 'standard')
    {
        if (!in_array($shippingMethod, ['standard', 'express'], true)) {
            throw new DomainException('Phương thức vận chuyển không hợp lệ.');
        }
        $this->pdo->beginTransaction();

        try {
            $subtotal = 0;
            foreach ($cart as $item) {
                $subtotal += (float) $item['sale_price'] * (int) $item['quantity'];
            }
            $discount = 0;
            if ($couponId) {
                $coupon = $this->execute(
                    "SELECT * FROM coupons
                     WHERE id = :id
                       AND is_active = 1
                       AND (starts_at IS NULL OR starts_at <= NOW())
                       AND (ends_at IS NULL OR ends_at >= NOW())
                       AND (usage_limit IS NULL OR used_count < usage_limit)
                     FOR UPDATE",
                    ['id' => (int) $couponId]
                )->fetch();
                if (
                    !$coupon
                    || ($coupon['starts_at'] && strtotime($coupon['starts_at']) > time())
                    || ($coupon['ends_at'] && strtotime($coupon['ends_at']) < time())
                    || ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit'])
                    || $subtotal < (float) $coupon['minimum_order']
                ) {
                    throw new DomainException('Mã giảm giá không còn hiệu lực hoặc không đủ điều kiện.');
                }
                $rawDiscount = $coupon['discount_type'] === 'percent'
                    ? $subtotal * (float) $coupon['discount_value'] / 100
                    : (float) $coupon['discount_value'];
                $discount = min($subtotal, max(0, $rawDiscount));
            }

            $this->execute(
                "INSERT INTO orders
                 (user_id, customer_name, phone, email, city, district, address, note, payment_method, shipping_method, status, shipping_fee, coupon_id, discount_amount, created_at)
                 VALUES (:user_id, :name, :phone, :email, :city, :district, :address, :note, :payment_method, :shipping_method, 'pending', :shipping_fee, :coupon_id, :discount_amount, NOW())",
                [
                    'user_id' => $userId ? (int) $userId : null,
                    'name' => $customer['full_name'],
                    'phone' => $customer['phone'],
                    'email' => $customer['email'],
                    'city' => $customer['city'],
                    'district' => $customer['district'],
                    'address' => $customer['address'],
                    'note' => $customer['note'],
                    'payment_method' => $paymentMethod,
                    'shipping_method' => $shippingMethod,
                    'shipping_fee' => $shippingFee,
                    'coupon_id' => $couponId ? (int) $couponId : null,
                    'discount_amount' => $discount,
                ]
            );
            $orderId = (int) $this->pdo->lastInsertId();
            $details = $this->pdo->prepare(
                'INSERT INTO order_details (order_id, product_id, product_name, unit_price, quantity)
                 VALUES (:order_id, :product_id, :product_name, :unit_price, :quantity)'
            );

            foreach ($cart as $item) {
                if ((int) $item['stock_quantity'] < (int) $item['quantity']) {
                    throw new DomainException('Số lượng trong kho không đủ cho sản phẩm: ' . $item['name']);
                }

                $details->execute([
                    'order_id' => $orderId,
                    'product_id' => (int) $item['id'],
                    'product_name' => $item['name'],
                    'unit_price' => $item['sale_price'],
                    'quantity' => (int) $item['quantity'],
                ]);
                $stockUpdate = $this->execute(
                    'UPDATE products SET stock_quantity = stock_quantity - :decrease,
                     sold_quantity = sold_quantity + :increase
                     WHERE id = :id AND stock_quantity >= :minimum',
                    [
                        'decrease' => (int) $item['quantity'],
                        'increase' => (int) $item['quantity'],
                        'minimum' => (int) $item['quantity'],
                        'id' => (int) $item['id'],
                    ]
                );
                if ($stockUpdate->rowCount() === 0) {
                    throw new DomainException('Số lượng trong kho không đủ cho sản phẩm: ' . $item['name']);
                }
            }

            $total = $subtotal + $shippingFee - $discount;
            $this->execute(
                'UPDATE orders SET total_amount = :total WHERE id = :id',
                ['total' => $total, 'id' => $orderId]
            );
            $this->execute(
                "INSERT INTO payments (order_id, method, amount, status, created_at)
                 VALUES (:order_id, :method, :amount, 'pending', NOW())",
                [
                    'order_id' => $orderId,
                    'method' => $paymentMethod,
                    'amount' => $total,
                ]
            );
            if ($couponId) {
                $this->execute(
                    'UPDATE coupons SET used_count = used_count + 1 WHERE id = :id',
                    ['id' => (int) $couponId]
                );
            }
            $this->pdo->commit();

            return $orderId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function updateStatus($orderId, $status)
    {
        $allowed = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            throw new DomainException('Trạng thái đơn hàng không hợp lệ.');
        }

        $this->pdo->beginTransaction();

        try {
            $order = $this->execute(
                'SELECT id, status, coupon_id FROM orders WHERE id = :id FOR UPDATE',
                ['id' => (int) $orderId]
            )->fetch();

            if (!$order) {
                throw new DomainException('Không tìm thấy đơn hàng.');
            }
            if ($order['status'] === $status) {
                $this->pdo->commit();

                return;
            }
            if ($order['status'] === 'cancelled') {
                throw new DomainException('Đơn hàng đã hủy không thể chuyển sang trạng thái khác.');
            }

            $this->execute(
                'UPDATE orders SET status = :status WHERE id = :id',
                ['status' => $status, 'id' => (int) $orderId]
            );

            if ($status === 'completed') {
                $this->execute(
                    "UPDATE payments SET status = 'paid', paid_at = NOW()
                     WHERE order_id = :id AND status IN ('pending', 'failed')",
                    ['id' => (int) $orderId]
                );
            }

            if ($status === 'cancelled') {
                $details = $this->execute(
                    'SELECT product_id, quantity FROM order_details
                     WHERE order_id = :id AND product_id IS NOT NULL',
                    ['id' => (int) $orderId]
                )->fetchAll();

                foreach ($details as $detail) {
                    $this->execute(
                        'UPDATE products
                         SET stock_quantity = stock_quantity + :increase,
                             sold_quantity = GREATEST(sold_quantity - :decrease, 0)
                         WHERE id = :id',
                        [
                            'increase' => (int) $detail['quantity'],
                            'decrease' => (int) $detail['quantity'],
                            'id' => (int) $detail['product_id'],
                        ]
                    );
                }

                $this->execute(
                    "UPDATE payments SET status = IF(status = 'paid', 'refunded', 'failed')
                     WHERE order_id = :id",
                    ['id' => (int) $orderId]
                );

                if (!empty($order['coupon_id'])) {
                    $this->execute(
                        'UPDATE coupons SET used_count = GREATEST(used_count - 1, 0) WHERE id = :id',
                        ['id' => (int) $order['coupon_id']]
                    );
                }
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Tra cứu đơn cho khách vãng lai: cần mã đơn VÀ email hoặc số điện thoại
     * trùng với thông tin đã đặt. Chỉ trả về các trường cần thiết cho hỗ trợ.
     */
    public function findForSupport($orderId, $email = '', $phone = '')
    {
        $orderId = (int) $orderId;
        $email = trim((string) $email);
        $phone = preg_replace('/\D+/', '', (string) $phone) ?: '';
        if ($orderId <= 0 || ($email === '' && $phone === '')) {
            return null;
        }

        $matches = [];
        $params = ['id' => $orderId];
        if ($email !== '') {
            $matches[] = 'LOWER(o.email) = :email';
            $params['email'] = mb_strtolower($email);
        }
        if ($phone !== '') {
            $matches[] = "REPLACE(REPLACE(REPLACE(o.phone, ' ', ''), '-', ''), '.', '') = :phone";
            $params['phone'] = $phone;
        }

        $order = $this->execute(
            "SELECT o.id, o.customer_name, o.status, o.created_at, o.total_amount,
                    o.payment_method, o.shipping_fee, o.discount_amount, o.city,
                    (SELECT COUNT(*) FROM order_details od WHERE od.order_id = o.id) AS item_count
             FROM orders o
             WHERE o.id = :id AND (" . implode(' OR ', $matches) . ")
             LIMIT 1",
            $params
        )->fetch();

        if (!$order) {
            return null;
        }

        $items = $this->execute(
            'SELECT product_name, unit_price, quantity FROM order_details WHERE order_id = :id ORDER BY id',
            ['id' => $orderId]
        )->fetchAll();

        return ['order' => $order, 'items' => $items];
    }

    public function getSavedAddresses($userId)
    {
        $rows = $this->execute(
            'SELECT customer_name, phone, email, city, district, address
             FROM orders
             WHERE user_id = :user_id
             ORDER BY id DESC
             LIMIT 6',
            ['user_id' => (int) $userId]
        )->fetchAll();

        $addresses = [];
        $seen = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim($row['phone'] . '|' . $row['city'] . '|' . $row['district'] . '|' . $row['address']));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $addresses[] = $row;
        }

        return $addresses;
    }

    public function getForUser($userId, $email = '')
    {
        return $this->execute(
            "SELECT o.id, o.total_amount, o.status, o.created_at, o.coupon_id,
                    c.code AS coupon_code, o.discount_amount, o.shipping_fee,
                    o.payment_method, o.customer_name, o.phone, o.address, o.district, o.city,
                    (SELECT COUNT(*) FROM order_details od WHERE od.order_id = o.id) AS item_count,
                    (SELECT COALESCE(SUM(od.quantity), 0) FROM order_details od WHERE od.order_id = o.id) AS item_quantity
             FROM orders o
             LEFT JOIN coupons c ON c.id = o.coupon_id
             WHERE o.user_id = :user_id OR (o.user_id IS NULL AND o.email = :email)
             ORDER BY o.created_at DESC, o.id DESC",
            ['user_id' => (int) $userId, 'email' => (string) $email]
        )->fetchAll();
    }

    public function getDetailsForUser($orderId, $userId, $email = '')
    {
        return $this->execute(
            "SELECT od.product_id, od.product_name, od.unit_price, od.quantity
             FROM order_details od
             INNER JOIN orders o ON o.id = od.order_id
             WHERE o.id = :order_id
               AND (o.user_id = :user_id OR (o.user_id IS NULL AND o.email = :email))
             ORDER BY od.id",
            [
                'order_id' => (int) $orderId,
                'user_id' => (int) $userId,
                'email' => (string) $email,
            ]
        )->fetchAll();
    }

    public function cancelForUser($orderId, $userId, $email = '')
    {
        $order = $this->execute(
            'SELECT id, status FROM orders
             WHERE id = :order_id
               AND (user_id = :user_id OR (user_id IS NULL AND email = :email))
             LIMIT 1',
            [
                'order_id' => (int) $orderId,
                'user_id' => (int) $userId,
                'email' => (string) $email,
            ]
        )->fetch();

        if (!$order) {
            throw new DomainException('Không tìm thấy đơn hàng trong lịch sử của bạn.');
        }
        if (!in_array((string) $order['status'], ['pending', 'confirmed'], true)) {
            throw new DomainException('Chỉ có thể hủy đơn khi đơn đang chờ xác nhận hoặc chờ lấy hàng.');
        }

        $this->updateStatus((int) $orderId, 'cancelled');
    }

    public function getReturnRequestForUser($orderId, $userId, $email = '')
    {
        $tableExists = $this->execute(
            "SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'return_requests'"
        )->fetchColumn();
        if (!(bool) $tableExists) {
            return null;
        }

        $request = $this->execute(
            "SELECT rr.id, rr.reason, rr.description, rr.status, rr.created_at, rr.reviewed_at
             FROM return_requests rr
             INNER JOIN orders o ON o.id = rr.order_id
             WHERE o.id = :order_id
               AND (o.user_id = :user_id OR (o.user_id IS NULL AND o.email = :email))
             ORDER BY rr.id DESC
             LIMIT 1",
            [
                'order_id' => (int) $orderId,
                'user_id' => (int) $userId,
                'email' => (string) $email,
            ]
        )->fetch();

        if (!$request) {
            return null;
        }

        $request['media'] = $this->execute(
            'SELECT media_type, file_path FROM return_request_media
             WHERE request_id = :request_id ORDER BY id',
            ['request_id' => (int) $request['id']]
        )->fetchAll();

        return $request;
    }

    public function submitReturnRequest($orderId, $userId, $email, $reason, $description, array $images, $video)
    {
        if (!array_key_exists($reason, self::returnReasons())) {
            throw new DomainException('Vui lòng chọn lý do trả hàng hợp lệ.');
        }
        if (trim($description) === '' || count($images) !== 4 || !is_string($video) || $video === '') {
            throw new DomainException('Vui lòng nhập chi tiết và gửi đủ 4 ảnh cùng 1 video.');
        }

        $this->pdo->beginTransaction();
        try {
            $order = $this->execute(
                'SELECT id, status FROM orders
                 WHERE id = :order_id
                   AND (user_id = :user_id OR (user_id IS NULL AND email = :email))
                 FOR UPDATE',
                [
                    'order_id' => (int) $orderId,
                    'user_id' => (int) $userId,
                    'email' => (string) $email,
                ]
            )->fetch();
            if (!$order || $order['status'] !== 'completed') {
                throw new DomainException('Chỉ có thể yêu cầu trả hàng cho đơn đã giao thành công.');
            }

            $activeRequest = $this->execute(
                "SELECT id FROM return_requests
                 WHERE order_id = :order_id AND status IN ('pending', 'approved')
                 LIMIT 1 FOR UPDATE",
                ['order_id' => (int) $orderId]
            )->fetch();
            if ($activeRequest) {
                throw new DomainException('Đơn hàng này đã có yêu cầu trả hàng đang chờ hoặc đã được duyệt.');
            }

            $this->execute(
                'INSERT INTO return_requests (order_id, reason, description, status, created_at)
                 VALUES (:order_id, :reason, :description, \'pending\', NOW())',
                [
                    'order_id' => (int) $orderId,
                    'reason' => $reason,
                    'description' => trim($description),
                ]
            );
            $requestId = (int) $this->pdo->lastInsertId();
            $media = $this->pdo->prepare(
                'INSERT INTO return_request_media (request_id, media_type, file_path)
                 VALUES (:request_id, :media_type, :file_path)'
            );
            foreach ($images as $image) {
                $media->execute([
                    'request_id' => $requestId,
                    'media_type' => 'image',
                    'file_path' => $image,
                ]);
            }
            $media->execute([
                'request_id' => $requestId,
                'media_type' => 'video',
                'file_path' => $video,
            ]);

            $this->pdo->commit();

            return $requestId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function getReturnRequestDetail($requestId)
    {
        $request = $this->execute(
            'SELECT rr.*, o.customer_name, o.phone, o.email, o.status AS order_status,
                    o.total_amount, o.payment_method, o.id AS order_id
             FROM return_requests rr
             INNER JOIN orders o ON o.id = rr.order_id
             WHERE rr.id = :id LIMIT 1',
            ['id' => (int) $requestId]
        )->fetch();
        if (!$request) {
            return null;
        }

        $request['media'] = $this->execute(
            'SELECT media_type, file_path FROM return_request_media
             WHERE request_id = :request_id ORDER BY id',
            ['request_id' => (int) $requestId]
        )->fetchAll();
        $request['items'] = $this->execute(
            'SELECT product_name, unit_price, quantity FROM order_details
             WHERE order_id = :order_id ORDER BY id',
            ['order_id' => (int) $request['order_id']]
        )->fetchAll();

        return $request;
    }

    public function reviewReturnRequest($requestId, $status)
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new DomainException('Trạng thái duyệt yêu cầu không hợp lệ.');
        }

        $this->pdo->beginTransaction();
        try {
            $request = $this->execute(
                'SELECT id, order_id, status FROM return_requests WHERE id = :id FOR UPDATE',
                ['id' => (int) $requestId]
            )->fetch();
            if (!$request) {
                throw new DomainException('Không tìm thấy yêu cầu trả hàng.');
            }
            if ($request['status'] !== 'pending') {
                throw new DomainException('Yêu cầu này đã được xử lý trước đó.');
            }

            $this->execute(
                'UPDATE return_requests SET status = :status, reviewed_at = NOW() WHERE id = :id',
                ['status' => $status, 'id' => (int) $requestId]
            );
            if ($status === 'approved') {
                $this->execute(
                    "UPDATE payments SET status = 'refunded'
                     WHERE order_id = :order_id AND status = 'paid'",
                    ['order_id' => (int) $request['order_id']]
                );
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
