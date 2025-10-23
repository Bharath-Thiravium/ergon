<?php
/**
 * Route Configuration
 * ERGON - Employee Tracker & Task Manager
 */

// Authentication Routes
$router->get('/', 'AuthController', 'index');
$router->get('/login', 'AuthController', 'showLogin');
$router->post('/login', 'AuthController', 'login');
$router->get('/logout', 'AuthController', 'logout');
$router->post('/logout', 'AuthController', 'logout');
$router->get('/auth/logout', 'AuthController', 'logout');
$router->post('/auth/logout', 'AuthController', 'logout');

// Dashboard Routes
$router->get('/dashboard', 'DashboardController', 'index');
$router->get('/owner/dashboard', 'OwnerController', 'dashboard');
$router->get('/admin/dashboard', 'AdminController', 'dashboard');
$router->get('/user/dashboard', 'UserController', 'dashboard');

// User Management
$router->get('/users', 'UsersController', 'index');
$router->get('/users/create', 'UsersController', 'create');
$router->post('/users/create', 'UsersController', 'store');
$router->get('/users/edit/{id}', 'UsersController', 'edit');
$router->post('/users/edit/{id}', 'UsersController', 'update');

// Department Management
$router->get('/departments', 'DepartmentController', 'index');
$router->get('/departments/create', 'DepartmentController', 'create');
$router->post('/departments/create', 'DepartmentController', 'store');

// Task Management
$router->get('/tasks', 'TasksController', 'index');
$router->get('/tasks/create', 'TasksController', 'create');
$router->post('/tasks/create', 'TasksController', 'store');
$router->get('/tasks/calendar', 'TasksController', 'calendar');

// Planner Management
$router->get('/planner/calendar', 'PlannerController', 'calendar');
$router->get('/planner/create', 'PlannerController', 'create');
$router->post('/planner/create', 'PlannerController', 'create');
$router->post('/planner/update', 'PlannerController', 'update');
$router->get('/planner/getDepartmentForm', 'PlannerController', 'getDepartmentForm');
$router->get('/planner/getPlansForDate', 'PlannerController', 'getPlansForDate');

// Attendance
$router->get('/attendance', 'AttendanceController', 'index');
$router->post('/attendance/clock', 'AttendanceController', 'clock');

// Leave Management
$router->get('/leaves', 'LeaveController', 'index');
$router->get('/leaves/create', 'LeaveController', 'create');
$router->post('/leaves/create', 'LeaveController', 'store');
$router->get('/leaves/approve/{id}', 'LeaveController', 'approve');
$router->get('/leaves/reject/{id}', 'LeaveController', 'reject');

// Expense Management
$router->get('/expenses', 'ExpenseController', 'index');
$router->get('/expenses/create', 'ExpenseController', 'create');
$router->post('/expenses/create', 'ExpenseController', 'store');

// Reports
$router->get('/reports', 'ReportsController', 'index');
$router->get('/reports/activity', 'ReportsController', 'activity');

// Settings
$router->get('/settings', 'SettingsController', 'index');
$router->post('/settings', 'SettingsController', 'update');

// API Routes
$router->post('/api/login', 'ApiController', 'login');
$router->post('/api/attendance', 'ApiController', 'attendance');
$router->get('/api/tasks', 'ApiController', 'tasks');
$router->post('/api/tasks/update', 'ApiController', 'updateTask');
$router->get('/api/generate-employee-id', 'ApiController', 'generateEmployeeId');
?>