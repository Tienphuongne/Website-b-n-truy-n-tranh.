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
        $tab = $_GET['tab'] ?? 'profile';
        $orders = [];
        $orderDetails = [];
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
            $orders = (new OrderModel())->getForUser($userId);
            if ($tab === 'orders' && !empty($_GET['order'])) {
                $orderDetails = (new OrderModel())->getDetailsForUser(
                    (int) $_GET['order'],
                    $userId
                );
            }
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Chưa thể tải lịch sử đơn hàng.';
        }

        $title = 'Tài khoản của tôi';
        $view = 'account/index';
        require PATH_VIEW . 'main.php';
    }

    public function cancelOrder()
    {
        $this->guardCustomer();
        $this->requirePost();
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

        if (!$orderId) {
            flash('warning', 'Đơn hàng không hợp lệ.');
            redirect('account?tab=orders');
        }

        try {
            (new OrderModel())->cancelForUser($orderId, (int) $_SESSION['user']['id']);
            flash('success', 'Đã hủy đơn hàng #' . (int) $orderId . '.');
        } catch (DomainException $exception) {
            flash('warning', $exception->getMessage());
        } catch (PDOException | RuntimeException $exception) {
            flash('danger', $this->reportDatabaseFailure($exception));
        }

        redirect('account?tab=orders&order=' . (int) $orderId);
    }

    public function reorder()
    {
        $this->guardCustomer();
        $this->requirePost();
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

        if (!$orderId) {
            flash('warning', 'Đơn hàng không hợp lệ.');
            redirect('account?tab=orders');
        }

        try {
            $details = (new OrderModel())->getDetailsForUser($orderId, (int) $_SESSION['user']['id']);
            if (!$details) {
                flash('warning', 'Không tìm thấy sản phẩm trong đơn hàng này.');
                redirect('account?tab=orders&order=' . (int) $orderId);
            }

            $productIds = [];
            foreach ($details as $detail) {
                if (!empty($detail['product_id'])) {
                    $productIds[] = (int) $detail['product_id'];
                }
            }
            $products = $productIds ? (new ProductModel())->getByIds($productIds) : [];
            $productMap = [];
            foreach ($products as $product) {
                $productMap[(int) $product['id']] = $product;
            }

            $cart = $_SESSION['cart'] ?? [];
            $added = 0;
            $skipped = 0;
            foreach ($details as $detail) {
                $productId = (int) ($detail['product_id'] ?? 0);
                $product = $productMap[$productId] ?? null;
                if (!$product || (int) $product['stock_quantity'] < 1) {
                    $skipped++;
                    continue;
                }
                $quantity = min(
                    max(1, (int) $detail['quantity']),
                    (int) $product['stock_quantity'],
                    99
                );
                $cart[$productId] = min(
                    (int) ($cart[$productId] ?? 0) + $quantity,
                    (int) $product['stock_quantity']
                );
                $added++;
            }
            $_SESSION['cart'] = $cart;

            if ($added > 0) {
                $message = 'Đã thêm ' . $added . ' sản phẩm từ đơn #' . (int) $orderId . ' vào giỏ hàng.';
                if ($skipped > 0) {
                    $message .= ' ' . $skipped . ' sản phẩm hiện không còn hàng.';
                }
                flash('success', $message);
                redirect('cart');
            }

            flash('warning', 'Các sản phẩm trong đơn hàng hiện đều không còn hàng.');
        } catch (PDOException | RuntimeException $exception) {
            flash('danger', $this->reportDatabaseFailure($exception));
        }

        redirect('account?tab=orders&order=' . (int) $orderId);
    }

    private function guardCustomer()
    {
        if (empty($_SESSION['user']['id'])) {
            flash('warning', 'Vui lòng đăng nhập để quản lý đơn hàng.');
            redirect('login');
        }
    }
}
