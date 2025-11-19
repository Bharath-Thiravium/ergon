<?php
// Test page to verify postpone functionality and SLA dashboard updates
session_start();

// Auto-login for testing
if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'user';
}

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

$planner = new DailyPlanner();
$userId = $_SESSION['user_id'];
$date = $_GET['date'] ?? date('Y-m-d');

echo "<h1>Postpone & SLA Dashboard Test</h1>";
echo "<p>Testing date: $date | User ID: $userId</p>";

// Get current stats
$stats = $planner->getDailyStats($userId, $date);
echo "<h2>Current Daily Stats</h2>";
echo "<pre>" . json_encode($stats, JSON_PRETTY_PRINT) . "</pre>";

// Get tasks for today
$tasks = $planner->getTasksForDate($userId, $date);
echo "<h2>Tasks for Today (" . count($tasks) . " tasks)</h2>";
foreach ($tasks as $task) {
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
    echo "<strong>ID: {$task['id']}</strong> - {$task['title']}<br>";
    echo "Status: {$task['status']} | Progress: {$task['completed_percentage']}%<br>";
    if ($task['status'] !== 'completed' && $task['status'] !== 'postponed') {
        echo "<button onclick=\"postponeTaskTest({$task['id']})\">Postpone This Task</button>";
    }
    echo "</div>";
}

// Debug API endpoint
echo "<h2>Debug Stats API</h2>";
echo "<button onclick=\"loadDebugStats()\">Load Debug Stats</button>";
echo "<div id='debugStats'></div>";

?>

<script>
function postponeTaskTest(taskId) {
    const newDate = prompt('Enter new date (YYYY-MM-DD):');
    if (!newDate) return;
    
    fetch('/ergon/api/daily_planner_workflow.php?action=postpone', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            task_id: parseInt(taskId), 
            new_date: newDate,
            reason: 'Test postpone from test page'
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Postpone result:', data);
        if (data.success) {
            alert('Task postponed successfully! Updated stats: ' + JSON.stringify(data.updated_stats));
            location.reload();
        } else {
            alert('Failed to postpone: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error postponing task');
    });
}

function loadDebugStats() {
    fetch('/ergon/api/daily_planner_workflow.php?action=debug-stats&date=<?= $date ?>')
    .then(response => response.json())
    .then(data => {
        document.getElementById('debugStats').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('debugStats').innerHTML = 'Error loading debug stats';
    });
}

// Auto-load debug stats
loadDebugStats();
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
button { padding: 8px 16px; margin: 5px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
button:hover { background: #005a87; }
pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>