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
                'SELECT id, status FROM orders WHERE id = :id FOR UPDATE',
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
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function getForUser($userId)
    {
        return $this->execute(
            'SELECT id, total_amount, status, created_at FROM orders
             WHERE user_id = :user_id ORDER BY created_at DESC',
            ['user_id' => (int) $userId]
        )->fetchAll();
    }

    public function getDetailsForUser($orderId, $userId)
    {
        return $this->execute(
            'SELECT od.product_name, od.unit_price, od.quantity
             FROM order_details od
             INNER JOIN orders o ON o.id = od.order_id
             WHERE o.id = :order_id AND o.user_id = :user_id
             ORDER BY od.id',
            ['order_id' => (int) $orderId, 'user_id' => (int) $userId]
        )->fetchAll();
    }
}
