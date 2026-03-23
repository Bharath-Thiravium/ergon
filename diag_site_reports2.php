<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/config/session.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$_SESSION['user_id']    = 1;
$_SESSION['role']       = 'admin';
$_SESSION['company_id'] = 1;

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/controllers/SiteReportController.php';

$ctrl = new SiteReportController();
$ctrl->index([]);
