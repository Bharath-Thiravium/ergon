<?php
/**
 * Validation Script for Enhanced Daily Planner Rollover System
 * Tests the FetchAndRolloverDailyTasks instruction implementation
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/DailyPlanner.php';

class RolloverSystemValidator {
    private $db;
    private $planner;
    
    public function __construct() {
        $this->db = Database::connect();
        $this->planner = new DailyPlanner();
    }
    
    public function runValidation() {
        echo "🔍 VALIDATING ENHANCED DAILY PLANNER ROLLOVER SYSTEM\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        $this->validateDatabaseStructure();
        $this->validateTaskFetching();
        $this->validateRolloverLogic();
        $this->validateAuditTrail();
        $this->validateDateRestrictions();
        
        echo "\n✅ VALIDATION COMPLETED\n";
    }
    
    private function validateDatabaseStructure() {
        echo "📋 Step 1: Validating Database Structure\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        // Check daily_tasks table structure
        $stmt = $this->db->query("DESCRIBE daily_tasks");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'original_task_id', 'source_field', 'rollover_source_date', 
            'rollover_timestamp', 'postponed_to_date'
        ];
        
        foreach ($requiredColumns as $column) {
            if (in_array($column, $columns)) {
                echo "✅ Column '{$column}' exists\n";
            } else {
                echo "❌ Column '{$column}' missing\n";
            }
        }
        
        // Check audit table
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM daily_planner_audit");
            echo "✅ Audit table 'daily_planner_audit' exists\n";
        } catch (Exception $e) {
            echo "❌ Audit table missing: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function validateTaskFetching() {
        echo "🎯 Step 2: Validating Task Fetching Logic\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $testUserId = 1;
        $testDate = date('Y-m-d');
        
        // Test fetching tasks for current date
        $tasks = $this->planner->getTasksForDate($testUserId, $testDate);
        echo "📊 Fetched " . count($tasks) . " tasks for current date\n";
        
        // Check for source field tracking
        foreach ($tasks as $task) {
            if (!empty($task['source_field'])) {
                echo "✅ Task '{$task['title']}' has source tracking: {$task['source_field']}\n";
            }
            if (!empty($task['task_indicator'])) {
                echo "🏷️ Task indicator: {$task['task_indicator']}\n";
            }
        }
        
        echo "\n";
    }
    
    private function validateRolloverLogic() {
        echo "🔄 Step 3: Validating Rollover Logic\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        // Test rollover prevention of duplicates
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');
        
        // Check for existing rollover entries
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as rollover_count 
            FROM daily_tasks 
            WHERE rollover_source_date = ? AND scheduled_date = ?
        ");
        $stmt->execute([$yesterday, $today]);
        $rolloverCount = $stmt->fetchColumn();
        
        echo "📈 Found {$rolloverCount} rollover entries from {$yesterday} to {$today}\n";
        
        // Test rollover execution
        $rolledOver = $this->planner->rolloverUncompletedTasks();
        echo "🔄 Rollover process handled {$rolledOver} tasks\n";
        
        // Validate no duplicates
        $stmt = $this->db->prepare("
            SELECT original_task_id, COUNT(*) as duplicate_count
            FROM daily_tasks 
            WHERE scheduled_date = ? AND user_id = 1
            GROUP BY original_task_id 
            HAVING COUNT(*) > 1
        ");
        $stmt->execute([$today]);
        $duplicates = $stmt->fetchAll();
        
        if (empty($duplicates)) {
            echo "✅ No duplicate rollover entries found\n";
        } else {
            echo "❌ Found " . count($duplicates) . " duplicate entries\n";
        }
        
        echo "\n";
    }
    
    private function validateAuditTrail() {
        echo "📝 Step 4: Validating Audit Trail\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        // Check task history entries
        $stmt = $this->db->query("
            SELECT COUNT(*) as history_count 
            FROM daily_task_history 
            WHERE action IN ('fetched', 'rolled_over')
        ");
        $historyCount = $stmt->fetchColumn();
        echo "📋 Found {$historyCount} audit history entries\n";
        
        // Check audit log entries
        $stmt = $this->db->query("
            SELECT COUNT(*) as audit_count 
            FROM daily_planner_audit 
            WHERE action IN ('view_access', 'daily_rollover')
        ");
        $auditCount = $stmt->fetchColumn();
        echo "🔍 Found {$auditCount} audit log entries\n";
        
        // Show recent audit entries
        $stmt = $this->db->query("
            SELECT action, target_date, task_count, timestamp 
            FROM daily_planner_audit 
            ORDER BY timestamp DESC 
            LIMIT 5
        ");
        $recentAudits = $stmt->fetchAll();
        
        echo "📊 Recent audit entries:\n";
        foreach ($recentAudits as $audit) {
            echo "   • {$audit['action']} on {$audit['target_date']} - {$audit['task_count']} tasks at {$audit['timestamp']}\n";
        }
        
        echo "\n";
    }
    
    private function validateDateRestrictions() {
        echo "📅 Step 5: Validating Date Restrictions\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $testUserId = 1;
        $pastDate = date('Y-m-d', strtotime('-3 days'));
        $currentDate = date('Y-m-d');
        
        // Test past date view
        $pastTasks = $this->planner->getTasksForDate($testUserId, $pastDate);
        echo "📜 Past date ({$pastDate}): " . count($pastTasks) . " tasks (historical view only)\n";
        
        // Test current date view
        $currentTasks = $this->planner->getTasksForDate($testUserId, $currentDate);
        echo "📅 Current date ({$currentDate}): " . count($currentTasks) . " tasks (includes rollovers)\n";
        
        // Validate past date restrictions
        $rolloverInPast = 0;
        foreach ($pastTasks as $task) {
            if (!empty($task['rollover_source_date'])) {
                $rolloverInPast++;
            }
        }
        
        if ($rolloverInPast == 0) {
            echo "✅ Past date correctly excludes rollover tasks\n";
        } else {
            echo "❌ Past date incorrectly shows {$rolloverInPast} rollover tasks\n";
        }
        
        echo "\n";
    }
}

// Run validation
try {
    $validator = new RolloverSystemValidator();
    $validator->runValidation();
} catch (Exception $e) {
    echo "❌ Validation failed: " . $e->getMessage() . "\n";
}
