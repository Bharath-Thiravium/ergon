-- Fix leaves table structure
-- Check if leaves table exists and has correct columns

-- Create leaves table with correct structure
CREATE TABLE IF NOT EXISTS leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type ENUM('Annual', 'Sick', 'Personal', 'Emergency', 'Maternity', 'Paternity') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_requested INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_leaves_user (user_id),
    INDEX idx_leaves_status (status),
    INDEX idx_leaves_dates (start_date, end_date)
);

-- Insert sample data
INSERT IGNORE INTO leaves (id, user_id, leave_type, start_date, end_date, days_requested, reason, status, created_at) VALUES
(1, 3, 'Annual', '2024-02-15', '2024-02-17', 3, 'Family vacation', 'Pending', NOW()),
(2, 4, 'Sick', '2024-02-10', '2024-02-10', 1, 'Medical appointment', 'Approved', NOW()),
(3, 5, 'Personal', '2024-02-20', '2024-02-21', 2, 'Personal matters', 'Pending', NOW());