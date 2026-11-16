-- Nâng cấp trang thanh toán: sổ địa chỉ và phương thức giao hàng.
-- Chạy một lần trên database hiện có:
--   mysql -u root manga_house < database/migrations/2026_11_16_smart_checkout.sql

ALTER TABLE orders
    ADD COLUMN ward VARCHAR(120) NULL AFTER district,
    ADD COLUMN shipping_method ENUM('standard', 'express') NOT NULL DEFAULT 'standard' AFTER payment_method,
    MODIFY COLUMN district VARCHAR(120) NOT NULL DEFAULT '';

CREATE TABLE user_addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(60) NOT NULL DEFAULT 'Địa chỉ đã lưu',
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    city VARCHAR(120) NOT NULL,
    district VARCHAR(120) NOT NULL DEFAULT '',
    ward VARCHAR(120) NULL,
    address VARCHAR(500) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_addresses_user (user_id, is_default, updated_at)
) ENGINE=InnoDB;
