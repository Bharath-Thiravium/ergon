<?php
/**
 * Test Leave and Attendance Integration
 * This script tests the leave approval and attendance disabling functionality
 */

require_once __DIR__ . '/app/config/database.php';

class LeaveAttendanceTest {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function runTests() {
        echo "=== Leave and Attendance Integration Test ===\n\n";
        
        try {
            $this->testDatabaseTables();
            $this->testLeaveApprovalProcess();
            $this->testAttendanceBlocking();
            $this->testLeaveAttendanceRecords();
            
            echo "\n=== All Tests Completed ===\n";
            
        } catch (Exception $e) {
            echo "Test failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function testDatabaseTables() {
        echo "1. Testing Database Tables...\n";
        
        // Check if leaves table exists
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'leaves'");
            if ($stmt->rowCount() > 0) {
                echo "   ✓ Leaves table exists\n";
            } else {
                echo "   ✗ Leaves table missing\n";
                return;
            }
        } catch (Exception $e) {
            echo "   ✗ Error checking leaves table: " . $e->getMessage() . "\n";
            return;
        }
        
        // Check if attendance table exists
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'attendance'");
            if ($stmt->rowCount() > 0) {
                echo "   ✓ Attendance table exists\n";
            } else {
                echo "   ✗ Attendance table missing\n";
                return;
            }
        } catch (Exception $e) {
            echo "   ✗ Error checking attendance table: " . $e->getMessage() . "\n";
            return;
        }
        
        // Check attendance table structure
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM attendance");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('location_name', $columns)) {
                echo "   ✓ Attendance table has location_name column\n";
            } else {
                echo "   ✗ Attendance table missing location_name column\n";
            }
            
            if (in_array('status', $columns)) {
                echo "   ✓ Attendance table has status column\n";
            } else {
                echo "   ✗ Attendance table missing status column\n";
            }
        } catch (Exception $e) {
            echo "   ✗ Error checking attendance table structure: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testLeaveApprovalProcess() {
        echo "2. Testing Leave Approval Process...\n";
        
        // Get approved leaves
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM leaves WHERE status = 'approved'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "   ✓ Found " . $result['count'] . " approved leaves\n";
            
            if ($result['count'] > 0) {
                // Get a sample approved leave
                $stmt = $this->db->query("SELECT * FROM leaves WHERE status = 'approved' LIMIT 1");
                $leave = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($leave) {
                    echo "   ✓ Sample approved leave: User ID " . $leave['user_id'] . 
                         " from " . $leave['start_date'] . " to " . $leave['end_date'] . "\n";
                }
            }
        } catch (Exception $e) {
            echo "   ✗ Error checking approved leaves: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testAttendanceBlocking() {
        echo "3. Testing Attendance Blocking for Leave Days...\n";
        
        try {
            // Check if users on leave have proper attendance records
            $stmt = $this->db->query("
                SELECT l.user_id, l.start_date, l.end_date, u.name,
                       COUNT(a.id) as attendance_records,
                       SUM(CASE WHEN a.location_name = 'On Approved Leave' THEN 1 ELSE 0 END) as leave_records
                FROM leaves l
                JOIN users u ON l.user_id = u.id
                LEFT JOIN attendance a ON l.user_id = a.user_id 
                    AND DATE(a.check_in) BETWEEN l.start_date AND l.end_date
                WHERE l.status = 'approved'
                GROUP BY l.id, l.user_id, l.start_date, l.end_date, u.name
                LIMIT 5
            ");
            
            $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($leaves as $leave) {
                echo "   User: " . $leave['name'] . " (ID: " . $leave['user_id'] . ")\n";
                echo "     Leave period: " . $leave['start_date'] . " to " . $leave['end_date'] . "\n";
                echo "     Attendance records: " . $leave['attendance_records'] . "\n";
                echo "     Leave attendance records: " . $leave['leave_records'] . "\n";
                
                if ($leave['leave_records'] > 0) {
                    echo "     ✓ Has proper leave attendance records\n";
                } else {
                    echo "     ✗ Missing leave attendance records\n";
                }
                echo "\n";
            }
            
        } catch (Exception $e) {
            echo "   ✗ Error testing attendance blocking: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testLeaveAttendanceRecords() {
        echo "4. Testing Leave Attendance Records...\n";
        
        try {
            // Count total leave attendance records
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM attendance WHERE location_name = 'On Approved Leave'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "   ✓ Total leave attendance records: " . $result['count'] . "\n";
            
            // Check for recent leave attendance records
            $stmt = $this->db->query("
                SELECT a.*, u.name 
                FROM attendance a 
                JOIN users u ON a.user_id = u.id 
                WHERE a.location_name = 'On Approved Leave' 
                AND DATE(a.check_in) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY a.check_in DESC 
                LIMIT 5
            ");
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($records) > 0) {
                echo "   ✓ Recent leave attendance records:\n";
                foreach ($records as $record) {
                    echo "     - " . $record['name'] . " on " . date('Y-m-d', strtotime($record['check_in'])) . 
                         " (Status: " . $record['status'] . ")\n";
                }
            } else {
                echo "   ! No recent leave attendance records found\n";
            }
            
            // Check for any inconsistencies
            $stmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM attendance 
                WHERE location_name = 'On Approved Leave' 
                AND status != 'present'
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo "   ✗ Found " . $result['count'] . " leave records with incorrect status\n";
            } else {
                echo "   ✓ All leave attendance records have correct status\n";
            }
            
        } catch (Exception $e) {
            echo "   ✗ Error testing leave attendance records: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    public function getEnvironmentInfo() {
        echo "=== Environment Information ===\n";
        
        try {
            require_once __DIR__ . '/app/config/environment.php';
            
            echo "Environment: " . Environment::detect() . "\n";
            echo "Is Development: " . (Environment::isDevelopment() ? 'Yes' : 'No') . "\n";
            echo "Is Production: " . (Environment::isProduction() ? 'Yes' : 'No') . "\n";
            echo "Is Hostinger: " . (Environment::isHostinger() ? 'Yes' : 'No') . "\n";
            echo "Base URL: " . Environment::getBaseUrl() . "\n";
            
            // Database info
            $db = new Database();
            echo "Database Environment: " . $db->getEnvironment() . "\n";
            
        } catch (Exception $e) {
            echo "Error getting environment info: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// Run the tests
try {
    $tester = new LeaveAttendanceTest();
    $tester->getEnvironmentInfo();
    $tester->runTests();
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
?>