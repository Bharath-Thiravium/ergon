<?php
/**
 * Fix Leave Attendance Records
 * This script corrects attendance records for approved leaves
 */

require_once __DIR__ . '/app/config/database.php';

class LeaveAttendanceFixer {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function fix() {
        echo "Starting Leave Attendance Fix...\n";
        
        try {
            // Step 1: Update existing attendance records for approved leaves
            $this->updateExistingRecords();
            
            // Step 2: Create missing attendance records for approved leaves
            $this->createMissingRecords();
            
            // Step 3: Verify the fix
            $this->verifyFix();
            
            echo "Leave Attendance Fix completed successfully!\n";
            
        } catch (Exception $e) {
            echo "Error during fix: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function updateExistingRecords() {
        echo "Step 1: Updating existing attendance records for approved leaves...\n";
        
        $sql = "UPDATE attendance a
                JOIN leaves l ON a.user_id = l.user_id 
                SET a.status = 'present', 
                    a.location_name = 'On Approved Leave'
                WHERE l.status = 'approved' 
                  AND DATE(a.check_in) BETWEEN l.start_date AND l.end_date
                  AND (a.location_name != 'On Approved Leave' OR a.location_name IS NULL)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $updatedRows = $stmt->rowCount();
        echo "Updated $updatedRows existing attendance records.\n";
    }
    
    private function createMissingRecords() {
        echo "Step 2: Creating missing attendance records for approved leaves...\n";
        
        // Get all approved leaves
        $stmt = $this->db->query("SELECT user_id, start_date, end_date FROM leaves WHERE status = 'approved'");
        $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $createdRecords = 0;
        
        foreach ($leaves as $leave) {
            $start = new DateTime($leave['start_date']);
            $end = new DateTime($leave['end_date']);
            $today = new DateTime();
            
            // Only create records up to today
            if ($end > $today) {
                $end = $today;
            }
            
            while ($start <= $end) {
                $currentDate = $start->format('Y-m-d');
                
                // Check if attendance record already exists
                $checkStmt = $this->db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
                $checkStmt->execute([$leave['user_id'], $currentDate]);
                
                if (!$checkStmt->fetch()) {
                    // Create new attendance record for leave
                    $insertStmt = $this->db->prepare("INSERT INTO attendance (user_id, check_in, check_out, status, location_name, created_at) VALUES (?, ?, NULL, 'present', 'On Approved Leave', NOW())");
                    $insertStmt->execute([$leave['user_id'], $currentDate . ' 09:00:00']);
                    $createdRecords++;
                }
                
                $start->add(new DateInterval('P1D'));
            }
        }
        
        echo "Created $createdRecords new leave attendance records.\n";
    }
    
    private function verifyFix() {
        echo "Step 3: Verifying the fix...\n";
        
        // Count leave attendance records
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM attendance WHERE location_name = 'On Approved Leave'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Total leave attendance records: " . $result['count'] . "\n";
        
        // Check for any approved leaves without proper attendance records
        $stmt = $this->db->query("
            SELECT COUNT(*) as missing_count
            FROM leaves l
            WHERE l.status = 'approved'
            AND l.start_date <= CURDATE()
            AND NOT EXISTS (
                SELECT 1 FROM attendance a 
                WHERE a.user_id = l.user_id 
                AND DATE(a.check_in) BETWEEN l.start_date AND LEAST(l.end_date, CURDATE())
                AND a.location_name = 'On Approved Leave'
            )
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['missing_count'] > 0) {
            echo "Warning: " . $result['missing_count'] . " approved leaves still missing proper attendance records.\n";
        } else {
            echo "All approved leaves have proper attendance records.\n";
        }
    }
}

// Run the fix
try {
    $fixer = new LeaveAttendanceFixer();
    $fixer->fix();
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
?>