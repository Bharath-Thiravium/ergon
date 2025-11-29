<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Simple request router for finance API
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Extract path after /finance/
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/ergon/finance/', '', $path);
$path = trim($path, '/');

// Simple routing
try {
    require_once __DIR__ . '/../app/controllers/FinanceController.php';
    require_once __DIR__ . '/../app/services/PrefixFallback.php';
    require_once __DIR__ . '/../utils/Logger.php';
    
    $controller = new FinanceController();
    
    // Create simple request object
    $request = new stdClass();
    $request->get = function($key) { return $_GET[$key] ?? null; };
    $request->post = function($key) { return $_POST[$key] ?? null; };
    $request->header = function($key) { return $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] ?? null; };
    
    switch ($path) {
        case '':
        case 'dashboard-stats':
            echo $controller->dashboardStats($request);
            break;
        case 'funnel-stats':
            echo $controller->funnelStats($request);
            break;
        case 'chart-stats':
            echo $controller->chartStats($request);
            break;
        case 'po-stats':
            echo $controller->poStats($request);
            break;
        case 'health':
            echo $controller->health($request);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>