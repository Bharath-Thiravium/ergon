-- Fix existing table structures by dropping and recreating them

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop dependent tables first
DROP TABLE IF EXISTS attendance_conflicts;
DROP TABLE IF EXISTS leaves;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS attendance;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create leaves table with correct structure
CREATE TABLE leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_requested INT NOT NULL,
    reason TEXT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create expenses table with correct structure
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    receipt_path VARCHAR(255) NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create attendance table with correct structure
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    clock_in_time TIME NULL,
    clock_out_time TIME NULL,
    date DATE NOT NULL,
    location_lat DECIMAL(10,8) DEFAULT 0,
    location_lng DECIMAL(11,8) DEFAULT 0,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO leaves (id, user_id, leave_type, start_date, end_date, days_requested, reason, status, created_at) VALUES
(1, 3, 'Annual', '2024-02-15', '2024-02-17', 3, 'Family vacation', 'Pending', NOW()),
(2, 4, 'Sick', '2024-02-10', '2024-02-10', 1, 'Medical appointment', 'Approved', NOW()),
(3, 5, 'Personal', '2024-02-20', '2024-02-21', 2, 'Personal matters', 'Pending', NOW());

INSERT INTO expenses (id, user_id, category, amount, description, receipt_path, status, created_at) VALUES
(1, 3, 'Travel', 250.00, 'Client meeting travel expenses', NULL, 'pending', NOW()),
(2, 4, 'Food', 45.50, 'Team lunch meeting', NULL, 'approved', NOW()),
(3, 5, 'Material', 120.00, 'Office supplies', NULL, 'pending', NOW());

INSERT INTO attendance (id, user_id, clock_in_time, clock_out_time, date, location_lat, location_lng, status, created_at) VALUES
(1, 3, '09:00:00', '17:30:00', CURDATE(), 0, 0, 'present', NOW()),
(2, 4, '08:45:00', '17:15:00', CURDATE(), 0, 0, 'present', NOW()),
(3, 5, '09:15:00', NULL, CURDATE(), 0, 0, 'present', NOW());