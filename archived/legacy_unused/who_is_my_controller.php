<?php
echo "<h1>🔍 Controller Identity Check</h1>";

// Check which controller file is being used
$controllerPath = __DIR__ . '/app/controllers/AttendanceController.php';

echo "<h2>📁 File Path Check</h2>";
echo "Expected path: <code>$controllerPath</code><br>";
echo "File exists: " . (file_exists($controllerPath) ? "✅ YES" : "❌ NO") . "<br>";

if (file_exists($controllerPath)) {
    echo "File size: " . filesize($controllerPath) . " bytes<br>";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($controllerPath)) . "<br>";
    
    echo "<h2>📄 Current Controller Content (First 50 lines)</h2>";
    $lines = file($controllerPath);
    echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:400px;'>";
    for ($i = 0; $i < min(50, count($lines)); $i++) {
        echo sprintf("%02d: %s", $i+1, htmlspecialchars($lines[$i]));
    }
    echo "</pre>";
    
    // Check for our specific changes
    $content = file_get_contents($controllerPath);
    
    echo "<h2>🔍 Key Method Checks</h2>";
    
    // Check if it has our IST storage method
    if (strpos($content, 'TimezoneHelper::nowIst()') !== false) {
        echo "✅ Contains IST storage method<br>";
    } else {
        echo "❌ Missing IST storage method<br>";
    }
    
    // Check if it has old UTC method
    if (strpos($content, 'TimezoneHelper::nowUtc()') !== false) {
        echo "⚠️ Still contains UTC storage method<br>";
    } else {
        echo "✅ No UTC storage method found<br>";
    }
    
    // Check for conversion loops
    if (strpos($content, 'Convert times to IST') !== false) {
        echo "⚠️ Still has conversion loops<br>";
    } else {
        echo "✅ No conversion loops found<br>";
    }
    
    // Check class definition
    if (strpos($content, 'class AttendanceController') !== false) {
        echo "✅ Valid AttendanceController class<br>";
    } else {
        echo "❌ No AttendanceController class found<br>";
    }
}

// Check routing
echo "<h2>🛣️ Routing Check</h2>";
try {
    // Simulate what happens when /attendance is accessed
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [];
    
    // Check if we can instantiate the controller
    require_once $controllerPath;
    $controller = new AttendanceController();
    echo "✅ Controller can be instantiated<br>";
    
    // Check methods
    $methods = get_class_methods($controller);
    echo "Available methods: " . implode(', ', $methods) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Controller error: " . $e->getMessage() . "<br>";
}

// Check if there are multiple controller files
echo "<h2>🔍 Multiple Controller Check</h2>";
$possiblePaths = [
    __DIR__ . '/app/controllers/AttendanceController.php',
    __DIR__ . '/controllers/AttendanceController.php',
    __DIR__ . '/app/AttendanceController.php',
    __DIR__ . '/AttendanceController.php'
];

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        echo "Found: <code>$path</code> (modified: " . date('Y-m-d H:i:s', filemtime($path)) . ")<br>";
    }
}

// Check .htaccess routing
echo "<h2>🔧 .htaccess Check</h2>";
$htaccessPath = __DIR__ . '/.htaccess';
if (file_exists($htaccessPath)) {
    echo "✅ .htaccess exists<br>";
    $htaccess = file_get_contents($htaccessPath);
    if (strpos($htaccess, 'attendance') !== false) {
        echo "✅ Contains attendance routing<br>";
    }
} else {
    echo "❌ No .htaccess found<br>";
}

echo "<h2>🎯 DIAGNOSIS</h2>";
echo "If you see this page but attendance still shows UTC times, then:<br>";
echo "1. ✅ The controller file exists and is readable<br>";
echo "2. ❌ But Hostinger is using a DIFFERENT file or has caching issues<br>";
echo "3. 🔧 Solution: Clear all caches or check for duplicate controllers<br>";
?>