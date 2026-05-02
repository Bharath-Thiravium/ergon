-- Enhanced RA Bills Schema
-- Run this SQL to add enhanced fields to existing tables

-- Add enhanced fields to ra_bills table
ALTER TABLE ra_bills ADD COLUMN IF NOT EXISTS work_order_date DATE NULL AFTER bill_date;
ALTER TABLE ra_bills ADD COLUMN IF NOT EXISTS site_engineer VARCHAR(255) NULL AFTER contractor;
ALTER TABLE ra_bills ADD COLUMN IF NOT EXISTS project_manager VARCHAR(255) NULL AFTER site_engineer;
ALTER TABLE ra_bills ADD COLUMN IF NOT EXISTS work_status ENUM('in_progress','completed','on_hold','pending_approval') DEFAULT 'in_progress' AFTER status;
ALTER TABLE ra_bills ADD COLUMN IF NOT EXISTS expected_completion DATE NULL AFTER work_status;
ALTER TABLE ra_bills ADD COLUMN IF NOT EXISTS approved_by INT NULL AFTER created_by;
ALTER TABLE ra_bills ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL AFTER approved_by;
ALTER TABLE ra_bills ADD COLUMN IF NOT EXISTS submitted_at TIMESTAMP NULL AFTER approved_at;
ALTER TABLE ra_bills ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL AFTER submitted_at;

-- Add indexes for better performance
ALTER TABLE ra_bills ADD INDEX IF NOT EXISTS idx_work_status (work_status);
ALTER TABLE ra_bills ADD INDEX IF NOT EXISTS idx_expected_completion (expected_completion);
ALTER TABLE ra_bills ADD INDEX IF NOT EXISTS idx_approved_by (approved_by);
ALTER TABLE ra_bills ADD INDEX IF NOT EXISTS idx_created_by (created_by);

-- Add enhanced fields to ra_bill_items table
ALTER TABLE ra_bill_items ADD COLUMN IF NOT EXISTS item_notes TEXT NULL AFTER cumulative_amount;
ALTER TABLE ra_bill_items ADD COLUMN IF NOT EXISTS measurement_date DATE NULL AFTER item_notes;
ALTER TABLE ra_bill_items ADD COLUMN IF NOT EXISTS measured_by VARCHAR(255) NULL AFTER measurement_date;
ALTER TABLE ra_bill_items ADD COLUMN IF NOT EXISTS verified_by VARCHAR(255) NULL AFTER measured_by;
ALTER TABLE ra_bill_items ADD COLUMN IF NOT EXISTS item_status ENUM('pending','measured','verified','approved') DEFAULT 'pending' AFTER verified_by;

-- Create measurement sheet templates table
CREATE TABLE IF NOT EXISTS measurement_sheet_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    company_id BIGINT NULL,
    template_data JSON NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_id (company_id),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create measurement sheet approvals table
