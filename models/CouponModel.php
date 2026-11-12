<?php

class CouponModel extends BaseModel
{
    public function findValidByCode($code, $subtotal)
    {
        $coupon = $this->execute(
            "SELECT * FROM coupons
             WHERE code = :code
               AND is_active = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
               AND (usage_limit IS NULL OR used_count < usage_limit)
             LIMIT 1",
            ['code' => strtoupper(trim($code))]
        )->fetch();

        if (!$coupon || (float) $subtotal < (float) $coupon['minimum_order']) {
            return false;
        }

        return $coupon;
    }

    public function discount(array $coupon, $subtotal)
    {
        $amount = $coupon['discount_type'] === 'percent'
            ? $subtotal * (float) $coupon['discount_value'] / 100
            : (float) $coupon['discount_value'];

        return min((float) $subtotal, max(0, $amount));
    }
}
