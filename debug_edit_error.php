<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';

try {
    require_once __DIR__ . '/app/controllers/UsersController.php';
    
    echo "Testing UsersController edit method...\n";
    
    $controller = new UsersController();
    $controller->edit(13);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>