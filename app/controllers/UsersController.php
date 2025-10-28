<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';

class UsersController extends Controller {
    
    public function index() {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $userModel = new User();
            $users = $userModel->getAll();
            
            $data = [
                'users' => $users,
                'active_page' => 'users'
            ];
            
            $this->view('users/index', $data);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }
    
    public function viewUser($id) {
        session_start();
        $userModel = new User();
        $user = $userModel->getById($id);
        
        if (!$user) {
            header('Location: /ergon/users?error=user_not_found');
            exit;
        }
        
        $data = ['user' => $user, 'active_page' => 'users'];
        $this->view('users/view', $data);
    }
    
    public function edit($id) {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $updateData = [
                    'name' => trim($_POST['name'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'date_of_birth' => $_POST['date_of_birth'] ?? null,
                    'gender' => $_POST['gender'] ?? null,
                    'address' => trim($_POST['address'] ?? ''),
                    'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
                    'joining_date' => $_POST['joining_date'] ?? null,
                    'designation' => trim($_POST['designation'] ?? ''),
                    'salary' => !empty($_POST['salary']) ? floatval($_POST['salary']) : null,
                    'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
                    'role' => $_POST['role'] ?? 'user',
                    'status' => $_POST['status'] ?? 'active'
                ];
                
                $sql = "UPDATE users SET 
                        name = ?, email = ?, phone = ?, date_of_birth = ?, gender = ?, 
                        address = ?, emergency_contact = ?, joining_date = ?, designation = ?, 
                        salary = ?, department_id = ?, role = ?, status = ?, updated_at = NOW() 
                        WHERE id = ?";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    $updateData['name'], $updateData['email'], $updateData['phone'],
                    $updateData['date_of_birth'], $updateData['gender'], $updateData['address'],
                    $updateData['emergency_contact'], $updateData['joining_date'], $updateData['designation'],
                    $updateData['salary'], $updateData['department_id'], $updateData['role'],
                    $updateData['status'], $id
                ]);
                
                if ($result) {
                    // Handle document uploads
                    $this->handleDocumentUploads($id);
                    
                    header('Location: /ergon/users/view/' . $id . '?success=User updated successfully');
                } else {
                    header('Location: /ergon/users/view/' . $id . '?error=Failed to update user');
                }
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/users/view/' . $id . '?error=Update failed: ' . urlencode($e->getMessage()));
                exit;
            }
        }
        
        $userModel = new User();
        $user = $userModel->getById($id);
        if (!$user) {
            header('Location: /ergon/users?error=user_not_found');
            exit;
        }
        
        $data = ['user' => $user, 'active_page' => 'users'];
        $this->view('users/edit', $data);
    }
    
    public function create() {
        session_start();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                // Auto-generate employee ID if not provided
                $employeeId = $_POST['employee_id'] ?? '';
                if (empty($employeeId)) {
                    $stmt = $db->prepare("SELECT employee_id FROM users WHERE employee_id LIKE 'EMP%' ORDER BY employee_id DESC LIMIT 1");
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result && $result['employee_id']) {
                        $lastNum = intval(substr($result['employee_id'], 3));
                        $nextNum = $lastNum + 1;
                    } else {
                        $nextNum = 1;
                    }
                    
                    $employeeId = 'EMP' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                }
                
                // Generate temporary password
                $tempPassword = 'PWD' . rand(1000, 9999);
                $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
                
                $stmt = $db->prepare("INSERT INTO users (employee_id, name, email, password, phone, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
                $result = $stmt->execute([
                    $employeeId,
                    trim($_POST['name'] ?? ''),
                    trim($_POST['email'] ?? ''),
                    $hashedPassword,
                    trim($_POST['phone'] ?? ''),
                    $_POST['role'] ?? 'user'
                ]);
                
                if ($result) {
                    $userId = $db->lastInsertId();
                    
                    // Handle document uploads
                    $this->handleDocumentUploads($userId);
                    
                    $_SESSION['new_credentials'] = [
                        'email' => $_POST['email'],
                        'password' => $tempPassword,
                        'employee_id' => $employeeId
                    ];
                    header('Location: /ergon/users?success=User created successfully');
                    exit;
                } else {
                    header('Location: /ergon/users/create?error=Failed to create user');
                    exit;
                }
            } catch (Exception $e) {
                error_log('User creation error: ' . $e->getMessage());
                header('Location: /ergon/users/create?error=Failed to create user');
                exit;
            }
        }
        
        $this->view('users/create', ['active_page' => 'users']);
    }
    
    public function resetPassword() {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            http_response_code(401);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_POST['user_id'];
            $tempPassword = 'RST' . rand(1000, 9999);
            
            $userModel = new User();
            $user = $userModel->getById($userId);
            
            if ($user) {
                // Direct database update to avoid any model complications
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                
                $_SESSION['reset_credentials'] = [
                    'email' => $user['email'],
                    'password' => $tempPassword
                ];
                
                error_log("Password reset for user {$userId}: {$tempPassword}");
                
                // Test the password immediately
                if (password_verify($tempPassword, $hashedPassword)) {
                    error_log("✅ Password verification successful for user {$userId}");
                } else {
                    error_log("❌ Password verification failed for user {$userId}");
                }
            }
        }
        
        header('Location: /ergon/users');
        exit;
    }
    
    public function downloadCredentials() {
        session_start();
        
        $credentials = $_SESSION['new_credentials'] ?? $_SESSION['reset_credentials'] ?? null;
        
        if (!$credentials) {
            header('Location: /ergon/users');
            exit;
        }
        
        $content = "ERGON User Credentials\n\n";
        $content .= "Username: " . $credentials['email'] . "\n";
        $content .= "Password: " . $credentials['password'] . "\n\n";
        $content .= "Please change password on first login.";
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="user_credentials.txt"');
        echo $content;
        
        unset($_SESSION['new_credentials'], $_SESSION['reset_credentials']);
        exit;
    }
    
    public function delete($id) {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE users SET status = 'deleted', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        exit;
    }
    
    public function inactive($id) {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    header('Location: /ergon/users?success=User deactivated successfully');
                } else {
                    header('Location: /ergon/users?error=Failed to deactivate user');
                }
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/users?error=Deactivation failed');
                exit;
            }
        }
    }
    
    public function export() {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT name, email, phone, designation, department_id, role, status, created_at FROM users WHERE status != 'deleted' ORDER BY created_at DESC");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Name', 'Email', 'Phone', 'Designation', 'Department ID', 'Role', 'Status', 'Created Date']);
            
            foreach ($users as $user) {
                fputcsv($output, [
                    $user['name'],
                    $user['email'],
                    $user['phone'],
                    $user['designation'],
                    $user['department_id'],
                    $user['role'],
                    $user['status'],
                    date('Y-m-d H:i:s', strtotime($user['created_at']))
                ]);
            }
            
            fclose($output);
            exit;
        } catch (Exception $e) {
            header('Location: /ergon/users?error=Export failed');
            exit;
        }
    }
    
    private function handleDocumentUploads($userId) {
        $uploadDir = __DIR__ . '/../../public/uploads/users/' . $userId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $maxSize = 5 * 1024 * 1024; // 5MB
        $docTypes = ['passport_photo', 'aadhar', 'pan', 'resume', 'education_docs', 'experience_certs'];
        
        foreach ($docTypes as $docType) {
            if (!isset($_FILES[$docType])) continue;
            
            $files = $_FILES[$docType];
            
            // Handle single file upload
            if (!is_array($files['name'])) {
                $this->uploadSingleFile($files, $docType, $uploadDir, $maxSize);
            } else {
                // Handle multiple file upload
                for ($i = 0; $i < count($files['name']); $i++) {
                    if (empty($files['name'][$i])) continue;
                    
                    $singleFile = [
                        'name' => $files['name'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'size' => $files['size'][$i],
                        'error' => $files['error'][$i]
                    ];
                    
                    $this->uploadSingleFile($singleFile, $docType, $uploadDir, $maxSize, $i + 1);
                }
            }
        }
    }
    
    private function uploadSingleFile($file, $docType, $uploadDir, $maxSize, $index = null) {
        if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return;
        
        $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, $allowedTypes) || $file['size'] > $maxSize) return;
        
        $suffix = $index ? "_{$index}" : '';
        $safeName = $docType . $suffix . '.' . $fileExt;
        $targetPath = $uploadDir . '/' . $safeName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            error_log("Document uploaded: {$safeName} for user");
        }
    }
    
    public function downloadDocument($userId, $filename) {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            http_response_code(403);
            exit;
        }
        
        $filePath = __DIR__ . '/../../public/uploads/users/' . $userId . '/' . $filename;
        
        if (!file_exists($filePath)) {
            http_response_code(404);
            exit;
        }
        
        $mimeType = mime_content_type($filePath);
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    
    public function deleteDocument($userId, $filename) {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        $filePath = __DIR__ . '/../../public/uploads/users/' . $userId . '/' . $filename;
        
        if (file_exists($filePath)) {
            $success = unlink($filePath);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'message' => 'File not found']);
        }
        exit;
    }
}
?>