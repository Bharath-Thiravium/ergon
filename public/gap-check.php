<?php
header('Content-Type: text/plain');
echo "=== ERGON GAP ANALYSIS ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "Host: " . $_SERVER['HTTP_HOST'] . "\n\n";

// 1. CSS File Check
$cssFile = __DIR__ . '/assets/css/ergon.css';
if (file_exists($cssFile)) {
    $css = file_get_contents($cssFile);
    echo "CSS FILE STATUS:\n";
    echo "- Size: " . filesize($cssFile) . " bytes\n";
    echo "- Modified: " . date('Y-m-d H:i:s', filemtime($cssFile)) . "\n";
    echo "- Hide Account CSS: " . (strpos($css, 'Hide Account section') !== false ? 'PRESENT' : 'MISSING') . "\n";
    echo "- Sidebar Controls: " . (strpos($css, 'sidebar__controls') !== false ? 'PRESENT' : 'MISSING') . "\n";
    echo "- Table Styles: " . (strpos($css, 'table-responsive') !== false ? 'PRESENT' : 'MISSING') . "\n\n";
} else {
    echo "CSS FILE: MISSING\n\n";
}

// 2. Dashboard Layout Check
$layoutFile = __DIR__ . '/../app/views/layouts/dashboard.php';
if (file_exists($layoutFile)) {
    $layout = file_get_contents($layoutFile);
    echo "DASHBOARD LAYOUT STATUS:\n";
    echo "- Size: " . filesize($layoutFile) . " bytes\n";
    echo "- Modified: " . date('Y-m-d H:i:s', filemtime($layoutFile)) . "\n";
    echo "- Account Section: " . (strpos($layout, 'Account</div>') !== false ? 'STILL PRESENT' : 'REMOVED') . "\n";
    echo "- My Profile Link: " . (strpos($layout, 'My Profile') !== false ? 'STILL PRESENT' : 'REMOVED') . "\n";
    
    // Extract CSS version
    if (preg_match('/ergon\.css\?v=([^"]+)/', $layout, $matches)) {
        echo "- CSS Version: " . $matches[1] . "\n";
    }
    echo "\n";
} else {
    echo "DASHBOARD LAYOUT: MISSING\n\n";
}

// 3. Key View Files
$viewFiles = [
    'owner/dashboard.php' => '../app/views/owner/dashboard.php',
    'attendance/index.php' => '../app/views/attendance/index.php'
];

echo "VIEW FILES STATUS:\n";
foreach ($viewFiles as $name => $path) {
    $fullPath = __DIR__ . '/' . $path;
    if (file_exists($fullPath)) {
        echo "- $name: EXISTS (" . date('Y-m-d H:i:s', filemtime($fullPath)) . ")\n";
    } else {
        echo "- $name: MISSING\n";
    }
}

// 4. Session Check
session_start();
echo "\nSESSION STATUS:\n";
echo "- User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "- Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";

// 5. Critical Differences Summary
echo "\n=== CRITICAL GAPS TO FIX ===\n";

if (file_exists($layoutFile)) {
    $layout = file_get_contents($layoutFile);
    if (strpos($layout, 'My Profile') !== false) {
        echo "1. UPLOAD dashboard.php - Account section still present\n";
    }
}

if (file_exists($cssFile)) {
    $css = file_get_contents($cssFile);
    if (strpos($css, 'Hide Account section') === false) {
        echo "2. UPLOAD ergon.css - Missing account hiding CSS\n";
    }
} else {
    echo "2. UPLOAD ergon.css - File missing\n";
}

echo "\n=== QUICK FIX ===\n";
echo "1. Upload latest dashboard.php to app/views/layouts/\n";
echo "2. Upload latest ergon.css to public/assets/css/\n";
echo "3. Hard refresh browser (Ctrl+F5)\n";
?>