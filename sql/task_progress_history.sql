-- Task Progress History Table
CREATE TABLE IF NOT EXISTS task_progress_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    progress_from INT NOT NULL DEFAULT 0,
    progress_to INT NOT NULL,
    description TEXT,
    status_from VARCHAR(50),
    status_to VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_task_id (task_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- Update tasks table to add progress_description field
ALTER TABLE tasks ADD COLUMN progress_description TEXT;