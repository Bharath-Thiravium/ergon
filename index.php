<?php
// ERGON Employee Tracker - Main Entry Point
require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/helpers/PerformanceOptimizer.php';
require_once __DIR__ . '/app/core/Router.php';
require_once __DIR__ . '/app/core/Session.php';

// Performance optimizations
PerformanceOptimizer::enableGzipCompression();
PerformanceOptimizer::setCacheHeaders(3600);

// Initialize session
Session::init();

// Initialize router
$router = new Router();

// Load routes
require_once __DIR__ . '/app/config/routes.php';

// Handle the request
$router->handleRequest();
?>