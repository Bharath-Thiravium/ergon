-- Quick Security Setup - Safe to run multiple times

-- Create tables only if they don't exist
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    INDEX idx_email_time (email, attempted_at),
    INDEX idx_ip_time (ip_address, attempted_at)
);

CREATE TABLE IF NOT EXISTS rate_limit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    action VARCHAR(50) NOT NULL DEFAULT 'login',
    attempted_at DATETIME NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    ip_address VARCHAR(45) NOT NULL,
    INDEX idx_identifier_action_time (identifier, action, attempted_at),
    INDEX idx_ip_time (ip_address, attempted_at)
);

CREATE TABLE IF NOT EXISTS password_change_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    changed_at DATETIME NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_time (user_id, changed_at)
);

-- Add missing columns to users table (MySQL 5.7+ syntax)
-- For older MySQL versions, run these individually and ignore errors:
-- ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL;
-- ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL;
-- ALTER TABLE users ADD COLUMN locked_until DATETIME NULL;
-- ALTER TABLE users ADD COLUMN failed_attempts INT DEFAULT 0;