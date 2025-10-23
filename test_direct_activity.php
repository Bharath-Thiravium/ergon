<?php
session_start();

// Set admin session for testing
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'admin';
$_SESSION['user_name'] = 'Athenas Admin';

// Directly call the activity report
require_once __DIR__ . '/app/controllers/ReportsController.php';
$controller = new ReportsController();
$controller->activityReport();
?>