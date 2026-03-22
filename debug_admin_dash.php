<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

session_start();
// Fake an admin session so the auth check passes
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['company_id'] = 1;

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/app/controllers/AdminController.php';

$c = new AdminController();
$c->dashboard();
