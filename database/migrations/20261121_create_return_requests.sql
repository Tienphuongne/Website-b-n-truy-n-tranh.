CREATE TABLE IF NOT EXISTS return_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(40) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    CONSTRAINT fk_return_requests_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_return_requests_status_created (status, created_at),
    INDEX idx_return_requests_order (order_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS return_request_media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id BIGINT UNSIGNED NOT NULL,
    media_type ENUM('image', 'video') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    CONSTRAINT fk_return_request_media_request FOREIGN KEY (request_id) REFERENCES return_requests(id) ON DELETE CASCADE,
    INDEX idx_return_request_media_request (request_id)
) ENGINE=InnoDB;
