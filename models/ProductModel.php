<?php

class ProductModel extends BaseModel
{
    public function getFeatured($limit = 8)
    {
        return $this->getPublishedProducts('p.is_featured = 1', [], $limit);
    }

    public function getLatest($limit = 8)
    {
        return $this->getPublishedProducts('1 = 1', [], $limit, 'p.created_at DESC');
    }

    public function getBestSellers($limit = 8)
    {
        return $this->getPublishedProducts('p.sold_quantity > 0', [], $limit, 'p.sold_quantity DESC');
    }

    public function getDiscounted($limit = 8)
    {
        return $this->getPublishedProducts(
            'p.original_price IS NOT NULL AND p.original_price > p.sale_price',
            [],
            $limit,
            'p.created_at DESC'
        );
    }

    public function getMostLoved($limit = 8)
    {
        $limit = max(1, min(24, (int) $limit));

        return $this->execute(
            "SELECT p.*, a.name AS author_name, r.review_count, r.avg_rating
             FROM products p
             LEFT JOIN authors a ON a.id = p.author_id
             INNER JOIN (
                 SELECT product_id, COUNT(*) AS review_count, AVG(rating) AS avg_rating
                 FROM reviews WHERE status = 'approved' GROUP BY product_id
             ) r ON r.product_id = p.id
             WHERE p.status = 'active'
             ORDER BY review_count DESC, avg_rating DESC, p.sold_quantity DESC, p.id DESC
             LIMIT $limit"
        )->fetchAll();
    }

