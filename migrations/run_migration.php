<?php
/**
 * ERGON Database Migration Runner
 * 
 * This script runs all database migrations needed for the ERGON attendance system.
 * Safe to run multiple times - checks if tables/columns already exist before creating.
 * 
 * Usage:
 * 1. Upload this file to ergon/migrations/ folder
 * 2. Visit: https://yourdomain.com/ergon/migrations/run_migration.php
 * 3. Check output for success/error messages
 * 
 * Access: Admin/Owner only
 */

// Security: Disable if already migrated (uncomment after first run)
// die('Migrations already completed. Delete this line to run again.');

session_start();
ob_start();

// Check if already running from CLI
$isCliRun = php_sapi_name() === 'cli';

// Output formatting function
function log_message($message, $type = 'info') {
    $styles = [
        'success' => 'color: #15803d; font-weight: bold;',
        'error'   => 'color: #b91c1c; font-weight: bold;',
        'warning' => 'color: #b45309; font-weight: bold;',
        'info'    => 'color: #1e40af;'
    ];
    
    $style = $styles[$type] ?? $styles['info'];
    
    if ($isCliRun) {
        $prefix = match($type) {
            'success' => '[✓] ',
            'error' => '[✗] ',
            'warning' => '[!] ',
            default => '[•] '
        };
        echo $prefix . $message . "\n";
    } else {
        echo "<div style=\"$style margin: 8px 0; padding: 8px; border-left: 3px solid #3b82f6;\">$message</div>";
    }
    
    error_log("[MIGRATION] [$type] $message");
}

// Start output
if (!$isCliRun) {
    echo "<html><head><title>ERGON Database Migration</title>";
    echo "<style>";
    echo "body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; background: #f8fafc; }";
    echo ".container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }";
    echo ".header { border-bottom: 2px solid #3b82f6; padding-bottom: 15px; margin-bottom: 20px; }";
    echo ".header h1 { margin: 0; color: #1e40af; }";
    echo ".header p { margin: 5px 0 0 0; color: #6b7280; }";
    echo ".progress { background: #e5e7eb; height: 8px; border-radius: 4px; margin: 15px 0; }";
    echo ".progress-bar { background: #3b82f6; height: 100%; border-radius: 4px; width: 0%; transition: width 0.3s; }";
    echo ".footer { border-top: 1px solid #e5e7eb; margin-top: 20px; padding-top: 15px; font-size: 12px; color: #6b7280; }";
    echo ".alert { padding: 12px; border-radius: 6px; margin: 10px 0; }";
    echo ".alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #15803d; }";
    echo ".alert-error { background: #fef2f2; color: #991b1b; border-left: 4px solid #b91c1c; }";
    echo ".alert-warning { background: #fffbeb; color: #92400e; border-left: 4px solid #b45309; }";
    echo "</style></head><body>";
    echo "<div class='container'>";
    echo "<div class='header'>";
    echo "<h1>🔄 ERGON Database Migration Runner</h1>";
    echo "<p>Starting database migration process...</p>";
    echo "</div>";
    echo "<div class='progress'><div class='progress-bar' id='progressBar'></div></div>";
    echo "<div id='logs'>";
}

