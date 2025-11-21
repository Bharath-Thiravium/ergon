<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    $selectedDate = date('Y-m-d');
    
    echo "<h2>Simple Query Test:</h2>";
    $stmt = $db->prepare("
        SELECT 
            u.id as user_id,
            u.name,
            u.email,
            u.role,
            u.status,
            a.id as attendance_id,
            a.check_in,
            a.check_out
        FROM users u
        LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
        WHERE u.status != 'removed'
        ORDER BY u.role DESC, u.name
    ");
    $stmt->execute([$selectedDate]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>