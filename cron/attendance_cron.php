<?php
/**
 * Attendance Cron Job
 * Run daily to handle auto-checkout and absent marking
 */

require_once __DIR__ . '/../app/config/database.php';

class AttendanceCron {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function run() {
        echo "[" . date('Y-m-d H:i:s') . "] Starting attendance cron job...\n";
        
        $this->autoCheckout();
        $this->markAbsent();
        
        echo "[" . date('Y-m-d H:i:s') . "] Attendance cron job completed.\n";
    }
    
    private function autoCheckout() {
        try {
            $stmt = $this->db->prepare("
                SELECT id, user_id, check_in 
                FROM attendance 
                WHERE DATE(check_in) = CURDATE() 
                AND check_out IS NULL 
                AND TIME(NOW()) > '18:00:00'
            ");
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($records as $record) {
                $checkIn = new DateTime($record['check_in']);
                $autoCheckout = new DateTime(date('Y-m-d 18:00:00'));
                $totalHours = $autoCheckout->diff($checkIn)->h + ($autoCheckout->diff($checkIn)->i / 60);
                
                $updateStmt = $this->db->prepare("
                    UPDATE attendance 
                    SET check_out = ?, total_hours = ?, is_auto_checkout = 1, 
                        remarks = 'Auto checkout by system', updated_at = NOW()
                    WHERE id = ?
                ");
                
                $updateStmt->execute([date('Y-m-d 18:00:00'), round($totalHours, 2), $record['id']]);
                echo "Auto checkout: User ID {$record['user_id']}\n";
            }
            
        } catch (Exception $e) {
            echo "Error in auto checkout: " . $e->getMessage() . "\n";
        }
    }
    
    private function markAbsent() {
        try {
            $stmt = $this->db->query("
                SELECT id FROM users 
                WHERE status = 'active' 
                AND role IN ('user', 'admin')
            ");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as $user) {
                $checkStmt = $this->db->prepare("
                    SELECT id FROM attendance 
                    WHERE user_id = ? AND (DATE(check_in) = CURDATE() OR date = CURDATE())
                ");
                $checkStmt->execute([$user['id']]);
                
                if (!$checkStmt->fetch()) {
                    $insertStmt = $this->db->prepare("
                        INSERT INTO attendance 
                        (user_id, date, status, created_at) 
                        VALUES (?, CURDATE(), 'absent', NOW())
                    ");
                    $insertStmt->execute([$user['id']]);
                    echo "Marked absent: User ID {$user['id']}\n";
                }
            }
            
        } catch (Exception $e) {
            echo "Error in marking absent: " . $e->getMessage() . "\n";
        }
    }
}

if (php_sapi_name() === 'cli') {
    $cron = new AttendanceCron();
    $cron->run();
} else {
    echo "This script should be run from command line only.";
}
?>
