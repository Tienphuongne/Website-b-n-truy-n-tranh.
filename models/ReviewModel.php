<?php

class ReviewModel extends BaseModel
{
    public function getApprovedForProduct($productId)
    {
        $reviews = $this->execute(
            "SELECT r.id, r.rating, r.content, r.created_at, u.full_name
             FROM reviews r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.product_id = :product_id AND r.status = 'approved'
             ORDER BY r.created_at DESC",
            ['product_id' => (int) $productId]
        )->fetchAll();

        return $this->attachMedia($reviews);
    }

    public function createForCompletedOrder($orderId, $productId, $userId, $email, $rating, $content, array $media)
    {
        if ($rating < 1 || $rating > 5 || count($media) > 5) {
            throw new DomainException('Thông tin đánh giá không hợp lệ.');
        }

        $this->pdo->beginTransaction();
        try {
            $orderedProduct = $this->execute(
                "SELECT o.id
                 FROM orders o
                 INNER JOIN order_details od ON od.order_id = o.id
                 WHERE o.id = :order_id
                   AND od.product_id = :product_id
                   AND o.status = 'completed'
                   AND (o.user_id = :user_id OR (o.user_id IS NULL AND o.email = :email))
                 LIMIT 1
                 FOR UPDATE",
                [
                    'order_id' => (int) $orderId,
                    'product_id' => (int) $productId,
                    'user_id' => (int) $userId,
                    'email' => (string) $email,
                ]
            )->fetch();
            if (!$orderedProduct) {
                throw new DomainException('Chỉ có thể đánh giá sản phẩm trong đơn hàng đã hoàn tất của bạn.');
            }

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
            $reviewId = (int) $this->pdo->lastInsertId();
            $mediaStatement = $this->pdo->prepare(
                'INSERT INTO review_media (review_id, media_type, file_path)
                 VALUES (:review_id, :media_type, :file_path)'
            );
            foreach ($media as $item) {
                $mediaStatement->execute([
                    'review_id' => $reviewId,
                    'media_type' => $item['media_type'],
                    'file_path' => $item['file_path'],
                ]);
            }

            $this->pdo->commit();

            return $reviewId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
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

    public function getForUserProduct($userId, $productId)
    {
        $review = $this->execute(
            'SELECT id, rating, content, status, created_at
             FROM reviews
             WHERE user_id = :user_id AND product_id = :product_id
             LIMIT 1',
            ['user_id' => (int) $userId, 'product_id' => (int) $productId]
        )->fetch();

        if ($review) {
            $review['media'] = $this->getMediaForReview((int) $review['id']);
        }

        return $review;
    }

    private function attachMedia(array $reviews)
    {
        if (!$reviews) {
            return $reviews;
        }

        $ids = array_map(static function ($review) {
            return (int) $review['id'];
        }, $reviews);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $media = $this->pdo->prepare(
            "SELECT review_id, media_type, file_path
             FROM review_media
             WHERE review_id IN ($placeholders)
             ORDER BY id"
        );
        $media->execute($ids);

        $mediaByReview = [];
        foreach ($media->fetchAll() as $item) {
            $mediaByReview[(int) $item['review_id']][] = $item;
        }
        foreach ($reviews as &$review) {
            $review['media'] = $mediaByReview[(int) $review['id']] ?? [];
        }
        unset($review);

        return $reviews;
    }

    private function getMediaForReview($reviewId)
    {
        return $this->execute(
            'SELECT media_type, file_path FROM review_media
             WHERE review_id = :review_id ORDER BY id',
            ['review_id' => (int) $reviewId]
        )->fetchAll();
    }
}
