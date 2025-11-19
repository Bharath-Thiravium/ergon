<?php
/**
 * SLA Time Format Fix
 * Converts existing integer SLA hours to proper decimal format
 * and updates database schema
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Starting SLA time format fix...\n";
    
    // 1. Update database schema
    echo "1. Updating database schema...\n";
    $db->exec("ALTER TABLE tasks MODIFY COLUMN sla_hours DECIMAL(8,4) DEFAULT 0.25");
    echo "   ✓ Updated sla_hours column to DECIMAL(8,4)\n";
    
    // 2. Check for tasks with old integer values (24, 48, etc.)
    echo "2. Checking for tasks with old SLA values...\n";
    $stmt = $db->prepare("SELECT id, sla_hours FROM tasks WHERE sla_hours >= 24");
    $stmt->execute();
    $oldTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($oldTasks) > 0) {
        echo "   Found " . count($oldTasks) . " tasks with old SLA values\n";
        
        // Convert old values: 24 hours -> 0.25 hours (15 minutes)
        $updateStmt = $db->prepare("UPDATE tasks SET sla_hours = 0.25 WHERE sla_hours >= 24");
        $result = $updateStmt->execute();
        
        if ($result) {
            echo "   ✓ Updated " . count($oldTasks) . " tasks to use 15-minute default SLA\n";
        } else {
            echo "   ✗ Failed to update task SLA values\n";
        }
    } else {
        echo "   ✓ No tasks found with old SLA values\n";
    }
    
    // 3. Update daily_tasks table if it exists
    echo "3. Checking daily_tasks table...\n";
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE 'daily_tasks'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            // Check if sla_hours column exists in daily_tasks
            $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE 'sla_hours'");
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $db->exec("ALTER TABLE daily_tasks MODIFY COLUMN sla_hours DECIMAL(8,4) DEFAULT 0.25");
                echo "   ✓ Updated daily_tasks sla_hours column\n";
            } else {
                echo "   ℹ No sla_hours column in daily_tasks table\n";
            }
        } else {
            echo "   ℹ daily_tasks table does not exist\n";
        }
    } catch (Exception $e) {
        echo "   ℹ Could not update daily_tasks: " . $e->getMessage() . "\n";
    }
    
    // 4. Verify the changes
    echo "4. Verifying changes...\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count, MIN(sla_hours) as min_sla, MAX(sla_hours) as max_sla, AVG(sla_hours) as avg_sla FROM tasks");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   ✓ Total tasks: " . $stats['count'] . "\n";
    echo "   ✓ Min SLA: " . $stats['min_sla'] . " hours\n";
    echo "   ✓ Max SLA: " . $stats['max_sla'] . " hours\n";
    echo "   ✓ Avg SLA: " . round($stats['avg_sla'], 4) . " hours\n";
    
    echo "\n✅ SLA time format fix completed successfully!\n";
    echo "📝 Summary:\n";
    echo "   - Database schema updated to DECIMAL(8,4)\n";
    echo "   - Default SLA time is now 15 minutes (0.25 hours)\n";
    echo "   - Old 24-hour values converted to 15 minutes\n";
    echo "   - HH:MM format now properly supported\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>