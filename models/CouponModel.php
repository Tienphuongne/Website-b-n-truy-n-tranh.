<?php

class CouponModel extends BaseModel
{
    public function findValidByCode($code, $subtotal, $userId = null, $email = '')
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return false;
        }

        $coupon = $this->execute(
            "SELECT * FROM coupons
             WHERE code = :code
               AND is_active = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
               AND (usage_limit IS NULL OR used_count < usage_limit)
             LIMIT 1",
            ['code' => $code]
        )->fetch();

        if (!$coupon || (float) $subtotal < (float) $coupon['minimum_order']) {
            return false;
        }
        if ($userId && $this->hasUserUsedCoupon((int) $coupon['id'], (int) $userId, (string) $email)) {
            return false;
        }

        return $coupon;
    }

    /**
     * Đếm voucher còn có thể dùng cho một người dùng.
     * Voucher đã dùng ở đơn bị hủy được tính lại là còn dùng được.
     */
    public function getAvailabilityForUser($userId, $email = '')
    {
        $userId = (int) $userId;
        $email = (string) $email;

        $validCoupons = $this->execute(
            "SELECT id, code, discount_type, discount_value, minimum_order, starts_at, ends_at, usage_limit, used_count
             FROM coupons
             WHERE is_active = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
               AND (usage_limit IS NULL OR used_count < usage_limit)
             ORDER BY (ends_at IS NULL) DESC, ends_at ASC, id DESC"
        )->fetchAll();

        $usedIds = [];
        if ($userId > 0) {
            $usedRows = $this->execute(
                "SELECT DISTINCT o.coupon_id
                 FROM orders o
                 WHERE o.coupon_id IS NOT NULL
                   AND o.status <> 'cancelled'
                   AND (o.user_id = :user_id OR (o.user_id IS NULL AND o.email = :email))",
                ['user_id' => $userId, 'email' => $email]
            )->fetchAll();

            foreach ($usedRows as $row) {
                $usedIds[(int) $row['coupon_id']] = true;
            }
        }

        $available = [];
        foreach ($validCoupons as $coupon) {
            if (isset($usedIds[(int) $coupon['id']])) {
                continue;
            }
            $available[] = $coupon;
        }

        return [
            'available' => count($available),
            'used' => count($usedIds),
            'total' => count($validCoupons),
            'codes' => array_slice($available, 0, 4),
            'promotions' => $validCoupons,
        ];
    }

    private function hasUserUsedCoupon($couponId, $userId, $email)
    {
        $row = $this->execute(
            "SELECT 1
             FROM orders
             WHERE coupon_id = :coupon_id
               AND status <> 'cancelled'
               AND (user_id = :user_id OR (user_id IS NULL AND email = :email))
             LIMIT 1",
            [
                'coupon_id' => (int) $couponId,
                'user_id' => (int) $userId,
                'email' => (string) $email,
            ]
        )->fetch();

        return (bool) $row;
    }

    public function discount(array $coupon, $subtotal)
    {
        $amount = $coupon['discount_type'] === 'percent'
            ? $subtotal * (float) $coupon['discount_value'] / 100
            : (float) $coupon['discount_value'];

        return min((float) $subtotal, max(0, $amount));
    }
}
