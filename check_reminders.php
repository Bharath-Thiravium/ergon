<?php
// Reminder popup check - can be called via AJAX
require_once 'app/config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['reminders' => []]);
    exit;
}

try {
    $db = Database::connect();
    
    // Get pending reminders for current user
    $stmt = $db->prepare("
        SELECT id, title, company_name, contact_person, next_reminder, reminder_time 
        FROM followups 
        WHERE user_id = ? 
        AND next_reminder IS NOT NULL 
        AND reminder_sent = 0 
        AND status NOT IN ('completed', 'cancelled')
        AND CONCAT(next_reminder, ' ', COALESCE(reminder_time, '09:00:00')) <= NOW()
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['reminders' => $reminders]);
    
} catch (Exception $e) {
    echo json_encode(['reminders' => [], 'error' => $e->getMessage()]);
}
?>