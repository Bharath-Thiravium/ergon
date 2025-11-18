-- Create missing sla_history table
CREATE TABLE IF NOT EXISTS sla_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daily_task_id INT NOT NULL,
    action VARCHAR(20) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_seconds INT DEFAULT 0,
    notes TEXT,
    INDEX idx_daily_task_id (daily_task_id)
);