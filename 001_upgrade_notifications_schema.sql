-- Enhanced Notifications Schema Migration
-- Run this to upgrade existing notifications table

-- Add new columns to existing notifications table
ALTER TABLE notifications 
ADD COLUMN uuid CHAR(36) NULL AFTER id,
ADD COLUMN payload JSON NULL AFTER message,
ADD COLUMN template_key VARCHAR(100) NULL AFTER action_type,
ADD COLUMN link VARCHAR(255) NULL AFTER message,
ADD COLUMN delivery_channel_set VARCHAR(100) DEFAULT 'inapp' AFTER reference_id,
ADD COLUMN priority TINYINT DEFAULT 2 AFTER delivery_channel_set,
ADD COLUMN status ENUM('queued','delivered','failed','deleted') DEFAULT 'queued' AFTER is_read,
ADD COLUMN retry_count INT DEFAULT 0 AFTER status,
ADD COLUMN expires_at DATETIME NULL AFTER retry_count,
ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Add indexes for performance
ALTER TABLE notifications 
ADD INDEX idx_uuid (uuid),
ADD INDEX idx_status_priority (status, priority),
ADD INDEX idx_expires (expires_at);

-- Create notification preferences table
CREATE TABLE notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    channel VARCHAR(50) NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    frequency ENUM('instant','daily_digest','weekly_digest') DEFAULT 'instant',
    dnd_start TIME DEFAULT NULL,
    dnd_end TIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_channel (user_id, channel),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create notification templates table
CREATE TABLE notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) NOT NULL,
    locale VARCHAR(10) DEFAULT 'en',
    subject VARCHAR(255) NOT NULL,
    body_html TEXT,
    body_text TEXT NOT NULL,
    variables JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_template_locale (template_key, locale)
);

-- Create notification audit logs table
CREATE TABLE notification_audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    notification_uuid CHAR(36) NOT NULL,
    channel VARCHAR(50) NOT NULL,
    status ENUM('attempted','success','failed') NOT NULL,
    response TEXT,
    error_message TEXT,
    attempt_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notification_uuid (notification_uuid),
    INDEX idx_attempt_at (attempt_at)
);

-- Create notification channels configuration
CREATE TABLE notification_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_name VARCHAR(50) NOT NULL UNIQUE,
    config JSON NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default notification preferences for existing users
INSERT INTO notification_preferences (user_id, channel, enabled, frequency)
SELECT id, 'inapp', 1, 'instant' FROM users
ON DUPLICATE KEY UPDATE enabled = enabled;

INSERT INTO notification_preferences (user_id, channel, enabled, frequency)
SELECT id, 'email', 1, 'instant' FROM users
ON DUPLICATE KEY UPDATE enabled = enabled;

-- Insert default templates
INSERT INTO notification_templates (template_key, locale, subject, body_text, body_html, variables) VALUES
('leave.request_submitted', 'en', '{{userName}} requested leave', 
 '{{userName}} submitted a leave request from {{startDate}} to {{endDate}}. View: {{link}}',
 '<p><strong>{{userName}}</strong> submitted a leave request from <strong>{{startDate}}</strong> to <strong>{{endDate}}</strong>.</p><p><a href="{{link}}">View Request</a></p>',
 '["userName", "startDate", "endDate", "link"]'),

('expense.claim_submitted', 'en', '{{userName}} submitted expense claim', 
 '{{userName}} submitted an expense claim of ₹{{amount}} for {{category}}. View: {{link}}',
 '<p><strong>{{userName}}</strong> submitted an expense claim of <strong>₹{{amount}}</strong> for <em>{{category}}</em>.</p><p><a href="{{link}}">View Claim</a></p>',
 '["userName", "amount", "category", "link"]'),

('advance.request_submitted', 'en', '{{userName}} requested salary advance', 
 '{{userName}} submitted a salary advance request of ₹{{amount}}. View: {{link}}',
 '<p><strong>{{userName}}</strong> submitted a salary advance request of <strong>₹{{amount}}</strong>.</p><p><a href="{{link}}">View Request</a></p>',
 '["userName", "amount", "link"]'),

('task.assigned', 'en', 'New task assigned: {{taskTitle}}', 
 'You have been assigned a new task: {{taskTitle}}. Due: {{dueDate}}. View: {{link}}',
 '<p>You have been assigned a new task: <strong>{{taskTitle}}</strong></p><p>Due: <strong>{{dueDate}}</strong></p><p><a href="{{link}}">View Task</a></p>',
 '["taskTitle", "dueDate", "link"]'),

('approval.decision', 'en', 'Your {{itemType}} has been {{decision}}', 
 'Your {{itemType}} has been {{decision}}. {{reason}}',
 '<p>Your <strong>{{itemType}}</strong> has been <strong>{{decision}}</strong>.</p><p>{{reason}}</p>',
 '["itemType", "decision", "reason"]');

-- Insert default channel configurations
INSERT INTO notification_channels (channel_name, config, enabled) VALUES
('inapp', '{"realtime": true, "polling_interval": 30}', 1),
('email', '{"smtp_host": "", "smtp_port": 587, "smtp_user": "", "smtp_pass": "", "from_email": ""}', 0),
('push', '{"fcm_server_key": "", "apns_key_id": "", "apns_team_id": ""}', 0),
('sms', '{"provider": "twilio", "account_sid": "", "auth_token": "", "from_number": ""}', 0);