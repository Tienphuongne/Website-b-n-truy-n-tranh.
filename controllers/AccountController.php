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
        $returnRequest = null;
        $selectedOrder = null;
        $requestedOrderId = !empty($_GET['order']) ? (int) $_GET['order'] : 0;
        $orderNotFound = false;
        $voucherSummary = ['available' => 0, 'used' => 0, 'total' => 0, 'codes' => []];
        $wishlistProducts = [];
        $addresses = [];
        $editingAddress = null;
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
                if (($_POST['form_type'] ?? '') === 'return_request') {
                    $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
                    if (!$orderId || $orderId <= 0) {
                        throw new DomainException('Mã đơn hàng không hợp lệ.');
                    }

                    $this->submitReturnRequest($orderId, $userId, $userEmail);
                    flash('success', 'Đã gửi yêu cầu trả hàng. Vui lòng chờ quản trị viên phê duyệt.');
                    redirect('account?tab=orders&order=' . (int) $orderId);
                }

                if (($_POST['form_type'] ?? '') === 'cancel_order') {
                    $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
                    if (!$orderId || $orderId <= 0) {
                        throw new DomainException('Mã đơn hàng không hợp lệ.');
                    }

                    (new OrderModel())->cancelForUser($orderId, $userId, $userEmail);
                    flash('success', 'Đã hủy đơn hàng #' . (int) $orderId . '.');
                    redirect('account?tab=orders&status=cancelled&order=' . (int) $orderId);
                }

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
                $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
                redirect($orderId ? 'account?tab=orders&order=' . (int) $orderId : 'account?tab=orders');
            } catch (PDOException | RuntimeException $exception) {
                flash('danger', $this->reportDatabaseFailure($exception));
                $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
                redirect($orderId ? 'account?tab=orders&order=' . (int) $orderId : 'account?tab=orders');
            }
        }

        try {
            $orderModel = new OrderModel();
            $allOrders = $orderModel->getForUser($userId, $userEmail);
            $orders = $allOrders;
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

            $orderStatusCounts = [
                'pending' => 0,
                'pickup' => 0,
                'shipping' => 0,
                'review' => 0,
                'cancelled' => 0,
            ];
            foreach ($allOrders as $order) {
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
                foreach ($allOrders as $order) {
                    if ((int) $order['id'] === $requestedOrderId) {
                        $selectedOrder = $order;
                        break;
                    }
                }
                if ($selectedOrder) {
                    $orderDetails = $orderModel->getDetailsForUser($requestedOrderId, $userId, $userEmail);
                    $returnRequest = $orderModel->getReturnRequestForUser($requestedOrderId, $userId, $userEmail);
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

    private function submitReturnRequest($orderId, $userId, $userEmail)
    {
        $reason = request_string($_POST, 'reason');
        $description = trim(request_string($_POST, 'description'));
        $descriptionLength = function_exists('mb_strlen')
            ? mb_strlen($description, 'UTF-8')
            : strlen($description);
        if ($description === '' || $descriptionLength > 3000) {
            throw new DomainException('Chi tiết lý do không được để trống và tối đa 3.000 ký tự.');
        }

        $storedFiles = [];
        try {
            $images = $_FILES['evidence_images'] ?? null;
            if (!is_array($images) || !isset($images['name']) || !is_array($images['name']) || count($images['name']) !== 4) {
                throw new DomainException('Vui lòng tải lên đúng 4 hình ảnh.');
            }
            foreach (array_keys($images['name']) as $index) {
                $file = [];
                foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $field) {
                    $file[$field] = $images[$field][$index] ?? null;
                }
                $storedFiles[] = $this->storeReturnEvidence($file, 'image');
            }

            $video = $_FILES['evidence_video'] ?? null;
            if (!is_array($video)) {
                throw new DomainException('Vui lòng tải lên 1 video minh chứng.');
            }
            $storedFiles[] = $this->storeReturnEvidence($video, 'video');

            (new OrderModel())->submitReturnRequest(
                $orderId,
                $userId,
                $userEmail,
                $reason,
                $description,
                array_slice($storedFiles, 0, 4),
                $storedFiles[4]
            );
        } catch (Throwable $exception) {
            foreach ($storedFiles as $storedFile) {
                delete_upload_file($storedFile);
            }
            throw $exception;
        }
    }

    private function storeReturnEvidence(array $file, $type)
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                throw new DomainException('Một tệp vượt quá giới hạn tải lên của máy chủ.');
            }
            throw new DomainException('Vui lòng chọn đủ hình ảnh và video minh chứng hợp lệ.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        $maxSize = $type === 'image' ? 5 * 1024 * 1024 : 50 * 1024 * 1024;
        if ($tmpName === '' || !is_uploaded_file($tmpName) || $size < 1024 || $size > $maxSize) {
            throw new DomainException($type === 'image'
                ? 'Mỗi ảnh cần có dung lượng từ 1KB đến 5MB.'
                : 'Video cần có dung lượng từ 1KB đến 50MB.');
        }

        if (!class_exists('finfo')) {
            throw new DomainException('Máy chủ chưa hỗ trợ kiểm tra tệp tải lên. Vui lòng liên hệ quản trị viên.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
        $extensions = $type === 'image'
            ? ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif']
            : ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
        if (!is_string($mime) || !isset($extensions[$mime])) {
            throw new DomainException($type === 'image'
                ? 'Ảnh chỉ hỗ trợ định dạng JPG, PNG, WEBP hoặc GIF.'
                : 'Video chỉ hỗ trợ định dạng MP4, WEBM hoặc MOV.');
        }
        if ($type === 'image' && !@getimagesize($tmpName)) {
            throw new DomainException('Tệp hình ảnh không hợp lệ.');
        }

        $folder = 'returns';
        $directory = PATH_ASSETS_UPLOADS . $folder . DIRECTORY_SEPARATOR;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new DomainException('Không thể lưu minh chứng trả hàng. Vui lòng liên hệ quản trị viên.');
        }
        $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($tmpName, $directory . $fileName)) {
            throw new DomainException('Không thể lưu minh chứng trả hàng. Vui lòng thử lại sau.');
        }

        return $folder . '/' . $fileName;
    }
}
