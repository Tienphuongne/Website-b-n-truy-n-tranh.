<?php

class AccountController extends BaseController
{
    public function index()
    {
        if (empty($_SESSION['user'])) {
            flash('warning', 'Vui lòng đăng nhập để xem thông tin tài khoản.');
            redirect('login');
        }

        $userId = (int) $_SESSION['user']['id'];
        $userEmail = (string) ($_SESSION['user']['email'] ?? '');
        $tab = $_GET['tab'] ?? 'profile';
        $orders = [];
        $orderDetails = [];
        $selectedOrder = null;
        $requestedOrderId = !empty($_GET['order']) ? (int) $_GET['order'] : 0;
        $orderNotFound = false;
        $voucherSummary = ['available' => 0, 'used' => 0, 'total' => 0, 'codes' => []];
        $databaseError = null;
        $orderStatusFilter = (string) ($_GET['status'] ?? 'pending');

        try {
            $model = new UserModel();
            $model->ensureProfileColumns();
            $profileRecord = $model->findById($userId);
            if (!$profileRecord) {
                flash('danger', 'Không tìm thấy tài khoản.');
                redirect('logout');
            }
            $profile = [
                'full_name' => (string) ($profileRecord['full_name'] ?? $_SESSION['user']['name']),
                'birth_date' => (string) ($profileRecord['birth_date'] ?? ''),
                'phone' => (string) ($profileRecord['phone'] ?? ''),
                'email' => (string) ($profileRecord['email'] ?? $userEmail),
                'city' => (string) ($profileRecord['city'] ?? ''),
                'district' => (string) ($profileRecord['district'] ?? ''),
                'address' => (string) ($profileRecord['address'] ?? ''),
            ];
            $_SESSION['user']['name'] = $profile['full_name'];
            $_SESSION['user']['email'] = $profile['email'];
            $_SESSION['user']['phone'] = $profile['phone'];
        } catch (PDOException | RuntimeException $exception) {
            $profile = [
                'full_name' => (string) ($_SESSION['user']['name'] ?? ''),
                'birth_date' => null,
                'phone' => (string) ($_SESSION['user']['phone'] ?? ''),
                'email' => $userEmail,
                'city' => '',
                'district' => '',
                'address' => '',
            ];
            $databaseError = $this->reportDatabaseFailure($exception);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            try {
                $model = new UserModel();
                if (($_POST['form_type'] ?? '') === 'profile') {
                    $profile = [
                        'full_name' => trim(request_string($_POST, 'full_name')),
                        'birth_day' => (int) trim(request_string($_POST, 'birth_day')),
                        'birth_month' => (int) trim(request_string($_POST, 'birth_month')),
                        'birth_year' => (int) trim(request_string($_POST, 'birth_year')),
                        'phone' => trim(request_string($_POST, 'phone')),
                        'email' => trim(request_string($_POST, 'email')),
                        'city' => trim(request_string($_POST, 'city')),
                        'district' => trim(request_string($_POST, 'district')),
                        'address' => trim(request_string($_POST, 'address')),
                        'birth_date' => null,
                    ];
                    $birthComplete = $profile['birth_day'] > 0 || $profile['birth_month'] > 0 || $profile['birth_year'] > 0;
                    if ($birthComplete) {
                        $profileValidBirth = $profile['birth_day'] >= 1
                            && $profile['birth_day'] <= 31
                            && $profile['birth_month'] >= 1
                            && $profile['birth_month'] <= 12
                            && $profile['birth_year'] >= 1900
                            && $profile['birth_year'] <= (int) date('Y')
                            && checkdate($profile['birth_month'], $profile['birth_day'], $profile['birth_year']);
                        if (!$profileValidBirth) {
                            flash('danger', 'Ngày sinh không hợp lệ.');
                            redirect('account');
                        }
                        $profile['birth_date'] = sprintf(
                            '%04d-%02d-%02d',
                            $profile['birth_year'],
                            $profile['birth_month'],
                            $profile['birth_day']
                        );
                    }
                    if ($profile['full_name'] === '') {
                        flash('danger', 'Họ tên không được để trống.');
                    } elseif (!filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
                        flash('danger', 'Email không hợp lệ.');
                    } elseif ($model->emailExistsForOther($profile['email'], $userId)) {
                        flash('danger', 'Email này đã được sử dụng bởi tài khoản khác.');
                    } else {
                        $model->ensureProfileColumns();
                        $model->updateProfile($userId, $profile);
                        $_SESSION['user']['name'] = $profile['full_name'];
                        $_SESSION['user']['email'] = $profile['email'];
                        $_SESSION['user']['phone'] = $profile['phone'];
                        flash('success', 'Đã cập nhật thông tin tài khoản.');
                    }
                    redirect('account');
                }

                if (($_POST['form_type'] ?? '') === 'password') {
                    $current = request_string($_POST, 'current_password');
                    $new = request_string($_POST, 'new_password');
                    $confirm = request_string($_POST, 'password_confirmation');
                    $record = $model->findByLogin($_SESSION['user']['email']);
                    if (!$record || !password_verify($current, $record['password_hash'])) {
                        flash('danger', 'Mật khẩu hiện tại không chính xác.');
                    } elseif (strlen($new) < 8 || $new !== $confirm) {
                        flash('danger', 'Mật khẩu mới cần ít nhất 8 ký tự và phải khớp xác nhận.');
                    } else {
                        $model->updatePassword($userId, $new);
                        flash('success', 'Đã đổi mật khẩu.');
                    }
                    redirect('account?tab=password');
                }

                if (($_POST['form_type'] ?? '') === 'review') {
                    $orderId = (int) request_string($_POST, 'order_id');
                    $productId = (int) request_string($_POST, 'product_id');
                    $rating = (int) request_string($_POST, 'rating');
                    $content = trim(request_string($_POST, 'content'));
                    $orderModel = new OrderModel();
                    $order = $orderModel->getForUser($userId, $userEmail);
                    $eligible = false;
                    $details = [];
                    foreach ($order as $item) {
                        if ((int) $item['id'] === $orderId && (string) $item['status'] === 'completed') {
                            $eligible = true;
                            break;
                        }
                    }
                    if ($eligible) {
                        $details = $orderModel->getDetailsForUser($orderId, $userId, $userEmail);
                    }
                    $productEligible = false;
                    foreach ($details as $detail) {
                        if ((int) $detail['product_id'] === $productId) {
                            $productEligible = true;
                            break;
                        }
                    }
                    if (!$productEligible) {
                        flash('danger', 'Chỉ có thể đánh giá sản phẩm trong đơn đã hoàn tất.');
                    } elseif ($rating < 1 || $rating > 5) {
                        flash('danger', 'Vui lòng chọn từ 1 đến 5 sao.');
                    } else {
                        (new ReviewModel())->create($productId, $userId, $rating, $content);
                        flash('success', 'Cảm ơn bạn! Đánh giá đang chờ duyệt.');
                    }
                    redirect('account?tab=orders&status=review');
                }

                if (($_POST['form_type'] ?? '') === 'cancel_order') {
                    $orderId = (int) request_string($_POST, 'order_id');
                    $orderModel = new OrderModel();
                    $ownedOrder = null;
                    foreach ($orderModel->getForUser($userId, $userEmail) as $order) {
                        if ((int) $order['id'] === $orderId) {
                            $ownedOrder = $order;
                            break;
                        }
                    }
                    if (!$ownedOrder) {
                        flash('danger', 'Không tìm thấy đơn hàng cần hủy.');
                    } elseif (!in_array((string) $ownedOrder['status'], ['pending', 'confirmed'], true)) {
                        flash('danger', 'Đơn hàng đã ở giai đoạn giao hàng hoặc đã hoàn tất, không thể hủy.');
                    } else {
                        $orderModel->updateStatus($orderId, 'cancelled');
                        flash('success', 'Đã hủy đơn hàng #' . $orderId . '.');
                    }
                    redirect('account?tab=orders&status=cancelled');
                }
            } catch (PDOException | RuntimeException $exception) {
                flash('danger', $this->reportDatabaseFailure($exception));
            }
        }

        try {
            $orderModel = new OrderModel();
            $orders = $orderModel->getForUser($userId, $userEmail);
            $voucherSummary = (new CouponModel())->getAvailabilityForUser($userId, $userEmail);

            $orderStatusCounts = [
                'pending' => 0,
                'pickup' => 0,
                'shipping' => 0,
                'review' => 0,
                'cancelled' => 0,
            ];
            foreach ($orders as $order) {
                $status = (string) $order['status'];
                if ($status === 'pending') {
                    $orderStatusCounts['pending']++;
                } elseif ($status === 'confirmed') {
                    $orderStatusCounts['pickup']++;
                } elseif ($status === 'shipping') {
                    $orderStatusCounts['shipping']++;
                } elseif ($status === 'completed') {
                    $orderStatusCounts['review']++;
                } elseif ($status === 'cancelled') {
                    $orderStatusCounts['cancelled']++;
                }
            }
            $statusDatabaseMap = [
                'pending' => 'pending',
                'pickup' => 'confirmed',
                'shipping' => 'shipping',
                'review' => 'completed',
                'cancelled' => 'cancelled',
            ];
            if (isset($statusDatabaseMap[$orderStatusFilter])) {
                $orders = array_values(array_filter(
                    $orders,
                    static fn($order) => (string) $order['status'] === $statusDatabaseMap[$orderStatusFilter]
                ));
            }

            if ($tab === 'orders' && $requestedOrderId > 0) {
                foreach ($orders as $order) {
                    if ((int) $order['id'] === $requestedOrderId) {
                        $selectedOrder = $order;
                        break;
                    }
                }
                if ($selectedOrder) {
                    $orderDetails = $orderModel->getDetailsForUser($requestedOrderId, $userId, $userEmail);
                } else {
                    $orderNotFound = true;
                }
            }
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Chưa thể tải lịch sử đơn hàng.';
        }

        $title = 'Tài khoản của tôi';
        $view = 'account/index';
        require PATH_VIEW . 'main.php';
    }
}
