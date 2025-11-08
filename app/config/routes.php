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
$router->get('/test-notifications', 'TestController', 'testNotifications');

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
$router->get('/admin/export', 'UsersController', 'export');

// User Routes - Complete User Panel
$router->get('/user/requests', 'UserController', 'myRequests');
$router->get('/user/my-requests', 'UserController', 'myRequests');
$router->get('/user/attendance', 'UserController', 'myAttendance');
$router->get('/user/my-attendance', 'UserController', 'myAttendance');
$router->get('/user/tasks', 'UserController', 'myTasks');
$router->get('/user/my-tasks', 'UserController', 'myTasks');
$router->post('/user/update-task-progress', 'UserController', 'updateTaskProgress');
$router->get('/user/submit-leave', 'UserController', 'submitLeaveRequest');
$router->post('/user/submit-leave', 'UserController', 'submitLeaveRequest');
$router->get('/user/submit-expense', 'UserController', 'submitExpenseClaim');
$router->post('/user/submit-expense', 'UserController', 'submitExpenseClaim');
$router->get('/user/submit-advance', 'UserController', 'submitAdvanceRequest');
$router->post('/user/submit-advance', 'UserController', 'submitAdvanceRequest');
$router->post('/user/clock-in', 'UserController', 'clockIn');
$router->post('/user/clock-out', 'UserController', 'clockOut');

// Department Management
$router->get('/departments', 'DepartmentController', 'index');
$router->get('/departments/create', 'DepartmentController', 'create');
$router->post('/departments/create', 'DepartmentController', 'store');
$router->get('/departments/view/{id}', 'DepartmentController', 'viewDepartment');
$router->get('/departments/edit/{id}', 'DepartmentController', 'edit');
$router->post('/departments/edit/{id}', 'DepartmentController', 'edit');
$router->post('/departments/delete/{id}', 'DepartmentController', 'delete');
$router->post('/departments/edit', 'DepartmentController', 'editPost');
$router->post('/departments/delete', 'DepartmentController', 'deletePost');

// Task Management
$router->get('/tasks', 'TasksController', 'index');
$router->get('/tasks/create', 'TasksController', 'create');
$router->post('/tasks/create', 'TasksController', 'store');
$router->get('/tasks/edit/{id}', 'TasksController', 'edit');
$router->post('/tasks/edit/{id}', 'TasksController', 'edit');
$router->get('/tasks/view/{id}', 'TasksController', 'viewTask');
$router->post('/tasks/delete/{id}', 'TasksController', 'delete');
$router->get('/tasks/calendar', 'TasksController', 'calendar');
$router->get('/tasks/overdue', 'TasksController', 'overdue');
$router->post('/tasks/bulk-create', 'TasksController', 'bulkCreate');

// Daily Planner Integration
$router->get('/planner', 'PlannerController', 'index');
$router->post('/planner/add-task', 'PlannerController', 'addTask');
$router->post('/planner/update-status', 'PlannerController', 'updateStatus');

// Evening Update Integration
$router->get('/evening-update', 'EveningUpdateController', 'index');
$router->post('/evening-update/submit', 'EveningUpdateController', 'submit');

// Attendance
$router->get('/attendance', 'AttendanceController', 'index');
$router->get('/attendance/clock', 'AttendanceController', 'clock');
$router->post('/attendance/clock', 'AttendanceController', 'clock');
$router->post('/attendance/manual', 'AttendanceController', 'manual');
$router->get('/attendance/status', 'AttendanceController', 'status');
$router->get('/attendance/conflicts', 'AttendanceController', 'conflicts');
$router->post('/attendance/resolve-conflict/{id}', 'AttendanceController', 'resolveConflict');

// Leave Management
$router->get('/leaves', 'LeaveController', 'index');
$router->get('/leaves/create', 'LeaveController', 'create');
$router->post('/leaves/create', 'LeaveController', 'store');
$router->get('/leaves/edit/{id}', 'LeaveController', 'edit');
$router->post('/leaves/edit/{id}', 'LeaveController', 'edit');
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
$router->get('/expenses/edit/{id}', 'ExpenseController', 'edit');
$router->post('/expenses/edit/{id}', 'ExpenseController', 'edit');
$router->get('/expenses/view/{id}', 'ExpenseController', 'viewExpense');
$router->post('/expenses/delete/{id}', 'ExpenseController', 'delete');
$router->get('/expenses/approve/{id}', 'ExpenseController', 'approve');
$router->post('/expenses/approve/{id}', 'ExpenseController', 'approve');
$router->get('/expenses/reject/{id}', 'ExpenseController', 'reject');
$router->post('/expenses/reject/{id}', 'ExpenseController', 'reject');