CREATE TABLE IF NOT EXISTS ra_bill_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ra_bill_id INT NOT NULL,
    approver_id INT NOT NULL,
    approval_level ENUM('site_engineer','project_manager','finance','owner') NOT NULL,
    approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approval_date TIMESTAMP NULL,
    comments TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ra_bill_id) REFERENCES ra_bills(id) ON DELETE CASCADE,
    UNIQUE KEY uq_ra_approver_level (ra_bill_id, approver_id, approval_level),
    INDEX idx_approver_id (approver_id),
    INDEX idx_approval_status (approval_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create measurement sheet attachments table
CREATE TABLE IF NOT EXISTS ra_bill_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ra_bill_id INT NOT NULL,
    attachment_type ENUM('photo','document','drawing','other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    description TEXT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ra_bill_id) REFERENCES ra_bills(id) ON DELETE CASCADE,
    INDEX idx_ra_bill_id (ra_bill_id),
    INDEX idx_attachment_type (attachment_type),
    INDEX idx_uploaded_by (uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create measurement sheet history table for audit trail
CREATE TABLE IF NOT EXISTS ra_bill_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ra_bill_id INT NOT NULL,
    action_type ENUM('created','updated','submitted','approved','rejected','cancelled') NOT NULL,
    action_by INT NOT NULL,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    old_values JSON NULL,
    new_values JSON NULL,
    comments TEXT NULL,
    FOREIGN KEY (ra_bill_id) REFERENCES ra_bills(id) ON DELETE CASCADE,
    INDEX idx_ra_bill_id (ra_bill_id),
    INDEX idx_action_type (action_type),
    INDEX idx_action_by (action_by),
    INDEX idx_action_date (action_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create measurement sheet notifications table
CREATE TABLE IF NOT EXISTS ra_bill_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ra_bill_id INT NOT NULL,
    notification_type ENUM('created','submitted','approved','rejected','reminder') NOT NULL,
    recipient_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (ra_bill_id) REFERENCES ra_bills(id) ON DELETE CASCADE,
    INDEX idx_ra_bill_id (ra_bill_id),
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_is_read (is_read),
    INDEX idx_notification_type (notification_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create measurement sheet settings table
CREATE TABLE IF NOT EXISTS measurement_sheet_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string','number','boolean','json') DEFAULT 'string',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_setting (company_id, setting_key),
    INDEX idx_company_id (company_id),
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default measurement sheet settings
INSERT IGNORE INTO measurement_sheet_settings (company_id, setting_key, setting_value, setting_type, description) VALUES
(NULL, 'default_logo_position', 'left', 'string', 'Default logo position in measurement sheet'),
(NULL, 'default_header_style', 'standard', 'string', 'Default header style for measurement sheet'),
(NULL, 'default_border_style', 'standard', 'string', 'Default border style for measurement sheet'),
(NULL, 'default_font_size', 'medium', 'string', 'Default font size for measurement sheet'),
(NULL, 'show_previous_claimed', 'true', 'boolean', 'Show previous claimed columns by default'),
(NULL, 'show_cumulative', 'true', 'boolean', 'Show cumulative columns by default'),
(NULL, 'include_clearance_sheet', 'true', 'boolean', 'Include clearance sheet by default'),
(NULL, 'auto_calculate_percentage', 'true', 'boolean', 'Auto calculate percentage when quantity is entered'),
(NULL, 'require_site_engineer_approval', 'true', 'boolean', 'Require site engineer approval for RA bills'),
(NULL, 'require_project_manager_approval', 'true', 'boolean', 'Require project manager approval for RA bills'),
(NULL, 'max_file_upload_size', '10485760', 'number', 'Maximum file upload size in bytes (10MB)'),
(NULL, 'allowed_file_types', '["jpg","jpeg","png","pdf","doc","docx","xls","xlsx"]', 'json', 'Allowed file types for attachments');

-- Create view for measurement sheet summary
CREATE OR REPLACE VIEW measurement_sheet_summary AS
SELECT 
    rb.id,
    rb.po_id,
    rb.po_number,
    rb.ra_bill_number,
    rb.ra_sequence,
    rb.bill_date,
    rb.project,
    rb.contractor,
    rb.total_claimed,
    rb.status,
    rb.work_status,
    rb.expected_completion,
    rb.site_engineer,
    rb.project_manager,
    rb.created_at,
    rb.updated_at,
    COUNT(rbi.id) as item_count,
    SUM(rbi.po_line_total) as po_total_value,
    SUM(rbi.cumulative_amount) as cumulative_claimed,
    (SUM(rbi.po_line_total) - SUM(rbi.cumulative_amount)) as remaining_balance,
    CASE 
        WHEN SUM(rbi.cumulative_amount) = 0 THEN 0
        ELSE ROUND((SUM(rbi.cumulative_amount) / SUM(rbi.po_line_total)) * 100, 2)
    END as completion_percentage,
    COUNT(rba.id) as attachment_count,
    MAX(rbh.action_date) as last_activity_date
FROM ra_bills rb
LEFT JOIN ra_bill_items rbi ON rb.id = rbi.ra_bill_id
LEFT JOIN ra_bill_attachments rba ON rb.id = rba.ra_bill_id
LEFT JOIN ra_bill_history rbh ON rb.id = rbh.ra_bill_id
GROUP BY rb.id;

-- Create indexes for the view
CREATE INDEX IF NOT EXISTS idx_ra_bills_po_id ON ra_bills(po_id);
CREATE INDEX IF NOT EXISTS idx_ra_bills_status ON ra_bills(status);
CREATE INDEX IF NOT EXISTS idx_ra_bills_work_status ON ra_bills(work_status);
CREATE INDEX IF NOT EXISTS idx_ra_bill_items_ra_bill_id ON ra_bill_items(ra_bill_id);

-- Add foreign key constraints if they don't exist
ALTER TABLE ra_bills 
ADD CONSTRAINT IF NOT EXISTS fk_ra_bills_approved_by 
FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE ra_bills 
ADD CONSTRAINT IF NOT EXISTS fk_ra_bills_created_by 
FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Create triggers for audit trail
DELIMITER //

CREATE TRIGGER IF NOT EXISTS ra_bill_audit_insert
AFTER INSERT ON ra_bills
FOR EACH ROW
BEGIN
    INSERT INTO ra_bill_history (ra_bill_id, action_type, action_by, new_values)
    VALUES (NEW.id, 'created', NEW.created_by, JSON_OBJECT(
        'ra_bill_number', NEW.ra_bill_number,
        'total_claimed', NEW.total_claimed,
        'status', NEW.status,
        'work_status', NEW.work_status
    ));
END//

CREATE TRIGGER IF NOT EXISTS ra_bill_audit_update
AFTER UPDATE ON ra_bills
FOR EACH ROW
BEGIN
    INSERT INTO ra_bill_history (ra_bill_id, action_type, action_by, old_values, new_values)
    VALUES (NEW.id, 'updated', NEW.created_by, 
        JSON_OBJECT(
            'status', OLD.status,
            'work_status', OLD.work_status,
            'total_claimed', OLD.total_claimed
        ),
        JSON_OBJECT(
            'status', NEW.status,
            'work_status', NEW.work_status,
            'total_claimed', NEW.total_claimed
        )
    );
END//

DELIMITER ;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS GetMeasurementSheetStats(IN company_id_param BIGINT)
BEGIN
    SELECT 
        COUNT(*) as total_ra_bills,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_bills,
        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_bills,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_bills,
        SUM(total_claimed) as total_claimed_amount,
        AVG(total_claimed) as average_bill_amount,
        COUNT(DISTINCT po_id) as unique_pos
    FROM ra_bills 
    WHERE (company_id_param IS NULL OR company_id = company_id_param)
    AND status != 'cancelled';
END//

CREATE PROCEDURE IF NOT EXISTS GetPendingApprovals(IN approver_id_param INT)
BEGIN
    SELECT 
        rb.id,
        rb.ra_bill_number,
        rb.project,
        rb.contractor,
        rb.total_claimed,
        rb.created_at,
        rba.approval_level
    FROM ra_bills rb
    JOIN ra_bill_approvals rba ON rb.id = rba.ra_bill_id
    WHERE rba.approver_id = approver_id_param
    AND rba.approval_status = 'pending'
    ORDER BY rb.created_at ASC;
END//

DELIMITER ;

-- Grant necessary permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON measurement_sheet_* TO 'your_app_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE GetMeasurementSheetStats TO 'your_app_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE GetPendingApprovals TO 'your_app_user'@'localhost';