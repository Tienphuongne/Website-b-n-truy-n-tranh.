<?php

class OrderController extends BaseController
{
    public function lookup()
    {
        $order = null;
        $items = [];
        $error = null;
        $orderCode = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token()) {
                http_response_code(403);
                exit('Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.');
            }

            $orderCode = trim(request_string($_POST, 'order_code'));
            $phone = normalize_phone_number(request_string($_POST, 'phone'));
            $orderId = (int) preg_replace('/\D+/', '', $orderCode);
            if ($orderId < 1) {
                $error = 'Vui lòng nhập mã đơn hàng hợp lệ.';
            } elseif (!is_valid_phone_number($phone)) {
                $error = 'Số điện thoại không hợp lệ. Vui lòng nhập số đã dùng khi đặt hàng.';
            } else {
                try {
                    $order = (new OrderModel())->findByCodeAndPhone($orderId, $phone);
                    if (!$order) {
                        $error = 'Không tìm thấy đơn hàng phù hợp. Vui lòng kiểm tra lại mã đơn và số điện thoại.';
                    } else {
                        $items = (new OrderModel())->getItems($orderId);
                    }
                } catch (PDOException | RuntimeException $exception) {
                    error_log($exception->getMessage());
                    $error = 'Chưa thể tra cứu đơn hàng. Vui lòng thử lại sau.';
                }
            }
        }

        $title = 'Tra cứu đơn hàng';
        $view = 'order/lookup';
        require PATH_VIEW . 'main.php';
    }
}
