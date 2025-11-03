-- Fix all database schema issues for ERGON system
-- Run this SQL script to add missing columns and fix database structure

-- 1. Fix leaves table - add missing approval columns
ALTER TABLE leaves 
ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS rejection_reason TEXT DEFAULT NULL;

-- 2. Fix expenses table - add missing approval columns  
ALTER TABLE expenses
ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS rejection_reason TEXT DEFAULT NULL;

-- 3. Fix advances table - add missing approval columns
ALTER TABLE advances
ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS rejection_reason TEXT DEFAULT NULL;

-- 4. Fix settings table - ensure all required columns exist
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) DEFAULT 'ERGON Company',
    company_email VARCHAR(255) DEFAULT '',
    company_phone VARCHAR(20) DEFAULT '',
    company_address TEXT DEFAULT '',
    working_hours_start TIME DEFAULT '09:00:00',
    working_hours_end TIME DEFAULT '18:00:00',
    timezone VARCHAR(50) DEFAULT 'Asia/Kolkata',
    base_location_lat DECIMAL(10,8) DEFAULT 0,
    base_location_lng DECIMAL(11,8) DEFAULT 0,
    attendance_radius INT DEFAULT 200,
    office_address TEXT DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add missing columns to settings if they don't exist
ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS timezone VARCHAR(50) DEFAULT 'Asia/Kolkata',
ADD COLUMN IF NOT EXISTS working_hours_start TIME DEFAULT '09:00:00',
ADD COLUMN IF NOT EXISTS office_address TEXT DEFAULT '',
ADD COLUMN IF NOT EXISTS base_location_lat DECIMAL(10,8) DEFAULT 0,
ADD COLUMN IF NOT EXISTS base_location_lng DECIMAL(11,8) DEFAULT 0,
ADD COLUMN IF NOT EXISTS attendance_radius INT DEFAULT 200;

-- 5. Ensure tasks table has all required columns
ALTER TABLE tasks
ADD COLUMN IF NOT EXISTS assigned_by INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS assigned_to INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS task_type VARCHAR(50) DEFAULT 'ad-hoc',
ADD COLUMN IF NOT EXISTS priority VARCHAR(20) DEFAULT 'medium',
ADD COLUMN IF NOT EXISTS deadline DATE DEFAULT NULL,
ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'assigned';

-- 6. Create leaves table if it doesn't exist
CREATE TABLE IF NOT EXISTS leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'casual',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- 7. Create expenses table if it doesn't exist
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    expense_date DATE NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    receipt_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- 8. Ensure followups tables exist with proper structure
CREATE TABLE IF NOT EXISTS followups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    company_name VARCHAR(255),
    contact_person VARCHAR(255),
    contact_phone VARCHAR(20),
    project_name VARCHAR(255),
    follow_up_date DATE NOT NULL,
    original_date DATE,
    description TEXT,
    status ENUM('pending','in_progress','completed','postponed','cancelled','rescheduled') DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    reminder_sent BOOLEAN DEFAULT FALSE,
    next_reminder DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_follow_date (follow_up_date),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS followup_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    followup_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_followup_id (followup_id)
);

-- Insert default settings if none exist
INSERT IGNORE INTO settings (id, company_name, timezone, working_hours_start, attendance_radius) 
VALUES (1, 'ERGON Company', 'Asia/Kolkata', '09:00:00', 200);

-- Add some sample data for testing if tables are empty
INSERT IGNORE INTO leaves (user_id, type, start_date, end_date, reason, status) VALUES
(2, 'casual', '2024-01-20', '2024-01-21', 'Personal work', 'pending'),
(3, 'sick', '2024-01-22', '2024-01-23', 'Medical appointment', 'pending');

INSERT IGNORE INTO expenses (user_id, amount, category, description, expense_date, status) VALUES
(2, 500.00, 'Travel', 'Client meeting travel', '2024-01-15', 'pending'),
(3, 250.00, 'Food', 'Team lunch', '2024-01-16', 'pending');

INSERT IGNORE INTO advances (user_id, type, amount, reason, status) VALUES
(2, 'salary', 5000.00, 'Emergency medical expense', 'pending'),
(3, 'travel', 2000.00, 'Business trip advance', 'pending');

COMMIT;