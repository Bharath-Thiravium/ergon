<?php
// Direct logout handler
require_once __DIR__ . '/../app/controllers/AuthController.php';

$controller = new AuthController();
$controller->logout();
?>