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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            try {
                $model = new UserModel();
                if (($_POST['form_type'] ?? '') === 'profile') {
                    $profile = [
                        'full_name' => trim(request_string($_POST, 'full_name')),
                        'phone' => trim(request_string($_POST, 'phone')),
                    ];
                    if ($profile['full_name'] === '') {
                        flash('danger', 'Họ tên không được để trống.');
                    } else {
                        $model->updateProfile($userId, $profile);
                        $_SESSION['user']['name'] = $profile['full_name'];
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
            } catch (PDOException | RuntimeException $exception) {
                flash('danger', $this->reportDatabaseFailure($exception));
            }
        }

        try {
            $orderModel = new OrderModel();
            $orders = $orderModel->getForUser($userId, $userEmail);
            $voucherSummary = (new CouponModel())->getAvailabilityForUser($userId, $userEmail);

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
