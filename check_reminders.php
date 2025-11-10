<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::connect();
    
    // Check for reminders that are due
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    
    $stmt = $db->prepare("
        SELECT f.*, 
               CONCAT(f.follow_up_date, ' ', IFNULL(f.reminder_time, '09:00:00')) as reminder_datetime
        FROM followups f 
        WHERE f.user_id = ? 
        AND f.status != 'completed' 
        AND f.reminder_sent = 0
        AND f.follow_up_date <= ?
        AND (
            f.reminder_time IS NULL 
            OR CONCAT(f.follow_up_date, ' ', f.reminder_time) <= ?
        )
        ORDER BY f.follow_up_date ASC, f.reminder_time ASC
        LIMIT 5
    ");
    
    $stmt->execute([$_SESSION['user_id'], $today, $now]);
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
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>