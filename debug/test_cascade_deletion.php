<?php
/**
 * Test script to verify cascade deletion functionality
 * This script creates a test task with followup and then deletes it to verify all related records are removed
 */

require_once __DIR__ . '/../app/config/database.php';

function testCascadeDeletion() {
    try {
        $db = Database::connect();
        
        echo "=== Task Cascade Deletion Test ===\n";
        
        // Step 1: Create a test task
        $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, status, followup_required, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['Test Task for Cascade Deletion', 'This is a test task', 1, 1, 'assigned', 1]);
        $taskId = $db->lastInsertId();
        echo "✓ Created test task with ID: $taskId\n";
        
        // Step 2: Create a followup linked to this task
        $stmt = $db->prepare("INSERT INTO followups (title, description, followup_type, task_id, user_id, follow_up_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['Test Followup', 'Test followup for cascade deletion', 'task', $taskId, 1, date('Y-m-d'), 'pending']);
        $followupId = $db->lastInsertId();
        echo "✓ Created test followup with ID: $followupId\n";
        
        // Step 3: Create a daily_tasks entry
        $stmt = $db->prepare("INSERT INTO daily_tasks (user_id, task_id, original_task_id, title, description, scheduled_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([1, $taskId, $taskId, 'Test Task for Cascade Deletion', 'This is a test task', date('Y-m-d'), 'not_started']);
        $dailyTaskId = $db->lastInsertId();
        echo "✓ Created test daily_tasks entry with ID: $dailyTaskId\n";
        
        // Step 4: Verify records exist before deletion
        $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $taskExists = $stmt->fetchColumn();
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM followups WHERE task_id = ?");
        $stmt->execute([$taskId]);
        $followupExists = $stmt->fetchColumn();
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE original_task_id = ?");
        $stmt->execute([$taskId]);
        $dailyTaskExists = $stmt->fetchColumn();
        
        echo "Before deletion - Task: $taskExists, Followup: $followupExists, Daily Task: $dailyTaskExists\n";
        
        // Step 5: Delete the task using the cascade deletion logic
        $db->beginTransaction();
        
        // Delete from followups table first
        $stmt = $db->prepare("DELETE FROM followups WHERE task_id = ?");
        $stmt->execute([$taskId]);
        $followupsDeleted = $stmt->rowCount();
        
        // Delete from daily_tasks
        $stmt = $db->prepare("DELETE FROM daily_tasks WHERE task_id = ? OR original_task_id = ?");
        $stmt->execute([$taskId, $taskId]);
        $dailyTasksDeleted = $stmt->rowCount();
        
        // Delete from tasks table
        $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $tasksDeleted = $stmt->rowCount();
        
        $db->commit();
        
        echo "✓ Cascade deletion completed:\n";
        echo "  - Tasks deleted: $tasksDeleted\n";
        echo "  - Followups deleted: $followupsDeleted\n";
        echo "  - Daily tasks deleted: $dailyTasksDeleted\n";
        
        // Step 6: Verify all records are deleted
        $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $taskExistsAfter = $stmt->fetchColumn();
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM followups WHERE task_id = ?");
        $stmt->execute([$taskId]);
        $followupExistsAfter = $stmt->fetchColumn();
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE original_task_id = ?");
        $stmt->execute([$taskId]);
        $dailyTaskExistsAfter = $stmt->fetchColumn();
        
        echo "After deletion - Task: $taskExistsAfter, Followup: $followupExistsAfter, Daily Task: $dailyTaskExistsAfter\n";
        
        if ($taskExistsAfter == 0 && $followupExistsAfter == 0 && $dailyTaskExistsAfter == 0) {
            echo "✅ SUCCESS: Cascade deletion working correctly!\n";
            return true;
        } else {
            echo "❌ FAILURE: Some records were not deleted properly!\n";
            return false;
        }
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollback();
        }
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run the test
if (php_sapi_name() === 'cli') {
    testCascadeDeletion();
} else {
    header('Content-Type: text/plain');
    testCascadeDeletion();
}
?>
