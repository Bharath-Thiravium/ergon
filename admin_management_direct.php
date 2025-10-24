<?php
session_start();

// Direct access to admin management without routing
require_once __DIR__ . '/app/controllers/AdminManagementController.php';

try {
    $controller = new AdminManagementController();
    $controller->index();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "<br><br>";
    echo "Session role: " . ($_SESSION['role'] ?? 'not set');
    echo "<br>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'not set');
}
?>