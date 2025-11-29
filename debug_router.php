<?php
// Debug router to see what's happening
echo "Router Debug Information:\n\n";

echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . "\n";

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "Parsed Path: " . $path . "\n";

// Check if it's localhost
$isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
echo "Is Localhost: " . ($isLocalhost ? 'Yes' : 'No') . "\n";

// Check base path logic
$basePath = '/ergon';
echo "Base Path: " . $basePath . "\n";

if (strpos($path, $basePath) === 0) {
    $cleanPath = substr($path, strlen($basePath));
    echo "Clean Path (after removing base): " . $cleanPath . "\n";
} else {
    echo "Path doesn't start with base path\n";
    $cleanPath = $path;
}

if (empty($cleanPath) || $cleanPath[0] !== '/') {
    $cleanPath = '/' . $cleanPath;
}

echo "Final Clean Path: " . $cleanPath . "\n";

// Check if finance route exists
require_once __DIR__ . '/app/core/Router.php';
$router = new Router();
require_once __DIR__ . '/app/config/routes.php';

echo "\nTesting finance route access...\n";
echo "Expected route: /finance\n";
echo "Actual path: " . $cleanPath . "\n";
echo "Match: " . ($cleanPath === '/finance' ? 'YES' : 'NO') . "\n";
?>