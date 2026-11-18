<?php

class OrderModel extends BaseModel
{
    public function createOrder(array $customer, array $cart, $paymentMethod, $shippingFee, $userId = null, $couponId = null)
    {
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
                 (user_id, customer_name, phone, email, city, district, address, note, payment_method, status, shipping_fee, coupon_id, discount_amount, created_at)
                 VALUES (:user_id, :name, :phone, :email, :city, :district, :address, :note, :payment_method, 'pending', :shipping_fee, :coupon_id, :discount_amount, NOW())",
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
}
