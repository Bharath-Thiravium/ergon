<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Fix existing attendance records for approved leaves
    $stmt = $db->prepare("
        UPDATE attendance a
        JOIN leaves l ON a.user_id = l.user_id 
        SET a.status = 'present', a.location_name = 'On Approved Leave'
        WHERE l.status = 'approved' 
        AND DATE(a.check_in) BETWEEN l.start_date AND l.end_date
        AND (a.location_name != 'On Approved Leave' OR a.location_name IS NULL)
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    
    // Create missing attendance records for approved leaves
    $stmt = $db->query("SELECT user_id, start_date, end_date FROM leaves WHERE status = 'approved'");
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $created = 0;
    foreach ($leaves as $leave) {
        $start = new DateTime($leave['start_date']);
        $end = new DateTime($leave['end_date']);
        $today = new DateTime();
        
        if ($end > $today) $end = $today;
        
        while ($start <= $end) {
            $currentDate = $start->format('Y-m-d');
            
            $checkStmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $checkStmt->execute([$leave['user_id'], $currentDate]);
            
            if (!$checkStmt->fetch()) {
                $insertStmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status, location_name, created_at) VALUES (?, ?, 'present', 'On Approved Leave', NOW())");
                $insertStmt->execute([$leave['user_id'], $currentDate . ' 09:00:00']);
                $created++;
            }
            
            $start->add(new DateInterval('P1D'));
        }
    }
    
    echo "Fixed $updated existing records and created $created new leave attendance records.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>