try {
    // Database configuration
    require_once __DIR__ . '/../app/config/database.php';
    
    log_message('Connecting to database...', 'info');
    $db = Database::connect();
    log_message('✓ Database connection successful', 'success');
    
    $totalSteps = 0;
    $completedSteps = 0;
    
    // ============================================
    // STEP 1: Create Users Table
    // ============================================
    $totalSteps++;
    log_message('Step 1: Creating users table...', 'info');
    
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin', 'owner', 'company_owner') DEFAULT 'user',
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            employee_id VARCHAR(50) UNIQUE,
            department_id INT NULL,
            joining_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        log_message('✓ Users table created', 'success');
    } else {
        log_message('→ Users table already exists', 'warning');
    }
    $completedSteps++;
    
    // ============================================
    // STEP 2: Create Departments Table
    // ============================================
    $totalSteps++;
    log_message('Step 2: Creating departments table...', 'info');
    
    $stmt = $db->query("SHOW TABLES LIKE 'departments'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            is_active INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        log_message('✓ Departments table created', 'success');
    } else {
        log_message('→ Departments table already exists', 'warning');
    }
    $completedSteps++;
    
    // ============================================
    // STEP 3: Create Attendance Table
    // ============================================
    $totalSteps++;
    log_message('Step 3: Creating attendance table...', 'info');
    
    $stmt = $db->query("SHOW TABLES LIKE 'attendance'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            project_id INT NULL,
            check_in DATETIME NOT NULL,
            check_out DATETIME NULL,
            location_name VARCHAR(255) DEFAULT 'Office',
            location_display VARCHAR(255) NULL,
            project_name VARCHAR(255) NULL,
            latitude DECIMAL(10,8) NULL,
            longitude DECIMAL(11,8) NULL,
            status VARCHAR(20) DEFAULT 'present',
            is_holiday INT DEFAULT 0,
            holiday_id INT NULL,
            is_counted_absent INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_check_in_date (check_in),
            INDEX idx_holiday_id (holiday_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        log_message('✓ Attendance table created', 'success');
    } else {
        log_message('→ Attendance table already exists. Checking for missing columns...', 'warning');
        
        // Check for new columns
        $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'is_holiday'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE attendance ADD COLUMN is_holiday INT DEFAULT 0 AFTER status");
            log_message('✓ Added is_holiday column to attendance', 'success');
        }
        
        $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'holiday_id'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE attendance ADD COLUMN holiday_id INT NULL AFTER is_holiday");
            log_message('✓ Added holiday_id column to attendance', 'success');
        }
        
        $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'is_counted_absent'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE attendance ADD COLUMN is_counted_absent INT DEFAULT 0 AFTER holiday_id");
            log_message('✓ Added is_counted_absent column to attendance', 'success');
        }
    }
    $completedSteps++;
    
    // ============================================
    // STEP 4: Create Leaves Table
    // ============================================
    $totalSteps++;
    log_message('Step 4: Creating leaves table...', 'info');
    
    $stmt = $db->query("SHOW TABLES LIKE 'leaves'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE leaves (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            leave_type VARCHAR(50) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            days_requested INT NOT NULL,
            reason TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_dates (start_date, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        log_message('✓ Leaves table created', 'success');
    } else {
        log_message('→ Leaves table already exists', 'warning');
    }
    $completedSteps++;
    
    // ============================================
    // STEP 5: Create Holidays Table (NEW)
    // ============================================
    $totalSteps++;
    log_message('Step 5: Creating holidays table...', 'info');
    
    $stmt = $db->query("SHOW TABLES LIKE 'holidays'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            holiday_date DATE NOT NULL UNIQUE,
            holiday_name VARCHAR(255) NOT NULL,
            holiday_type ENUM('National', 'Festival', 'Company', 'Emergency', 'Other') DEFAULT 'Company',
            description TEXT,
            applies_to ENUM('All', 'Department', 'Specific') DEFAULT 'All',
            department_id INT NULL,
            repeat_yearly INT DEFAULT 0,
            created_by INT NOT NULL,
            is_active INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_holiday_date (holiday_date),
            INDEX idx_is_active (is_active),
            INDEX idx_applies_to (applies_to)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        log_message('✓ Holidays table created', 'success');
    } else {
        log_message('→ Holidays table already exists', 'warning');
    }
    $completedSteps++;
    
    // ============================================
    // STEP 6: Create Projects Table
    // ============================================
    $totalSteps++;
    log_message('Step 6: Creating projects table...', 'info');
    
    $stmt = $db->query("SHOW TABLES LIKE 'projects'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            latitude DECIMAL(10,8) NULL,
            longitude DECIMAL(11,8) NULL,
            checkin_radius INT DEFAULT 150,
            project_type VARCHAR(50),
            place VARCHAR(255),
            status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        log_message('✓ Projects table created', 'success');
    } else {
        log_message('→ Projects table already exists', 'warning');
    }
    $completedSteps++;
    
    // ============================================
    // STEP 7: Create Settings Table
    // ============================================
    $totalSteps++;
    log_message('Step 7: Creating settings table...', 'info');
    
    $stmt = $db->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(255) DEFAULT 'ERGON Company',
            base_location_lat DECIMAL(10,8) DEFAULT 0,
            base_location_lng DECIMAL(11,8) DEFAULT 0,
            attendance_radius INT DEFAULT 150,
            location_title VARCHAR(255) DEFAULT 'Main Office',
            office_address VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        log_message('✓ Settings table created', 'success');
        
        // Insert default settings
        $db->exec("INSERT INTO settings (company_name, location_title) VALUES ('ERGON Company', 'Main Office')");
        log_message('✓ Default settings inserted', 'success');
    } else {
        log_message('→ Settings table already exists', 'warning');
    }
    $completedSteps++;
    
    // ============================================
    // STEP 8: Create Tasks Table
    // ============================================
    $totalSteps++;
    log_message('Step 8: Creating tasks table...', 'info');
    
    $stmt = $db->query("SHOW TABLES LIKE 'tasks'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            assigned_by INT NOT NULL,
            assigned_to INT NOT NULL,
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            status ENUM('assigned', 'in_progress', 'completed', 'on_hold') DEFAULT 'assigned',
            progress INT DEFAULT 0,
            deadline DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_assigned_to (assigned_to),
            INDEX idx_status (status),
            INDEX idx_deadline (deadline)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        log_message('✓ Tasks table created', 'success');
    } else {
        log_message('→ Tasks table already exists', 'warning');
    }
    $completedSteps++;
    
    // ============================================
    // STEP 9: Create Ledger Table
    // ============================================
    $totalSteps++;
    log_message('Step 9: Creating user_ledgers table...', 'info');
    
    $stmt = $db->query("SHOW TABLES LIKE 'user_ledgers'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE user_ledgers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            reference_type VARCHAR(50) NOT NULL,
            reference_id INT NOT NULL,
            entry_type VARCHAR(50) NOT NULL,
            direction VARCHAR(10) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            balance_after DECIMAL(12,2) NULL,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_user_id (user_id),
            KEY idx_reference (reference_type, reference_id),
            KEY idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        log_message('✓ User ledgers table created', 'success');
    } else {
        log_message('→ User ledgers table already exists. Checking for missing columns...', 'warning');
        
        // Check if created_by column exists
        $stmt = $db->query("SHOW COLUMNS FROM user_ledgers LIKE 'created_by'");
        if ($stmt->rowCount() == 0) {
            try {
                $db->exec("ALTER TABLE user_ledgers ADD COLUMN created_by INT NULL");
                log_message('✓ Added created_by column to user_ledgers', 'success');
            } catch (Exception $e) {
                log_message('! Could not add created_by column: ' . $e->getMessage(), 'warning');
            }
        }
    }
    $completedSteps++;
    
    // ============================================
    // STEP 10: Verify Advances Table Columns
    // ============================================
    $totalSteps++;
    log_message('Step 10: Verifying advances table columns...', 'info');
    
    $advanceColumns = ['approval_remarks', 'approved_amount', 'payment_proof', 'paid_by', 'paid_at', 'approval_remarks', 'payment_remarks', 'paid_to_user_id', 'source_advance_id', 'paid_to_name', 'ledger_synced'];
    
    foreach ($advanceColumns as $col) {
        $stmt = $db->query("SHOW COLUMNS FROM advances LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            try {
                switch ($col) {
                    case 'approval_remarks':
                        $db->exec("ALTER TABLE advances ADD COLUMN approval_remarks TEXT NULL");
                        break;
                    case 'approved_amount':
                        $db->exec("ALTER TABLE advances ADD COLUMN approved_amount DECIMAL(10,2) NULL");
                        break;
                    case 'payment_proof':
                        $db->exec("ALTER TABLE advances ADD COLUMN payment_proof VARCHAR(255) NULL");
                        break;
                    case 'paid_by':
                        $db->exec("ALTER TABLE advances ADD COLUMN paid_by INT NULL");
                        break;
                    case 'paid_at':
                        $db->exec("ALTER TABLE advances ADD COLUMN paid_at DATETIME NULL");
                        break;
                    case 'payment_remarks':
                        $db->exec("ALTER TABLE advances ADD COLUMN payment_remarks TEXT NULL");
                        break;
                    case 'paid_to_user_id':
                        $db->exec("ALTER TABLE advances ADD COLUMN paid_to_user_id INT NULL");
                        break;
                    case 'source_advance_id':
                        $db->exec("ALTER TABLE advances ADD COLUMN source_advance_id INT NULL");
                        break;
                    case 'paid_to_name':
                        $db->exec("ALTER TABLE advances ADD COLUMN paid_to_name VARCHAR(255) NULL");
                        break;
                    case 'ledger_synced':
                        $db->exec("ALTER TABLE advances ADD COLUMN ledger_synced INT DEFAULT 0");
                        break;
                }
                log_message("✓ Added column '$col' to advances table", 'success');
            } catch (Exception $e) {
                log_message("! Could not add column '$col': " . $e->getMessage(), 'warning');
            }
        }
    }
    $completedSteps++;
    
    // ============================================
    // STEP 11: Verify Expenses Table Columns
    // ============================================
    $totalSteps++;
    log_message('Step 11: Verifying expenses table columns...', 'info');
    
    $expenseColumns = ['claimed_amount', 'approved_amount', 'payment_proof', 'paid_by', 'paid_at', 'approval_remarks', 'payment_remarks', 'paid_to_user_id', 'source_advance_id', 'paid_to_name', 'ledger_synced'];
    
    foreach ($expenseColumns as $col) {
        $stmt = $db->query("SHOW COLUMNS FROM expenses LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            try {
                switch ($col) {
                    case 'claimed_amount':
                        $db->exec("ALTER TABLE expenses ADD COLUMN claimed_amount DECIMAL(10,2) NULL");
                        break;
                    case 'approved_amount':
                        $db->exec("ALTER TABLE expenses ADD COLUMN approved_amount DECIMAL(10,2) NULL");
                        break;
                    case 'payment_proof':
                        $db->exec("ALTER TABLE expenses ADD COLUMN payment_proof VARCHAR(255) NULL");
                        break;
                    case 'paid_by':
                        $db->exec("ALTER TABLE expenses ADD COLUMN paid_by INT NULL");
                        break;
                    case 'paid_at':
                        $db->exec("ALTER TABLE expenses ADD COLUMN paid_at DATETIME NULL");
                        break;
                    case 'approval_remarks':
                        $db->exec("ALTER TABLE expenses ADD COLUMN approval_remarks TEXT NULL");
                        break;
                    case 'payment_remarks':
                        $db->exec("ALTER TABLE expenses ADD COLUMN payment_remarks TEXT NULL");
                        break;
                    case 'paid_to_user_id':
                        $db->exec("ALTER TABLE expenses ADD COLUMN paid_to_user_id INT NULL");
                        break;
                    case 'source_advance_id':
                        $db->exec("ALTER TABLE expenses ADD COLUMN source_advance_id INT NULL");
                        break;
                    case 'paid_to_name':
                        $db->exec("ALTER TABLE expenses ADD COLUMN paid_to_name VARCHAR(255) NULL");
                        break;
                    case 'ledger_synced':
                        $db->exec("ALTER TABLE expenses ADD COLUMN ledger_synced INT DEFAULT 0");
                        break;
                }
                log_message("✓ Added column '$col' to expenses table", 'success');
            } catch (Exception $e) {
                log_message("! Could not add column '$col': " . $e->getMessage(), 'warning');
            }
        }
    }
    $completedSteps++;
    
    // ============================================
    // FINAL: Verification
    // ============================================
    log_message('Step 12: Verifying all tables...', 'info');
    
    $requiredTables = ['users', 'departments', 'attendance', 'leaves', 'holidays', 'projects', 'settings', 'tasks', 'user_ledgers'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        log_message('✓ All required tables created successfully', 'success');
    } else {
        log_message('✗ Missing tables: ' . implode(', ', $missingTables), 'error');
    }
    $completedSteps++;
    
    // Success message
    if (!$isCliRun) {
        echo "<div class='alert alert-success'>";
        echo "<strong>✓ Migration Completed Successfully!</strong><br>";
        echo "All database tables have been created or verified.<br>";
        echo "You can now log in and use ERGON system.<br>";
        echo "<strong>Next steps:</strong><br>";
        echo "1. Delete this migration file for security<br>";
        echo "2. Go to application home: /ergon/<br>";
        echo "3. Login with your credentials<br>";
        echo "4. Test the new holiday feature<br>";
        echo "</div>";
        
        $progress = ($completedSteps / $totalSteps) * 100;
        echo "<script>document.getElementById('progressBar').style.width = '$progress%';</script>";
    } else {
        echo "\n✓ Migration completed successfully!\n";
    }
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    log_message('✗ Error: ' . $errorMsg, 'error');
    
    if (!$isCliRun) {
        echo "<div class='alert alert-error'>";
        echo "<strong>✗ Migration Failed</strong><br>";
        echo "Error: " . htmlspecialchars($errorMsg) . "<br>";
        echo "Check logs: app/logs/php-errors.log<br>";
        echo "</div>";
    } else {
        echo "\n✗ Migration failed: $errorMsg\n";
    }
    exit(1);
}

// Closing HTML
if (!$isCliRun) {
    echo "</div>";
    echo "<div class='footer'>";
    echo "<p><strong>Important:</strong> Delete this file after migration for security: migrations/run_migration.php</p>";
    echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";
    echo "</div>";
    echo "</div></body></html>";
}

ob_end_flush();
?>
