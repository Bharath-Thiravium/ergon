-- ERGON Safe Database Schema Update
-- Only creates new tables and columns that don't exist

-- User preferences table
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(50) NOT NULL,
    preference_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_pref (user_id, preference_key)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Daily planners table
CREATE TABLE IF NOT EXISTS daily_planners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department_id INT,
    plan_date DATE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    estimated_hours DECIMAL(4,2) DEFAULT 0,
    actual_hours DECIMAL(4,2),
    completion_percentage INT DEFAULT 0,
    completion_status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    reminder_time TIME,
    is_reminder_sent TINYINT(1) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    module VARCHAR(50),
    action VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add safe column additions with error handling
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'leaves' 
     AND column_name = 'remarks' 
     AND table_schema = DATABASE()) > 0,
    'SELECT "Column remarks already exists in leaves table"',
    'ALTER TABLE leaves ADD COLUMN remarks TEXT'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'leaves' 
     AND column_name = 'attachment' 
     AND table_schema = DATABASE()) > 0,
    'SELECT "Column attachment already exists in leaves table"',
    'ALTER TABLE leaves ADD COLUMN attachment VARCHAR(255)'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert sample notifications for testing (basic format)
INSERT IGNORE INTO notifications (user_id, title, message) VALUES
(NULL, 'Welcome to ERGON', 'Welcome to the ERGON Employee Management System!'),
(NULL, 'System Update', 'New features have been added to improve your experience.');

-- Database setup complete
-- Note: Indexes will be created automatically with table creation or can be added manually if needed