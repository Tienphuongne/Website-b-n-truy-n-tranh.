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
        $wishlistProducts = [];
        $addresses = [];
        $editingAddress = null;
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

                $formType = $_POST['form_type'] ?? '';
                if (in_array($formType, ['address_create', 'address_update', 'address_delete', 'address_default'], true)) {
                    $addressModel = new AddressModel();
                    $addressId = filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT);

                    if ($formType === 'address_delete') {
                        $addressModel->delete($addressId, $userId);
                        flash('success', 'Đã xóa địa chỉ.');
                    } elseif ($formType === 'address_default') {
                        $addressModel->setDefault($addressId, $userId);
                        flash('success', 'Đã đặt địa chỉ mặc định.');
                    } else {
                        $address = [
                            'label' => limit_text(trim(request_string($_POST, 'label')), 60),
                            'full_name' => trim(request_string($_POST, 'full_name')),
                            'phone' => trim(request_string($_POST, 'phone')),
                            'city' => trim(request_string($_POST, 'city')),
                            'district' => trim(request_string($_POST, 'district')),
                            'ward' => trim(request_string($_POST, 'ward')),
                            'address' => trim(request_string($_POST, 'address')),
                            'is_default' => !empty($_POST['is_default']),
                        ];
                        $errors = $this->validateAddress($address);
                        if ($errors) {
                            flash('danger', implode(' ', $errors));
                        } else {
                            if ($formType === 'address_update' && $addressId) {
                                $addressModel->update($addressId, $userId, $address);
                                flash('success', 'Đã cập nhật địa chỉ.');
                            } else {
                                $addressModel->create($userId, $address);
                                flash('success', 'Đã thêm địa chỉ mới.');
                            }
                        }
                    }
                    redirect('account?tab=addresses');
                }
            } catch (DomainException $exception) {
                flash('danger', $exception->getMessage());
            } catch (PDOException | RuntimeException $exception) {
                flash('danger', $this->reportDatabaseFailure($exception));
            }
        }

        try {
            $orderModel = new OrderModel();
            $orders = $orderModel->getForUser($userId, $userEmail);
            $voucherSummary = (new CouponModel())->getAvailabilityForUser($userId, $userEmail);
            $wishlistProducts = (new WishlistModel())->listForUser($userId);
            $addresses = (new AddressModel())->listForUser($userId);

            if ($tab === 'addresses' && !empty($_GET['edit'])) {
                $editId = (int) $_GET['edit'];
                foreach ($addresses as $address) {
                    if ((int) $address['id'] === $editId) {
                        $editingAddress = $address;
                        break;
                    }
                }
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

    /**
     * Endpoint JSON dùng ở trang thanh toán để lưu địa chỉ vào sổ địa chỉ.
     */
    public function saveAddress()
    {
        $this->requirePost();
        if (empty($_SESSION['user'])) {
            json_response(['error' => 'Vui lòng đăng nhập để lưu địa chỉ.'], 401);
            return;
        }

        $input = json_request_body();
        $address = [
            'label' => limit_text(trim((string) ($input['label'] ?? 'Địa chỉ đã lưu')), 60),
            'full_name' => trim((string) ($input['full_name'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? '')),
            'city' => trim((string) ($input['city'] ?? '')),
            'district' => trim((string) ($input['district'] ?? '')),
            'ward' => trim((string) ($input['ward'] ?? '')),
            'address' => trim((string) ($input['address'] ?? '')),
            'is_default' => !empty($input['is_default']),
        ];
        $errors = $this->validateAddress($address);
        if ($errors) {
            json_response(['error' => implode(' ', $errors)], 422);
            return;
        }

        try {
            $id = (new AddressModel())->create((int) $_SESSION['user']['id'], $address);
            json_response(['message' => 'Đã lưu địa chỉ vào sổ địa chỉ.', 'id' => $id]);
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            json_response(['error' => 'Chưa lưu được địa chỉ. Vui lòng thử lại.'], 500);
        }
    }

    public function deleteAddress()
    {
        $this->requirePost();
        if (empty($_SESSION['user'])) {
            json_response(['error' => 'Vui lòng đăng nhập.'], 401);
            return;
        }

        $input = json_request_body();
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            json_response(['error' => 'Địa chỉ không hợp lệ.'], 422);
            return;
        }

        try {
            (new AddressModel())->delete($id, (int) $_SESSION['user']['id']);
            json_response(['message' => 'Đã xóa địa chỉ.']);
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            json_response(['error' => 'Chưa xóa được địa chỉ.'], 500);
        }
    }

    private function validateAddress(array $address)
    {
        $errors = [];
        foreach (['full_name', 'phone', 'city', 'address'] as $field) {
            if ($address[$field] === '') {
                $errors[] = 'Vui lòng nhập đầy đủ họ tên, điện thoại, tỉnh thành và địa chỉ.';
                break;
            }
        }
        if ($address['phone'] !== '' && !preg_match('/^(0|\+84)[0-9]{9}$/', str_replace([' ', '.', '-', '(', ')'], '', $address['phone']))) {
            $errors[] = 'Số điện thoại không hợp lệ.';
        }

        return $errors;
    }
}
