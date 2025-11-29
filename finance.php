<?php
// Direct finance access for testing
require_once __DIR__ . '/app/controllers/FinanceController.php';

// Initialize controller and handle request
$controller = new FinanceController();
$controller->handleRequest();
?>