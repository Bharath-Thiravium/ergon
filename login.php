<?php
// Direct login page
require_once __DIR__ . '/app/controllers/AuthController.php';

$controller = new AuthController();
$controller->login();
?>