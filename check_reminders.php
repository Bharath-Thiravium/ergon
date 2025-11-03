<?php
/**
 * Follow-up Reminders Check Script
 * This script checks for follow-up reminders and returns them as JSON
 */

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/app/config/database.php';
    
    $db = Database::connect();
    
    // Check for follow-ups due today or overdue with reminders
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');
    
    $stmt = $db->prepare("
        SELECT f.*, u.name as user_name 
        FROM followups f 
        LEFT JOIN users u ON f.user_id = u.id 
        WHERE f.status IN ('pending', 'in_progress') 
        AND (
            (f.follow_up_date = ? AND f.reminder_time <= ? AND f.reminder_sent = 0)
            OR (f.follow_up_date < ? AND f.reminder_sent = 0)
        )
        ORDER BY f.follow_up_date ASC, f.reminder_time ASC
        LIMIT 10
    ");
    
    $stmt->execute([$today, $currentTime, $today]);
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark reminders as sent
    if (!empty($reminders)) {
        $ids = array_column($reminders, 'id');
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $updateStmt = $db->prepare("UPDATE followups SET reminder_sent = 1 WHERE id IN ($placeholders)");
        $updateStmt->execute($ids);
    }
    
    echo json_encode([
        'success' => true,
        'reminders' => $reminders,
        'count' => count($reminders)
    ]);
    
} catch (Exception $e) {
    error_log('Reminder check error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'reminders' => []
    ]);
}
?>