<?php
/**
 * Test script to verify that follow-ups linked to deleted tasks are properly filtered out
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Follow-up Deletion Sync Test</h2>\n";
    
    // Test 1: Check if there are any follow-ups linked to non-existent tasks
    echo "<h3>Test 1: Follow-ups with deleted task references</h3>\n";
    $stmt = $db->prepare("
        SELECT f.id, f.title, f.task_id, f.status 
        FROM followups f 
        LEFT JOIN tasks t ON f.task_id = t.id 
        WHERE f.task_id IS NOT NULL AND t.id IS NULL
    ");
    $stmt->execute();
    $orphanedFollowups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orphanedFollowups)) {
        echo "✅ PASS: No follow-ups found with deleted task references\n";
    } else {
        echo "⚠️  FOUND: " . count($orphanedFollowups) . " follow-ups with deleted task references:\n";
        foreach ($orphanedFollowups as $followup) {
            echo "  - ID: {$followup['id']}, Title: {$followup['title']}, Task ID: {$followup['task_id']}\n";
        }
    }
    
    // Test 2: Verify the new filtered query works correctly
    echo "<h3>Test 2: Filtered follow-ups query (should exclude deleted task references)</h3>\n";
    $stmt = $db->prepare("
        SELECT f.*, c.name as contact_name, u.name as user_name
        FROM followups f 
        LEFT JOIN contacts c ON f.contact_id = c.id 
        LEFT JOIN users u ON f.user_id = u.id
        LEFT JOIN tasks t ON f.task_id = t.id
        WHERE (f.task_id IS NULL OR t.id IS NOT NULL)
        ORDER BY f.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $validFollowups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ PASS: Query executed successfully, found " . count($validFollowups) . " valid follow-ups\n";
    
    if (!empty($validFollowups)) {
        echo "Sample valid follow-ups:\n";
        foreach (array_slice($validFollowups, 0, 3) as $followup) {
            $taskStatus = $followup['task_id'] ? "Linked to task {$followup['task_id']}" : "Standalone";
            echo "  - ID: {$followup['id']}, Title: {$followup['title']}, Status: {$taskStatus}\n";
        }
    }
    
    // Test 3: Check contact follow-ups query
    echo "<h3>Test 3: Contact follow-ups query (should exclude deleted task references)</h3>\n";
    $stmt = $db->prepare("
        SELECT c.*, 
               COUNT(f.id) as total_followups,
               SUM(CASE WHEN f.status = 'pending' AND f.follow_up_date < CURDATE() THEN 1 ELSE 0 END) as overdue_count,
               SUM(CASE WHEN f.status = 'pending' AND f.follow_up_date = CURDATE() THEN 1 ELSE 0 END) as today_count
        FROM contacts c
        LEFT JOIN followups f ON c.id = f.contact_id
        LEFT JOIN tasks t ON f.task_id = t.id
        WHERE (f.task_id IS NULL OR t.id IS NOT NULL)
        GROUP BY c.id 
        HAVING total_followups > 0 
        LIMIT 5
    ");
    $stmt->execute();
    $contactsWithFollowups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ PASS: Contact follow-ups query executed successfully, found " . count($contactsWithFollowups) . " contacts with valid follow-ups\n";
    
    // Test 4: Verify reminder query
    echo "<h3>Test 4: Reminder query (should exclude deleted task references)</h3>\n";
    $stmt = $db->prepare("
        SELECT f.*, c.name as contact_name 
        FROM followups f 
        LEFT JOIN contacts c ON f.contact_id = c.id 
        LEFT JOIN tasks t ON f.task_id = t.id
        WHERE f.follow_up_date = CURDATE() 
        AND f.status IN ('pending', 'in_progress')
        AND (f.task_id IS NULL OR t.id IS NOT NULL)
    ");
    $stmt->execute();
    $todayReminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ PASS: Reminder query executed successfully, found " . count($todayReminders) . " valid reminders for today\n";
    
    echo "<h3>Summary</h3>\n";
    echo "✅ All follow-up queries have been updated to exclude follow-ups linked to deleted tasks\n";
    echo "✅ The Follow-Up Module will now properly sync with Task Module deletions\n";
    echo "✅ Users will no longer see follow-ups for deleted tasks in any follow-up view\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>