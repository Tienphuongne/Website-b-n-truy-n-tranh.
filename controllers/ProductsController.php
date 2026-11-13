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
        if (!in_array($filters['sort'], ['newest', 'best_selling', 'price_asc', 'price_desc', 'rating'], true)) {
            $filters['sort'] = 'newest';
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => 12];
        $categories = $genres = $authors = [];
        $activeFilters = [];
        $databaseError = null;

        try {
            $model = new ProductModel();
            $result = $model->search($filters, $page);
            $categories = $model->getCategories();
            $genres = $model->getGenresList();
            $authors = $model->getAuthors();
            $activeFilters = $this->activeFilters($filters, $categories, $genres, $authors);
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Chưa thể tải danh sách truyện. Vui lòng thử lại sau.';
        }

        $hasKeyword = $filters['q'] !== '';
        $heading = $hasKeyword ? 'Kết quả cho “' . $filters['q'] . '”' : 'Tất cả truyện tranh';
        $subheading = $hasKeyword
            ? 'Tìm theo tên truyện, tác giả, nhà xuất bản hoặc nội dung.'
            : 'Tìm câu chuyện phù hợp với bạn.';
        $title = $hasKeyword ? 'Tìm kiếm: ' . $filters['q'] : 'Tất cả truyện tranh';
        $view = 'products/index';
        require PATH_VIEW . 'main.php';
    }

    public function detail()
    {
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
                    $related = $model->getRelated($id, $product['category_id'], $product['author_id']);
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

    private function activeFilters(array $filters, array $categories, array $genres, array $authors)
    {
        $categoryNames = $this->nameMap($categories);
        $genreNames = $this->nameMap($genres);
        $authorNames = $this->nameMap($authors);
        $chips = [];

        if ($filters['q'] !== '') {
            $chips[] = $this->filterChip('Từ khóa: “' . $filters['q'] . '”', $this->filterUrl($filters, 'q'));
        }
        if (!empty($filters['category']) && isset($categoryNames[(int) $filters['category']])) {
            $chips[] = $this->filterChip(
                'Danh mục: ' . $categoryNames[(int) $filters['category']],
                $this->filterUrl($filters, 'category')
            );
        }
        if (!empty($filters['genre']) && isset($genreNames[(int) $filters['genre']])) {
            $chips[] = $this->filterChip(
                'Thể loại: ' . $genreNames[(int) $filters['genre']],
                $this->filterUrl($filters, 'genre')
            );
        }
        if (!empty($filters['author']) && isset($authorNames[(int) $filters['author']])) {
            $chips[] = $this->filterChip(
                'Tác giả: ' . $authorNames[(int) $filters['author']],
                $this->filterUrl($filters, 'author')
            );
        }
        if ($filters['min_price'] !== '') {
            $chips[] = $this->filterChip(
                'Giá từ ' . number_format((float) $filters['min_price'], 0, ',', '.') . '₫',
                $this->filterUrl($filters, 'min_price')
            );
        }
        if ($filters['max_price'] !== '') {
            $chips[] = $this->filterChip(
                'Giá đến ' . number_format((float) $filters['max_price'], 0, ',', '.') . '₫',
                $this->filterUrl($filters, 'max_price')
            );
        }
        if ($filters['in_stock']) {
            $chips[] = $this->filterChip('Chỉ còn hàng', $this->filterUrl($filters, 'in_stock'));
        }

        return $chips;
    }

    private function filterChip($label, $removeUrl)
    {
        return ['label' => $label, 'url' => $removeUrl];
    }

    private function nameMap(array $rows)
    {
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = $row['name'];
        }

        return $map;
    }

    private function filterUrl(array $filters, $unsetKey)
    {
        $query = [];
        foreach ($filters as $key => $value) {
            if ($key === $unsetKey || $value === '' || $value === false) {
                continue;
            }
            if ($key === 'sort' && $value === 'newest') {
                continue;
            }
            $query[$key] = $value;
        }

        return 'products' . ($query ? '?' . http_build_query($query) : '');
    }

    private function priceFilter($value)
    {
        if ($value === '' || !is_numeric($value)) {
            return '';
        }

        return max(0, (float) $value);
    }
}
