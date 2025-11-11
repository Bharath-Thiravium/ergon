<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== INJECTING DUMMY DATA ===\n";
    
    // Run the dummy data script
    $sql = file_get_contents('inject_dummy_data.sql');
    $db->exec($sql);
    
    echo "✅ Dummy data injected successfully!\n\n";
    
    // Show summary of injected data
    echo "=== DATA SUMMARY ===\n";
    
    $tables = [
        'tasks' => 'Tasks',
        'daily_planner' => 'Daily Planner Entries', 
        'evening_updates' => 'Evening Updates',
        'attendance' => 'Attendance Records',
        'leaves' => 'Leave Requests',
        'expenses' => 'Expense Claims',
        'advances' => 'Advance Requests'
    ];
    
    foreach ($tables as $table => $label) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "📊 $label: $count records\n";
    }
    
    echo "\n=== FOLLOW-UP TASKS ===\n";
    $stmt = $db->query("SELECT title, company_name, contact_person, followup_date FROM tasks WHERE followup_required = 1");
    $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($followups as $followup) {
        echo "📞 {$followup['title']} - {$followup['company_name']} ({$followup['contact_person']}) - {$followup['followup_date']}\n";
    }
    
    echo "\n=== TASK CATEGORIES BY DEPARTMENT ===\n";
    $stmt = $db->query("SELECT department_name, COUNT(*) as count FROM task_categories GROUP BY department_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categories as $cat) {
        echo "🏢 {$cat['department_name']}: {$cat['count']} categories\n";
    }
    
    echo "\n🎉 All modules now have test data!\n";
    echo "You can now test:\n";
    echo "- Task management with follow-ups\n";
    echo "- Daily planner with entries\n";
    echo "- Evening updates\n";
    echo "- Attendance tracking\n";
    echo "- Leave management\n";
    echo "- Expense claims\n";
    echo "- Advance requests\n";
    echo "- Follow-up search suggestions\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>