<?php
// Check routes configuration
echo "<h2>Routes Configuration</h2>";

$routesFile = __DIR__ . '/../app/config/routes.php';
if (file_exists($routesFile)) {
    echo "<h3>app/config/routes.php:</h3><pre>";
    echo htmlspecialchars(file_get_contents($routesFile));
    echo "</pre>";
} else {
    echo "<p>routes.php not found</p>";
}

// Also check Router.php to see how it maps controllers
$routerFile = __DIR__ . '/../app/core/Router.php';
if (file_exists($routerFile)) {
    echo "<h3>app/core/Router.php (first 100 lines):</h3><pre>";
    $lines = file($routerFile);
    for ($i = 0; $i < min(100, count($lines)); $i++) {
        echo sprintf("%02d: %s", $i+1, htmlspecialchars($lines[$i]));
    }
    echo "</pre>";
}
?>