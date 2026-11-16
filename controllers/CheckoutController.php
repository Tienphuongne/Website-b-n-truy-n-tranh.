<?php

class CheckoutController extends BaseController
{
    public function index()
    {
        $items = [];
        $databaseError = null;
        $coupon = null;
        $discount = 0;
        $orderComplete = filter_input(INPUT_GET, 'success', FILTER_VALIDATE_INT) ?: 0;
        $savedAddresses = [];
        $selectedAddressId = 0;
        $shippingMethod = request_string($_POST, 'shipping_method', 'standard');
        if (!in_array($shippingMethod, shipping_methods(), true) && !array_key_exists($shippingMethod, shipping_methods())) {
            $shippingMethod = 'standard';
        }
        $shippingMethods = shipping_methods();
        $shippingMethod = array_key_exists($shippingMethod, $shippingMethods) ? $shippingMethod : 'standard';

        $form = $this->defaultForm();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            $shippingMethod = request_string($_POST, 'shipping_method', 'standard');
            if (!array_key_exists($shippingMethod, $shippingMethods)) {
                $shippingMethod = 'standard';
            }
            $form = $this->formFromPost($_POST);

            if (isset($_POST['apply_coupon'])) {
                try {
                    $cartItems = $this->loadCart();
                    if (!$cartItems) {
                        flash('warning', 'Giỏ hàng đang trống, chưa thể áp dụng mã giảm giá.');
                        redirect('cart');
                    }
                    $subtotal = $this->subtotal($cartItems);
                    $coupon = (new CouponModel())->findValidByCode(
                        request_string($_POST, 'coupon'),
                        $subtotal
                    );
                    if (!$coupon) {
                        unset($_SESSION['coupon']);
                        flash('warning', 'Mã giảm giá không hợp lệ hoặc đơn hàng chưa đạt giá trị tối thiểu.');
                    } else {
                        $_SESSION['coupon'] = $coupon['code'];
                        flash('success', 'Đã áp dụng mã giảm giá.');
                    }
                    $this->storeDraft($form, $shippingMethod);
                    redirect('checkout');
                } catch (DomainException $exception) {
                    flash('warning', $exception->getMessage());
                    redirect('cart');
                } catch (PDOException | RuntimeException $exception) {
                    flash('danger', $this->reportDatabaseFailure($exception));
                    $this->storeDraft($form, $shippingMethod);
                    redirect('checkout');
                }
            }

            $errors = $this->validate($form, request_string($_POST, 'payment_method', 'cod'), $shippingMethod);

            if ($errors) {
                flash('danger', implode(' ', $errors));
            } else {
                try {
                    $cartItems = $this->loadCart();
                    if (!$cartItems) {
                        flash('warning', 'Giỏ hàng đang trống.');
                        redirect('cart');
                    }
                    $coupon = null;
                    $subtotal = $this->subtotal($cartItems);
                    if (!empty($_SESSION['coupon'])) {
                        $coupon = (new CouponModel())->findValidByCode($_SESSION['coupon'], $subtotal);
                        if (!$coupon) {
                            unset($_SESSION['coupon']);
                            throw new DomainException('Mã giảm giá đã hết hiệu lực. Vui lòng kiểm tra lại đơn hàng.');
                        }
                    }
                    $shippingFee = shipping_fee_for($shippingMethod, $subtotal);
                    $orderId = (new OrderModel())->createOrder(
                        $form,
                        $cartItems,
                        request_string($_POST, 'payment_method', 'cod'),
                        $shippingFee,
                        $_SESSION['user']['id'] ?? null,
                        $coupon ? (int) $coupon['id'] : null,
                        $shippingMethod
                    );

                    if (!empty($_SESSION['user']['id']) && isset($_POST['save_address'])) {
                        try {
                            (new AddressModel())->saveForUser((int) $_SESSION['user']['id'], [
                                'label' => trim(request_string($_POST, 'address_label', 'Nhà riêng')),
                                'full_name' => $form['full_name'],
                                'phone' => $form['phone'],
                                'city' => $form['city'],
                                'district' => $form['district'],
                                'ward' => $form['ward'],
                                'address' => $form['address'],
                            ]);
                        } catch (Throwable $e) {
                            error_log($e->getMessage());
                        }
                    }

                    try {
                        $orderForEmail = (new OrderModel())->getById($orderId);
                        if ($orderForEmail) {
                            send_order_confirmation_email($orderForEmail);
                        }
                    } catch (Throwable $e) {
                        error_log('Order confirmation email failed: ' . $e->getMessage());
                    }

                    $_SESSION['cart'] = [];
                    unset($_SESSION['coupon'], $_SESSION['checkout_draft']);
                    redirect('checkout?success=' . $orderId);
                } catch (DomainException $exception) {
                    flash('warning', $exception->getMessage());
                } catch (PDOException | RuntimeException $exception) {
                    $databaseError = $this->reportDatabaseFailure($exception);
                }
            }
        }

