<?php
/**
 * ERGON - Employee Tracker & Task Manager
 * Clean Architecture - Main Entry Point
 */

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Security headers
header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Include core files
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/config/constants.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/helpers/SecurityHeaders.php';
require_once __DIR__ . '/../app/helpers/PerformanceBooster.php';

// Set security headers
SecurityHeaders::setAll();

// Enable performance optimizations
PerformanceBooster::enableCompression();

try {
    // Initialize router
    $router = new Router();
    
    // Authentication Routes
    $router->get('/', 'AuthController', 'index');
    $router->get('/login', 'AuthController', 'showLogin');
    $router->post('/login', 'AuthController', 'login');
    $router->get('/logout', 'AuthController', 'logout');
    $router->post('/logout', 'AuthController', 'logout');
    $router->get('/auth/reset-password', 'AuthController', 'resetPassword');
    $router->post('/auth/reset-password', 'AuthController', 'resetPassword');
    
    // Dashboard Routes
    $router->get('/dashboard', 'DashboardController', 'index');
    $router->get('/owner/dashboard', 'OwnerController', 'dashboard');
    $router->get('/admin/dashboard', 'AdminController', 'dashboard');
    $router->get('/user/dashboard', 'UserController', 'dashboard');
    
    // User Management
    $router->get('/users', 'UsersController', 'index');
    $router->get('/users/create', 'UsersController', 'create');
    $router->post('/users/create', 'UsersController', 'create');
    $router->get('/users/edit/{id}', 'UsersController', 'edit');
    $router->post('/users/edit/{id}', 'UsersController', 'edit');
    
    // Tasks
    $router->get('/tasks', 'TasksController', 'index');
    $router->get('/tasks/create', 'TasksController', 'create');
    $router->post('/tasks/create', 'TasksController', 'store');
    $router->get('/user/tasks', 'UserController', 'tasks');
    
    // Attendance
    $router->get('/attendance', 'AttendanceController', 'index');
    $router->get('/attendance/clock', 'AttendanceController', 'clock');
    $router->post('/attendance/clock', 'AttendanceController', 'clock');
    $router->get('/user/attendance', 'UserController', 'attendance');
    
    // Leaves
    $router->get('/leaves', 'LeaveController', 'index');
    $router->get('/leaves/create', 'LeaveController', 'create');
    $router->post('/leaves/create', 'LeaveController', 'store');
    $router->post('/leaves/approve/{id}', 'LeaveController', 'approve');
    $router->post('/leaves/reject/{id}', 'LeaveController', 'reject');
    
    // Expenses
    $router->get('/expenses', 'ExpenseController', 'index');
    $router->get('/expenses/create', 'ExpenseController', 'create');
    $router->post('/expenses/create', 'ExpenseController', 'create');
    $router->post('/expenses/approve/{id}', 'ExpenseController', 'approve');
    $router->post('/expenses/reject/{id}', 'ExpenseController', 'reject');
    
    // Reports
    $router->get('/reports', 'ReportsController', 'index');
    
    // Settings
    $router->get('/settings', 'SettingsController', 'index');
    $router->post('/settings', 'SettingsController', 'update');
    
    // Profile
    $router->get('/profile', 'ProfileController', 'index');
    $router->post('/profile', 'ProfileController', 'update');
    
    // System Admin Routes (Owner only)
    $router->get('/system-admin', 'SystemAdminController', 'index');
    $router->post('/system-admin/create', 'SystemAdminController', 'create');
    $router->post('/system-admin/deactivate', 'SystemAdminController', 'deactivate');
    
    // Admin Management Routes
    $router->get('/admin/management', 'AdminManagementController', 'index');
    $router->post('/admin/assign', 'AdminManagementController', 'assignAdmin');
    $router->post('/admin/remove', 'AdminManagementController', 'removeAdmin');
    
    // Department Management
    $router->get('/departments', 'DepartmentController', 'index');
    $router->get('/departments/create', 'DepartmentController', 'create');
    $router->post('/departments/create', 'DepartmentController', 'store');
    
    // Planner Management
    $router->get('/planner/calendar', 'PlannerController', 'calendar');
    $router->get('/planner/create', 'PlannerController', 'create');
    $router->post('/planner/create', 'PlannerController', 'store');
    $router->post('/planner/update', 'PlannerController', 'update');
    
    // Daily Task Planner Routes
    $router->get('/daily-planner', 'DailyTaskPlannerController', 'index');
    $router->get('/daily-planner/dashboard', 'DailyTaskPlannerController', 'dashboard');
    $router->post('/daily-planner/submit', 'DailyTaskPlannerController', 'submitTask');
    $router->get('/daily-planner/project-overview', 'DailyTaskPlannerController', 'projectOverview');
    $router->get('/daily-planner/delayed-tasks-overview', 'DailyTaskPlannerController', 'delayedTasksOverview');
    
    // User Request Routes
    $router->get('/user/requests', 'UserController', 'requests');
    
    // Owner Approvals
    $router->get('/owner/approvals', 'OwnerController', 'approvals');
    
    // Reports & Activity
    $router->get('/reports/activity', 'ReportsController', 'activity');
    $router->get('/reports/export', 'ReportsController', 'export');
    
    // Profile Routes
    $router->get('/profile/change-password', 'ProfileController', 'changePassword');
    $router->post('/profile/change-password', 'ProfileController', 'changePassword');
    $router->get('/profile/preferences', 'ProfileController', 'preferences');
    $router->post('/profile/preferences', 'ProfileController', 'preferences');
    
    // Notifications
    $router->get('/notifications', 'NotificationController', 'index');
    $router->get('/api/notifications/unread-count', 'NotificationController', 'getUnreadCount');
    $router->post('/api/notifications/mark-read', 'NotificationController', 'markAsRead');
    
    // API Routes
    $router->post('/api/login', 'ApiController', 'login');
    $router->post('/api/attendance', 'ApiController', 'attendance');
    $router->get('/api/tasks', 'ApiController', 'tasks');
    $router->post('/api/tasks/update', 'ApiController', 'updateTask');
    $router->get('/api/generate-employee-id', 'ApiController', 'generateEmployeeId');
    $router->post('/api/update-preference', 'ApiController', 'updatePreference');
    $router->post('/api/activity-log', 'ApiController', 'activityLog');
    
    // Static Routes
    $router->get('/favicon.ico', 'StaticController', 'favicon');
    
    // Handle the request
    $router->handleRequest();
    
} catch (Exception $e) {
    error_log('ERGON Error: ' . $e->getMessage());
    http_response_code(500);
    echo "System Error - Please try again later";
}
?>
