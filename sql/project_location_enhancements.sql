-- Project-based location tracking enhancements
-- Most columns already exist, these are optional improvements

-- Add location_title to projects table for better naming
ALTER TABLE projects ADD COLUMN location_title VARCHAR(255) NULL AFTER checkin_radius;

-- Update existing projects to have location titles
UPDATE projects SET location_title = CONCAT(name, ' Site') WHERE location_title IS NULL;

-- Add service_history table for project-based attendance tracking
CREATE TABLE IF NOT EXISTS service_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    attendance_id INT NULL,
    service_date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    hours_worked DECIMAL(4,2) DEFAULT 0.00,
    location_lat DECIMAL(10,8) NULL,
    location_lng DECIMAL(11,8) NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_project (user_id, project_id),
    INDEX idx_service_date (service_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE SET NULL
);