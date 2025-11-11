<!DOCTYPE html>
<html>
<head>
    <title>Cleanup Redundant Files</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
        .section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .delete { background: #ffe6e6; }
        .keep { background: #e6ffe6; }
        .btn { padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn-safe { background: #28a745; }
        .file-list { font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <h1>🧹 Audit & Cleanup Redundant Files</h1>

    <?php
    // Define redundant files to delete
    $redundantFiles = [
        // Legacy Controllers (replaced by UnifiedWorkflowController)
        'app/controllers/DailyTaskPlannerController.php',
        'app/controllers/DailyWorkflowController.php', 
        'app/controllers/EveningUpdateController.php',
        'app/controllers/PlannerController.php',
        
        // Legacy Models (replaced by unified system)
        'app/models/DailyTaskPlanner.php',
        'app/models/DailyWorkflow.php',
        
        // Legacy Views (replaced by unified views)
        'views/daily_planner/dashboard.php',
        'views/daily_planner/delayed_tasks_overview.php',
        'views/daily_planner/index.php',
        'views/daily_planner/project_overview.php',
        'views/daily_workflow/daily_planner.php',
        'views/daily_workflow/evening_update.php',
        'views/daily_workflow/morning_planner.php',
        'views/daily_workflow/progress_dashboard.php',
        'views/evening-update/index.php',
        'views/planner/calendar.php',
        'views/planner/create.php',
        'views/planner/index.php',
        'views/tasks/calendar.php',
        
        // Migration and Debug Files
        'database/complete_unified_migration.sql',
        'database/fixed_unified_migration.sql',
        'database/unified_planner_final.sql',
        'database/unified_workflow_migration.sql',
        'apply_unified_migration.php',
        'migrate.php',
        'run_migration.php',
        'test_workflow.php',
        
        // Debug Files
        'debug_endpoints.php',
        'debug_followup_assignment.php', 
        'debug_followups.php',
        'debug_specific_followup.php',
        'fix_followup_assignment.php',
        'fix_followup_constraint.php',
        'fix_followup_system.php',
        'link_followups_to_tasks.php',
        'test_followup_creation.php',
        'test_notifications_api.php',
        'test_notifications.php',
        
        // Documentation Files (keep only essential ones)
        'ERROR_FIXES_APPLIED.md',
        'FOLLOWUP_SYSTEM_SUMMARY.md',
        'NOTIFICATION_FIXES_APPLIED.md',
        'RESUME_UNIFIED_WORKFLOW.md',
        'ROLE_BASED_IMPLEMENTATION.md',
        'UNIFIED_WORKFLOW_IMPLEMENTATION.md'
    ];

    // Files to keep (active unified system)
    $keepFiles = [
        'app/controllers/UnifiedWorkflowController.php',
        'app/controllers/TasksController.php',
        'app/controllers/FollowupController.php',
        'views/daily_workflow/unified_daily_planner.php',
        'views/evening-update/unified_index.php',
        'views/tasks/unified_calendar.php',
        'views/tasks/create.php',
        'views/followups/index.php',
        'database/minimal_migration.sql'
    ];
    ?>

    <div class="section delete">
        <h2>🗑️ Files to Delete (<?= count($redundantFiles) ?> files)</h2>
        <p>These files are redundant and replaced by the unified workflow system:</p>
        <div class="file-list">
            <?php foreach ($redundantFiles as $file): ?>
                <div>❌ <?= $file ?></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section keep">
        <h2>✅ Files to Keep (Active Unified System)</h2>
        <div class="file-list">
            <?php foreach ($keepFiles as $file): ?>
                <div>✅ <?= $file ?></div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!isset($_POST['confirm_delete'])): ?>
        <form method="POST">
            <button type="submit" name="confirm_delete" class="btn">🗑️ Delete Redundant Files</button>
            <button type="button" class="btn btn-safe" onclick="window.location.href='/ergon/dashboard'">Cancel</button>
        </form>
    <?php else: ?>
        <div class="section">
            <h2>🧹 Cleanup Results</h2>
            <?php
            $deleted = 0;
            $errors = 0;
            
            foreach ($redundantFiles as $file) {
                $fullPath = __DIR__ . '/' . $file;
                if (file_exists($fullPath)) {
                    if (unlink($fullPath)) {
                        echo "<div style='color: green'>✅ Deleted: $file</div>";
                        $deleted++;
                    } else {
                        echo "<div style='color: red'>❌ Failed to delete: $file</div>";
                        $errors++;
                    }
                } else {
                    echo "<div style='color: gray'>⚪ Not found: $file</div>";
                }
            }
            
            // Remove empty directories
            $emptyDirs = ['views/daily_planner', 'views/planner'];
            foreach ($emptyDirs as $dir) {
                $fullPath = __DIR__ . '/' . $dir;
                if (is_dir($fullPath) && count(scandir($fullPath)) == 2) {
                    if (rmdir($fullPath)) {
                        echo "<div style='color: green'>✅ Removed empty directory: $dir</div>";
                    }
                }
            }
            
            echo "<br><strong>Summary: $deleted deleted, $errors errors</strong>";
            ?>
            
            <br><br>
            <button class="btn btn-safe" onclick="window.location.href='/ergon/tasks/create'">Test Unified Workflow</button>
            <button class="btn btn-safe" onclick="window.location.href='/ergon/dashboard'">Back to Dashboard</button>
        </div>
    <?php endif; ?>

    <div class="section">
        <h2>📋 Audit Summary</h2>
        <p><strong>Unified Workflow System Status:</strong></p>
        <ul>
            <li>✅ Single entry point: <code>/tasks/create</code></li>
            <li>✅ Unified daily planner: <code>/workflow/daily-planner</code></li>
            <li>✅ Unified evening update: <code>/workflow/evening-update</code></li>
            <li>✅ Unified follow-ups: <code>/workflow/followups</code></li>
            <li>✅ Unified calendar: <code>/workflow/calendar</code></li>
        </ul>
        
        <p><strong>Legacy Systems Replaced:</strong></p>
        <ul>
            <li>❌ Multiple task creation forms → Single unified form</li>
            <li>❌ Separate daily planner system → Integrated planner</li>
            <li>❌ Standalone evening updates → Unified updates</li>
            <li>❌ Multiple calendar views → Single calendar</li>
            <li>❌ Separate follow-up creation → Automatic detection</li>
        </ul>
    </div>
</body>
</html>