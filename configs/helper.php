<?php

if (!function_exists('debug')) {
    function debug($data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }
}

if (!function_exists('upload_file')) {
    function upload_file($folder, $file)
    {
        $targetFile = $folder . '/' . time() . '-' . $file["name"];

        if (move_uploaded_file($file["tmp_name"], PATH_ASSETS_UPLOADS . $targetFile)) {
            return $targetFile;
        }

        throw new Exception('Upload file không thành công!');
    }
}

if (!function_exists('save_uploaded_image')) {
    /**
     * Lưu một ảnh tải lên vào assets/uploads/{folder} và trả về đường dẫn tương đối.
     * Chỉ chấp nhận các định dạng ảnh phổ biến, tối đa 5MB.
     */
    function save_uploaded_image(array $file, $folder = 'products')
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new DomainException('Tải ảnh lên không thành công. Vui lòng thử lại.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new DomainException('Tệp ảnh tải lên không hợp lệ.');
        }
        if ($size < 1024 || $size > 5 * 1024 * 1024) {
            throw new DomainException('Ảnh phải có dung lượng từ 1KB đến 5MB.');
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        $mime = null;
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmpName) ?: null;
        }
        if ($mime === null) {
            $info = @getimagesize($tmpName);
            $mime = $info['mime'] ?? null;
        }
        if (!is_string($mime) || !isset($extensions[$mime])) {
            throw new DomainException('Chỉ hỗ trợ ảnh JPG, PNG, WEBP hoặc GIF.');
        }

        $folder = trim(str_replace('\\', '/', (string) $folder), '/');
        if ($folder === '' || strpos($folder, '..') !== false) {
            throw new DomainException('Thư mục lưu ảnh không hợp lệ.');
        }

        $directory = PATH_ASSETS_UPLOADS . $folder . '/';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new DomainException('Không thể tạo thư mục lưu ảnh. Vui lòng kiểm tra quyền ghi.');
        }

        $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($tmpName, $directory . $fileName)) {
            throw new DomainException('Không thể lưu ảnh tải lên. Vui lòng thử lại.');
        }

        return $folder . '/' . $fileName;
    }
}

if (!function_exists('delete_upload_file')) {
    function delete_upload_file($relativePath)
    {
        $relativePath = ltrim(str_replace(['\\', '..'], ['/', ''], (string) $relativePath), '/');
        if ($relativePath === '') {
            return;
        }

        $base = realpath(PATH_ASSETS_UPLOADS);
        $path = realpath(PATH_ASSETS_UPLOADS . $relativePath);
        if ($base && $path && strpos($path, $base . DIRECTORY_SEPARATOR) === 0 && is_file($path)) {
            @unlink($path);
        }
    }
}

if (!function_exists('e')) {
    function e($value)
    {
        if (!is_scalar($value) && $value !== null) {
            $value = '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('request_string')) {
    function request_string(array $source, $key, $default = '')
    {
        $value = $source[$key] ?? $default;

        return is_scalar($value) ? (string) $value : (string) $default;
    }
}

if (!function_exists('limit_text')) {
    /**
     * Cắt chuỗi theo số ký tự (không cắt giữa một ký tự UTF-8).
     */
    function limit_text($value, $maxLength)
    {
        $value = (string) $value;
        $maxLength = max(0, (int) $maxLength);

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength, 'UTF-8');
        }

        return substr($value, 0, $maxLength);
    }
}

if (!function_exists('url')) {
    function url($path = '')
    {
        return BASE_URL . ltrim($path, '/');
    }
}

if (!function_exists('absolute_url')) {
    function absolute_url($path = '')
    {
        $url = url($path);
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . '/' . ltrim($url, '/');
    }
}

if (!function_exists('send_mail')) {
    function send_mail($to, $subject, $body)
    {
        $host = parse_url(absolute_url(''), PHP_URL_HOST) ?: 'localhost';
        $from = 'no-reply@' . preg_replace('/^www\./i', '', $host);
        $headers = "From: TruyenThuVi <{$from}>\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n";

        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    }
}

if (!function_exists('product_image_url')) {
    function product_image_url($coverImage)
    {
        if (empty($coverImage)) {
            return '';
        }
        if (str_starts_with($coverImage, 'http://') || str_starts_with($coverImage, 'https://')) {
            return $coverImage;
        }
        if (str_starts_with($coverImage, 'assets/')) {
            return BASE_URL . ltrim($coverImage, '/');
        }
        return BASE_ASSETS_UPLOADS . ltrim($coverImage, '/\\');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token()
    {
        $submitted = $_POST['_csrf_token'] ?? '';
        $saved = $_SESSION['_csrf_token'] ?? '';

        return is_string($submitted) && is_string($saved) && $saved !== ''
            && hash_equals($saved, $submitted);
    }
}

if (!function_exists('flash')) {
    function flash($type, $message)
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('take_flash_messages')) {
    function take_flash_messages()
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return $messages;
    }
}

