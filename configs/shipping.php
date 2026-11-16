<?php

/**
 * Cấu hình giao hàng và các tiện ích dùng chung cho trang thanh toán.
 * Có thể điều chỉnh mức phí / ngưỡng miễn phí tại đây mà không cần sửa controller.
 */
const SHIPPING_STANDARD_FEE = 30000.0;
const SHIPPING_EXPRESS_FEE = 45000.0;
const SHIPPING_FREE_THRESHOLD = 300000.0;

if (!function_exists('shipping_methods')) {
    function shipping_methods()
    {
        return [
            'standard' => [
                'label' => 'Giao tiêu chuẩn',
                'description' => 'Toàn quốc, 2 – 4 ngày làm việc',
                'min_days' => 2,
                'max_days' => 4,
            ],
            'express' => [
                'label' => 'Giao nhanh',
                'description' => 'Ưu tiên xử lý, 1 – 2 ngày làm việc',
                'min_days' => 1,
                'max_days' => 2,
            ],
        ];
    }
}

if (!function_exists('shipping_fee_for')) {
    function shipping_fee_for($method, $subtotal)
    {
        if ($method === 'express') {
            return SHIPPING_EXPRESS_FEE;
        }

        return (float) $subtotal >= SHIPPING_FREE_THRESHOLD ? 0.0 : SHIPPING_STANDARD_FEE;
    }
}

if (!function_exists('shipping_fee_label')) {
    function shipping_fee_label($fee)
    {
        return (float) $fee <= 0 ? 'Miễn phí' : number_format((float) $fee, 0, ',', '.') . '₫';
    }
}

if (!function_exists('free_shipping_remaining')) {
    function free_shipping_remaining($subtotal)
    {
        return max(0.0, SHIPPING_FREE_THRESHOLD - (float) $subtotal);
    }
}

if (!function_exists('add_delivery_days')) {
    /**
     * Cộng số ngày làm việc, bỏ qua Chủ nhật.
     */
    function add_delivery_days($startTimestamp, $days)
    {
        $timestamp = (int) $startTimestamp;
        $added = 0;
        while ($added < (int) $days) {
            $timestamp = strtotime('+1 day', $timestamp);
            if ($timestamp === false) {
                break;
            }
            if ((int) date('N', $timestamp) !== 7) {
                $added++;
            }
        }

        return $timestamp;
    }
}

if (!function_exists('delivery_window')) {
    /**
     * Khoảng ngày giao dự kiến, ví dụ: "18/11 – 20/11".
     */
    function delivery_window($method, $startTimestamp = null)
    {
        $methods = shipping_methods();
        $config = $methods[$method] ?? $methods['standard'];
        $start = $startTimestamp ?: time();
        $from = add_delivery_days($start, $config['min_days']);
        $to = add_delivery_days($start, $config['max_days']);

        return date('d/m', $from) . ' – ' . date('d/m', $to);
    }
}

if (!function_exists('normalize_phone_number')) {
    function normalize_phone_number($phone)
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) === 11 && strpos($digits, '84') === 0) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }
}

if (!function_exists('is_valid_phone_number')) {
    function is_valid_phone_number($phone)
    {
        return (bool) preg_match('/^0\d{9,10}$/', (string) $phone);
    }
}

if (!function_exists('vietnam_provinces')) {
    /**
     * 34 đơn vị hành chính cấp tỉnh (sau sáp nhập năm 2025).
     */
    function vietnam_provinces()
    {
        return [
            'Thành phố trực thuộc Trung ương' => [
                'Hà Nội',
                'Hải Phòng',
                'Huế',
                'Đà Nẵng',
                'Hồ Chí Minh',
                'Cần Thơ',
            ],
            'Tỉnh' => [
                'An Giang',
                'Bắc Ninh',
                'Cà Mau',
                'Cao Bằng',
                'Đắk Lắk',
                'Điện Biên',
                'Đồng Nai',
                'Đồng Tháp',
                'Gia Lai',
                'Hà Tĩnh',
                'Hưng Yên',
                'Khánh Hòa',
                'Lai Châu',
                'Lâm Đồng',
                'Lạng Sơn',
                'Lào Cai',
                'Nghệ An',
                'Ninh Bình',
                'Phú Thọ',
                'Quảng Ngãi',
                'Quảng Ninh',
                'Quảng Trị',
                'Sơn La',
                'Tây Ninh',
                'Thái Nguyên',
                'Thanh Hóa',
                'Tuyên Quang',
                'Vĩnh Long',
            ],
        ];
    }
}

if (!function_exists('vietnam_province_names')) {
    function vietnam_province_names()
    {
        $names = [];
        foreach (vietnam_provinces() as $group) {
            foreach ($group as $name) {
                $names[] = $name;
            }
        }

        return $names;
    }
}

if (!function_exists('full_address_line')) {
    function full_address_line(array $address)
    {
        $parts = [
            $address['address'] ?? '',
            $address['ward'] ?? '',
            $address['district'] ?? '',
            $address['city'] ?? '',
        ];

        return implode(', ', array_values(array_filter(array_map('trim', $parts), function ($part) {
            return $part !== '';
        })));
    }
}
