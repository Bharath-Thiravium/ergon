<?php
// Simple API test to check JSON response
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

echo json_encode([
    'success' => true,
    'message' => 'API test successful',
    'timestamp' => date('Y-m-d H:i:s')
]);
exit;
?>