// Advance Management
$router->get('/advances', 'AdvanceController', 'index');
$router->get('/advances/create', 'AdvanceController', 'create');
$router->post('/advances/create', 'AdvanceController', 'store');
$router->get('/advances/edit/{id}', 'AdvanceController', 'edit');
$router->post('/advances/edit/{id}', 'AdvanceController', 'edit');
$router->post('/advances/store', 'AdvanceController', 'store');
$router->get('/advances/view/{id}', 'AdvanceController', 'viewAdvance');
$router->post('/advances/delete/{id}', 'AdvanceController', 'delete');
$router->get('/advances/approve/{id}', 'AdvanceController', 'approve');
$router->post('/advances/approve/{id}', 'AdvanceController', 'approve');
$router->get('/advances/reject/{id}', 'AdvanceController', 'reject');
$router->post('/advances/reject/{id}', 'AdvanceController', 'reject');

// Reports
$router->get('/reports', 'ReportsController', 'index');
$router->get('/reports/activity', 'ReportsController', 'activity');
$router->get('/reports/export', 'ReportsController', 'export');
$router->get('/reports/approvals-export', 'ReportsController', 'approvalsExport');

// Settings
$router->get('/settings', 'SettingsController', 'index');
$router->get('/settings/location', 'SettingsController', 'locationPicker');
$router->get('/settings/map-picker', 'SettingsController', 'mapPicker');
$router->get('/settings/map-picker', 'SettingsController', 'mapPicker');
$router->post('/settings', 'SettingsController', 'update');
$router->post('/settings/save', 'SettingsController', 'update');

// Owner Routes - Complete Management
$router->get('/owner/approvals', 'OwnerController', 'approvals');
$router->get('/owner/approvals/view/{type}/{id}', 'OwnerController', 'viewApproval');
$router->post('/owner/approvals/delete/{type}/{id}', 'OwnerController', 'deleteApproval');
$router->post('/owner/final-approve', 'OwnerController', 'finalApprove');
$router->get('/owner/create-user', 'OwnerController', 'createUser');
$router->post('/owner/create-user', 'OwnerController', 'createUser');
$router->get('/owner/manage-users', 'OwnerController', 'manageUsers');
$router->post('/owner/assign-role', 'OwnerController', 'assignRole');
$router->get('/owner/system-settings', 'OwnerController', 'systemSettings');
$router->post('/owner/system-settings', 'OwnerController', 'systemSettings');
$router->get('/owner/analytics', 'OwnerController', 'analytics');

// Legacy Owner Routes
$router->post('/owner/approveRequest', 'OwnerController', 'approveRequest');
$router->post('/owner/rejectRequest', 'OwnerController', 'rejectRequest');
$router->post('/owner/approve-request', 'OwnerController', 'approveRequest');
$router->post('/owner/reject-request', 'OwnerController', 'rejectRequest');

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
$router->post('/api/notifications/mark-as-read', 'NotificationController', 'markAsRead');
$router->post('/api/notifications/mark-all-read', 'NotificationController', 'markAllAsRead');
$router->post('/notifications/markAsRead', 'NotificationController', 'markAsRead');
$router->post('/notifications/markAllAsRead', 'NotificationController', 'markAllAsRead');
$router->post('/notifications/mark-all-read', 'NotificationController', 'markAllAsRead');
$router->post('/notifications/mark-as-read', 'NotificationController', 'markAsRead');

// Additional notification API routes
$router->get('/api/notifications', 'NotificationController', 'getUnreadCount');
$router->post('/api/notifications', 'NotificationController', 'markAllAsRead');

