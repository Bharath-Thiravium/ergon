-- Drop table if exists to recreate
DROP TABLE IF EXISTS projects;

-- Create projects table
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    checkin_radius INT DEFAULT 100,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample projects
INSERT INTO projects (name, description, latitude, longitude, checkin_radius) VALUES
('Head Office', 'Main office location', 12.9716, 77.5946, 50),
('Site A', 'Construction site A', 12.9800, 77.6000, 100),
('Site B', 'Construction site B', 12.9500, 77.5800, 150);

-- Add project location fields to users table (skip if already exist)
-- Run these one by one and ignore errors if columns exist:
-- ALTER TABLE users ADD COLUMN current_project_id INT DEFAULT NULL;
-- ALTER TABLE users ADD COLUMN project_name VARCHAR(255) DEFAULT NULL;

-- Add foreign key constraint
-- ALTER TABLE users ADD CONSTRAINT fk_users_project FOREIGN KEY (current_project_id) REFERENCES projects(id) ON DELETE SET NULL;