<?php
/**
 * ERGON Holiday Management System Migration
 * Run this to set up the holiday management feature
 */

require_once __DIR__ . '/../app/config/database.php';

class HolidayMigration {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::connect();
    }
    
    public function run() {
        echo "Starting Holiday Management System migration...\n";
        
        try {
            // Create holidays table
            $this->createHolidaysTable();
            echo "✓ Holidays table created\n";
            
            // Add columns to attendance table
            $this->addHolidayColumnsToAttendance();
            echo "✓ Holiday columns added to attendance table\n";
            
            // Create indexes
            $this->createIndexes();
            echo "✓ Indexes created\n";
            
            // Add foreign key constraints
            $this->addForeignKeyConstraints();
            echo "✓ Foreign key constraints added\n";
            
            echo "\n✅ Holiday Management System successfully initialized!\n";
            return true;
        } catch (Exception $e) {
            echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function createHolidaysTable() {
        $sql = "CREATE TABLE IF NOT EXISTS holidays (
          id INT AUTO_INCREMENT PRIMARY KEY,
          holiday_date DATE NOT NULL UNIQUE,
          holiday_name VARCHAR(255) NOT NULL,
          holiday_type ENUM('National', 'Festival', 'Company', 'Emergency', 'Other') DEFAULT 'Company',
          description TEXT NULL,
          applies_to ENUM('All', 'Department', 'Specific') DEFAULT 'All',
          department_id INT NULL,
          repeat_yearly BOOLEAN DEFAULT FALSE,
          is_active BOOLEAN DEFAULT TRUE,
          created_by INT NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->exec($sql);
    }
    
    private function addHolidayColumnsToAttendance() {
        // Add is_holiday column
        try {
            $this->conn->exec("ALTER TABLE attendance ADD COLUMN is_holiday BOOLEAN DEFAULT FALSE AFTER status");
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
        
        // Add holiday_id column
        try {
            $this->conn->exec("ALTER TABLE attendance ADD COLUMN holiday_id INT NULL AFTER is_holiday");
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
        
        // Add is_counted_absent column
        try {
            $this->conn->exec("ALTER TABLE attendance ADD COLUMN is_counted_absent BOOLEAN DEFAULT TRUE AFTER holiday_id");
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
    }
    
    private function createIndexes() {
        try {
            $this->conn->exec("ALTER TABLE holidays ADD INDEX idx_holiday_date (holiday_date)");
        } catch (Exception $e) {}
        
        try {
            $this->conn->exec("ALTER TABLE holidays ADD INDEX idx_holiday_type (holiday_type)");
        } catch (Exception $e) {}
        
        try {
            $this->conn->exec("ALTER TABLE holidays ADD INDEX idx_applies_to (applies_to)");
        } catch (Exception $e) {}
        
        try {
            $this->conn->exec("ALTER TABLE holidays ADD INDEX idx_department_id (department_id)");
        } catch (Exception $e) {}
        
        try {
            $this->conn->exec("ALTER TABLE holidays ADD INDEX idx_is_active (is_active)");
        } catch (Exception $e) {}
        
        try {
            $this->conn->exec("ALTER TABLE attendance ADD INDEX idx_is_holiday (is_holiday)");
        } catch (Exception $e) {}
        
        try {
            $this->conn->exec("ALTER TABLE attendance ADD INDEX idx_is_counted_absent (is_counted_absent)");
        } catch (Exception $e) {}
    }
    
    private function addForeignKeyConstraints() {
        try {
            $this->conn->exec(
                "ALTER TABLE holidays ADD CONSTRAINT fk_holiday_creator 
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL"
            );
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Constraint') === false) {
                // Not a constraint error, re-throw
                throw $e;
            }
        }
        
        try {
            $this->conn->exec(
                "ALTER TABLE holidays ADD CONSTRAINT fk_holiday_department 
                FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL"
            );
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Constraint') === false) {
                throw $e;
            }
        }
        
        try {
            $this->conn->exec(
                "ALTER TABLE attendance ADD CONSTRAINT fk_attendance_holiday 
                FOREIGN KEY (holiday_id) REFERENCES holidays(id) ON DELETE SET NULL"
            );
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Constraint') === false) {
                throw $e;
            }
        }
    }
}

// Run migration
if (php_sapi_name() === 'cli') {
    $migration = new HolidayMigration();
    $migration->run();
} else {
    // For web access
    header('Content-Type: application/json');
    $migration = new HolidayMigration();
    $result = $migration->run();
    echo json_encode(['success' => $result]);
}
?>
