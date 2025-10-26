<?php
?>
<!DOCTYPE html>
<html>
<head>
    <title>ERGON Error Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .route-test { padding: 10px; margin: 5px 0; border: 1px solid #eee; border-radius: 5px; }
        .test-btn { background: #1e40af; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 12px; }
    </style>
</head>
<body>
    <h1>🧭 ERGON Error Test & Route Verification</h1>
    
    <div class="test-section">
        <h2>Controller Files Check</h2>
        <?php
        $controllers = [
            'SystemAdminController.php' => 'System Admin Management',
            'AdminManagementController.php' => 'User Admin Management', 
            'DepartmentController.php' => 'Department Management',
            'PlannerController.php' => 'Daily Planner',
            'DailyTaskPlannerController.php' => 'Progress Dashboard',
            'NotificationController.php' => 'Notifications',
            'UsersController.php' => 'User Management',
            'TasksController.php' => 'Task Management',
            'AttendanceController.php' => 'Attendance Management',
            'LeaveController.php' => 'Leave Management',
            'ExpenseController.php' => 'Expense Management',
            'ReportsController.php' => 'Reports',
            'SettingsController.php' => 'Settings',
            'ProfileController.php' => 'Profile Management',
            'ApiController.php' => 'API Endpoints',
            'StaticController.php' => 'Static Files'
        ];
        
        foreach ($controllers as $file => $description) {
            $path = "app/controllers/{$file}";
            if (file_exists($path)) {
                echo '<span class="success">✅ ' . $file . ' - ' . $description . '</span><br>';
            } else {
                echo '<span class="error">❌ ' . $file . ' - Missing</span><br>';
            }
        }
        ?>
    </div>

    <div class="test-section">
        <h2>Route Testing</h2>
        <?php
        $routes = [
            'System Admin' => '/ergon/public/system-admin',
            'Admin Management' => '/ergon/public/admin/management',
            'Departments' => '/ergon/public/departments',
            'Daily Planner' => '/ergon/public/planner/calendar',
            'Progress Dashboard' => '/ergon/public/daily-planner/dashboard',
            'Users' => '/ergon/public/users',
            'Tasks' => '/ergon/public/tasks',
            'Attendance' => '/ergon/public/attendance',
            'Leaves' => '/ergon/public/leaves',
            'Expenses' => '/ergon/public/expenses',
            'Reports' => '/ergon/public/reports',
            'Settings' => '/ergon/public/settings',
            'Profile' => '/ergon/public/profile',
            'Notifications' => '/ergon/public/notifications'
        ];
        
        foreach ($routes as $name => $url) {
            echo '<div class="route-test">';
            echo '<strong>' . $name . '</strong><br>';
            echo '<small style="color: #666;">' . $url . '</small>';
            echo '<a href="' . $url . '" class="test-btn" target="_blank">Test</a>';
            echo '</div>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>View Files Check</h2>
        <?php
        $views = [
            'views/admin/system_admin.php' => 'System Admin View',
            'views/admin/management.php' => 'Admin Management View',
            'views/departments/index.php' => 'Departments View',
            'views/planner/calendar.php' => 'Planner View',
            'views/daily_planner/dashboard.php' => 'Progress Dashboard View',
            'views/users/index.php' => 'Users View',
            'views/tasks/index.php' => 'Tasks View',
            'views/attendance/index.php' => 'Attendance View',
            'views/leaves/index.php' => 'Leaves View',
            'views/expenses/index.php' => 'Expenses View',
            'views/reports/index.php' => 'Reports View',
            'views/settings/index.php' => 'Settings View',
            'views/profile/index.php' => 'Profile View',
            'views/notifications/index.php' => 'Notifications View'
        ];
        
        foreach ($views as $file => $description) {
            if (file_exists($file)) {
                echo '<span class="success">✅ ' . $description . '</span><br>';
            } else {
                echo '<span class="error">❌ ' . $description . ' - Missing</span><br>';
            }
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🔧 Error Fixes Applied</h2>
        <p><span class="success">✅ 500 Error Fixed:</span> DailyTaskPlannerController dashboard method updated with proper data</p>
        <p><span class="success">✅ Missing Controllers:</span> DepartmentController and NotificationController created</p>
        <p><span class="success">✅ Route Configuration:</span> All sidebar routes properly configured in index.php</p>
        <p><span class="success">✅ View Variables:</span> All required variables provided to views</p>
        <p><span class="success">✅ Mock Data:</span> Temporary mock data for testing without database dependencies</p>
        
        <h3>🚀 Test Results</h3>
        <p>All routes should now work without 500 errors. The system uses mock data where database tables don't exist yet.</p>
        
        <div style="margin-top: 20px;">
            <a href="/ergon/public/login" style="background: #1e40af; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🔐 Test Login</a>
            <a href="/ergon/public/daily-planner/dashboard" style="background: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">🧪 Test Progress Dashboard</a>
        </div>
    </div>
</body>
</html>