if (!function_exists('redirect')) {
    function redirect($path)
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('set_remember_cookie')) {
    function set_remember_cookie($token, $expiresAt)
    {
        $path = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';
        setcookie('manga_house_remember', $token, [
            'expires' => $expiresAt,
            'path' => $path,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

if (!function_exists('clear_remember_cookie')) {
    function clear_remember_cookie()
    {
        set_remember_cookie('', time() - 3600);
    }
}

if (!function_exists('order_status_label')) {
    function order_status_label($status)
    {
        $labels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
        ];

        return $labels[$status] ?? (string) $status;
    }
}

if (!function_exists('payment_method_label')) {
    function payment_method_label($method)
    {
        $labels = [
            'cod' => 'Thanh toán khi nhận hàng (COD)',
            'bank_transfer' => 'Chuyển khoản ngân hàng',
        ];

        return $labels[$method] ?? (string) $method;
    }
}

if (!function_exists('shipping_method_label')) {
    function shipping_method_label($method)
    {
        $labels = [
            'standard' => 'Giao tiêu chuẩn',
            'express' => 'Giao nhanh',
        ];

        return $labels[$method] ?? 'Giao tiêu chuẩn';
    }
}

if (!function_exists('order_email_body')) {
    /**
     * Nội dung email dạng text thuần cho đơn hàng.
     */
    function order_email_body(array $order, $heading)
    {
        $money = function ($value) {
            return number_format((float) $value, 0, ',', '.') . '₫';
        };
        $subtotal = 0.0;
        $items = $order['items'] ?? [];
        foreach ($items as $item) {
            $subtotal += (float) $item['unit_price'] * (int) $item['quantity'];
        }

        $lines = [
            'Xin chào ' . ($order['customer_name'] ?? 'bạn') . ',',
            '',
            $heading,
            '',
            'Mã đơn hàng: #' . (int) ($order['id'] ?? 0),
            'Trạng thái: ' . order_status_label($order['status'] ?? 'pending'),
            'Ngày đặt: ' . ($order['created_at'] ?? date('Y-m-d H:i:s')),
        ];

        if ($items) {
            $lines[] = '';
            $lines[] = 'Sản phẩm:';
            foreach ($items as $item) {
                $lines[] = '- ' . $item['product_name'] . ' × ' . (int) $item['quantity']
                    . ': ' . $money((float) $item['unit_price'] * (int) $item['quantity']);
            }
        }

        $lines[] = '';
        $lines[] = 'Tạm tính: ' . $money($subtotal);
        $lines[] = 'Phí vận chuyển (' . shipping_method_label($order['shipping_method'] ?? 'standard') . '): '
            . $money($order['shipping_fee'] ?? 0);
        if ((float) ($order['discount_amount'] ?? 0) > 0) {
            $lines[] = 'Giảm giá: -' . $money($order['discount_amount']);
        }
        $lines[] = 'Tổng thanh toán: ' . $money($order['total_amount'] ?? 0);
        $lines[] = '';
        $lines[] = 'Giao đến: ' . full_address_line($order);
        $lines[] = 'Người nhận: ' . ($order['customer_name'] ?? '') . ' · ' . ($order['phone'] ?? '');
        $lines[] = 'Thanh toán: ' . payment_method_label($order['payment_method'] ?? 'cod');

        if (!empty($order['note'])) {
            $lines[] = 'Ghi chú: ' . $order['note'];
        }

        $lines[] = '';
        $lines[] = 'Dự kiến giao: ' . delivery_window($order['shipping_method'] ?? 'standard');
        $lines[] = '';
        $lines[] = 'Bạn có thể tra cứu trạng thái đơn tại: ' . absolute_url('order/lookup');
        $lines[] = '';
        $lines[] = 'Cảm ơn bạn đã mua truyện tại TruyenThuVi!';

        return implode("\n", $lines);
    }
}

if (!function_exists('send_order_confirmation_email')) {
    function send_order_confirmation_email(array $order)
    {
        if (empty($order['email']) || !filter_var($order['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return send_mail(
            $order['email'],
            'Xác nhận đơn hàng #' . (int) ($order['id'] ?? 0) . ' - TruyenThuVi',
            order_email_body($order, 'Cảm ơn bạn đã đặt hàng! Chúng tôi đã nhận được đơn của bạn và sẽ liên hệ xác nhận sớm nhất.')
        );
    }
}

if (!function_exists('send_order_status_email')) {
    function send_order_status_email(array $order)
    {
        if (empty($order['email']) || !filter_var($order['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $status = $order['status'] ?? 'pending';
        $heading = 'Đơn hàng của bạn đã được cập nhật trạng thái: ' . order_status_label($status) . '.';

        return send_mail(
            $order['email'],
            'Cập nhật đơn hàng #' . (int) ($order['id'] ?? 0) . ': ' . order_status_label($status) . ' - TruyenThuVi',
            order_email_body($order, $heading)
        );
    }
}