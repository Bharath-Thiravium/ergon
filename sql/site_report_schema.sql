-- Site Daily Report Module
-- Mirrors the WhatsApp report format used by site supervisors

CREATE TABLE IF NOT EXISTS site_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT DEFAULT NULL,
    project_id INT DEFAULT NULL,
    site_name VARCHAR(255) NOT NULL,
    report_date DATE NOT NULL,
    submitted_by INT NOT NULL,
    total_manpower INT DEFAULT 0,
    remarks TEXT DEFAULT NULL,
    status ENUM('draft','submitted','acknowledged') DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_site_date (site_name(100), report_date),
    INDEX idx_report_date (report_date),
    INDEX idx_project_id (project_id),
    INDEX idx_submitted_by (submitted_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Manpower by category (engineers named, others by count)
CREATE TABLE IF NOT EXISTS site_report_manpower (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    category ENUM(
        'engineer','supervisor','ac_dc_team','mms_team',
        'civil_mason','local_labour','driver_operator','other'
    ) NOT NULL,
    count INT DEFAULT 0,
    -- Named individuals (JSON array of names, for engineers/supervisors)
    names JSON DEFAULT NULL,
    -- Matched user IDs from users table (JSON array, parallel to names)
    linked_user_ids JSON DEFAULT NULL,
    FOREIGN KEY (report_id) REFERENCES site_reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Machinery utilisation
CREATE TABLE IF NOT EXISTS site_report_machinery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    machine_type ENUM('tractor','jcb','hydra','tata_ace','dg','crane','other') NOT NULL,
    machine_label VARCHAR(100) DEFAULT NULL,  -- "JCB 1", "JCB 2"
    count INT DEFAULT 0,
    hours_worked DECIMAL(5,2) DEFAULT NULL,
    fuel_litres DECIMAL(8,2) DEFAULT NULL,
    remarks VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (report_id) REFERENCES site_reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Today's tasks (free text list)
CREATE TABLE IF NOT EXISTS site_report_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    task_description TEXT NOT NULL,
    sort_order TINYINT DEFAULT 0,
    FOREIGN KEY (report_id) REFERENCES site_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expense requests ("process my account")
CREATE TABLE IF NOT EXISTS site_report_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_type ENUM('labour','machinery','transport','fuel','site_expense','advance','other') DEFAULT 'other',
    status ENUM('pending','approved','rejected','processed') DEFAULT 'pending',
    linked_expense_id INT DEFAULT NULL,  -- links to main expenses table once processed
    FOREIGN KEY (report_id) REFERENCES site_reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
