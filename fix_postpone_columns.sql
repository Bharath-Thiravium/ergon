-- Add missing postpone tracking column
ALTER TABLE daily_tasks ADD COLUMN postponed_to_date DATE NULL;

-- Ensure history tables exist
CREATE TABLE IF NOT EXISTS daily_task_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daily_task_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_daily_task_id (daily_task_id)
);

CREATE TABLE IF NOT EXISTS sla_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daily_task_id INT NOT NULL,
    action VARCHAR(20) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_seconds INT DEFAULT 0,
    notes TEXT,
    INDEX idx_daily_task_id (daily_task_id)
);