-- Projects Table Setup
-- Creates the projects table if it doesn't exist

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    department_id INT,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_department (department_id)
);

-- project_name column already exists in tasks table

-- Verify tables
SELECT 'Projects table ready' as message;
SELECT COUNT(*) as existing_projects FROM projects;