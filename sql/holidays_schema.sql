-- Holiday Management Schema
-- Integrated with ERGON Attendance System

CREATE TABLE IF NOT EXISTS holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  holiday_date DATE NOT NULL UNIQUE,
  holiday_name VARCHAR(255) NOT NULL,
  holiday_type ENUM('National', 'Festival', 'Company', 'Emergency', 'Other') DEFAULT 'Company',
  description TEXT NULL,
  applies_to ENUM('All', 'Department', 'Specific') DEFAULT 'All',
  department_id INT NULL,
  repeat_yearly BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_holiday_date (holiday_date),
  INDEX idx_holiday_type (holiday_type),
  INDEX idx_applies_to (applies_to),
  INDEX idx_department_id (department_id),
  INDEX idx_is_active (is_active),
  CONSTRAINT fk_holiday_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_holiday_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add is_holiday column to attendance table if it doesn't exist
ALTER TABLE attendance ADD COLUMN is_holiday BOOLEAN DEFAULT FALSE AFTER status;
ALTER TABLE attendance ADD COLUMN holiday_id INT NULL AFTER is_holiday;
ALTER TABLE attendance ADD INDEX idx_is_holiday (is_holiday);
ALTER TABLE attendance ADD CONSTRAINT fk_attendance_holiday FOREIGN KEY (holiday_id) REFERENCES holidays(id) ON DELETE SET NULL;

-- Add holiday-aware columns to track absence calculations
ALTER TABLE attendance ADD COLUMN is_counted_absent BOOLEAN DEFAULT TRUE AFTER is_holiday;
ALTER TABLE attendance ADD INDEX idx_is_counted_absent (is_counted_absent);
