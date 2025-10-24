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
$router->get('/users/view/{id}', 'UsersController', 'viewUser');
$router->get('/users/edit/{id}', 'UsersController', 'edit');
$router->post('/users/edit/{id}', 'UsersController', 'edit');
$router->get('/users/download-document/{userId}/{filename}', 'UsersController', 'downloadDocument');
$router->post('/users/inactive/{id}', 'UsersController', 'inactive');
$router->post('/users/delete/{id}', 'UsersController', 'delete');
$router->post('/users/reset-password', 'UsersController', 'resetUserPassword');

// User Request Routes (for mobile/web)
$router->get('/user/requests', 'UserController', 'requests');
$router->get('/user/attendance', 'UserController', 'attendance');
$router->get('/user/tasks', 'UserController', 'tasks');

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
$router->post('/planner/create', 'PlannerController', 'store');
$router->post('/planner/update', 'PlannerController', 'update');
$router->get('/planner/getDepartmentForm', 'PlannerController', 'getDepartmentForm');
$router->get('/planner/getPlansForDate', 'PlannerController', 'getPlansForDate');

// Attendance
$router->get('/attendance', 'AttendanceController', 'index');
$router->get('/attendance/clock', 'AttendanceController', 'clock');
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
$router->post('/expenses/create', 'ExpenseController', 'create');

// Advance Management
$router->get('/advances', 'AdvanceController', 'index');
$router->get('/advances/create', 'AdvanceController', 'create');
$router->post('/advances/create', 'AdvanceController', 'store');

// Reports
$router->get('/reports', 'ReportsController', 'index');
$router->get('/reports/activity', 'ReportsController', 'activity');

// Settings
$router->get('/settings', 'SettingsController', 'index');
$router->post('/settings', 'SettingsController', 'update');
$router->post('/settings/save', 'SettingsController', 'update');

// Owner Approvals
$router->get('/owner/approvals', 'OwnerController', 'approvals');

// Profile
$router->get('/profile', 'ProfileController', 'index');
$router->post('/profile', 'ProfileController', 'update');
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
$router->post('/api/session_from_jwt', 'ApiController', 'sessionFromJWT');
$router->post('/api/test', 'ApiController', 'test');

// Mobile API Routes
$router->post('/api/register_device', 'ApiController', 'registerDevice');
$router->post('/api/sync', 'ApiController', 'syncOfflineData');
$router->post('/api/leaves/create', 'LeaveController', 'apiCreate');
$router->post('/api/expenses/create', 'ExpenseController', 'apiCreate');

// Enhanced Leave Routes
$router->post('/leaves/approve/{id}', 'LeaveController', 'approve');
$router->post('/leaves/reject/{id}', 'LeaveController', 'reject');

// Enhanced Expense Routes  
$router->post('/expenses/approve/{id}', 'ExpenseController', 'approve');
$router->post('/expenses/reject/{id}', 'ExpenseController', 'reject');

// Advanced Attendance Routes
$router->get('/attendance/conflicts', 'AttendanceController', 'conflicts');
$router->post('/attendance/resolve-conflict/{id}', 'AttendanceController', 'resolveConflict');
$router->get('/attendance/anomalies/{userId}', 'AttendanceController', 'getAnomalies');

// Advanced Task Routes
$router->get('/tasks/overdue', 'TasksController', 'overdue');
$router->get('/tasks/sla-breaches', 'TasksController', 'slaBreaches');
$router->get('/tasks/velocity/{userId}', 'TasksController', 'getVelocity');
$router->get('/tasks/productivity/{userId}', 'TasksController', 'getProductivity');
$router->post('/tasks/bulk-create', 'TasksController', 'bulkCreate');
$router->get('/tasks/{id}/subtasks', 'TasksController', 'getSubtasks');

// Daily Task Planner Routes
$router->get('/daily-planner', 'DailyTaskPlannerController', 'index');
$router->post('/daily-planner/submit', 'DailyTaskPlannerController', 'submitTask');
$router->get('/daily-planner/get-tasks', 'DailyTaskPlannerController', 'getProjectTasks');
$router->get('/daily-planner/dashboard', 'DailyTaskPlannerController', 'dashboard');
$router->get('/daily-planner/project-overview', 'DailyTaskPlannerController', 'projectOverview');
$router->get('/daily-planner/delayed-tasks-overview', 'DailyTaskPlannerController', 'delayedTasksOverview');
$router->get('/api/project-progress', 'DailyTaskPlannerController', 'projectProgressApi');

// Export Routes
$router->get('/reports/export', 'ReportsController', 'export');

// Admin Management Routes
$router->get('/admin/management', 'AdminManagementController', 'index');
$router->post('/admin/assign', 'AdminManagementController', 'assignAdmin');
$router->post('/admin/remove', 'AdminManagementController', 'removeAdmin');

// System Admin Management Routes (Owner only)
$router->get('/system-admin', 'SystemAdminController', 'index');
$router->post('/system-admin/create', 'SystemAdminController', 'create');
$router->post('/system-admin/deactivate', 'SystemAdminController', 'deactivate');
?>