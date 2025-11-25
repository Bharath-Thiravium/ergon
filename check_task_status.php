<?php
session_start();
require_once 'app/config/database.php';

$_SESSION['user_id'] = 16; // From your data

try {
    $db = Database::connect();
    
    echo "<h1>Current Task Status Check</h1>";
    
    // Check the actual status of the tasks in the database
    $taskIds = [212, 209, 210];
    
    foreach ($taskIds as $taskId) {
        $stmt = $db->prepare("SELECT id, title, status, start_time, pause_start_time, resume_time FROM daily_tasks WHERE id = ? AND user_id = 16");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task) {
            echo "<h3>Task {$taskId}: {$task['title']}</h3>";
            echo "<p><strong>Status:</strong> {$task['status']}</p>";
            echo "<p><strong>Start Time:</strong> " . ($task['start_time'] ?: 'NULL') . "</p>";
            echo "<p><strong>Pause Start Time:</strong> " . ($task['pause_start_time'] ?: 'NULL') . "</p>";
            echo "<p><strong>Resume Time:</strong> " . ($task['resume_time'] ?: 'NULL') . "</p>";
            
            // Determine what actions should be available
            echo "<p><strong>Available Actions:</strong> ";
            switch ($task['status']) {
                case 'not_started':
                    echo "Start";
                    break;
                case 'in_progress':
                    echo "Pause, Update Progress";
                    break;
                case 'on_break':
                    echo "Resume, Update Progress";
                    break;
                case 'completed':
                    echo "None (completed)";
                    break;
                case 'postponed':
                    echo "None (postponed)";
                    break;
                default:
                    echo "Unknown status: " . $task['status'];
            }
            echo "</p><hr>";
        } else {
            echo "<h3>Task {$taskId}: NOT FOUND</h3><hr>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>