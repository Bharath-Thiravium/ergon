<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

$db = Database::connect();

echo "<h2>Owner Attendance Buttons Debug</h2>";

// 1. Check session
echo "<h3>1. Session Check</h3>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "User Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";

// 2. Check database user role
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>2. Database User</h3>";
    echo "Name: " . ($user['name'] ?? 'NOT FOUND') . "<br>";
    echo "Role: " . ($user['role'] ?? 'NOT FOUND') . "<br>";
}

// 3. Check if owner_index.php exists
echo "<h3>3. View Files</h3>";
$ownerView = __DIR__ . '/views/attendance/owner_index.php';
echo "owner_index.php exists: " . (file_exists($ownerView) ? 'YES' : 'NO') . "<br>";
echo "File size: " . (file_exists($ownerView) ? filesize($ownerView) : 'N/A') . " bytes<br>";

// 4. Check view content for buttons
if (file_exists($ownerView)) {
    $content = file_get_contents($ownerView);
    echo "<h3>4. Button Count in owner_index.php</h3>";
    $buttonCount = substr_count($content, 'onclick=');
    echo "Buttons found: " . $buttonCount . "<br>";
    
    // Check for specific functions
    echo "<h3>5. Function Calls in owner_index.php</h3>";
    echo "clockInUser: " . (strpos($content, 'clockInUser') ? 'YES' : 'NO') . "<br>";
    echo "clockOutUser: " . (strpos($content, 'clockOutUser') ? 'YES' : 'NO') . "<br>";
    echo "markManualAttendance: " . (strpos($content, 'markManualAttendance') ? 'YES' : 'NO') . "<br>";
    echo "generateUserReport: " . (strpos($content, 'generateUserReport') ? 'YES' : 'NO') . "<br>";
}

// 5. Check controller routing
echo "<h3>6. Controller Routing</h3>";
$controllerFile = __DIR__ . '/app/controllers/AttendanceController.php';
$controllerContent = file_get_contents($controllerFile);
$ownerCheck = strpos($controllerContent, "attendance/owner_index");
echo "owner_index routing in controller: " . ($ownerCheck ? 'YES (line ~' . substr_count($controllerContent, "\n", 0, $ownerCheck) . ')' : 'NO') . "<br>";

// 6. Test API endpoints
echo "<h3>7. API Endpoint Tests</h3>";
echo "Manual attendance endpoint: " . (file_exists(__DIR__ . '/attendance/manual') ? 'YES' : 'CHECK URL') . "<br>";
echo "Simple attendance endpoint: " . (file_exists(__DIR__ . '/api/simple_attendance.php') ? 'YES' : 'NO') . "<br>";

// 7. Check for CSS that might hide buttons
echo "<h3>8. CSS Check</h3>";
$cssFiles = glob(__DIR__ . '/assets/css/*.css');
echo "CSS files found: " . count($cssFiles) . "<br>";
foreach ($cssFiles as $css) {
    $cssContent = file_get_contents($css);
    if (strpos($cssContent, 'display: none') || strpos($cssContent, 'visibility: hidden')) {
        echo "⚠️ " . basename($css) . " contains hide rules<br>";
    }
}

// 8. Check browser console errors
echo "<h3>9. Recommended Actions</h3>";
echo "1. Open browser DevTools (F12)<br>";
echo "2. Go to Console tab<br>";
echo "3. Check for JavaScript errors<br>";
echo "4. Check Network tab for failed API calls<br>";
echo "5. Look for 403 Forbidden responses<br>";

?>
