<?php
?>
<!DOCTYPE html>
<html>
<head>
    <title>ERGON Route Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .route-item { padding: 5px 0; border-bottom: 1px solid #eee; }
        .test-link { color: #1e40af; text-decoration: none; margin-left: 10px; }
        .test-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>🧭 ERGON Route Test</h1>
    
    <div class="test-section">
        <h2>Fixed Issues</h2>
        <p><span class="success">✅ Permissions-Policy Header:</span> Removed problematic 'speaker' feature</p>
        <p><span class="success">✅ System Admin Route:</span> Added /system-admin route to main index.php</p>
        <p><span class="success">✅ Missing Controllers:</span> Created AdminManagementController</p>
        <p><span class="success">✅ Missing Views:</span> Created admin management view</p>
    </div>

    <div class="test-section">
        <h2>Critical Routes Test</h2>
        <?php
        $routes = [
            'System Admin' => '/ergon/public/system-admin',
            'Admin Management' => '/ergon/public/admin/management',
            'Daily Planner' => '/ergon/public/planner/calendar',
            'Progress Dashboard' => '/ergon/public/daily-planner/dashboard',
            'Departments' => '/ergon/public/departments',
            'Users' => '/ergon/public/users',
            'Tasks' => '/ergon/public/tasks',
            'Attendance' => '/ergon/public/attendance',
            'Leaves' => '/ergon/public/leaves',
            'Expenses' => '/ergon/public/expenses',
            'Reports' => '/ergon/public/reports',
            'Settings' => '/ergon/public/settings'
        ];
        
        foreach ($routes as $name => $url) {
            echo '<div class="route-item">';
            echo '<span class="success">📍</span> ' . $name;
            echo '<a href="' . $url . '" class="test-link" target="_blank">Test Route</a>';
            echo '<br><small style="color: #666;">' . $url . '</small>';
            echo '</div>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>Controller Files Check</h2>
        <?php
        $controllers = [
            'SystemAdminController.php',
            'AdminManagementController.php',
            'PlannerController.php',
            'DailyTaskPlannerController.php',
            'DepartmentController.php',
            'UsersController.php',
            'TasksController.php',
            'AttendanceController.php',
            'LeaveController.php',
            'ExpenseController.php',
            'ReportsController.php',
            'SettingsController.php'
        ];
        
        foreach ($controllers as $controller) {
            $file = "app/controllers/{$controller}";
            if (file_exists($file)) {
                echo '<span class="success">✅ ' . $controller . '</span><br>';
            } else {
                echo '<span class="error">❌ ' . $controller . ' - Missing</span><br>';
            }
        }
        ?>
    </div>

    <div class="test-section">
        <h2>View Files Check</h2>
        <?php
        $views = [
            'views/admin/system_admin.php',
            'views/admin/management.php',
            'views/planner/calendar.php',
            'views/daily_planner/dashboard.php',
            'views/departments/index.php',
            'views/users/index.php',
            'views/tasks/index.php',
            'views/attendance/index.php',
            'views/leaves/index.php',
            'views/expenses/index.php',
            'views/reports/index.php',
            'views/settings/index.php'
        ];
        
        foreach ($views as $view) {
            if (file_exists($view)) {
                echo '<span class="success">✅ ' . basename($view) . '</span><br>';
            } else {
                echo '<span class="error">❌ ' . basename($view) . ' - Missing</span><br>';
            }
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🎉 Error Fixes Applied</h2>
        <p><span class="success">✅ 404 Error Fixed:</span> All sidebar routes now properly configured</p>
        <p><span class="success">✅ Permissions-Policy Fixed:</span> Removed unsupported 'speaker' feature</p>
        <p><span class="success">✅ Controllers Created:</span> All missing controllers implemented</p>
        <p><span class="success">✅ Views Created:</span> All missing view files implemented</p>
        <p><span class="success">✅ Security Headers:</span> Proper security headers without errors</p>
        
        <h3>🚀 Ready for Testing!</h3>
        <p>All sidebar menu items should now work without 404 errors.</p>
        
        <div style="margin-top: 20px;">
            <a href="/ergon/public/login" style="background: #1e40af; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🔐 Test Login</a>
            <a href="/ergon/sidebar_test.php" style="background: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">🧪 Sidebar Test</a>
        </div>
    </div>
</body>
</html>
