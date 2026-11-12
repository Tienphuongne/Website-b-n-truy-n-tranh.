<?php

class ProductsController extends BaseController
{
    public function index()
    {
        $filters = [
            'q' => trim(substr(request_string($_GET, 'q'), 0, 100)),
            'category' => filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: '',
            'genre' => filter_input(INPUT_GET, 'genre', FILTER_VALIDATE_INT) ?: '',
            'author' => filter_input(INPUT_GET, 'author', FILTER_VALIDATE_INT) ?: '',
            'min_price' => $this->priceFilter($_GET['min_price'] ?? ''),
            'max_price' => $this->priceFilter($_GET['max_price'] ?? ''),
            'in_stock' => !empty($_GET['in_stock']),
            'sort' => request_string($_GET, 'sort', 'newest'),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => 12];
        $categories = $genres = $authors = [];
        $databaseError = null;

        try {
            $model = new ProductModel();
            $result = $model->search($filters, $page);
            $categories = $model->getCategories();
            $genres = $model->getGenresList();
            $authors = $model->getAuthors();
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Chưa thể tải danh sách truyện. Vui lòng thử lại sau.';
        }

        $title = 'Tất cả truyện tranh';
        $view = 'products/index';
        require PATH_VIEW . 'main.php';
    }

    public function detail()
    {
        // Router gán id vào $_GET, filter_input() không đọc được giá trị này.
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $product = null;
        $genres = [];
        $images = [];
        $reviews = [];
        $related = [];
        $databaseError = null;

        try {
            $model = new ProductModel();
            if ($id) {
                $product = $model->find($id);
                if ($product) {
                    $genres = $model->getGenres($id);
                    $images = $model->getImages($id);
                    $related = $model->getRelated($id, $product['category_id']);
                    $reviews = (new ReviewModel())->getApprovedForProduct($id);
                }
            }
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Chưa thể tải thông tin sản phẩm. Vui lòng thử lại sau.';
        }

        if (!$product && !$databaseError) {
            http_response_code(404);
        }

        $title = $product ? $product['name'] : 'Không tìm thấy truyện';
        $view = 'products/detail';
        require PATH_VIEW . 'main.php';
    }

    private function priceFilter($value)
    {
        if ($value === '' || !is_numeric($value)) {
            return '';
        }

        return max(0, (float) $value);
    }
}
