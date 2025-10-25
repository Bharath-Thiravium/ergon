<?php
// Syntax check for main files
$files = [
    'index.php',
    'app/controllers/AuthController.php',
    'app/models/User.php',
    'app/helpers/Security.php',
    'app/core/Router.php',
    'app/core/Controller.php',
    'config/database.php',
    'config/constants.php'
];

echo "<!DOCTYPE html><html><head><title>Syntax Check</title></head><body>";
echo "<h1>PHP Syntax Check</h1>";

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        $output = [];
        $return = 0;
        exec("php -l \"$fullPath\" 2>&1", $output, $return);
        
        if ($return === 0) {
            echo "<p>✅ $file: OK</p>";
        } else {
            echo "<p>❌ $file: " . htmlspecialchars(implode(' ', $output)) . "</p>";
        }
    } else {
        echo "<p>⚠️ $file: File not found</p>";
    }
}

echo "</body></html>";
?>