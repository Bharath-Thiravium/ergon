<?php
/**
 * Route Configuration
 * ergon - Employee Tracker & Task Manager
 */

// Favicon route
$router->get('/favicon.ico', 'StaticController', 'favicon');

// Test routes
$router->get('/test', 'TestController', 'index');
$router->get('/status', 'TestController', 'status');

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
$router->post('/users/inactive/{id}', 'UsersController', 'inactive');
$router->post('/users/delete/{id}', 'UsersController', 'delete');
$router->post('/users/reset-password', 'UsersController', 'resetPassword');
$router->get('/users/download-credentials', 'UsersController', 'downloadCredentials');
$router->get('/users/export', 'UsersController', 'export');

// User Request Routes
$router->get('/user/requests', 'UserController', 'requests');
$router->get('/user/attendance', 'UserController', 'attendance');
$router->get('/user/tasks', 'UserController', 'tasks');

// Department Management
$router->get('/departments', 'DepartmentController', 'index');
$router->get('/departments/create', 'DepartmentController', 'create');
$router->post('/departments/create', 'DepartmentController', 'store');
$router->get('/departments/view/{id}', 'DepartmentController', 'view');
$router->get('/departments/edit/{id}', 'DepartmentController', 'edit');
$router->post('/departments/edit/{id}', 'DepartmentController', 'update');
$router->post('/departments/delete/{id}', 'DepartmentController', 'delete');
$router->post('/departments/edit', 'DepartmentController', 'editPost');
$router->post('/departments/delete', 'DepartmentController', 'deletePost');

// Task Management
$router->get('/tasks', 'TasksController', 'index');
$router->get('/tasks/create', 'TasksController', 'create');
$router->post('/tasks/create', 'TasksController', 'store');
$router->get('/tasks/view/{id}', 'TasksController', 'view');
$router->post('/tasks/delete/{id}', 'TasksController', 'delete');
$router->get('/tasks/calendar', 'TasksController', 'calendar');
$router->get('/tasks/overdue', 'TasksController', 'overdue');
$router->post('/tasks/bulk-create', 'TasksController', 'bulkCreate');

// Attendance
$router->get('/attendance', 'AttendanceController', 'index');
$router->get('/attendance/clock', 'AttendanceController', 'clock');
$router->post('/attendance/clock', 'AttendanceController', 'clock');
$router->get('/attendance/conflicts', 'AttendanceController', 'conflicts');
$router->post('/attendance/resolve-conflict/{id}', 'AttendanceController', 'resolveConflict');

// Leave Management
$router->get('/leaves', 'LeaveController', 'index');
$router->get('/leaves/create', 'LeaveController', 'create');
$router->post('/leaves/create', 'LeaveController', 'store');
$router->get('/leaves/view/{id}', 'LeaveController', 'viewLeave');
$router->post('/leaves/delete/{id}', 'LeaveController', 'delete');
$router->get('/leaves/approve/{id}', 'LeaveController', 'approve');
$router->get('/leaves/reject/{id}', 'LeaveController', 'reject');
$router->post('/leaves/approve/{id}', 'LeaveController', 'approve');
$router->post('/leaves/reject/{id}', 'LeaveController', 'reject');

// Expense Management
$router->get('/expenses', 'ExpenseController', 'index');
$router->get('/expenses/create', 'ExpenseController', 'create');
$router->post('/expenses/create', 'ExpenseController', 'create');
$router->get('/expenses/view/{id}', 'ExpenseController', 'viewExpense');
$router->post('/expenses/delete/{id}', 'ExpenseController', 'delete');
$router->post('/expenses/approve/{id}', 'ExpenseController', 'approve');
$router->post('/expenses/reject/{id}', 'ExpenseController', 'reject');

// Advance Management
$router->get('/advances', 'AdvanceController', 'index');
$router->get('/advances/create', 'AdvanceController', 'create');
$router->post('/advances/create', 'AdvanceController', 'store');
$router->get('/advances/view/{id}', 'AdvanceController', 'view');
$router->post('/advances/delete/{id}', 'AdvanceController', 'delete');
$router->post('/advances/approve/{id}', 'AdvanceController', 'approve');
$router->post('/advances/reject/{id}', 'AdvanceController', 'reject');

// Reports
$router->get('/reports', 'ReportsController', 'index');
$router->get('/reports/activity', 'ReportsController', 'activity');
$router->get('/reports/export', 'ReportsController', 'export');

// Settings
$router->get('/settings', 'SettingsController', 'index');
$router->get('/settings/location', 'SettingsController', 'locationPicker');
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
$router->post('/notifications/mark-all-read', 'NotificationController', 'markAllAsRead');
$router->post('/notifications/mark-as-read', 'NotificationController', 'markAsRead');

