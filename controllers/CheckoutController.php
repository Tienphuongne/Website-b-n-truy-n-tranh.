<?php

class CheckoutController extends BaseController
{
    private $shippingFee = 30000;

    public function index()
    {
        $items = [];
        $databaseError = null;
        $coupon = null;
        $discount = 0;
        $orderComplete = filter_input(INPUT_GET, 'success', FILTER_VALIDATE_INT) ?: 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            if (isset($_POST['apply_coupon'])) {
                try {
                    $cartItems = $this->loadCart();
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
                    redirect('checkout');
                } catch (DomainException $exception) {
                    flash('warning', $exception->getMessage());
                    redirect('cart');
                } catch (PDOException | RuntimeException $exception) {
                    flash('danger', $this->reportDatabaseFailure($exception));
                    redirect('checkout');
                }
            }

            $customer = [
                'full_name' => trim(request_string($_POST, 'full_name')),
                'phone' => trim(request_string($_POST, 'phone')),
                'email' => trim(request_string($_POST, 'email')),
                'city' => trim(request_string($_POST, 'city')),
                'district' => trim(request_string($_POST, 'district')),
                'address' => trim(request_string($_POST, 'address')),
                'note' => trim(request_string($_POST, 'note')),
            ];
            $paymentMethod = $_POST['payment_method'] ?? 'cod';
            $errors = $this->validate($customer, $paymentMethod);

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
                    if (!empty($_SESSION['coupon'])) {
                        $coupon = (new CouponModel())->findValidByCode(
                            $_SESSION['coupon'],
                            $this->subtotal($cartItems)
                        );
                        if (!$coupon) {
                            unset($_SESSION['coupon']);
                            throw new DomainException('Mã giảm giá đã hết hiệu lực. Vui lòng kiểm tra lại đơn hàng.');
                        }
                    }
                    $orderId = (new OrderModel())->createOrder(
                        $customer,
                        $cartItems,
                        $paymentMethod,
                        $this->shippingFee,
                        $_SESSION['user']['id'] ?? null,
                        $coupon ? (int) $coupon['id'] : null
                    );
                    $_SESSION['cart'] = [];
                    unset($_SESSION['coupon']);
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
            if (!empty($_SESSION['coupon']) && $items) {
                $coupon = (new CouponModel())->findValidByCode(
                    $_SESSION['coupon'],
                    $this->subtotal($items)
                );
                if (!$coupon) {
                    unset($_SESSION['coupon']);
                } else {
                    $discount = (new CouponModel())->discount($coupon, $this->subtotal($items));
                }
            }
        } catch (DomainException $exception) {
            flash('warning', $exception->getMessage());
            redirect('cart');
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Chưa thể tải thông tin thanh toán.';
        }

        $title = 'Thanh toán';
        $view = 'checkout/index';
        require PATH_VIEW . 'main.php';
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

    private function validate(array $customer, $paymentMethod)
    {
        $errors = [];
        foreach (['full_name', 'phone', 'city', 'district', 'address'] as $field) {
            if ($customer[$field] === '') {
                $errors[] = 'Vui lòng nhập đầy đủ thông tin nhận hàng.';
                break;
            }
        }
        if ($customer['email'] !== '' && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ.';
        }
        if (!in_array($paymentMethod, ['cod', 'bank_transfer'], true)) {
            $errors[] = 'Phương thức thanh toán không hợp lệ.';
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
