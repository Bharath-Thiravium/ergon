<?php

// Finance sync routes
require_once __DIR__ . '/../app/controllers/FinanceController.php';

$financeController = new FinanceController();

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

switch ($action) {
    case 'sync':
        echo $financeController->syncData($_REQUEST);
        break;
        
    case 'table-data':
        echo $financeController->getTableData($_REQUEST);
        break;
        
    case 'sync-history':
        echo $financeController->getSyncHistory($_REQUEST);
        break;
        
    case 'dashboard':
    default:
        $financeController->dashboard($_REQUEST);
        break;
}
