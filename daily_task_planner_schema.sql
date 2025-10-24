-- Daily Task Planner Schema - Minimal Implementation
-- Run this after your existing database.sql

-- Projects Master Table
CREATE TABLE IF NOT EXISTS projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    department VARCHAR(50),
    status ENUM('active', 'completed', 'on_hold') DEFAULT 'active',
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Task Categories (Department-wise predefined tasks)
CREATE TABLE IF NOT EXISTS task_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department VARCHAR(50) NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    weight DECIMAL(5,2) DEFAULT 1.00, -- For project progress calculation
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Project Tasks (Reusable task bucket)
CREATE TABLE IF NOT EXISTS project_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    category_id INT NOT NULL,
    task_name VARCHAR(200) NOT NULL,
    weight DECIMAL(5,2) DEFAULT 1.00, -- Weight in project completion
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('active', 'completed', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES task_categories(id)
);

-- Daily Task Entries (Employee daily work log)
CREATE TABLE IF NOT EXISTS daily_task_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    task_id INT NOT NULL,
    entry_date DATE NOT NULL,
    progress_percentage DECIMAL(5,2) NOT NULL,
    hours_spent DECIMAL(4,2) DEFAULT 0.00,
    work_notes TEXT,
    attachment_path VARCHAR(255),
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (task_id) REFERENCES project_tasks(id),
    UNIQUE KEY unique_daily_task (user_id, task_id, entry_date)
);

-- Insert default task categories by department
INSERT INTO task_categories (department, category_name, weight) VALUES
-- IT Department
('IT', 'Development', 2.0),
('IT', 'Testing', 1.5),
('IT', 'Bug Fixing', 1.0),
('IT', 'Hosting & Deployment', 1.0),

-- Accounts Department  
('Accounts', 'GST Work', 2.0),
('Accounts', 'Ledger Update', 1.5),
('Accounts', 'Follow-up', 1.0),
('Accounts', 'PO/Invoice', 1.5),

-- Civil Department
('Civil', 'Casting', 3.0),
('Civil', 'Punch Points', 2.0),
('Civil', 'Material Handling', 1.5),

-- Sales Department
('Sales', 'Client Follow-up', 2.0),
('Sales', 'Quotes', 1.5),
('Sales', 'Negotiation', 2.5),

-- Marketing Department
('Marketing', 'Leads', 2.0),
('Marketing', 'Campaigns', 2.5),
('Marketing', 'Communication', 1.0),

-- HR Department
('HR', 'Recruitment', 2.0),
('HR', 'Attendance Check', 1.0),
('HR', 'Training', 1.5),

-- Admin/Operations
('Admin', 'Procurements', 2.0),
('Admin', 'Logistics', 1.5),
('Admin', 'Facility', 1.0);

-- Insert sample projects
INSERT INTO projects (name, description, department) VALUES
('ERP System Development', 'Complete ERP system for internal use', 'IT'),
('Solar Site Construction', 'Solar panel installation project', 'Civil'),
('Q4 Marketing Campaign', 'End of year marketing initiatives', 'Marketing'),
('Office Renovation', 'Complete office space renovation', 'Admin');

-- Insert sample project tasks
INSERT INTO project_tasks (project_id, category_id, task_name, weight) VALUES
-- ERP System tasks
(1, 1, 'API Development', 3.0),
(1, 1, 'Frontend Development', 2.5),
(1, 2, 'Unit Testing', 1.5),
(1, 2, 'Integration Testing', 2.0),

-- Solar Site tasks  
(2, 5, 'Foundation Casting', 4.0),
(2, 6, 'Panel Installation Points', 3.0),
(2, 7, 'Material Transportation', 2.0),

-- Marketing Campaign tasks
(3, 9, 'Lead Generation', 2.5),
(3, 10, 'Social Media Campaign', 3.0),
(3, 11, 'Client Communication', 1.5);