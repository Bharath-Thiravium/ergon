CREATE TABLE IF NOT EXISTS project_subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    budget DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('active','completed','on_hold') DEFAULT 'active',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
