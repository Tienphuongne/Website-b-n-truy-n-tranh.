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
                $this->execute(
                    'UPDATE products SET stock_quantity = stock_quantity - :decrease,
                     sold_quantity = sold_quantity + :increase WHERE id = :id',
                    [
                        'decrease' => (int) $item['quantity'],
                        'increase' => (int) $item['quantity'],
                        'id' => (int) $item['id'],
                    ]
                );
            }

            $this->execute(
                'UPDATE orders SET total_amount = :total WHERE id = :id',
                ['total' => $subtotal + $shippingFee - $discount, 'id' => $orderId]
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
