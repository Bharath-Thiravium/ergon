-- Unified Planner System - Tables Only (No View)
-- Run this to create the unified system tables

-- Create unified_entries table
CREATE TABLE IF NOT EXISTS unified_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entry_type ENUM('task','followup','planner') NOT NULL,
    reference_id INT NOT NULL,
    date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('low','medium','high') DEFAULT 'medium',
    status VARCHAR(50) DEFAULT 'pending',
    progress INT DEFAULT 0,
    department_id INT DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    sla_hours INT DEFAULT 24,
    carry_forward_count INT DEFAULT 0,
    last_sync_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_date (user_id, date),
    INDEX idx_entry_type (entry_type),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_due_date (due_date)
);

-- Create sync_log table
CREATE TABLE IF NOT EXISTS sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_type ENUM('task','followup','planner','evening_update') NOT NULL,
    reference_id INT NOT NULL,
    action ENUM('create','update','delete','sync','carry_forward') NOT NULL,
    old_values JSON DEFAULT NULL,
    new_values JSON DEFAULT NULL,
    sync_status ENUM('success','failed','pending') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_entry_ref (entry_type, reference_id),
    INDEX idx_user_id (user_id),
    INDEX idx_sync_status (sync_status),
    INDEX idx_created_at (created_at)
);

-- Create carry_forward_rules table
CREATE TABLE IF NOT EXISTS carry_forward_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    department_id INT DEFAULT NULL,
    rule_type ENUM('task','followup','both') DEFAULT 'both',
    conditions JSON NOT NULL,
    actions JSON NOT NULL,
    priority_boost BOOLEAN DEFAULT TRUE,
    auto_escalate_days INT DEFAULT 3,
    max_carry_forwards INT DEFAULT 5,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_department_id (department_id),
    INDEX idx_rule_type (rule_type),
    INDEX idx_is_active (is_active)
);

-- Create smart_categories table
CREATE TABLE IF NOT EXISTS smart_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    category_type ENUM('task','followup','both') DEFAULT 'both',
    auto_followup BOOLEAN DEFAULT FALSE,
    default_sla_hours INT DEFAULT 24,
    priority_weight INT DEFAULT 1,
    keywords JSON DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_dept_category (department_id, category_name),
    INDEX idx_department_id (department_id),
    INDEX idx_category_type (category_type),
    INDEX idx_is_active (is_active)
);

-- Create automation_triggers table
CREATE TABLE IF NOT EXISTS automation_triggers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trigger_name VARCHAR(100) NOT NULL,
    trigger_type ENUM('time_based','event_based','condition_based') NOT NULL,
    trigger_conditions JSON NOT NULL,
    actions JSON NOT NULL,
    target_users JSON DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_executed_at TIMESTAMP NULL,
    execution_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_trigger_type (trigger_type),
    INDEX idx_is_active (is_active),
    INDEX idx_last_executed (last_executed_at)
);

-- Create automation_log table
CREATE TABLE IF NOT EXISTS automation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation VARCHAR(100) NOT NULL,
    status ENUM('success','failed','warning') NOT NULL,
    details TEXT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_operation (operation),
    INDEX idx_executed_at (executed_at)
);

-- Insert default smart categories
INSERT IGNORE INTO smart_categories (department_id, category_name, category_type, auto_followup, default_sla_hours, keywords) VALUES
(1, 'Development', 'task', FALSE, 48, '["code", "develop", "build", "implement"]'),
(1, 'Bug Fix', 'task', TRUE, 24, '["bug", "fix", "error", "issue"]'),
(1, 'Code Review', 'task', FALSE, 8, '["review", "code", "pull request"]'),
(1, 'Follow-up', 'followup', FALSE, 24, '["follow", "followup", "check", "update"]'),
(2, 'Recruitment', 'task', TRUE, 72, '["hire", "recruit", "interview"]'),
(2, 'Training', 'task', TRUE, 48, '["train", "onboard", "learn"]'),
(2, 'Performance Review', 'task', TRUE, 168, '["review", "performance", "appraisal"]'),
(2, 'Follow-up', 'followup', FALSE, 24, '["follow", "followup", "check"]');

-- Insert default automation triggers
INSERT IGNORE INTO automation_triggers (trigger_name, trigger_type, trigger_conditions, actions) VALUES
('Daily Carry Forward', 'time_based', '{"time": "06:00", "days": ["monday","tuesday","wednesday","thursday","friday"]}', '{"action": "carry_forward", "auto_escalate": true}'),
('Overdue Task Alert', 'condition_based', '{"condition": "task_overdue", "threshold_hours": 24}', '{"action": "send_notification", "escalate_priority": true}'),
('SLA Breach Warning', 'condition_based', '{"condition": "sla_approaching", "threshold_percent": 80}', '{"action": "send_alert", "notify_manager": true}');

-- Insert sample automation rules
INSERT IGNORE INTO carry_forward_rules (user_id, rule_type, conditions, actions, priority_boost, auto_escalate_days) VALUES
(NULL, 'both', '{"incomplete_tasks": true, "overdue_followups": true}', '{"boost_priority": true, "create_planner_entry": true}', TRUE, 2),
(NULL, 'task', '{"status": ["assigned", "in_progress"], "progress_lt": 100}', '{"escalate_after_days": 3, "notify_manager": true}', TRUE, 3);