    public function search(array $filters, $page = 1, $perPage = 12)
    {
        $conditions = ["p.status = 'active'"];
        $params = [];

        if (!empty($filters['q'])) {
            $search = '%' . $filters['q'] . '%';
            $conditions[] = '(p.name LIKE :search_name
                OR p.short_description LIKE :search_description
                OR p.description LIKE :search_long_description
                OR EXISTS (SELECT 1 FROM authors sa WHERE sa.id = p.author_id AND sa.name LIKE :search_author)
                OR EXISTS (SELECT 1 FROM publishers sp WHERE sp.id = p.publisher_id AND sp.name LIKE :search_publisher))';
            $params['search_name'] = $search;
            $params['search_description'] = $search;
            $params['search_long_description'] = $search;
            $params['search_author'] = $search;
            $params['search_publisher'] = $search;
        }
        if (!empty($filters['category'])) {
            $conditions[] = 'p.category_id = :category';
            $params['category'] = (int) $filters['category'];
        }
        if (!empty($filters['author'])) {
            $conditions[] = 'p.author_id = :author';
            $params['author'] = (int) $filters['author'];
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $conditions[] = 'p.sale_price >= :min_price';
            $params['min_price'] = (float) $filters['min_price'];
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $conditions[] = 'p.sale_price <= :max_price';
            $params['max_price'] = (float) $filters['max_price'];
        }
        if (!empty($filters['in_stock'])) {
            $conditions[] = 'p.stock_quantity > 0';
        }
        if (!empty($filters['genre'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM product_genres pg WHERE pg.product_id = p.id AND pg.genre_id = :genre)';
            $params['genre'] = (int) $filters['genre'];
        }

        $sortOptions = [
            'price_asc' => 'p.sale_price ASC, p.id DESC',
            'price_desc' => 'p.sale_price DESC, p.id DESC',
            'best_selling' => 'p.sold_quantity DESC, p.id DESC',
            'rating' => 'COALESCE(rs.avg_rating, 0) DESC, COALESCE(rs.review_count, 0) DESC, p.id DESC',
            'newest' => 'p.created_at DESC, p.id DESC',
        ];
        $orderBy = $sortOptions[$filters['sort'] ?? 'newest'] ?? $sortOptions['newest'];
        $where = implode(' AND ', $conditions);
        $count = $this->execute(
            "SELECT COUNT(*) FROM products p WHERE $where",
            $params
        )->fetchColumn();

        $page = max(1, (int) $page);
        $perPage = max(1, min(48, (int) $perPage));
        $offset = ($page - 1) * $perPage;
        $items = $this->execute(
            "SELECT p.*, c.name AS category_name, a.name AS author_name,
                    COALESCE(rs.avg_rating, 0) AS avg_rating,
                    COALESCE(rs.review_count, 0) AS review_count
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN authors a ON a.id = p.author_id
             LEFT JOIN (
                 SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                 FROM reviews WHERE status = 'approved' GROUP BY product_id
             ) rs ON rs.product_id = p.id
             WHERE $where
             ORDER BY $orderBy
             LIMIT $perPage OFFSET $offset",
            $params
        )->fetchAll();

        return [
            'items' => $items,
            'total' => (int) $count,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function find($id)
    {
        return $this->execute(
            "SELECT p.*, c.name AS category_name, a.name AS author_name, pub.name AS publisher_name,
                    (SELECT AVG(r.rating) FROM reviews r
                     WHERE r.product_id = p.id AND r.status = 'approved') AS avg_rating,
                    (SELECT COUNT(*) FROM reviews r
                     WHERE r.product_id = p.id AND r.status = 'approved') AS review_count
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN authors a ON a.id = p.author_id
             LEFT JOIN publishers pub ON pub.id = p.publisher_id
             WHERE p.id = :id AND p.status = 'active'
             LIMIT 1",
            ['id' => (int) $id]
        )->fetch();
    }

    public function getGenres($productId)
    {
        return $this->execute(
            'SELECT g.id, g.name FROM genres g
             INNER JOIN product_genres pg ON pg.genre_id = g.id
             WHERE pg.product_id = :product_id ORDER BY g.name',
            ['product_id' => (int) $productId]
        )->fetchAll();
    }

    public function getImages($productId)
    {
        return $this->execute(
            'SELECT image_path FROM product_images WHERE product_id = :product_id ORDER BY sort_order, id',
            ['product_id' => (int) $productId]
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getRelated($productId, $categoryId, $authorId = null, $limit = 4)
    {
        $limit = max(1, min(12, (int) $limit));
        $params = ['product_id' => (int) $productId];
        $matches = [];
        $order = [];

        if ((int) $categoryId) {
            $matches[] = 'p.category_id = :category_id';
            $params['category_id'] = (int) $categoryId;
            $order[] = '(p.category_id = :order_category_id) DESC';
            $params['order_category_id'] = (int) $categoryId;
        }
        if ((int) $authorId) {
            $matches[] = 'p.author_id = :author_id';
            $params['author_id'] = (int) $authorId;
            $order[] = '(p.author_id = :order_author_id) DESC';
            $params['order_author_id'] = (int) $authorId;
        }

        $matches[] = 'EXISTS (
            SELECT 1 FROM product_genres pg
            WHERE pg.product_id = p.id
              AND pg.genre_id IN (SELECT genre_id FROM product_genres WHERE product_id = :genre_product_id)
        )';
        $params['genre_product_id'] = (int) $productId;
        $order[] = 'p.is_featured DESC';
        $order[] = 'p.sold_quantity DESC';
        $order[] = 'p.created_at DESC';
        $order[] = 'p.id DESC';

        return $this->execute(
            "SELECT p.*, a.name AS author_name,
                    COALESCE(rs.avg_rating, 0) AS avg_rating,
                    COALESCE(rs.review_count, 0) AS review_count
             FROM products p
             LEFT JOIN authors a ON a.id = p.author_id
             LEFT JOIN (
                 SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                 FROM reviews WHERE status = 'approved' GROUP BY product_id
             ) rs ON rs.product_id = p.id
             WHERE p.status = 'active' AND p.id <> :product_id
               AND (" . implode(' OR ', $matches) . ')
             ORDER BY ' . implode(', ', $order) . "
             LIMIT $limit",
            $params
        )->fetchAll();
    }

    public function getByIds(array $ids)
    {
        $ids = array_values(array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        }));
        if (!$ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return $this->execute(
            "SELECT p.*, a.name AS author_name
             FROM products p
             LEFT JOIN authors a ON a.id = p.author_id
             WHERE p.id IN ($placeholders) AND p.status = 'active'",
            $ids
        )->fetchAll();
    }

    public function getCategories()
    {
        return $this->execute(
            'SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name'
        )->fetchAll();
    }

    public function getGenresList()
    {
        return $this->execute('SELECT id, name FROM genres ORDER BY name')->fetchAll();
    }

    public function getAuthors()
    {
        return $this->execute('SELECT id, name FROM authors ORDER BY name')->fetchAll();
    }

    private function getPublishedProducts($condition, array $params, $limit, $orderBy = 'p.sold_quantity DESC')
    {
        $limit = max(1, min(24, (int) $limit));

        return $this->execute(
            "SELECT p.*, a.name AS author_name,
                    COALESCE(rs.avg_rating, 0) AS avg_rating,
                    COALESCE(rs.review_count, 0) AS review_count
             FROM products p
             LEFT JOIN authors a ON a.id = p.author_id
             LEFT JOIN (
                 SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                 FROM reviews WHERE status = 'approved' GROUP BY product_id
             ) rs ON rs.product_id = p.id
             WHERE p.status = 'active' AND $condition
             ORDER BY $orderBy, p.id DESC
             LIMIT $limit",
            $params
        )->fetchAll();
    }
}
