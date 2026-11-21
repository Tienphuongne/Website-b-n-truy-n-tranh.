<?php

class WishlistModel extends BaseModel
{
    public function idsForUser($userId)
    {
        return array_map('intval', $this->execute(
            'SELECT product_id FROM wishlists WHERE user_id = :user_id',
            ['user_id' => (int) $userId]
        )->fetchAll(PDO::FETCH_COLUMN));
    }

    public function has($userId, $productId)
    {
        return (bool) $this->execute(
            'SELECT 1 FROM wishlists WHERE user_id = :user_id AND product_id = :product_id LIMIT 1',
            ['user_id' => (int) $userId, 'product_id' => (int) $productId]
        )->fetchColumn();
    }

    /**
     * @return bool true nếu sản phẩm vừa được thêm, false nếu vừa được bỏ.
     */
    public function toggle($userId, $productId)
    {
        $userId = (int) $userId;
        $productId = (int) $productId;
        if ($userId < 1 || $productId < 1) {
            return false;
        }

        if ($this->has($userId, $productId)) {
            $this->execute(
                'DELETE FROM wishlists WHERE user_id = :user_id AND product_id = :product_id',
                ['user_id' => $userId, 'product_id' => $productId]
            );

            return false;
        }

        $this->execute(
            'INSERT IGNORE INTO wishlists (user_id, product_id, created_at) VALUES (:user_id, :product_id, NOW())',
            ['user_id' => $userId, 'product_id' => $productId]
        );

        return true;
    }

    public function remove($userId, $productId)
    {
        $this->execute(
            'DELETE FROM wishlists WHERE user_id = :user_id AND product_id = :product_id',
            ['user_id' => (int) $userId, 'product_id' => (int) $productId]
        );
    }

    public function listForUser($userId)
    {
        return $this->execute(
            "SELECT p.*, c.name AS category_name, a.name AS author_name,
                    COALESCE(rs.avg_rating, 0) AS avg_rating,
                    COALESCE(rs.review_count, 0) AS review_count,
                    w.created_at AS wishlisted_at
             FROM wishlists w
             INNER JOIN products p ON p.id = w.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN authors a ON a.id = p.author_id
             LEFT JOIN (
                 SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                 FROM reviews WHERE status = 'approved' GROUP BY product_id
             ) rs ON rs.product_id = p.id
             WHERE w.user_id = :user_id AND p.status = 'active'
             ORDER BY w.created_at DESC, w.id DESC",
            ['user_id' => (int) $userId]
        )->fetchAll();
    }

    public function countForUser($userId)
    {
        return (int) $this->execute(
            'SELECT COUNT(*) FROM wishlists WHERE user_id = :user_id',
            ['user_id' => (int) $userId]
        )->fetchColumn();
    }
}
