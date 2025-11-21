<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

$userId = $_SESSION['user_id'] ?? 16;
$yesterday = date('Y-m-d', strtotime('-1 day'));
$today = date('Y-m-d');

echo "<h2>Task Investigation</h2>";

$db = Database::connect();

// Check today's tasks and their rollover sources
echo "<h3>Today's Tasks Analysis</h3>";
$stmt = $db->prepare("
    SELECT id, title, priority, rollover_source_date, scheduled_date, created_at, source_field
    FROM daily_tasks 
    WHERE user_id = ? AND scheduled_date = ?
    ORDER BY id
");
$stmt->execute([$userId, $today]);
$todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($todayTasks as $task) {
    echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
    echo "<strong>Title:</strong> {$task['title']}<br>";
    echo "<strong>Priority:</strong> {$task['priority']}<br>";
    echo "<strong>Rollover Source:</strong> " . ($task['rollover_source_date'] ?? 'None') . "<br>";
    echo "<strong>Created:</strong> {$task['created_at']}<br>";
    echo "<strong>Source Field:</strong> " . ($task['source_field'] ?? 'None') . "<br>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Create Missing Yesterday Tasks</h3>";

// Create the 5 tasks for yesterday based on what you mentioned
$tasksToCreate = [
    [
        'title' => 'Competitor Analysis',
        'description' => 'Analyze top 3 competitors and their strategies',
        'priority' => 'high',
        'planned_duration' => 1440 // 24 hours in minutes
    ],
    [
        'title' => 'Complete Website Content Review',
        'description' => 'Review and update all website content for accuracy and SEO optimization',
        'priority' => 'high',
        'planned_duration' => 2880 // 48 hours in minutes
    ],
    [
        'title' => 'Blog Content Creation',
        'description' => 'Write 5 blog posts for company website',
        'priority' => 'medium',
        'planned_duration' => 2400 // 40 hours in minutes
    ],
    [
        'title' => 'Social Media Campaign Setup',
        'description' => 'Set up social media campaigns for Q1 marketing push',
        'priority' => 'medium',
        'planned_duration' => 1440 // 24 hours in minutes
    ],
    [
        'title' => 'Email Newsletter Design',
        'description' => 'Design monthly email newsletter template',
        'priority' => 'low',
        'planned_duration' => 480 // 8 hours in minutes
    ]
];

foreach ($tasksToCreate as $taskData) {
    // Check if task already exists
    $checkStmt = $db->prepare("
        SELECT COUNT(*) FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date = ? AND title = ?
    ");
    $checkStmt->execute([$userId, $yesterday, $taskData['title']]);
    
    if ($checkStmt->fetchColumn() == 0) {
        $insertStmt = $db->prepare("
            INSERT INTO daily_tasks 
            (user_id, title, description, scheduled_date, priority, status, 
             planned_duration, source_field, created_at)
            VALUES (?, ?, ?, ?, ?, 'not_started', ?, 'manual_restore', ?)
        ");
        
        $result = $insertStmt->execute([
            $userId,
            $taskData['title'],
            $taskData['description'],
            $yesterday,
            $taskData['priority'],
            $taskData['planned_duration'],
            $yesterday . ' 08:00:00'
        ]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Created: {$taskData['title']}</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create: {$taskData['title']}</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Already exists: {$taskData['title']}</p>";
    }
}

// Now create rolled-over versions for today
echo "<h3>Creating Rolled-over Tasks for Today</h3>";
foreach ($tasksToCreate as $taskData) {
    $checkStmt = $db->prepare("
        SELECT COUNT(*) FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date = ? AND title = ? AND rollover_source_date = ?
    ");
    $checkStmt->execute([$userId, $today, $taskData['title'], $yesterday]);
    
    if ($checkStmt->fetchColumn() == 0) {
        $insertStmt = $db->prepare("
            INSERT INTO daily_tasks 
            (user_id, title, description, scheduled_date, priority, status, 
             planned_duration, rollover_source_date, rollover_timestamp, source_field, created_at)
            VALUES (?, ?, ?, ?, ?, 'not_started', ?, ?, NOW(), 'rollover', NOW())
        ");
        
        $result = $insertStmt->execute([
            $userId,
            $taskData['title'],
            $taskData['description'],
            $today,
            $taskData['priority'],
            $taskData['planned_duration'],
            $yesterday
        ]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Rolled over: {$taskData['title']}</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Rollover already exists: {$taskData['title']}</p>";
    }
}

echo "<hr>";
echo "<p><a href='/ergon/workflow/daily-planner/{$yesterday}'>View Yesterday's Tasks</a></p>";
echo "<p><a href='/ergon/workflow/daily-planner/{$today}'>View Today's Tasks</a></p>";
?>