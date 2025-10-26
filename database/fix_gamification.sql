-- Fix Gamification Tables
-- Run this to create missing gamification tables

-- User Points table
CREATE TABLE IF NOT EXISTS user_points (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    points INT NOT NULL,
    reason VARCHAR(200) NOT NULL,
    reference_type ENUM('task', 'attendance', 'workflow', 'bonus') DEFAULT 'task',
    reference_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_points (user_id),
    INDEX idx_created_at (created_at)
);

-- Badge Definitions table
CREATE TABLE IF NOT EXISTS badge_definitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT '🏆',
    criteria_type ENUM('points', 'tasks', 'streak', 'productivity') NOT NULL,
    criteria_value INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Badges table
CREATE TABLE IF NOT EXISTS user_badges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    awarded_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badge_definitions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    INDEX idx_user_badges (user_id)
);

-- Add total_points column to users table if it doesn't exist
ALTER TABLE users ADD COLUMN total_points INT DEFAULT 0;

-- Insert default badges if they don't exist
INSERT IGNORE INTO badge_definitions (id, name, description, icon, criteria_type, criteria_value) VALUES
(1, 'First Task', 'Complete your first task', '🎯', 'tasks', 1),
(2, 'Task Master', 'Complete 10 tasks', '⭐', 'tasks', 10),
(3, 'Productivity Pro', 'Achieve 90% productivity score', '🚀', 'productivity', 90),
(4, 'Point Collector', 'Earn 100 points', '💎', 'points', 100),
(5, 'Consistent Performer', '5-day task completion streak', '🔥', 'streak', 5);