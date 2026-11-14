<?php

class WishlistController extends BaseController
{
    public function index()
    {
        $this->guardCustomer();
        $items = [];
        $databaseError = null;

        try {
            $items = (new WishlistModel())->getForUser((int) $_SESSION['user']['id']);
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Chưa thể tải danh sách truyện yêu thích.';
        }

        $title = 'Truyện yêu thích';
        $view = 'wishlist/index';
        require PATH_VIEW . 'main.php';
    }

    public function toggle()
    {
        $this->guardCustomer();
        $this->requirePost();
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

        if (!$productId) {
            flash('warning', 'Sản phẩm không hợp lệ.');
            $this->redirectBack('wishlist');
        }

        try {
            $product = (new ProductModel())->find($productId);
            if (!$product) {
                flash('warning', 'Sản phẩm không tồn tại hoặc đã ngừng bán.');
                $this->redirectBack('products');
            }

            $added = (new WishlistModel())->toggle((int) $_SESSION['user']['id'], $productId);
            flash(
                'success',
                $added
                    ? 'Đã thêm "' . $product['name'] . '" vào truyện yêu thích.'
                    : 'Đã bỏ "' . $product['name'] . '" khỏi truyện yêu thích.'
            );
            $this->redirectBack('wishlist');
        } catch (PDOException | RuntimeException $exception) {
            flash('danger', $this->reportDatabaseFailure($exception));
            $this->redirectBack('wishlist');
        }
    }

    private function guardCustomer()
    {
        if (empty($_SESSION['user']['id'])) {
            flash('warning', 'Vui lòng đăng nhập để dùng danh sách truyện yêu thích.');
            redirect('login');
        }
    }

    private function redirectBack($fallback)
    {
        $target = trim(request_string($_POST, 'redirect'));
        if (
            $target === ''
            || preg_match('#^(?:[a-z]+:)?//#i', $target)
            || strpos($target, '..') !== false
        ) {
            $target = $fallback;
        }

        redirect(ltrim($target, '/'));
    }
}