// Daily Workflow Management (New Integrated System)
$router->get('/daily-workflow/morning-planner', 'PlannerController', 'index');
$router->post('/daily-workflow/submit-morning-plans', 'DailyWorkflowController', 'submitMorningPlans');
$router->get('/daily-workflow/evening-update', 'DailyWorkflowController', 'eveningUpdate');
$router->post('/daily-workflow/submit-evening-updates', 'DailyWorkflowController', 'submitEveningUpdates');
$router->post('/daily-workflow/add-task', 'DailyWorkflowController', 'addTask');
$router->post('/daily-workflow/update-task', 'DailyWorkflowController', 'updateTask');
$router->post('/daily-workflow/delete-task', 'DailyWorkflowController', 'deleteTask');
$router->post('/daily-workflow/delete-user-workflow', 'DailyWorkflowController', 'deleteUserWorkflow');
$router->get('/daily-workflow/progress-dashboard', 'DailyWorkflowController', 'progressDashboard');
$router->get('/daily-workflow/task-categories', 'DailyWorkflowController', 'getTaskCategories');
$router->get('/api/projects-by-department', 'DailyWorkflowController', 'getProjectsByDepartment');
$router->get('/api/task-categories-by-department', 'DailyWorkflowController', 'getTaskCategoriesByDepartment');

// Legacy Planner Management (Redirected to new system)
$router->get('/planner/calendar', 'PlannerController', 'index');
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
$router->get('/api/task-categories', 'ApiController', 'taskCategories');
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
$router->post('/users/delete-document/{userId}/{filename}', 'UsersController', 'deleteDocument');

// Admin Routes - Department vs System Admin
$router->get('/admin/create-task', 'AdminController', 'createTask');
$router->post('/admin/create-task', 'AdminController', 'createTask');
$router->get('/admin/manage-tasks', 'AdminController', 'manageTasks');
$router->post('/admin/approve-request', 'AdminController', 'approveRequest');
$router->get('/admin/manage-users', 'AdminController', 'manageUsers');
$router->get('/admin/create-user', 'AdminController', 'createUser');
$router->post('/admin/create-user', 'AdminController', 'createUser');
$router->get('/admin/attendance-overview', 'AdminController', 'attendanceOverview');
$router->get('/admin/reports', 'AdminController', 'reports');

// System Admin Only Routes
$router->get('/admin/system-settings', 'AdminController', 'systemSettings');
$router->post('/admin/system-settings', 'AdminController', 'systemSettings');
$router->get('/admin/manage-departments', 'AdminController', 'manageDepartments');

// Legacy Admin Management Routes
$router->get('/admin/management', 'AdminManagementController', 'index');
$router->post('/admin/assign', 'AdminManagementController', 'assignAdmin');
$router->post('/admin/remove', 'AdminManagementController', 'removeAdmin');
$router->post('/admin/change-password', 'AdminManagementController', 'changePassword');

// System Admin Management Routes (Owner only)
$router->get('/system-admin', 'SystemAdminController', 'index');
$router->post('/system-admin/create', 'SystemAdminController', 'create');
$router->post('/system-admin/edit', 'SystemAdminController', 'edit');
$router->post('/system-admin/change-password', 'SystemAdminController', 'changePassword');
$router->post('/system-admin/delete', 'SystemAdminController', 'delete');
$router->post('/system-admin/toggle-status', 'SystemAdminController', 'toggleStatus');
$router->get('/system-admin/export', 'SystemAdminController', 'export');

// Project Management Routes (Admin/Owner)
$router->get('/project-management', 'ProjectManagementController', 'index');
$router->post('/project-management/create', 'ProjectManagementController', 'create');
$router->post('/project-management/update', 'ProjectManagementController', 'update');
$router->post('/project-management/delete', 'ProjectManagementController', 'delete');

// Follow-up Routes
$router->get('/followups', 'FollowupController', 'index');
$router->get('/followups/create', 'FollowupController', 'create');
$router->post('/followups', 'FollowupController', 'handlePost');
$router->post('/followups/create', 'FollowupController', 'store');
$router->get('/followups/view/{id}', 'FollowupController', 'viewFollowup');
$router->post('/followups/update', 'FollowupController', 'update');
$router->post('/followups/reschedule', 'FollowupController', 'reschedule');
$router->post('/followups/complete', 'FollowupController', 'complete');
$router->post('/followups/update-item', 'FollowupController', 'updateItem');
$router->post('/followups/delete', 'FollowupController', 'delete');
$router->post('/followups/create-from-task', 'FollowupController', 'createFromTask');
$router->get('/followups/history/{id}', 'FollowupController', 'getHistory');

// Gamification Routes
$router->get('/gamification/team-competition', 'GamificationController', 'teamCompetition');
$router->get('/gamification/individual', 'GamificationController', 'individual');
?>
