<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';

class UsersController extends Controller {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $current_role = $_SESSION['role'] ?? 'user';
        
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
            'current_role' => $current_role,
            'active_page' => 'users'
        ];
        
        $this->view('users/index', $data);
    }
    
    public function create() {
        $error = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $result = $this->userModel->createEnhanced($_POST);
                if ($result && isset($result['temp_password'])) {
                    $_SESSION['new_user_credentials'] = [
                        'employee_id' => $result['employee_id'],
                        'email' => $_POST['email'],
                        'temp_password' => $result['temp_password']
                    ];
                    header('Location: /ergon_clean/public/users?success=created');
                    exit;
                } else {
                    $error = 'Failed to create user. Please try again.';
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        require_once __DIR__ . '/../models/Department.php';
        $departmentModel = new Department();
        $departments = $departmentModel->getAll();
        
        $data = [
            'departments' => $departments,
            'error' => $error,
            'old_data' => $_POST ?? [],
            'active_page' => 'users'
        ];
        
        $this->view('users/create', $data);
    }
    
    public function edit($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleFileUploads($id);
            $this->updateUserData($id, $_POST);
            header('Location: /ergon_clean/public/users?success=updated');
            exit;
        }
        
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $user = [
                'id' => $id, 'name' => '', 'email' => '', 'phone' => '',
                'role' => 'user', 'status' => 'active', 'employee_id' => '',
                'department' => '', 'designation' => '', 'joining_date' => '',
                'salary' => '', 'date_of_birth' => '', 'gender' => '',
                'address' => '', 'emergency_contact' => ''
            ];
        }
        
        $data = ['user' => $user, 'active_page' => 'users'];
        $this->view('users/edit', $data);
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
        
        $tempPassword = 'RST' . rand(1000, 9999) . chr(rand(65, 90));
        
        require_once __DIR__ . '/../helpers/Security.php';
        $hashedPassword = Security::hashPassword($tempPassword);
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'User not found']);
                exit;
            }
            
            $stmt = $db->prepare("
                UPDATE users 
                SET password = ?, temp_password = ?, is_first_login = TRUE, password_reset_required = TRUE 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$hashedPassword, $tempPassword, $userId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'email' => $user['email'],
                    'temp_password' => $tempPassword,
                    'message' => 'Password reset successfully. Login with EMAIL, not username.'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to reset password']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        
        exit;
    }
    
    public function viewUser($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $user = $this->userModel->getById($id);
        if (!$user) {
            header('Location: /ergon_clean/public/users?error=user_not_found');
            exit;
        }
        
        $documents = $this->getUserDocuments($id);
        
        $data = ['user' => $user, 'documents' => $documents, 'active_page' => 'users'];
        $this->view('users/view', $data);
    }
    
    public function inactive($id) {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'User marked as inactive']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update user status']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        
        exit;
    }
    
    public function delete($id) {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'User permanently deleted']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        
        exit;
    }
    
    private function updateUserData($id, $data) {
        $db = Database::connect();
        
        $departments = isset($data['departments']) ? implode(',', $data['departments']) : '';
        
        $sql = "UPDATE users SET 
                name = ?, email = ?, phone = ?, role = ?, status = ?,
                department = ?, designation = ?, joining_date = ?, salary = ?,
                date_of_birth = ?, gender = ?, address = ?, emergency_contact = ?
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['name'] ?? '',
            $data['email'] ?? '',
            $data['phone'] ?? '',
            $data['role'] ?? 'user',
            $data['status'] ?? 'active',
            $departments,
            $data['designation'] ?? '',
            $data['joining_date'] ?? null,
            $data['salary'] ?? null,
            $data['date_of_birth'] ?? null,
            $data['gender'] ?? null,
            $data['address'] ?? '',
            $data['emergency_contact'] ?? '',
            $id
        ]);
    }
    
    private function getUserDocuments($userId) {
        $uploadDir = __DIR__ . '/../../storage/user_documents/' . $userId . '/';
        $documents = [];
        
        if (is_dir($uploadDir)) {
            $files = scandir($uploadDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $filePath = $uploadDir . $file;
                    $documents[] = [
                        'filename' => $file,
                        'name' => $this->getDocumentDisplayName($file),
                        'size' => $this->formatFileSize(filesize($filePath))
                    ];
                }
            }
        }
        
        return $documents;
    }
    
    private function getDocumentDisplayName($filename) {
        $parts = explode('_', $filename, 3);
        if (count($parts) >= 2) {
            $type = str_replace('_', ' ', ucfirst($parts[0]));
            return $type;
        }
        return $filename;
    }
    
    private function formatFileSize($bytes) {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    private function handleFileUploads($userId) {
        $uploadDir = __DIR__ . '/../../storage/user_documents/' . $userId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileFields = ['profile_photo', 'pan_card', 'aadhar_card', 'resume', 'passport', 'driving_license'];
        
        foreach ($fileFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $fileName = $field . '_' . time() . '_' . $_FILES[$field]['name'];
                move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $fileName);
            }
        }
    }
}
?>