ALTER TABLE orders
    MODIFY COLUMN status ENUM(
        'pending',
        'confirmed',
        'picked_up',
        'shipping',
        'completed',
        'cancelled'
    ) NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS cancellation_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(40) NOT NULL,
    description TEXT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    CONSTRAINT fk_cancellation_requests_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_cancellation_requests_status_created (status, created_at),
    INDEX idx_cancellation_requests_order (order_id)
) ENGINE=InnoDB;
