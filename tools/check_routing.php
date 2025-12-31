<?php
// Check which controller is actually being used by the routing system
echo "<h2>Routing Check</h2>";

// Check .htaccess
$htaccess = __DIR__ . '/../.htaccess';
if (file_exists($htaccess)) {
    echo "<h3>.htaccess content:</h3><pre>";
    echo htmlspecialchars(file_get_contents($htaccess));
    echo "</pre>";
}

// Check index.php
$index = __DIR__ . '/../index.php';
if (file_exists($index)) {
    echo "<h3>index.php content:</h3><pre>";
    echo htmlspecialchars(file_get_contents($index));
    echo "</pre>";
}

// Check for router files
$possibleRouters = [
    __DIR__ . '/../router.php',
    __DIR__ . '/../routes.php',
    __DIR__ . '/../app/routes.php',
    __DIR__ . '/../config/routes.php'
];

foreach ($possibleRouters as $router) {
    if (file_exists($router)) {
        echo "<h3>Router file: " . basename($router) . "</h3><pre>";
        echo htmlspecialchars(file_get_contents($router));
        echo "</pre>";
    }
}
?>