        try {
            $items = $this->loadCart();
            $subtotal = $this->subtotal($items);
            if (!empty($_SESSION['coupon']) && $items) {
                $coupon = (new CouponModel())->findValidByCode($_SESSION['coupon'], $subtotal);
                if (!$coupon) {
                    unset($_SESSION['coupon']);
                } else {
                    $discount = (new CouponModel())->discount($coupon, $subtotal);
                }
            }

            if (!empty($_SESSION['user']['id'])) {
                $savedAddresses = (new AddressModel())->forUser((int) $_SESSION['user']['id']);
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $form = $this->prefillForm($savedAddresses);
                $shippingMethod = $_SESSION['checkout_draft']['shipping_method'] ?? 'standard';
                if (!array_key_exists($shippingMethod, $shippingMethods)) {
                    $shippingMethod = 'standard';
                }
            }
            $selectedAddressId = $this->detectSelectedAddress($form, $savedAddresses);
        } catch (DomainException $exception) {
            flash('warning', $exception->getMessage());
            redirect('cart');
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = $databaseError ?: 'Chưa thể tải thông tin thanh toán.';
        }

        $subtotal = $this->subtotal($items);
        $shippingFee = shipping_fee_for($shippingMethod, $subtotal);
        $deliveryEta = delivery_window($shippingMethod);
        $provinceGroups = vietnam_provinces();

