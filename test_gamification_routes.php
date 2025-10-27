<?php
// Test script to verify gamification routes
echo "<h2>Testing Gamification Routes</h2>";

// Test 1: Check if routes file has the individual route
$routesFile = file_get_contents(__DIR__ . '/app/config/routes.php');
if (strpos($routesFile, "gamification/individual") !== false) {
    echo "✅ Individual gamification route found in routes.php<br>";
} else {
    echo "❌ Individual gamification route NOT found in routes.php<br>";
}

// Test 2: Check if GamificationController has individual method
$controllerFile = file_get_contents(__DIR__ . '/app/controllers/GamificationController.php');
if (strpos($controllerFile, "public function individual()") !== false) {
    echo "✅ Individual method found in GamificationController<br>";
} else {
    echo "❌ Individual method NOT found in GamificationController<br>";
}

// Test 3: Check if individual view exists
if (file_exists(__DIR__ . '/views/gamification/individual.php')) {
    echo "✅ Individual gamification view file exists<br>";
} else {
    echo "❌ Individual gamification view file does NOT exist<br>";
}

// Test 4: Check if ApiController has activityLog method
$apiControllerFile = file_get_contents(__DIR__ . '/app/controllers/ApiController.php');
if (strpos($apiControllerFile, "public function activityLog()") !== false) {
    echo "✅ ActivityLog method found in ApiController<br>";
} else {
    echo "❌ ActivityLog method NOT found in ApiController<br>";
}

// Test 5: Check if User model has getAllUsers method
$userModelFile = file_get_contents(__DIR__ . '/app/models/User.php');
if (strpos($userModelFile, "public function getAllUsers()") !== false) {
    echo "✅ getAllUsers method found in User model<br>";
} else {
    echo "❌ getAllUsers method NOT found in User model<br>";
}

echo "<br><h3>Routes to test:</h3>";
echo "1. <a href='/ergon/gamification/individual' target='_blank'>/ergon/gamification/individual</a><br>";
echo "2. POST to /ergon/api/activity-log (requires authentication)<br>";

echo "<br><h3>Navigation:</h3>";
echo "The 'My Performance' link should be visible in the Overview dropdown for regular users.<br>";
?>