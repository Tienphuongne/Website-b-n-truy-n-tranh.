<?php

class CartController extends BaseController
{
    public function index()
    {
        $cart = $_SESSION['cart'] ?? [];
        $products = [];
        $databaseError = null;

        try {
            if ($cart) {
                $products = (new ProductModel())->getByIds(array_keys($cart));
            }
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Chưa thể tải giỏ hàng. Vui lòng thử lại sau.';
        }

        $items = [];
        foreach ($products as $product) {
            $product['quantity'] = min(
                max(1, (int) ($cart[$product['id']] ?? 1)),
                max(1, (int) $product['stock_quantity'])
            );
            $items[] = $product;
        }

        $title = 'Giỏ hàng';
        $view = 'cart/index';
        require PATH_VIEW . 'main.php';
    }

    public function add()
    {
        $this->requirePost();
        $id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $quantity = max(1, min(99, (int) ($_POST['quantity'] ?? 1)));

        try {
            $product = $id ? (new ProductModel())->find($id) : false;
        } catch (PDOException | RuntimeException $exception) {
            flash('danger', $this->reportDatabaseFailure($exception));
            redirect('products');
        }

        if (!$product || (int) $product['stock_quantity'] < 1) {
            flash('warning', 'Sản phẩm hiện không có sẵn.');
            redirect($id ? 'product/detail/' . $id : 'products');
        }

        $cart = $_SESSION['cart'] ?? [];
        $cart[$id] = min(
            (int) ($cart[$id] ?? 0) + $quantity,
            (int) $product['stock_quantity']
        );
        $_SESSION['cart'] = $cart;
        flash('success', 'Đã thêm truyện vào giỏ hàng.');
        redirect(isset($_POST['buy_now']) ? 'checkout' : 'cart');
    }

    public function update()
    {
        $this->requirePost();
        $id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if (!$id || !$quantity) {
            flash('warning', 'Số lượng không hợp lệ.');
            redirect('cart');
        }

        try {
            $product = (new ProductModel())->find($id);
        } catch (PDOException | RuntimeException $exception) {
            flash('danger', $this->reportDatabaseFailure($exception));
            redirect('cart');
        }

        if (!$product || (int) $product['stock_quantity'] < 1) {
            unset($_SESSION['cart'][$id]);
            flash('warning', 'Sản phẩm không còn khả dụng và đã được xóa khỏi giỏ.');
        } else {
            $_SESSION['cart'][$id] = min(max(1, $quantity), (int) $product['stock_quantity']);
            flash('success', 'Đã cập nhật giỏ hàng.');
        }
        redirect('cart');
    }

    public function remove()
    {
        $this->requirePost();
        $id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        if ($id) {
            unset($_SESSION['cart'][$id]);
            flash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
        }
        redirect('cart');
    }
}
