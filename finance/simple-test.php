<?php
// Simplified controller test
ini_set('display_errors', 0);
error_reporting(0);

$action = $_GET['action'] ?? 'test';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

switch ($action) {
    case 'company-prefix':
        echo json_encode(['success' => true, 'prefix' => 'TC']);
        break;
    case 'customers':
        echo json_encode(['success' => true, 'customers' => []]);
        break;
    case 'dashboard-stats':
        echo json_encode(['success' => true, 'totalInvoiceAmount' => 0]);
        break;
    default:
        echo json_encode(['success' => true, 'message' => 'Simple test working']);
        break;
}
exit;
?>
