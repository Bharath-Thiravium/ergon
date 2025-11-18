<?php
// Debug API Test - Check if daily planner API is working
session_start();

// Simulate logged in user for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Use actual user ID from your system
    $_SESSION['username'] = 'test_user';
}

echo "<h2>API Debug Test</h2>";
echo "<p>Testing daily planner API endpoints...</p>";

// Test 1: Check if API file exists
$apiFile = __DIR__ . '/api/daily_planner_workflow.php';
echo "<h3>1. API File Check</h3>";
if (file_exists($apiFile)) {
    echo "✅ API file exists: " . $apiFile . "<br>";
} else {
    echo "❌ API file missing: " . $apiFile . "<br>";
}

// Test 2: Check database connection
echo "<h3>2. Database Connection</h3>";
try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "✅ Database connection successful<br>";
    
    // Check daily_tasks table
    $stmt = $db->query("SHOW TABLES LIKE 'daily_tasks'");
    if ($stmt->rowCount() > 0) {
        echo "✅ daily_tasks table exists<br>";
        
        // Check for test tasks
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM daily_tasks WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "📊 Tasks for user {$_SESSION['user_id']}: {$result['count']}<br>";
    } else {
        echo "❌ daily_tasks table missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Test API endpoint directly
echo "<h3>3. API Endpoint Test</h3>";
echo "<button onclick='testStartTask()'>Test Start Task API</button><br><br>";

// Test 4: Check for any tasks to start
echo "<h3>4. Available Tasks</h3>";
try {
    $stmt = $db->prepare("SELECT id, title, status FROM daily_tasks WHERE user_id = ? AND scheduled_date = CURDATE() LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($tasks) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Action</th></tr>";
        foreach ($tasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td><button onclick='testTaskAction({$task['id']}, \"start\")'>Test Start</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No tasks found for today. <button onclick='createTestTask()'>Create Test Task</button>";
    }
} catch (Exception $e) {
    echo "Error fetching tasks: " . $e->getMessage();
}
?>

<script>
function testStartTask() {
    console.log('Testing API endpoint...');
    
    fetch('./api/daily_planner_workflow.php?action=start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: 1 })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed JSON:', data);
            alert('API Response: ' + JSON.stringify(data, null, 2));
        } catch (e) {
            console.error('JSON parse error:', e);
            alert('API returned non-JSON response: ' + text);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Network error: ' + error.message);
    });
}

function testTaskAction(taskId, action) {
    console.log(`Testing ${action} for task ${taskId}`);
    
    fetch(`./api/daily_planner_workflow.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    })
    .then(response => response.json())
    .then(data => {
        console.log('API Response:', data);
        alert(JSON.stringify(data, null, 2));
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    });
}

function createTestTask() {
    fetch('./api/daily_planner_workflow.php?action=quick-add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'title=Test Task&description=Debug test task&scheduled_date=<?= date('Y-m-d') ?>&duration=60&priority=medium'
    })
    .then(response => response.json())
    .then(data => {
        alert('Create task result: ' + JSON.stringify(data));
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        alert('Error creating task: ' + error.message);
    });
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
button { margin: 5px; padding: 5px 10px; }
</style>