<?php
// Fix Hostinger path issues
echo "<h1>Hostinger Path Fix</h1>";

// Check current paths
echo "<h2>Current Paths:</h2>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "<br>";

// Check CSS file
$cssPath = __DIR__ . '/public/assets/css/ergon.css';
if (file_exists($cssPath)) {
    echo "✅ CSS file exists<br>";
} else {
    echo "❌ CSS file missing at: $cssPath<br>";
}

// Check if we can access the daily planner
echo "<h2>Daily Planner Test:</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if planner tables exist
    $tables = ['daily_plans', 'departments'];
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM $table");
            echo "✅ Table $table exists<br>";
        } catch (Exception $e) {
            echo "❌ Table $table missing<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Create missing directories
$dirs = ['/storage', '/logs', '/public/uploads'];
foreach ($dirs as $dir) {
    $fullPath = __DIR__ . $dir;
    if (!is_dir($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            echo "✅ Created directory: $dir<br>";
        } else {
            echo "❌ Failed to create: $dir<br>";
        }
    } else {
        echo "✅ Directory exists: $dir<br>";
    }
}
?>