    -- Add employee_id column to existing users table
    ALTER TABLE users ADD COLUMN employee_id VARCHAR(20) UNIQUE AFTER id;
    ALTER TABLE users ADD INDEX idx_employee_id (employee_id);