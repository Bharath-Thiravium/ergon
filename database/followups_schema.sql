-- Follow-ups System Database Schema

-- Main follow-ups table
CREATE TABLE IF NOT EXISTS followups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    task_id INT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    company_name VARCHAR(255),
    contact_person VARCHAR(255),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    project_name VARCHAR(255),
    department_id INT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'postponed', 'cancelled') DEFAULT 'pending',
    follow_up_date DATE NOT NULL,
    original_date DATE NOT NULL,
    reschedule_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_user_date (user_id, follow_up_date),
    INDEX idx_status (status),
    INDEX idx_company (company_name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES daily_plans(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- Follow-up action items (checklist)
CREATE TABLE IF NOT EXISTS followup_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    followup_id INT NOT NULL,
    item_text TEXT NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (followup_id) REFERENCES followups(id) ON DELETE CASCADE
);

-- Follow-up history/logs
CREATE TABLE IF NOT EXISTS followup_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    followup_id INT NOT NULL,
    action_type ENUM('created', 'updated', 'rescheduled', 'completed', 'postponed', 'cancelled') NOT NULL,
    old_date DATE NULL,
    new_date DATE NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (followup_id) REFERENCES followups(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Follow-up reminders
CREATE TABLE IF NOT EXISTS followup_reminders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    followup_id INT NOT NULL,
    reminder_date DATE NOT NULL,
    reminder_time TIME DEFAULT '09:00:00',
    is_sent BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (followup_id) REFERENCES followups(id) ON DELETE CASCADE
);

-- Add follow-up flag to daily_plans table
ALTER TABLE daily_plans 
ADD COLUMN is_followup BOOLEAN DEFAULT FALSE,
ADD COLUMN followup_id INT NULL,
ADD INDEX idx_followup (is_followup, followup_id);