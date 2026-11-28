ALTER TABLE return_requests
    MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled')
    NOT NULL DEFAULT 'pending';
