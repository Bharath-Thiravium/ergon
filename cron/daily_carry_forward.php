<?php
/**
 * Daily Carry Forward Cron Job
 * 
 * This script should run once per day at 12:01 AM to carry forward
 * unattended/not started tasks from previous days.
 * 
 * Schedule: 0 0 * * * (daily at midnight)
 */

require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::connect();
    $today = date('Y-m-d');
    $logFile = __DIR__ . '/../storage/logs/carry_forward_' . date('Y-m') . '.log';
    
    // Ensure log directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Daily Carry Forward Job Started\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Create carry forward log table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS carry_forward_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        user_id INT NOT NULL,
        task_id INT NOT NULL,
        from_date DATE NOT NULL,
        to_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_date (date),
        INDEX idx_user (user_id)
    )");
    
    // Get all users with pending tasks from past dates
    $stmt = $db->prepare("
        SELECT DISTINCT assigned_to as user_id
        FROM tasks 
        WHERE status IN ('assigned', 'not_started') 
        AND planned_date < ? 
        AND planned_date IS NOT NULL
    ");
    $stmt->execute([$today]);
    $usersWithPendingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalCarriedForward = 0;
    
    foreach ($usersWithPendingTasks as $user) {
        $userId = $user['user_id'];
        
        // Get pending tasks for this user
        $stmt = $db->prepare("
            SELECT id, title, planned_date
            FROM tasks 
            WHERE assigned_to = ? 
            AND status IN ('assigned', 'not_started') 
            AND planned_date < ? 
            AND planned_date IS NOT NULL
        ");
        $stmt->execute([$userId, $today]);
        $pendingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($pendingTasks)) {
            // Check if we already carried forward for this user today
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM carry_forward_log 
                WHERE date = ? AND user_id = ?
            ");
            $stmt->execute([$today, $userId]);
            $alreadyCarriedForward = $stmt->fetchColumn() > 0;
            
            if (!$alreadyCarriedForward) {
                // Carry forward tasks for this user
                foreach ($pendingTasks as $task) {
                    // Update task planned date
                    $stmt = $db->prepare("UPDATE tasks SET planned_date = ? WHERE id = ?");
                    $stmt->execute([$today, $task['id']]);
                    
                    // Log the carry forward
                    $stmt = $db->prepare("
                        INSERT INTO carry_forward_log (date, user_id, task_id, from_date, to_date) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$today, $userId, $task['id'], $task['planned_date'], $today]);
                    
                    $totalCarriedForward++;
                }
                
                $logMessage = "[" . date('Y-m-d H:i:s') . "] User $userId: Carried forward " . count($pendingTasks) . " tasks\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND);
            } else {
                $logMessage = "[" . date('Y-m-d H:i:s') . "] User $userId: Already carried forward today, skipping\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND);
            }
        }
    }
    
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Daily Carry Forward Job Completed. Total tasks carried forward: $totalCarriedForward\n\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    echo "Daily carry forward completed. $totalCarriedForward tasks carried forward.\n";
    
} catch (Exception $e) {
    $errorMessage = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n\n";
    file_put_contents($logFile, $errorMessage, FILE_APPEND);
    echo "Error: " . $e->getMessage() . "\n";
}
?>