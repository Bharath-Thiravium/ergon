<?php
// Debug routing for project management
echo "Current REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "Parsed path: " . $path . "\n";

$basePath = '/ergon';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
echo "Path after base removal: " . $path . "\n";

// Test project management routes
$routes = [
    '/project-management' => 'ProjectManagementController::index',
    '/project-management/create' => 'ProjectManagementController::create',
    '/project-management/update' => 'ProjectManagementController::update',
    '/project-management/delete' => 'ProjectManagementController::delete'
];

echo "\nProject Management Routes:\n";
foreach ($routes as $route => $handler) {
    echo "$route => $handler\n";
}
?>