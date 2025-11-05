<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_error.log');

echo "<h1>Debug Script for Daily Planner Endpoints</h1>";

// Test 1: Check if controller file exists
echo "<h2>1. Controller File Check</h2>";
$controllerPath = __DIR__ . '/app/controllers/DailyTaskPlannerController.php';
if (file_exists($controllerPath)) {
    echo "✅ Controller file exists: $controllerPath<br>";
} else {
    echo "❌ Controller file missing: $controllerPath<br>";
}

// Test 2: Check if controller can be included
echo "<h2>2. Controller Include Test</h2>";
try {
    include_once $controllerPath;
    echo "✅ Controller file included successfully<br>";
} catch (Exception $e) {
    echo "❌ Controller include error: " . $e->getMessage() . "<br>";
}

// Test 3: Check if class exists
echo "<h2>3. Class Existence Check</h2>";
if (class_exists('DailyTaskPlannerController')) {
    echo "✅ DailyTaskPlannerController class exists<br>";
} else {
    echo "❌ DailyTaskPlannerController class not found<br>";
}

// Test 4: Check if methods exist
echo "<h2>4. Method Existence Check</h2>";
if (class_exists('DailyTaskPlannerController')) {
    $methods = get_class_methods('DailyTaskPlannerController');
    echo "Available methods: " . implode(', ', $methods) . "<br>";
    
    if (method_exists('DailyTaskPlannerController', 'projectOverview')) {
        echo "✅ projectOverview method exists<br>";
    } else {
        echo "❌ projectOverview method missing<br>";
    }
    
    if (method_exists('DailyTaskPlannerController', 'delayedTasksOverview')) {
        echo "✅ delayedTasksOverview method exists<br>";
    } else {
        echo "❌ delayedTasksOverview method missing<br>";
    }
}

// Test 5: Check routes file
echo "<h2>5. Routes Configuration Check</h2>";
$routesPath = __DIR__ . '/app/config/routes.php';
if (file_exists($routesPath)) {
    echo "✅ Routes file exists<br>";
    $routesContent = file_get_contents($routesPath);
    if (strpos($routesContent, 'project-overview') !== false) {
        echo "✅ project-overview route found in routes.php<br>";
    } else {
        echo "❌ project-overview route not found in routes.php<br>";
    }
    if (strpos($routesContent, 'delayed-tasks-overview') !== false) {
        echo "✅ delayed-tasks-overview route found in routes.php<br>";
    } else {
        echo "❌ delayed-tasks-overview route not found in routes.php<br>";
    }
} else {
    echo "❌ Routes file missing<br>";
}

// Test 6: Try to instantiate controller
echo "<h2>6. Controller Instantiation Test</h2>";
try {
    if (class_exists('DailyTaskPlannerController')) {
        $controller = new DailyTaskPlannerController();
        echo "✅ Controller instantiated successfully<br>";
        
        // Test method call
        echo "<h3>6a. Method Call Test</h3>";
        ob_start();
        try {
            $controller->projectOverview();
            $output = ob_get_clean();
            echo "✅ projectOverview method called successfully<br>";
            echo "Output: " . htmlspecialchars($output) . "<br>";
        } catch (Exception $e) {
            ob_end_clean();
            echo "❌ projectOverview method error: " . $e->getMessage() . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Controller instantiation error: " . $e->getMessage() . "<br>";
}

// Test 7: Check for syntax errors
echo "<h2>7. Syntax Check</h2>";
$syntaxCheck = shell_exec("php -l $controllerPath 2>&1");
if (strpos($syntaxCheck, 'No syntax errors') !== false) {
    echo "✅ No syntax errors in controller<br>";
} else {
    echo "❌ Syntax errors found:<br><pre>" . htmlspecialchars($syntaxCheck) . "</pre>";
}

// Test 8: Check error log
echo "<h2>8. Error Log Check</h2>";
$errorLogPath = __DIR__ . '/debug_error.log';
if (file_exists($errorLogPath)) {
    $errors = file_get_contents($errorLogPath);
    if (!empty($errors)) {
        echo "❌ Errors found in log:<br><pre>" . htmlspecialchars($errors) . "</pre>";
    } else {
        echo "✅ No errors in debug log<br>";
    }
} else {
    echo "ℹ️ No debug error log created yet<br>";
}

echo "<h2>Debug Complete</h2>";
echo "Access this script at: https://athenas.co.in/ergon/debug_endpoints.php";
?>