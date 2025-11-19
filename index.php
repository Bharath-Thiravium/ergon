<?php
/**
 * Ergon - Employee Tracker & Task Manager
 * Main Application Entry Point
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Include autoloader and core files
require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/Router.php';
require_once __DIR__ . '/app/core/Controller.php';

// Initialize router
$router = new Router();

// Load routes
require_once __DIR__ . '/app/config/routes.php';

// Handle the request
$router->handleRequest();
?>