<?php
// Simple route testing script
require_once __DIR__ . '/app/core/Router.php';

$router = new Router();

// Load routes
require_once __DIR__ . '/app/config/routes.php';

echo "<h2>Testing Routes</h2>";

// Test the problematic routes
$testRoutes = [
    '/daily-planner/project-overview',
    '/daily-planner/delayed-tasks-overview'
];

foreach ($testRoutes as $route) {
    echo "<p>Testing route: <strong>$route</strong></p>";
    
    // Check if route exists in router
    $reflection = new ReflectionClass($router);
    $routesProperty = $reflection->getProperty('routes');
    $routesProperty->setAccessible(true);
    $routes = $routesProperty->getValue($router);
    
    if (isset($routes['GET'][$route])) {
        $handler = $routes['GET'][$route];
        echo "<div style='color: green; margin-left: 20px;'>✓ Route found: {$handler['controller']}::{$handler['method']}</div>";
        
        // Check if controller file exists
        $controllerFile = __DIR__ . "/app/controllers/{$handler['controller']}.php";
        if (file_exists($controllerFile)) {
            echo "<div style='color: green; margin-left: 40px;'>✓ Controller file exists</div>";
            
            // Check if method exists
            require_once $controllerFile;
            if (class_exists($handler['controller']) && method_exists($handler['controller'], $handler['method'])) {
                echo "<div style='color: green; margin-left: 40px;'>✓ Method exists</div>";
            } else {
                echo "<div style='color: red; margin-left: 40px;'>✗ Method does not exist</div>";
            }
        } else {
            echo "<div style='color: red; margin-left: 40px;'>✗ Controller file does not exist</div>";
        }
    } else {
        echo "<div style='color: red; margin-left: 20px;'>✗ Route not found</div>";
    }
    echo "<br>";
}

echo "<h3>All GET Routes:</h3>";
echo "<pre>";
print_r($routes['GET'] ?? []);
echo "</pre>";
?>