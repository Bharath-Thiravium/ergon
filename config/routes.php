<?php
/**
 * Route Configuration
 * ERGON - Employee Tracker & Task Manager
 */

return [
    // Web Routes
    'web' => [
        'GET' => [
            '/' => ['AuthController', 'showLogin'],
            '/login' => ['AuthController', 'showLogin'],
            '/dashboard' => ['DashboardController', 'index'],
            '/users' => ['UsersController', 'index'],
            '/users/create' => ['UsersController', 'create'],
            '/reports' => ['ReportsController', 'index'],
            '/reports/activity' => ['ReportsController', 'activityReport'],
            '/settings' => ['SettingsController', 'index'],
            '/tasks' => ['TasksController', 'index'],
            '/tasks/create' => ['TasksController', 'create'],
            '/tasks/calendar' => ['TasksController', 'calendar'],
            '/planner/calendar' => ['PlannerController', 'calendar'],
            '/planner/create' => ['PlannerController', 'create'],
            '/planner/getDepartmentForm' => ['PlannerController', 'getDepartmentForm'],
            '/planner/getPlansForDate' => ['PlannerController', 'getPlansForDate'],
        ],
        'POST' => [
            '/auth/login' => ['AuthController', 'login'],
            '/auth/logout' => ['AuthController', 'logout'],
            '/attendance/clock' => ['AttendanceController', 'clock'],
            '/tasks/create' => ['TasksController', 'create'],
            '/planner/create' => ['PlannerController', 'create'],
            '/planner/update' => ['PlannerController', 'update'],
            '/settings' => ['SettingsController', 'index'],
        ]
    ],
    
    // API Routes for Mobile App
    'api' => [
        'POST' => [
            '/api/login' => ['ApiController', 'apiLogin'],
            '/api/attendance' => ['ApiController', 'apiAttendance'],
            '/api/tasks/update' => ['ApiController', 'apiTaskUpdate'],
        ],
        'GET' => [
            '/api/tasks' => ['ApiController', 'apiTasks'],
        ]
    ]
];
?>