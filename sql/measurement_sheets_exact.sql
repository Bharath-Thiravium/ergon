-- Measurement Sheet Database Structure
-- Based on the exact requirements provided

-- A. measurement_sheets (Header Data)
CREATE TABLE IF NOT EXISTS measurement_sheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_name VARCHAR(255) NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    contractor VARCHAR(255) NOT NULL,
    po_ref VARCHAR(100) NOT NULL,
    ra_bill_no VARCHAR(50) NOT NULL,
    bill_date DATE NOT NULL,
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_ra_bill_no (ra_bill_no),
    INDEX idx_po_ref (po_ref),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- B. measurement_items (Line Items)
CREATE TABLE IF NOT EXISTS measurement_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sheet_id INT NOT NULL,
    s_no INT NOT NULL,
    description TEXT NOT NULL,
    uom VARCHAR(20) NOT NULL,
    
    -- As Per WO columns
    wo_qty DECIMAL(12,3) DEFAULT 0,
    wo_amount DECIMAL(15,2) DEFAULT 0,
    
    -- Previous Bills columns
    prev_qty DECIMAL(12,3) DEFAULT 0,
    prev_amount DECIMAL(15,2) DEFAULT 0,
    
    -- Present Bill columns
    present_qty DECIMAL(12,3) DEFAULT 0,
    present_amount DECIMAL(15,2) DEFAULT 0,
    
    -- Cumulative Bill columns (auto-calculated)
    cumulative_qty DECIMAL(12,3) DEFAULT 0,
    cumulative_amount DECIMAL(15,2) DEFAULT 0,
    
    -- Additional fields
    section_name VARCHAR(100), -- For grouping (MMS PILING, AC WORK, DC WORK)
    remarks VARCHAR(255),
    
    FOREIGN KEY (sheet_id) REFERENCES measurement_sheets(id) ON DELETE CASCADE,
    INDEX idx_sheet_id (sheet_id),
    INDEX idx_section_name (section_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- C. measurement_signatures (Signature tracking)
CREATE TABLE IF NOT EXISTS measurement_signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sheet_id INT NOT NULL,
    role VARCHAR(100) NOT NULL, -- 'BK Construction Engineer', 'Prozeal Site Engineer', 'Prozeal Site Manager'
    name VARCHAR(255),
    signature_path VARCHAR(255),
    signed_at DATETIME,
    
    FOREIGN KEY (sheet_id) REFERENCES measurement_sheets(id) ON DELETE CASCADE,
    INDEX idx_sheet_id (sheet_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- D. measurement_sections (For section grouping)
CREATE TABLE IF NOT EXISTS measurement_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default sections
INSERT IGNORE INTO measurement_sections (name, description, sort_order) VALUES
('MMS PILING', 'MMS Piling Work', 1),
('AC WORK', 'AC Work', 2),
('DC WORK', 'DC Work', 3),
('CIVIL WORK', 'Civil Construction Work', 4),
('ELECTRICAL WORK', 'Electrical Installation', 5),
('MECHANICAL WORK', 'Mechanical Installation', 6);

-- Create triggers for auto-calculation
DELIMITER //

-- Trigger to auto-calculate cumulative values on insert
CREATE TRIGGER IF NOT EXISTS measurement_items_calculate_cumulative_insert
BEFORE INSERT ON measurement_items
FOR EACH ROW
BEGIN
    SET NEW.cumulative_qty = NEW.prev_qty + NEW.present_qty;
    SET NEW.cumulative_amount = NEW.prev_amount + NEW.present_amount;
END//

-- Trigger to auto-calculate cumulative values on update
CREATE TRIGGER IF NOT EXISTS measurement_items_calculate_cumulative_update
BEFORE UPDATE ON measurement_items
FOR EACH ROW
BEGIN
    SET NEW.cumulative_qty = NEW.prev_qty + NEW.present_qty;
    SET NEW.cumulative_amount = NEW.prev_amount + NEW.present_amount;
END//

DELIMITER ;

-- Create view for measurement sheet summary
CREATE OR REPLACE VIEW measurement_sheet_summary AS
SELECT 
    ms.id,
    ms.work_name,
    ms.project_name,
    ms.contractor,
    ms.po_ref,
    ms.ra_bill_no,
    ms.bill_date,
    ms.status,
    ms.created_at,
    COUNT(mi.id) as total_items,
    SUM(mi.wo_amount) as total_wo_amount,
    SUM(mi.prev_amount) as total_prev_amount,
    SUM(mi.present_amount) as total_present_amount,
    SUM(mi.cumulative_amount) as total_cumulative_amount,
    CASE 
        WHEN SUM(mi.wo_amount) > 0 THEN 
            ROUND((SUM(mi.cumulative_amount) / SUM(mi.wo_amount)) * 100, 2)
        ELSE 0 
    END as completion_percentage
FROM measurement_sheets ms
LEFT JOIN measurement_items mi ON ms.id = mi.sheet_id
GROUP BY ms.id;

-- Create stored procedure for validation
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS ValidateMeasurementItem(
    IN p_present_qty DECIMAL(12,3),
    IN p_prev_qty DECIMAL(12,3),
    IN p_wo_qty DECIMAL(12,3),
    OUT p_is_valid BOOLEAN,
    OUT p_error_message VARCHAR(255)
)
BEGIN
    DECLARE cumulative_qty DECIMAL(12,3);
    
    SET cumulative_qty = p_prev_qty + p_present_qty;
    SET p_is_valid = TRUE;
    SET p_error_message = '';
    
    -- Check for negative values
    IF p_present_qty < 0 THEN
        SET p_is_valid = FALSE;
        SET p_error_message = 'Present quantity cannot be negative';
    END IF;
    
    -- Check if cumulative exceeds WO quantity
    IF cumulative_qty > p_wo_qty THEN
        SET p_is_valid = FALSE;
        SET p_error_message = 'Cumulative quantity cannot exceed WO quantity';
    END IF;
    
END//

DELIMITER ;

-- Add foreign key constraint for created_by
ALTER TABLE measurement_sheets 
ADD CONSTRAINT IF NOT EXISTS fk_measurement_sheets_created_by 
FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;