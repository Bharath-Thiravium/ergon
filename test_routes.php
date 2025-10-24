<?php
/**
 * Test Daily Planner Routes
 */

echo "<h2>Testing Daily Planner Routes</h2>";

// Test controller file exists
$controllerFile = __DIR__ . '/app/controllers/DailyTaskPlannerController.php';
if (file_exists($controllerFile)) {
    echo "<p>✅ DailyTaskPlannerController.php exists</p>";
} else {
    echo "<p>❌ DailyTaskPlannerController.php missing</p>";
}

// Test class exists
require_once $controllerFile;
if (class_exists('DailyTaskPlannerController')) {
    echo "<p>✅ DailyTaskPlannerController class exists</p>";
    
    // Test methods exist
    $methods = ['index', 'dashboard', 'submitTask', 'getProjectTasks'];
    foreach ($methods as $method) {
        if (method_exists('DailyTaskPlannerController', $method)) {
            echo "<p>✅ Method $method exists</p>";
        } else {
            echo "<p>❌ Method $method missing</p>";
        }
    }
} else {
    echo "<p>❌ DailyTaskPlannerController class missing</p>";
}

// Test routes
echo "<h3>Route Tests:</h3>";
echo "<p><a href='/ergon/daily-planner'>Test /daily-planner</a></p>";
echo "<p><a href='/ergon/daily-planner/dashboard'>Test /daily-planner/dashboard</a></p>";

// Show current URL info
echo "<h3>Current Request Info:</h3>";
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "</p>";
?>