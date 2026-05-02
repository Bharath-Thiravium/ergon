<?php
/**
 * Measurement Sheets Migration Script
 * Run this file in browser to setup the measurement sheets database
 * URL: /ergon/migrate_measurement_sheets.php
 */

// Security check - only allow in development or with proper authentication
if (!isset($_SESSION)) session_start();

// Basic security - you can enhance this
$allowed = false;

// Check if user is logged in and has admin rights
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $allowed = in_array($_SESSION['role'], ['owner', 'admin', 'system_admin']);
}

// Or allow if running locally
if (in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']) || 
    strpos($_SERVER['SERVER_NAME'], 'laragon') !== false) {
    $allowed = true;
}

if (!$allowed) {
    die('Access denied. Please login as admin or run locally.');
}

require_once __DIR__ . '/app/config/database.php';

$results = [];
$errors = [];

try {
    $db = Database::connect();
    
    // Initialize transaction flag
    $transactionStarted = false;
    
    // Start transaction
    $db->beginTransaction();
    $transactionStarted = true;
    
    $migrations = [
        // 1. Create measurement_sheets table
        "measurement_sheets" => "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        // 2. Create measurement_items table
        "measurement_items" => "
            CREATE TABLE IF NOT EXISTS measurement_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sheet_id INT NOT NULL,
                s_no INT NOT NULL,
                description TEXT NOT NULL,
                uom VARCHAR(20) NOT NULL,
                
                wo_qty DECIMAL(12,3) DEFAULT 0,
                wo_amount DECIMAL(15,2) DEFAULT 0,
                
                prev_qty DECIMAL(12,3) DEFAULT 0,
                prev_amount DECIMAL(15,2) DEFAULT 0,
                
                present_qty DECIMAL(12,3) DEFAULT 0,
                present_amount DECIMAL(15,2) DEFAULT 0,
                
                cumulative_qty DECIMAL(12,3) DEFAULT 0,
                cumulative_amount DECIMAL(15,2) DEFAULT 0,
                
                section_name VARCHAR(100),
                remarks VARCHAR(255),
                
                FOREIGN KEY (sheet_id) REFERENCES measurement_sheets(id) ON DELETE CASCADE,
                INDEX idx_sheet_id (sheet_id),
                INDEX idx_section_name (section_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        // 3. Create measurement_signatures table
        "measurement_signatures" => "
            CREATE TABLE IF NOT EXISTS measurement_signatures (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sheet_id INT NOT NULL,
                role VARCHAR(100) NOT NULL,
                name VARCHAR(255),
                signature_path VARCHAR(255),
                signed_at DATETIME,
                
                FOREIGN KEY (sheet_id) REFERENCES measurement_sheets(id) ON DELETE CASCADE,
                INDEX idx_sheet_id (sheet_id),
                INDEX idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        // 4. Create measurement_sections table
        "measurement_sections" => "
            CREATE TABLE IF NOT EXISTS measurement_sections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                sort_order INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                
                INDEX idx_sort_order (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        "
    ];
    
    // Execute table creation (within transaction)
    foreach ($migrations as $table => $sql) {
        try {
            $db->exec($sql);
            $results[] = "✅ Table '$table' created successfully";
        } catch (Exception $e) {
            $errors[] = "❌ Error creating table '$table': " . $e->getMessage();
            // Don't throw here, continue with other tables
        }
    }
    
    // Insert default sections (within transaction)
    $defaultSections = [
        ['MMS PILING', 'MMS Piling Work', 1],
        ['AC WORK', 'AC Work', 2],
        ['DC WORK', 'DC Work', 3],
        ['CIVIL WORK', 'Civil Construction Work', 4],
        ['ELECTRICAL WORK', 'Electrical Installation', 5],
        ['MECHANICAL WORK', 'Mechanical Installation', 6]
    ];
    
    $sectionStmt = $db->prepare("
        INSERT IGNORE INTO measurement_sections (name, description, sort_order) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($defaultSections as $section) {
        try {
            $sectionStmt->execute($section);
            $results[] = "✅ Section '{$section[0]}' inserted";
        } catch (Exception $e) {
            $errors[] = "❌ Error inserting section '{$section[0]}': " . $e->getMessage();
        }
    }
    
    // Commit transaction for tables and basic data
    if (isset($transactionStarted) && $transactionStarted) {
        $db->commit();
        $transactionStarted = false;
        $results[] = "✅ Core tables and data committed successfully";
    }
    
    // Create triggers for auto-calculation (outside transaction)
    $triggers = [
        "measurement_items_calculate_cumulative_insert" => "
            CREATE TRIGGER measurement_items_calculate_cumulative_insert
            BEFORE INSERT ON measurement_items
            FOR EACH ROW
            BEGIN
                SET NEW.cumulative_qty = NEW.prev_qty + NEW.present_qty;
                SET NEW.cumulative_amount = NEW.prev_amount + NEW.present_amount;
            END
        ",
        
        "measurement_items_calculate_cumulative_update" => "
            CREATE TRIGGER measurement_items_calculate_cumulative_update
            BEFORE UPDATE ON measurement_items
            FOR EACH ROW
            BEGIN
                SET NEW.cumulative_qty = NEW.prev_qty + NEW.present_qty;
                SET NEW.cumulative_amount = NEW.prev_amount + NEW.present_amount;
            END
        "
    ];
    
    foreach ($triggers as $triggerName => $sql) {
        try {
            // Drop trigger if exists
            $db->exec("DROP TRIGGER IF EXISTS $triggerName");
            $db->exec($sql);
            $results[] = "✅ Trigger '$triggerName' created successfully";
        } catch (Exception $e) {
            $errors[] = "❌ Error creating trigger '$triggerName': " . $e->getMessage();
        }
    }
    
    // Create view for measurement sheet summary
    try {
        $db->exec("DROP VIEW IF EXISTS measurement_sheet_summary");
        $db->exec("
            CREATE VIEW measurement_sheet_summary AS
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
                COALESCE(SUM(mi.wo_amount), 0) as total_wo_amount,
                COALESCE(SUM(mi.prev_amount), 0) as total_prev_amount,
                COALESCE(SUM(mi.present_amount), 0) as total_present_amount,
                COALESCE(SUM(mi.cumulative_amount), 0) as total_cumulative_amount,
                CASE 
                    WHEN SUM(mi.wo_amount) > 0 THEN 
                        ROUND((SUM(mi.cumulative_amount) / SUM(mi.wo_amount)) * 100, 2)
                    ELSE 0 
                END as completion_percentage
            FROM measurement_sheets ms
            LEFT JOIN measurement_items mi ON ms.id = mi.sheet_id
            GROUP BY ms.id
        ");
        $results[] = "✅ View 'measurement_sheet_summary' created successfully";
    } catch (Exception $e) {
        $errors[] = "❌ Error creating view: " . $e->getMessage();
    }
    
    // Create stored procedure for validation
    try {
        $db->exec("DROP PROCEDURE IF EXISTS ValidateMeasurementItem");
        $db->exec("
            CREATE PROCEDURE ValidateMeasurementItem(
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
                
                IF p_present_qty < 0 THEN
                    SET p_is_valid = FALSE;
                    SET p_error_message = 'Present quantity cannot be negative';
                END IF;
                
                IF cumulative_qty > p_wo_qty THEN
                    SET p_is_valid = FALSE;
                    SET p_error_message = 'Cumulative quantity cannot exceed WO quantity';
                END IF;
            END
        ");
        $results[] = "✅ Stored procedure 'ValidateMeasurementItem' created successfully";
    } catch (Exception $e) {
        $errors[] = "❌ Error creating stored procedure: " . $e->getMessage();
    }
    
    // Add foreign key constraint for created_by (if users table exists)
    try {
        $checkUsers = $db->query("SHOW TABLES LIKE 'users'")->fetch();
        if ($checkUsers) {
            $db->exec("
                ALTER TABLE measurement_sheets 
                ADD CONSTRAINT fk_measurement_sheets_created_by 
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ");
            $results[] = "✅ Foreign key constraint added for created_by";
        } else {
            $results[] = "⚠️ Users table not found, skipping foreign key constraint";
        }
    } catch (Exception $e) {
        // Constraint might already exist
        if (strpos($e->getMessage(), 'Duplicate key') === false) {
            $errors[] = "❌ Error adding foreign key: " . $e->getMessage();
        } else {
            $results[] = "ℹ️ Foreign key constraint already exists";
        }
    }
    
    // Create sample data (optional, in separate transaction)
    if (isset($_GET['sample']) && $_GET['sample'] === '1') {
        try {
            $db->beginTransaction();
            
            // Insert sample measurement sheet
            $sampleStmt = $db->prepare("
                INSERT INTO measurement_sheets 
                (work_name, project_name, contractor, po_ref, ra_bill_no, bill_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $sampleStmt->execute([
                'Construction of Office Building',
                'Corporate Tower Project',
                'ABC Construction Ltd',
                'PO-2024-001',
                'RA-01',
                date('Y-m-d'),
                $_SESSION['user_id'] ?? 1
            ]);
            
            $sampleSheetId = $db->lastInsertId();
            
            // Insert sample items
            $itemStmt = $db->prepare("
                INSERT INTO measurement_items 
                (sheet_id, s_no, description, uom, wo_qty, wo_amount, prev_qty, prev_amount, 
                 present_qty, present_amount, section_name, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $sampleItems = [
                [1, 'Excavation for foundation', 'Cum', 100, 50000, 0, 0, 25, 12500, 'CIVIL WORK', 'First phase'],
                [2, 'Concrete for foundation', 'Cum', 50, 75000, 0, 0, 15, 22500, 'CIVIL WORK', 'M25 grade'],
                [3, 'Steel reinforcement', 'MT', 10, 80000, 0, 0, 3, 24000, 'CIVIL WORK', 'TMT bars']
            ];
            
            foreach ($sampleItems as $item) {
                $itemStmt->execute(array_merge([$sampleSheetId], $item));
            }
            
            $db->commit();
            $results[] = "✅ Sample measurement sheet created with ID: $sampleSheetId";
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors[] = "❌ Error creating sample data: " . $e->getMessage();
        }
    }
    
    // All operations completed - no final commit needed since transaction was already committed
    $results[] = "✅ Migration completed successfully";
    
} catch (Exception $e) {
    if (isset($db) && isset($transactionStarted) && $transactionStarted && $db->inTransaction()) {
        $db->rollBack();
    }
    $errors[] = "❌ Migration failed: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Measurement Sheets Migration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f8fafc;
            color: #1a202c;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2d3748;
            margin-bottom: 20px;
            text-align: center;
        }
        .result {
            margin: 8px 0;
            padding: 8px 12px;
            border-radius: 6px;
            font-family: monospace;
        }
        .success {
            background: #f0fff4;
            color: #22543d;
            border-left: 4px solid #38a169;
        }
        .error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #e53e3e;
        }
        .warning {
            background: #fffbeb;
            color: #744210;
            border-left: 4px solid #d69e2e;
        }
        .info {
            background: #ebf8ff;
            color: #2a4365;
            border-left: 4px solid #3182ce;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 8px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background: #4299e1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
        .btn:hover {
            background: #3182ce;
        }
        .btn-success {
            background: #38a169;
        }
        .btn-success:hover {
            background: #2f855a;
        }
        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Measurement Sheets Migration</h1>
        
        <div style="margin-bottom: 20px; text-align: center;">
            <p>This script will setup the measurement sheets database structure.</p>
            <?php if (!isset($_GET['run'])): ?>
                <a href="?run=1" class="btn">🚀 Run Migration</a>
                <a href="?run=1&sample=1" class="btn btn-success">🚀 Run with Sample Data</a>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['run'])): ?>
            <h2>Migration Results:</h2>
            
            <?php foreach ($results as $result): ?>
                <?php
                $class = 'success';
                if (strpos($result, '⚠️') !== false) $class = 'warning';
                if (strpos($result, 'ℹ️') !== false) $class = 'info';
                ?>
                <div class="result <?= $class ?>"><?= htmlspecialchars($result) ?></div>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $error): ?>
                <div class="result error"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
            
            <div class="summary">
                <?php if (empty($errors)): ?>
                    <h3 style="color: #38a169;">✅ Migration Completed Successfully!</h3>
                    <p>All database structures have been created.</p>
                    <a href="/ergon/measurement-sheets" class="btn btn-success">Go to Measurement Sheets</a>
                <?php else: ?>
                    <h3 style="color: #e53e3e;">❌ Migration Completed with Errors</h3>
                    <p><?= count($results) ?> operations succeeded, <?= count($errors) ?> failed.</p>
                    <a href="?run=1" class="btn">🔄 Retry Migration</a>
                <?php endif; ?>
                
                <a href="/ergon/dashboard" class="btn">← Back to Dashboard</a>
            </div>
            
            <?php if (isset($_GET['sample']) && $_GET['sample'] === '1' && empty($errors)): ?>
                <div style="margin-top: 20px;">
                    <h3>Sample Data Created:</h3>
                    <p>A sample measurement sheet has been created with 3 items. You can view it in the measurement sheets module.</p>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <h2>What this migration will do:</h2>
            <ul style="line-height: 1.8;">
                <li>✅ Create <code>measurement_sheets</code> table for header data</li>
                <li>✅ Create <code>measurement_items</code> table for line items</li>
                <li>✅ Create <code>measurement_signatures</code> table for approvals</li>
                <li>✅ Create <code>measurement_sections</code> table for work sections</li>
                <li>✅ Insert default sections (MMS PILING, AC WORK, DC WORK, etc.)</li>
                <li>✅ Create auto-calculation triggers</li>
                <li>✅ Create summary view for reporting</li>
                <li>✅ Create validation stored procedure</li>
                <li>✅ Add foreign key constraints</li>
                <li>✅ Optionally create sample data</li>
            </ul>
            
            <div style="margin-top: 20px; padding: 15px; background: #fffbeb; border-radius: 6px;">
                <strong>⚠️ Important:</strong> This migration is safe to run multiple times. 
                Existing data will not be affected.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>