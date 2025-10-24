<?php
require_once __DIR__ . '/../models/User.php';

class UsersController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $current_role = $_SESSION['role'] ?? 'user';
        
        // Role-based user management
        if ($current_role === 'owner') {
            $users = $this->userModel->getUsersByRole(['admin']);
            $manageable_role = 'admin';
        } elseif ($current_role === 'admin') {
            $users = $this->userModel->getUsersByRole(['user']);
            $manageable_role = 'user';
        } else {
            $users = [];
            $manageable_role = 'user';
        }
        
        $stats = $this->userModel->getStatsByRole($manageable_role);
        
        $data = [
            'users' => $users,
            'stats' => $stats,
            'manageable_role' => $manageable_role,
            'current_role' => $current_role
        ];
        
        include __DIR__ . '/../views/users/index.php';
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->userModel->createEnhanced($_POST);
            if ($result && isset($result['temp_password'])) {
                $_SESSION['new_user_credentials'] = [
                    'employee_id' => $result['employee_id'],
                    'email' => $_POST['email'],
                    'temp_password' => $result['temp_password']
                ];
            }
        }
        
        require_once __DIR__ . '/../models/Department.php';
        $departmentModel = new Department();
        $departments = $departmentModel->getAll();
        
        $data = ['departments' => $departments];
        include __DIR__ . '/../views/users/create.php';
    }
    
    public function edit($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->userModel->updateEnhanced($id, $_POST);
            if ($result) {
                header('Location: /ergon/users?success=updated');
                exit;
            }
        }
        
        $user = $this->userModel->getById($id);
        
        // Debug: Check if user data is loaded
        if (!$user) {
            $data = ['user' => null, 'departments' => [], 'user_departments' => []];
            include __DIR__ . '/../views/users/edit.php';
            return;
        }
        
        require_once __DIR__ . '/../models/Department.php';
        $departmentModel = new Department();
        $departments = $departmentModel->getAll();
        
        // Get user departments
        $userDepartments = $this->getUserDepartments($id);
        
        $data = [
            'user' => $user, 
            'departments' => $departments, 
            'user_departments' => $userDepartments
        ];
        
        include __DIR__ . '/../views/users/edit.php';
    }
    
    public function downloadCredentials() {
        if (!isset($_SESSION['temp_password']) || !isset($_SESSION['new_user_email'])) {
            header('Location: /ergon/users?error=no_credentials');
            exit;
        }
        
        $email = $_SESSION['new_user_email'];
        $password = $_SESSION['temp_password'];
        
        $content = "ERGON Employee Login Credentials\n";
        $content .= "================================\n\n";
        $content .= "Email: {$email}\n";
        $content .= "Temporary Password: {$password}\n\n";
        $content .= "Instructions:\n";
        $content .= "1. Login at: " . $_SERVER['HTTP_HOST'] . "/ergon/login\n";
        $content .= "2. You will be required to reset your password on first login\n";
        $content .= "3. Choose a strong password (minimum 6 characters)\n\n";
        $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n";
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="credentials_' . str_replace('@', '_', $email) . '.txt"');
        echo $content;
        
        // Clear session data after download
        unset($_SESSION['temp_password']);
        unset($_SESSION['new_user_email']);
        exit;
    }
    
    public function resetUserPassword() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? 0;
        
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
            exit;
        }
        
        // Generate new temporary password
        $tempPassword = 'RST' . rand(1000, 9999) . chr(rand(65, 90));
        
        // Update user password
        require_once __DIR__ . '/../helpers/Security.php';
        $hashedPassword = Security::hashPassword($tempPassword);
        
        try {
            require_once __DIR__ . '/../../config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("
                UPDATE users 
                SET password = ?, temp_password = ?, is_first_login = TRUE, password_reset_required = TRUE 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$hashedPassword, $tempPassword, $userId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'temp_password' => $tempPassword,
                    'message' => 'Password reset successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to reset password']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        
        exit;
    }
    
    private function downloadNewCredentials($userData, $password) {
        require_once __DIR__ . '/../helpers/EmployeeHelper.php';
        require_once __DIR__ . '/../../config/database.php';
        
        $database = new Database();
        $conn = $database->getConnection();
        $stmt = $conn->prepare("SELECT company_name FROM settings LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch();
        
        $userData['company_name'] = $settings['company_name'] ?? 'Company';
        $content = EmployeeHelper::createCredentialsPDF($userData, $password);
        
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="credentials_' . $userData['employee_id'] . '.html"');
        echo $content;
        
        unset($_SESSION['temp_password']);
        exit;
    }
    
    private function getUserDepartments($userId) {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            $stmt = $conn->prepare("SELECT department_id FROM user_departments WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return $result ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
}
?>