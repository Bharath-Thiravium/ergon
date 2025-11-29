<?php
// Disable all output and error display
ini_set('display_errors', 0);
ini_set('log_errors', 0);
error_reporting(0);

// Clean any existing output
if (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output buffering
ob_start();

// Direct finance module access
require_once __DIR__ . '/../app/controllers/FinanceController.php';

// Initialize and handle request
$controller = new FinanceController();
$controller->handleRequest();
?>