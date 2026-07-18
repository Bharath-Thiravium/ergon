-- ========================================
-- Holiday Table Setup
-- ========================================
-- This SQL creates the holidays table needed for the Mark Holiday feature
-- Run this in your MySQL database (ergon_db)

-- Create holidays table
CREATE TABLE IF NOT EXISTS holidays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    holiday_date DATE NOT NULL UNIQUE,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_type VARCHAR(50) DEFAULT 'Company',
    description LONGTEXT,
    applies_to VARCHAR(50) DEFAULT 'All',
    department_id INT,
    repeat_yearly BOOLEAN DEFAULT 0,
    created_by INT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_holiday_date (holiday_date),
    KEY idx_applies_to (applies_to),
    KEY idx_department_id (department_id),
    KEY idx_created_by (created_by),
    KEY idx_is_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify table was created
SHOW TABLES LIKE 'holidays';

-- Show table structure
DESCRIBE holidays;

-- You can now insert test holidays:
-- INSERT INTO holidays (holiday_date, holiday_name, holiday_type, description, applies_to, created_by)
-- VALUES ('2026-06-15', 'Test Holiday', 'National', 'Test holiday', 'All', 1);