// Daily Workflow Management (New Integrated System)
$router->get('/daily-workflow/morning-planner', 'DailyWorkflowController', 'morningPlanner');
$router->get('/daily-workflow/evening-update', 'DailyWorkflowController', 'eveningUpdate');
$router->post('/daily-workflow/add-task', 'DailyWorkflowController', 'addTask');
$router->post('/daily-workflow/update-task', 'DailyWorkflowController', 'updateTask');
$router->post('/daily-workflow/delete-task', 'DailyWorkflowController', 'deleteTask');
$router->post('/daily-workflow/delete-user-workflow', 'DailyWorkflowController', 'deleteUserWorkflow');
$router->get('/daily-workflow/progress-dashboard', 'DailyWorkflowController', 'progressDashboard');
$router->get('/daily-workflow/task-categories', 'DailyWorkflowController', 'getTaskCategories');
$router->get('/api/projects-by-department', 'DailyWorkflowController', 'getProjectsByDepartment');
$router->get('/api/task-categories-by-department', 'DailyWorkflowController', 'getTaskCategoriesByDepartment');

// Legacy Planner Management (Redirected to new system)
$router->get('/planner/calendar', 'DailyWorkflowController', 'morningPlanner');
$router->get('/planner/create', 'PlannerController', 'create');
$router->post('/planner/create', 'PlannerController', 'store');
$router->post('/planner/update', 'PlannerController', 'update');
$router->get('/planner/getDepartmentForm', 'PlannerController', 'getDepartmentForm');
$router->get('/planner/getPlansForDate', 'PlannerController', 'getPlansForDate');

// Legacy Daily Task Planner Routes (Redirected to new system)
$router->get('/daily-planner', 'DailyWorkflowController', 'eveningUpdate');
$router->post('/daily-planner/submit', 'DailyTaskPlannerController', 'submitTask');
$router->get('/daily-planner/get-tasks', 'DailyTaskPlannerController', 'getProjectTasks');
$router->get('/daily-planner/dashboard', 'DailyWorkflowController', 'progressDashboard');
$router->get('/daily-planner/project-overview', 'DailyTaskPlannerController', 'projectOverview');
$router->get('/daily-planner/delayed-tasks-overview', 'DailyTaskPlannerController', 'delayedTasksOverview');
$router->get('/api/project-progress', 'DailyTaskPlannerController', 'projectProgressApi');

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

// Advanced Task Routes
$router->get('/tasks/kanban', 'TasksController', 'kanban');
$router->get('/tasks/sla-breaches', 'TasksController', 'slaBreaches');
$router->get('/tasks/velocity/{userId}', 'TasksController', 'getVelocity');
$router->get('/tasks/productivity/{userId}', 'TasksController', 'getProductivity');
$router->get('/tasks/{id}/subtasks', 'TasksController', 'getSubtasks');

// Advanced Attendance Routes
$router->get('/attendance/anomalies/{userId}', 'AttendanceController', 'getAnomalies');

// User Document Downloads
$router->get('/users/download-document/{userId}/{filename}', 'UsersController', 'downloadDocument');

// Admin Management Routes
$router->get('/admin/management', 'AdminManagementController', 'index');
$router->post('/admin/assign', 'AdminManagementController', 'assignAdmin');
$router->post('/admin/remove', 'AdminManagementController', 'removeAdmin');

// System Admin Management Routes (Owner only)
$router->get('/system-admin', 'SystemAdminController', 'index');
$router->post('/system-admin/create', 'SystemAdminController', 'create');
$router->post('/system-admin/edit', 'SystemAdminController', 'edit');
$router->post('/system-admin/deactivate', 'SystemAdminController', 'deactivate');
$router->get('/system-admin/export', 'SystemAdminController', 'export');

// Project Management Routes (Admin/Owner)
$router->get('/project-management', 'ProjectManagementController', 'index');
$router->post('/project-management/create', 'ProjectManagementController', 'create');
$router->post('/project-management/update', 'ProjectManagementController', 'update');
$router->post('/project-management/delete', 'ProjectManagementController', 'delete');

// Follow-up Routes
$router->get('/followups', 'FollowupController', 'index');
$router->post('/followups/create', 'FollowupController', 'create');
$router->get('/followups/view/{id}', 'FollowupController', 'view');
$router->post('/followups/update', 'FollowupController', 'update');
$router->post('/followups/reschedule', 'FollowupController', 'reschedule');
$router->post('/followups/complete', 'FollowupController', 'complete');
$router->post('/followups/update-item', 'FollowupController', 'updateItem');
$router->post('/followups/delete', 'FollowupController', 'delete');
$router->post('/followups/create-from-task', 'FollowupController', 'createFromTask');

// Gamification Routes
$router->get('/gamification/team-competition', 'GamificationController', 'teamCompetition');
$router->get('/gamification/individual', 'GamificationController', 'individual');
?>
