<?php

class ReviewController extends BaseController
{
    public function create()
    {
        $this->requirePost();
        if (empty($_SESSION['user'])) {
            flash('warning', 'Vui lòng đăng nhập để gửi đánh giá.');
            redirect('login');
        }

        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
        $content = trim(request_string($_POST, 'content'));
        if (!$productId || !$rating || $rating < 1 || $rating > 5 || strlen($content) > 3000) {
            flash('danger', 'Đánh giá không hợp lệ.');
            redirect($productId ? 'product/detail/' . $productId : 'products');
        }

        try {
            if (!(new ProductModel())->find($productId)) {
                flash('warning', 'Không tìm thấy sản phẩm cần đánh giá.');
                redirect('products');
            }
            (new ReviewModel())->create(
                $productId,
                (int) $_SESSION['user']['id'],
                $rating,
                $content
            );
            flash('success', 'Cảm ơn bạn! Đánh giá sẽ hiển thị sau khi được duyệt.');
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            flash('danger', 'Không thể gửi đánh giá. Bạn có thể đã đánh giá truyện này trước đó.');
        } catch (RuntimeException $exception) {
            flash('danger', $this->reportDatabaseFailure($exception));
        }

        redirect('product/detail/' . $productId);
    }
}
