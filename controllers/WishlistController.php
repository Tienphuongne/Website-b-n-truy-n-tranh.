<?php

class WishlistController extends BaseController
{
    public function toggle()
    {
        $this->requirePost();
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $returnTo = $this->returnTarget('products');
        $userId = (int) ($_SESSION['user']['id'] ?? 0);

        if ($userId < 1) {
            flash('warning', 'Vui lòng đăng nhập để lưu truyện yêu thích.');
            redirect('login');
        }
        if (!$productId) {
            flash('warning', 'Truyện không hợp lệ.');
            redirect($returnTo);
        }

        try {
            $product = (new ProductModel())->find($productId);
            if (!$product) {
                flash('warning', 'Không tìm thấy truyện này.');
                redirect($returnTo);
            }
            $added = (new WishlistModel())->toggle($userId, $productId);
            refresh_wishlist_ids();
            flash('success', $added ? 'Đã thêm truyện vào danh sách yêu thích.' : 'Đã bỏ truyện khỏi danh sách yêu thích.');
        } catch (PDOException | RuntimeException $exception) {
            flash('danger', $this->reportDatabaseFailure($exception));
        }

        redirect($returnTo === 'wishlist' ? 'account?tab=wishlist' : $returnTo);
    }

    public function remove()
    {
        $this->requirePost();
        if (empty($_SESSION['user'])) {
            redirect('login');
        }

        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        if ($productId) {
            try {
                (new WishlistModel())->remove((int) $_SESSION['user']['id'], $productId);
                refresh_wishlist_ids();
                flash('success', 'Đã xóa truyện khỏi danh sách yêu thích.');
            } catch (PDOException | RuntimeException $exception) {
                flash('danger', $this->reportDatabaseFailure($exception));
            }
        }

        redirect('account?tab=wishlist');
    }

    private function returnTarget($fallback)
    {
        $target = trim(request_string($_POST, 'return'));
        if (
            $target === ''
            || str_starts_with($target, '//')
            || str_starts_with($target, 'http:')
            || str_starts_with($target, 'https:')
            || !preg_match('#^[A-Za-z0-9_/?=&.%:-]*$#', $target)
        ) {
            return $fallback;
        }

        return ltrim($target, '/');
    }
}
