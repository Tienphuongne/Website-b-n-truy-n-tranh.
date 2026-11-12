<?php

class ReviewModel extends BaseModel
{
    public function getApprovedForProduct($productId)
    {
        return $this->execute(
            "SELECT r.rating, r.content, r.created_at, u.full_name
             FROM reviews r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.product_id = :product_id AND r.status = 'approved'
             ORDER BY r.created_at DESC",
            ['product_id' => (int) $productId]
        )->fetchAll();
    }

    public function create($productId, $userId, $rating, $content)
    {
        $this->execute(
            "INSERT INTO reviews (product_id, user_id, rating, content, status, created_at)
             VALUES (:product_id, :user_id, :rating, :content, 'pending', NOW())",
            [
                'product_id' => (int) $productId,
                'user_id' => (int) $userId,
                'rating' => (int) $rating,
                'content' => $content,
            ]
        );
    }
}
