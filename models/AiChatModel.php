<?php

class AiChatModel extends BaseModel
{
    /**
     * Danh mục sản phẩm dạng văn bản để đưa vào ngữ cảnh cho trợ lý AI.
     * Chỉ lấy sản phẩm đang bán, giới hạn số lượng để tiết kiệm token.
     */
    public function catalogContext($limit = 80)
    {
        $rows = $this->execute(
            "SELECT p.id, p.name, p.sale_price, p.original_price, p.stock_quantity,
                    p.short_description, c.name AS category_name, a.name AS author_name,
                    (SELECT GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ', ')
                     FROM product_genres pg
                     JOIN genres g ON g.id = pg.genre_id
                     WHERE pg.product_id = p.id) AS genre_names
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN authors a ON a.id = p.author_id
             WHERE p.status = 'active'
             ORDER BY p.sold_quantity DESC, p.id DESC
             LIMIT " . max(1, min(120, (int) $limit))
        )->fetchAll();

        if (!$rows) {
            return '';
        }

        $lines = [];
        foreach ($rows as $row) {
            $price = number_format((float) $row['sale_price'], 0, ',', '.') . '₫';
            if (!empty($row['original_price']) && (float) $row['original_price'] > (float) $row['sale_price']) {
                $price .= ' (giá gốc ' . number_format((float) $row['original_price'], 0, ',', '.') . '₫)';
            }

            $meta = [];
            if (!empty($row['genre_names'])) {
                $meta[] = 'thể loại: ' . $row['genre_names'];
            }
            if (!empty($row['author_name'])) {
                $meta[] = 'tác giả: ' . $row['author_name'];
            }
            if (!empty($row['category_name'])) {
                $meta[] = 'danh mục: ' . $row['category_name'];
            }

            $stock = (int) $row['stock_quantity'] > 0
                ? 'còn ' . (int) $row['stock_quantity'] . ' cuốn'
                : 'tạm hết hàng';

            $description = trim((string) ($row['short_description'] ?? ''));
            if ($description !== '') {
                $description = ' — ' . limit_text($description, 140);
            }

            $lines[] = sprintf(
                '- #%d | %s | %s | %s%s | %s | %s',
                (int) $row['id'],
                $row['name'],
                $price,
                $stock,
                $description,
                implode('; ', $meta),
                absolute_url('product/detail/' . (int) $row['id'])
            );
        }

        return implode("\n", $lines);
    }
}
