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
            "SELECT p.*, a.name AS author_name, r.review_count
             FROM products p
             LEFT JOIN authors a ON a.id = p.author_id
             INNER JOIN (
                 SELECT product_id, COUNT(*) AS review_count
                 FROM reviews WHERE status = 'approved' GROUP BY product_id
             ) r ON r.product_id = p.id
             WHERE p.status = 'active'
             ORDER BY review_count DESC, p.sold_quantity DESC
             LIMIT $limit"
        )->fetchAll();
    }

    public function search(array $filters, $page = 1, $perPage = 12)
    {
        $conditions = ["p.status = 'active'"];
        $params = [];

        if (!empty($filters['q'])) {
            $conditions[] = 'p.name LIKE :search';
            $params['search'] = '%' . $filters['q'] . '%';
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
            'price_asc' => 'p.sale_price ASC',
            'price_desc' => 'p.sale_price DESC',
            'best_selling' => 'p.sold_quantity DESC',
            'newest' => 'p.created_at DESC',
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
            "SELECT p.*, c.name AS category_name, a.name AS author_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN authors a ON a.id = p.author_id
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
            "SELECT p.*, c.name AS category_name, a.name AS author_name, pub.name AS publisher_name
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
            'SELECT g.name FROM genres g
             INNER JOIN product_genres pg ON pg.genre_id = g.id
             WHERE pg.product_id = :product_id ORDER BY g.name',
            ['product_id' => (int) $productId]
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getImages($productId)
    {
        return $this->execute(
            'SELECT image_path FROM product_images WHERE product_id = :product_id ORDER BY sort_order, id',
            ['product_id' => (int) $productId]
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getRelated($productId, $categoryId, $limit = 4)
    {
        if (!(int) $categoryId) {
            return [];
        }
        $limit = max(1, min(12, (int) $limit));

        return $this->execute(
            "SELECT p.*, a.name AS author_name FROM products p
             LEFT JOIN authors a ON a.id = p.author_id
             WHERE p.status = 'active' AND p.category_id = :category_id AND p.id <> :product_id
             ORDER BY p.is_featured DESC, p.sold_quantity DESC LIMIT $limit",
            ['category_id' => (int) $categoryId, 'product_id' => (int) $productId]
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
            "SELECT p.*, a.name AS author_name
             FROM products p
             LEFT JOIN authors a ON a.id = p.author_id
             WHERE p.status = 'active' AND $condition
             ORDER BY $orderBy
             LIMIT $limit",
            $params
        )->fetchAll();
    }
}
