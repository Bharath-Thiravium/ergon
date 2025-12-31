<?php
/** Clean diagnose_deploy.php - single copy with error display enabled */
header('Content-Type: text/plain; charset=utf-8');
@ini_set('display_errors', 1);
@ini_set('display_startup_errors', 1);
@error_reporting(E_ALL);

$out = [];
$out[] = "=== Ergon Hostinger Browser Diagnostic ===";
$out[] = "Timestamp: " . date('c');
$out[] = "PHP version: " . PHP_VERSION;
 
