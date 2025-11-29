<?php

// Finance API Routes
// All routes are prefixed with /finance

$router->get('/dashboard-stats', 'FinanceController@dashboardStats');
$router->get('/funnel-stats', 'FinanceController@funnelStats');
$router->get('/chart-stats', 'FinanceController@chartStats');
$router->get('/po-stats', 'FinanceController@poStats');
$router->post('/sync', 'FinanceController@triggerSync');
$router->get('/health', 'FinanceController@health');

// Optional: Add middleware for authentication on sensitive endpoints
$router->group(['middleware' => 'auth'], function($router) {
    $router->post('/sync', 'FinanceController@triggerSync');
});

// Optional: Add CORS headers for API endpoints
$router->group(['middleware' => 'cors'], function($router) {
    $router->get('/dashboard-stats', 'FinanceController@dashboardStats');
    $router->get('/funnel-stats', 'FinanceController@funnelStats');
    $router->get('/chart-stats', 'FinanceController@chartStats');
    $router->get('/po-stats', 'FinanceController@poStats');
    $router->get('/health', 'FinanceController@health');
});