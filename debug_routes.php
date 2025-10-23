<?php
session_start();

echo "<h1>Route Debug Information</h1>";

echo "<h2>Current Request:</h2>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'Not set') . "<br>";

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
echo "Parsed Path: " . $path . "<br>";

$basePath = '/ergon';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
echo "Path after base removal: " . $path . "<br>";

echo "<h2>Session Info:</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
echo "User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "<br>";

echo "<h2>Test Links:</h2>";
echo "<a href='/ergon/users'>Test Users Link</a><br>";
echo "<a href='/ergon/tasks'>Test Tasks Link</a><br>";
echo "<a href='/ergon/reports'>Test Reports Link</a><br>";
echo "<a href='/ergon/settings'>Test Settings Link</a><br>";
?>