<?php

class WishlistModel extends BaseModel
{
    public function exists($userId, $productId)
    {
        return (bool) $this->execute(
            'SELECT 1 FROM wishlists WHERE user_id = :user_id AND product_id = :product_id LIMIT 1',
            ['user_id' => (int) $userId, 'product_id' => (int) $productId]
        )->fetchColumn();
    }

    /**
     * Thêm hoặc bỏ sản phẩm khỏi danh sách yêu thích.
     * Trả về true nếu vừa thêm, false nếu vừa bỏ.
     */
    public function toggle($userId, $productId)
    {
        if ($this->exists($userId, $productId)) {
            $this->execute(
                'DELETE FROM wishlists WHERE user_id = :user_id AND product_id = :product_id',
                ['user_id' => (int) $userId, 'product_id' => (int) $productId]
            );

            return false;
        }

        $this->execute(
            'INSERT INTO wishlists (user_id, product_id, created_at)
             VALUES (:user_id, :product_id, NOW())',
            ['user_id' => (int) $userId, 'product_id' => (int) $productId]
        );

        return true;
    }

    public function getForUser($userId)
    {
        return $this->execute(
            "SELECT p.*, a.name AS author_name,
                    COALESCE(rs.avg_rating, 0) AS avg_rating,
                    COALESCE(rs.review_count, 0) AS review_count
             FROM wishlists w
             INNER JOIN products p ON p.id = w.product_id
             LEFT JOIN authors a ON a.id = p.author_id
             LEFT JOIN (
                 SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                 FROM reviews WHERE status = 'approved' GROUP BY product_id
             ) rs ON rs.product_id = p.id
             WHERE w.user_id = :user_id AND p.status = 'active'
             ORDER BY w.created_at DESC, p.id DESC",
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
