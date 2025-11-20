<?php
/**
 * Create Leave Attendance Records - Auto-create attendance records for approved leaves
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Creating leave attendance records...\n";
    
    // Get all approved leaves that don't have attendance records
    $stmt = $db->query("
        SELECT l.user_id, l.start_date, l.end_date 
        FROM leaves l 
        WHERE l.status = 'approved' 
        AND NOT EXISTS (
            SELECT 1 FROM attendance a 
            WHERE a.user_id = l.user_id 
            AND DATE(a.check_in) BETWEEN DATE(l.start_date) AND DATE(l.end_date)
        )
    ");
    
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($leaves as $leave) {
        $startDate = new DateTime($leave['start_date']);
        $endDate = new DateTime($leave['end_date']);
        
        // Create attendance record for each day of leave
        while ($startDate <= $endDate) {
            $leaveDate = $startDate->format('Y-m-d');
            
            // Insert leave attendance record
            $insertStmt = $db->prepare("
                INSERT INTO attendance (user_id, check_in, check_out, status, location_name, created_at) 
                VALUES (?, ?, ?, 'absent', 'On Leave', NOW())
            ");
            
            $insertStmt->execute([
                $leave['user_id'],
                $leaveDate . ' 00:00:00',
                $leaveDate . ' 00:00:00'
            ]);
            
            echo "Created leave record for user {$leave['user_id']} on {$leaveDate}\n";
            
            $startDate->add(new DateInterval('P1D'));
        }
    }
    
    echo "Leave attendance records created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>