        $title = 'Thanh toán';
        $view = 'checkout/index';
        require PATH_VIEW . 'main.php';
    }

    private function defaultForm()
    {
        return [
            'full_name' => '',
            'phone' => '',
            'email' => '',
            'city' => '',
            'district' => '',
            'ward' => '',
            'address' => '',
            'note' => '',
        ];
    }

    private function formFromPost(array $post)
    {
        $phone = normalize_phone_number(trim(request_string($post, 'phone')));

        return [
            'full_name' => trim(request_string($post, 'full_name')),
            'phone' => $phone,
            'email' => trim(request_string($post, 'email')),
            'city' => trim(request_string($post, 'city')),
            'district' => trim(request_string($post, 'district')),
            'ward' => trim(request_string($post, 'ward')),
            'address' => trim(request_string($post, 'address')),
            'note' => trim(request_string($post, 'note')),
        ];
    }

    private function prefillForm(array $savedAddresses)
    {
        $draft = $_SESSION['checkout_draft']['form'] ?? null;
        if (is_array($draft)) {
            return array_merge($this->defaultForm(), array_intersect_key($draft, $this->defaultForm()));
        }

        $form = $this->defaultForm();
        if (!empty($savedAddresses)) {
            $default = $savedAddresses[0];
            $form['full_name'] = (string) ($default['full_name'] ?? '');
            $form['phone'] = (string) ($default['phone'] ?? '');
            $form['city'] = (string) ($default['city'] ?? '');
            $form['district'] = (string) ($default['district'] ?? '');
            $form['ward'] = (string) ($default['ward'] ?? '');
            $form['address'] = (string) ($default['address'] ?? '');
        } elseif (!empty($_SESSION['user']['id'])) {
            try {
                $user = (new UserModel())->findById((int) $_SESSION['user']['id']);
                if ($user) {
                    $form['full_name'] = (string) ($user['full_name'] ?? '');
                    $form['phone'] = normalize_phone_number($user['phone'] ?? '');
                    $form['email'] = (string) ($user['email'] ?? '');
                }
                $last = (new OrderModel())->getLatestAddressForUser((int) $_SESSION['user']['id']);
                if ($last) {
                    $form['full_name'] = $form['full_name'] ?: (string) ($last['customer_name'] ?? '');
                    $form['phone'] = $form['phone'] ?: (string) ($last['phone'] ?? '');
                    $form['email'] = $form['email'] ?: (string) ($last['email'] ?? '');
                    $form['city'] = (string) ($last['city'] ?? '');
                    $form['district'] = (string) ($last['district'] ?? '');
                    $form['ward'] = (string) ($last['ward'] ?? '');
                    $form['address'] = (string) ($last['address'] ?? '');
                }
            } catch (Throwable $e) {
                error_log($e->getMessage());
            }
        }

        if (!empty($_SESSION['user'])) {
            $form['full_name'] = $form['full_name'] ?: (string) ($_SESSION['user']['name'] ?? '');
            $form['email'] = $form['email'] ?: (string) ($_SESSION['user']['email'] ?? '');
            $form['phone'] = $form['phone'] ?: normalize_phone_number($_SESSION['user']['phone'] ?? '');
        }

        return $form;
    }

    private function detectSelectedAddress(array $form, array $savedAddresses)
    {
        foreach ($savedAddresses as $addr) {
            if (
                trim((string) $addr['full_name']) === $form['full_name']
                && normalize_phone_number($addr['phone'] ?? '') === normalize_phone_number($form['phone'])
                && trim((string) $addr['city']) === $form['city']
                && trim((string) ($addr['ward'] ?? '')) === $form['ward']
                && trim((string) $addr['address']) === $form['address']
            ) {
                return (int) $addr['id'];
            }
        }

        return 0;
    }

    private function storeDraft(array $form, $shippingMethod)
    {
        $_SESSION['checkout_draft'] = [
            'form' => $form,
            'shipping_method' => $shippingMethod,
        ];
    }

    private function loadCart()
    {
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) {
            return [];
        }

        $products = (new ProductModel())->getByIds(array_keys($cart));
        $items = [];
        foreach ($products as $product) {
            $quantity = max(1, (int) ($cart[$product['id']] ?? 1));
            if ((int) $product['stock_quantity'] < $quantity) {
                throw new DomainException('Số lượng truyện "' . $product['name'] . '" trong kho không đủ.');
            }
            $product['quantity'] = $quantity;
            $items[] = $product;
        }

        return $items;
    }

    private function validate(array $customer, $paymentMethod, $shippingMethod)
    {
        $errors = [];
        foreach (['full_name', 'phone', 'city', 'address'] as $field) {
            if (trim((string) ($customer[$field] ?? '')) === '') {
                $errors[] = 'Vui lòng nhập đầy đủ thông tin nhận hàng (họ tên, số điện thoại, tỉnh/thành và địa chỉ chi tiết).';
                break;
            }
        }
        if (!is_valid_phone_number($customer['phone'] ?? '')) {
            $errors[] = 'Số điện thoại không hợp lệ. Vui lòng nhập 10 chữ số (bắt đầu bằng 0).';
        }
        if (($customer['email'] ?? '') !== '' && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ.';
        }
        if (!in_array($paymentMethod, ['cod', 'bank_transfer'], true)) {
            $errors[] = 'Phương thức thanh toán không hợp lệ.';
        }
        if (!array_key_exists($shippingMethod, shipping_methods())) {
            $errors[] = 'Phương thức giao hàng không hợp lệ.';
        }

        return $errors;
    }

    private function subtotal(array $items)
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float) $item['sale_price'] * (int) $item['quantity'];
        }

        return $subtotal;
    }
}
