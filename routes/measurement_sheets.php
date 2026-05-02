<?php
// Add these routes to your existing routes.php file

// Measurement Sheets Routes
$router->get('/measurement-sheets', 'MeasurementSheetController', 'index');
$router->get('/measurement-sheets/create', 'MeasurementSheetController', 'create');
$router->post('/measurement-sheets/create', 'MeasurementSheetController', 'create');
$router->get('/measurement-sheets/{id}/view', 'MeasurementSheetController', 'view');
$router->get('/measurement-sheets/{id}/edit', 'MeasurementSheetController', 'edit');
$router->post('/measurement-sheets/{id}/edit', 'MeasurementSheetController', 'edit');
$router->get('/measurement-sheets/{id}/pdf', 'MeasurementSheetController', 'generatePDF');

// AJAX Routes for dynamic functionality
$router->post('/api/measurement-sheets/add-row', 'MeasurementSheetController', 'addRow');
$router->post('/api/measurement-sheets/validate-item', 'MeasurementSheetController', 'validateItem');
$router->get('/api/measurement-sheets/stats', 'MeasurementSheetController', 'getStats');

// Optional: Bulk operations
$router->post('/measurement-sheets/bulk-approve', 'MeasurementSheetController', 'bulkApprove');
$router->post('/measurement-sheets/bulk-export', 'MeasurementSheetController', 'bulkExport');
?>