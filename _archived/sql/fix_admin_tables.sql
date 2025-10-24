-- Run each statement separately in phpMyAdmin

-- Step 1: Add system admin column to users (skip if exists)
-- ALTER TABLE users ADD COLUMN is_system_admin BOOLEAN DEFAULT FALSE AFTER role;

-- Step 2: Create admin_positions table
CREATE TABLE IF NOT EXISTS admin_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    assigned_department VARCHAR(100) NULL,
    permissions TEXT NULL,
    is_system_admin BOOLEAN DEFAULT FALSE,
    assigned_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admin_positions_system_admin (is_system_admin),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

-- Step 3: Add index to users (skip if exists)
-- ALTER TABLE users ADD INDEX idx_users_system_admin (is_system_admin);