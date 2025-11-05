<?php
header('Content-Type: application/json');
require_once 'app/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT f.*, u.name as user_name 
              FROM followups f 
              LEFT JOIN users u ON f.user_id = u.id 
              WHERE f.reminder_time <= NOW() 
              AND f.status = 'pending' 
              ORDER BY f.reminder_time ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'reminders' => $reminders]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>