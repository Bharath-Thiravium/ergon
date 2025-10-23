<?php
session_start();

// Include configuration
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove base path if exists
$basePath = '/ergon';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Route handling
switch ($path) {
    case '/':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        // Role-based dashboard redirect
        $role = $_SESSION['role'] ?? 'user';
        switch ($role) {
            case 'owner':
                header('Location: /ergon/owner/dashboard');
                break;
            case 'admin':
                header('Location: /ergon/admin/dashboard');
                break;
            default:
                header('Location: /ergon/user/dashboard');
                break;
        }
        exit;
        break;
        
    case '/dashboard':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        // Role-based dashboard redirect
        $role = $_SESSION['role'] ?? 'user';
        switch ($role) {
            case 'owner':
                header('Location: /ergon/owner/dashboard');
                break;
            case 'admin':
                header('Location: /ergon/admin/dashboard');
                break;
            default:
                header('Location: /ergon/user/dashboard');
                break;
        }
        exit;
        break;
        
    case '/login':
        if (isset($_SESSION['user_id'])) {
            header('Location: /ergon/dashboard');
            exit;
        }
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->login();
        break;
        
    case '/auth/login':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->login();
        break;
        
    case '/auth/logout':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;
        
    case '/auth/reset-password':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->resetPassword();
        break;
        
    case '/attendance/clock':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/AttendanceController.php';
        $controller = new AttendanceController();
        $controller->clock();
        break;
        
    case '/users':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/UsersController.php';
        $controller = new UsersController();
        $controller->index();
        break;
        
    case '/reports':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/ReportsController.php';
        $controller = new ReportsController();
        $controller->index();
        break;
        
    case '/settings':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/SettingsController.php';
        $controller = new SettingsController();
        $controller->index();
        break;
        
    case '/leaves':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/LeaveController.php';
        $controller = new LeaveController();
        $controller->index();
        break;
        
    case '/leaves/create':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/LeaveController.php';
        $controller = new LeaveController();
        $controller->create();
        break;
        
    case '/expenses':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/ExpenseController.php';
        $controller = new ExpenseController();
        $controller->index();
        break;
        
    case '/expenses/create':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/ExpenseController.php';
        $controller = new ExpenseController();
        $controller->create();
        break;
        
    case '/advances/create':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/AdvanceController.php';
        $controller = new AdvanceController();
        $controller->create();
        break;
        
    case '/attendance':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/AttendanceController.php';
        $controller = new AttendanceController();
        $controller->index();
        break;
        
    case '/owner/dashboard':
        require_once __DIR__ . '/../app/middlewares/RoleMiddleware.php';
        require_once __DIR__ . '/../app/controllers/OwnerController.php';
        $controller = new OwnerController();
        $controller->dashboard();
        break;
        
    case '/owner/approvals':
        require_once __DIR__ . '/../app/middlewares/RoleMiddleware.php';
        require_once __DIR__ . '/../app/controllers/OwnerController.php';
        $controller = new OwnerController();
        $controller->approvals();
        break;
        
    case '/user/dashboard':
        require_once __DIR__ . '/../app/middlewares/RoleMiddleware.php';
        require_once __DIR__ . '/../app/controllers/UserController.php';
        $controller = new UserController();
        $controller->dashboard();
        break;
        
    case '/user/requests':
        require_once __DIR__ . '/../app/middlewares/RoleMiddleware.php';
        require_once __DIR__ . '/../app/controllers/UserController.php';
        $controller = new UserController();
        $controller->requests();
        break;
        
    case '/admin/dashboard':
        require_once __DIR__ . '/../app/middlewares/RoleMiddleware.php';
        require_once __DIR__ . '/../app/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->dashboard();
        break;
        
    case '/tasks':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/TasksController.php';
        $controller = new TasksController();
        $controller->index();
        break;
        
    case '/tasks/create':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/TasksController.php';
        $controller = new TasksController();
        $controller->create();
        break;
        
    case '/tasks/calendar':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/TasksController.php';
        $controller = new TasksController();
        $controller->calendar();
        break;
        
    case '/planner/calendar':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/PlannerController.php';
        $controller = new PlannerController();
        $controller->calendar();
        break;
        
    case '/planner/create':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/PlannerController.php';
        $controller = new PlannerController();
        $controller->create();
        break;
        
    case '/planner/update':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/PlannerController.php';
        $controller = new PlannerController();
        $controller->update();
        break;
        
    case '/planner/getDepartmentForm':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/PlannerController.php';
        $controller = new PlannerController();
        $controller->getDepartmentForm();
        break;
        
    case '/planner/getPlansForDate':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/PlannerController.php';
        $controller = new PlannerController();
        $controller->getPlansForDate();
        break;
        
    case '/setup-planner':
        AuthMiddleware::requireAuth();
        include __DIR__ . '/../setup_web.php';
        break;
        
    case '/setup-with-data':
        AuthMiddleware::requireAuth();
        include __DIR__ . '/../setup_with_dummy_data.php';
        break;
        
    case '/modal-demo':
        AuthMiddleware::requireAuth();
        include __DIR__ . '/../modal_demo.php';
        break;
        
    case '/test-attendance':
        include __DIR__ . '/../test_attendance.php';
        break;
        
    case '/test-sidebar':
        include __DIR__ . '/../test_sidebar.php';
        break;
        
    case '/debug-routes':
        include __DIR__ . '/../debug_routes.php';
        break;
        
    case '/create-sample-activity':
        include __DIR__ . '/../create_sample_activity.php';
        break;
        
    case '/test-session-security':
        include __DIR__ . '/../test_session_security.php';
        break;
        
    case '/show-credentials':
        include __DIR__ . '/../show_credentials.php';
        break;
        
    case '/reset-passwords':
        include __DIR__ . '/../reset_passwords.php';
        break;
        
    case '/test-destroy-session':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            session_unset();
            session_destroy();
            echo "Session destroyed manually";
        }
        exit;
        break;
        
    case '/api/test-clock':
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'API endpoint working', 'session' => isset($_SESSION['user_id'])]);
        break;
        
    case '/api/generate-employee-id':
        AuthMiddleware::requireAuth();
        header('Content-Type: application/json');
        try {
            require_once __DIR__ . '/../app/helpers/EmployeeHelper.php';
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();
            $stmt = $conn->prepare("SELECT company_name FROM settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            $companyName = $settings['company_name'] ?? 'ERGON Company';
            $employeeId = EmployeeHelper::generateEmployeeId($companyName);
            echo json_encode(['success' => true, 'employee_id' => $employeeId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case '/departments':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/DepartmentController.php';
        $controller = new DepartmentController();
        $controller->index();
        break;
        
    case '/departments/create':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/DepartmentController.php';
        $controller = new DepartmentController();
        $controller->create();
        break;
        
    case '/users/create':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/UsersController.php';
        $controller = new UsersController();
        $controller->create();
        break;
        
    case '/users/download-credentials':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/UsersController.php';
        $controller = new UsersController();
        $controller->downloadCredentials();
        break;
        
    case '/users/reset-password':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/UsersController.php';
        $controller = new UsersController();
        $controller->resetUserPassword();
        break;
        
    case '/reports/activity':
        error_log("Activity reports route hit");
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/ReportsController.php';
        $controller = new ReportsController();
        $controller->activityReport();
        break;
        
    case '/reports/export':
        AuthMiddleware::requireAuth();
        require_once __DIR__ . '/../app/controllers/ReportsController.php';
        $controller = new ReportsController();
        $controller->export();
        break;
        

        
    // API Routes for Mobile App
    case '/api/login':
        require_once __DIR__ . '/../app/controllers/ApiController.php';
        $controller = new ApiController();
        $controller->apiLogin();
        break;
        
    case '/api/attendance':
        require_once __DIR__ . '/../app/controllers/ApiController.php';
        $controller = new ApiController();
        $controller->apiAttendance();
        break;
        
    case '/api/tasks':
        require_once __DIR__ . '/../app/controllers/ApiController.php';
        $controller = new ApiController();
        $controller->apiTasks();
        break;
        
    case '/api/tasks/update':
        require_once __DIR__ . '/../app/controllers/ApiController.php';
        $controller = new ApiController();
        $controller->apiTaskUpdate();
        break;
        
    case '/api/activity-log':
        require_once __DIR__ . '/../app/controllers/ApiController.php';
        $controller = new ApiController();
        $controller->apiActivityLog();
        break;
        
    case '/api/check-session':
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        echo json_encode(['valid' => isset($_SESSION['user_id'])]);
        exit;
        break;
        
    default:
        // Handle dynamic routes with parameters
        if (preg_match('/^\/leaves\/approve\/(\d+)$/', $path, $matches)) {
            AuthMiddleware::requireAuth();
            require_once __DIR__ . '/../app/controllers/LeaveController.php';
            $controller = new LeaveController();
            $controller->approve($matches[1]);
            break;
        }
        
        if (preg_match('/^\/leaves\/reject\/(\d+)$/', $path, $matches)) {
            AuthMiddleware::requireAuth();
            require_once __DIR__ . '/../app/controllers/LeaveController.php';
            $controller = new LeaveController();
            $controller->reject($matches[1]);
            break;
        }
        
        if (preg_match('/^\/expenses\/approve\/(\d+)$/', $path, $matches)) {
            AuthMiddleware::requireAuth();
            require_once __DIR__ . '/../app/controllers/ExpenseController.php';
            $controller = new ExpenseController();
            $controller->approve($matches[1]);
            break;
        }
        
        if (preg_match('/^\/expenses\/reject\/(\d+)$/', $path, $matches)) {
            AuthMiddleware::requireAuth();
            require_once __DIR__ . '/../app/controllers/ExpenseController.php';
            $controller = new ExpenseController();
            $controller->reject($matches[1]);
            break;
        }
        
        if (preg_match('/^\/departments\/edit\/(\d+)$/', $path, $matches)) {
            AuthMiddleware::requireAuth();
            require_once __DIR__ . '/../app/controllers/DepartmentController.php';
            $controller = new DepartmentController();
            $controller->edit($matches[1]);
            break;
        }
        
        if (preg_match('/^\/users\/edit\/(\d+)$/', $path, $matches)) {
            AuthMiddleware::requireAuth();
            require_once __DIR__ . '/../app/controllers/UsersController.php';
            $controller = new UsersController();
            $controller->edit($matches[1]);
            break;
        }
        http_response_code(404);
        echo "<!DOCTYPE html><html><head><title>404 - Page Not Found</title></head><body><h1>404 - Page Not Found</h1><p>The requested page could not be found.</p><a href='/ergon/login'>Go to Login</a></body></html>";
        break;
}
?>