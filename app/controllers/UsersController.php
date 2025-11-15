<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';

class UsersController extends Controller {
    
    public function index() {
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $userModel = new User();
            $users = $userModel->getAll();
            
            // Debug logging
            error_log('Users index: Retrieved ' . count($users) . ' users');
            foreach ($users as $user) {
                error_log("User {$user['id']}: {$user['name']} - Status: {$user['status']}");
            }
            
            $data = [
                'users' => $users,
                'active_page' => 'users'
            ];
            
            $this->view('users/index', $data);
        } catch (Exception $e) {
            error_log('Users index error: ' . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }
    
    public function viewUser($id) {
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure employee_id column exists and generate IDs if needed
            $this->ensureUserColumns($db);
            
            // Fetch user with department name
            $stmt = $db->prepare("SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                header('Location: /ergon/users?error=user_not_found');
                exit;
            }
            
            // Fetch user documents
            $documents = $this->getUserDocuments($user['id']);
            
            $data = [
                'user' => $user, 
                'documents' => $documents,
                'active_page' => 'users'
            ];
            $this->view('users/view', $data);
        } catch (Exception $e) {
            error_log('viewUser error: ' . $e->getMessage());
            // Fallback to model method
            $userModel = new User();
            $user = $userModel->getById($id);
            
            if (!$user) {
                header('Location: /ergon/users?error=user_not_found');
                exit;
            }
            
            // Fetch user documents
            $documents = $this->getUserDocuments($user['id']);
            
            $data = [
                'user' => $user, 
                'documents' => $documents,
                'active_page' => 'users'
            ];
            $this->view('users/view', $data);
        }
    }
    
    public function edit($id) {
        $this->ensureDepartmentsTable();
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                // Ensure all required columns exist
                $this->ensureUserColumns($db);
                
                $updateData = [
                    'name' => trim($_POST['name'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
                    'gender' => $_POST['gender'] ?? null,
                    'address' => trim($_POST['address'] ?? ''),
                    'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
                    'joining_date' => !empty($_POST['joining_date']) ? $_POST['joining_date'] : null,
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
                    $this->handleDocumentUploads($id);
                    header('Location: /ergon/users/view/' . $id . '?success=User updated successfully');
                } else {
                    header('Location: /ergon/users/view/' . $id . '?error=Failed to update user');
                }
                exit;
            } catch (Exception $e) {
                error_log('User edit error: ' . $e->getMessage());
                header('Location: /ergon/users/view/' . $id . '?error=Update failed');
                exit;
            }
        }
        
        $userModel = new User();
        $user = $userModel->getById($id);
        if (!$user) {
            header('Location: /ergon/users?error=user_not_found');
            exit;
        }
        
        // Fetch departments for dropdown
        require_once __DIR__ . '/../models/Department.php';
        $departmentModel = new Department();
        $departments = $departmentModel->getAll();
        
        $data = [
            'user' => $user, 
            'active_page' => 'users',
            'departments' => $departments
        ];
        $this->view('users/edit', $data);
    }
    
    public function create() {
        $this->ensureDepartmentsTable();
        
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
                
                // Handle department - get department ID from form
                $departmentId = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
                
                // Ensure users table has department_id column
                $this->ensureUserColumns($db);
                
                $stmt = $db->prepare("INSERT INTO users (employee_id, name, email, password, phone, role, status, department_id, designation, joining_date, salary, date_of_birth, gender, address, emergency_contact, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([
                    $employeeId,
                    trim($_POST['name'] ?? ''),
                    trim($_POST['email'] ?? ''),
                    $hashedPassword,
                    trim($_POST['phone'] ?? ''),
                    $_POST['role'] ?? 'user',
                    $departmentId,
                    trim($_POST['designation'] ?? ''),
                    !empty($_POST['joining_date']) ? $_POST['joining_date'] : null,
                    !empty($_POST['salary']) ? floatval($_POST['salary']) : null,
                    !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
                    $_POST['gender'] ?? null,
                    trim($_POST['address'] ?? ''),
                    trim($_POST['emergency_contact'] ?? '')
                ]);
                
                error_log('User creation data: ' . json_encode($_POST));
                
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
                    $_SESSION['old_data'] = $_POST;
                    header('Location: /ergon/users/create?error=Failed to create user');
                    exit;
                }
            } catch (Exception $e) {
                error_log('User creation error: ' . $e->getMessage());
                $_SESSION['old_data'] = $_POST;
                header('Location: /ergon/users/create?error=Failed to create user');
                exit;
            }
        }
        
        // Fetch departments for dropdown
        $departments = [];
        try {
            require_once __DIR__ . '/../models/Department.php';
            $departmentModel = new Department();
            $departments = $departmentModel->getAll();
            if (empty($departments)) {
                // Fallback: fetch directly from database
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $stmt = $db->query("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
                $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log('Department fetch error in create: ' . $e->getMessage());
            $departments = [];
        }
        
        $data = [
            'active_page' => 'users',
            'departments' => $departments,
            'old_data' => $_SESSION['old_data'] ?? []
        ];
        
        // Clear old data after use
        unset($_SESSION['old_data']);
        
        $this->view('users/create', $data);
    }
    
    public function resetPassword() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = $_POST['user_id'] ?? null;
                if (!$userId) {
                    echo json_encode(['success' => false, 'message' => 'User ID required']);
                    exit;
                }
                
                $tempPassword = 'RST' . rand(1000, 9999);
                
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $result = $stmt->execute([$hashedPassword, $userId]);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Password reset successfully',
                            'credentials' => [
                                'email' => $user['email'],
                                'password' => $tempPassword
                            ]
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Reset failed: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        }
        exit;
    }
    
    public function downloadCredentials() {
        
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
    
    public function inactive($id) {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // First check if user exists and current status
            $checkStmt = $db->prepare("SELECT id, status FROM users WHERE id = ?");
            $checkStmt->execute([$id]);
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $this->invalidateUserSessions($id);
                error_log("User {$id} status changed from '{$user['status']}' to 'inactive'");
            }
            
            echo json_encode(['success' => $result, 'message' => $result ? 'User deactivated successfully' : 'Deactivation failed']);
        } catch (Exception $e) {
            error_log('User inactive error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Deactivation failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function delete($id) {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // First check if user exists and current status
            $checkStmt = $db->prepare("SELECT id, status FROM users WHERE id = ?");
            $checkStmt->execute([$id]);
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE users SET status = 'removed', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $this->invalidateUserSessions($id);
                error_log("User {$id} status changed from '{$user['status']}' to 'removed'");
            }
            
            echo json_encode(['success' => $result, 'message' => $result ? 'User removed successfully' : 'Removal failed']);
        } catch (Exception $e) {
            error_log('User delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Removal failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function activate($id) {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // First check if user exists and current status
            $checkStmt = $db->prepare("SELECT id, status FROM users WHERE id = ?");
            $checkStmt->execute([$id]);
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                error_log("User {$id} status changed from '{$user['status']}' to 'active'");
            }
            
            echo json_encode(['success' => $result, 'message' => $result ? 'User activated successfully' : 'Activation failed']);
        } catch (Exception $e) {
            error_log('User activate error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Activation failed: ' . $e->getMessage()]);
        }
        exit;
    }
    

    
    public function export() {
        
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
    
    private function ensureUserColumns($db) {
        try {
            $stmt = $db->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredColumns = [
                'employee_id' => 'VARCHAR(20) UNIQUE',
                'phone' => 'VARCHAR(20)',
                'date_of_birth' => 'DATE',
                'gender' => 'ENUM(\'male\', \'female\', \'other\')',
                'address' => 'TEXT',
                'emergency_contact' => 'VARCHAR(255)',
                'joining_date' => 'DATE',
                'designation' => 'VARCHAR(255)',
                'salary' => 'DECIMAL(10,2)',
                'department_id' => 'INT DEFAULT NULL'
            ];
            
            foreach ($requiredColumns as $column => $type) {
                if (!in_array($column, $columns)) {
                    $db->exec("ALTER TABLE users ADD COLUMN $column $type");
                    error_log("Added column $column to users table");
                }
            }
            
            // Generate employee IDs for existing users without them
            $this->generateEmployeeIds($db);
        } catch (Exception $e) {
            error_log('ensureUserColumns error: ' . $e->getMessage());
        }
    }
    
    private function generateEmployeeIds($db) {
        try {
            // Get users without employee IDs
            $stmt = $db->query("SELECT id FROM users WHERE employee_id IS NULL OR employee_id = ''");
            $usersWithoutIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($usersWithoutIds)) return;
            
            // Get the highest existing employee ID number
            $stmt = $db->query("SELECT employee_id FROM users WHERE employee_id LIKE 'EMP%' ORDER BY employee_id DESC LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $nextNum = 1;
            if ($result && $result['employee_id']) {
                $lastNum = intval(substr($result['employee_id'], 3));
                $nextNum = $lastNum + 1;
            }
            
            // Generate IDs for users without them
            foreach ($usersWithoutIds as $user) {
                $employeeId = 'EMP' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                $stmt = $db->prepare("UPDATE users SET employee_id = ? WHERE id = ?");
                $stmt->execute([$employeeId, $user['id']]);
                $nextNum++;
            }
            
            error_log('Generated employee IDs for ' . count($usersWithoutIds) . ' users');
        } catch (Exception $e) {
            error_log('generateEmployeeIds error: ' . $e->getMessage());
        }
    }
    
    private function getUserDocuments($userId) {
        $documents = [];
        $uploadDir = __DIR__ . '/../../public/uploads/users/' . $userId;
        
        if (is_dir($uploadDir)) {
            $files = scandir($uploadDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && is_file($uploadDir . '/' . $file)) {
                    $filePath = $uploadDir . '/' . $file;
                    $documents[] = [
                        'name' => $this->getDocumentDisplayName($file),
                        'filename' => $file,
                        'size' => $this->formatFileSize(filesize($filePath)),
                        'type' => pathinfo($file, PATHINFO_EXTENSION)
                    ];
                }
            }
        }
        
        return $documents;
    }
    
    private function getDocumentDisplayName($filename) {
        $docTypes = [
            'passport_photo' => 'Passport Photo',
            'aadhar' => 'Aadhar Card',
            'pan' => 'PAN Card',
            'resume' => 'Resume',
            'education_docs' => 'Education Documents',
            'experience_certs' => 'Experience Certificates'
        ];
        
        foreach ($docTypes as $type => $displayName) {
            if (strpos($filename, $type) === 0) {
                return $displayName;
            }
        }
        
        return ucfirst(str_replace('_', ' ', pathinfo($filename, PATHINFO_FILENAME)));
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
    
    private function invalidateUserSessions($userId) {
        try {
            // Create user_sessions table if it doesn't exist
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $db->exec("CREATE TABLE IF NOT EXISTS user_sessions (
                id VARCHAR(128) PRIMARY KEY,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id)
            )");
            
            // Remove all sessions for this user
            $stmt = $db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
        } catch (Exception $e) {
            error_log('Session invalidation error: ' . $e->getMessage());
        }
    }
    
    private function ensureDepartmentsTable() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Create departments table if it doesn't exist
            $stmt = $db->query("SHOW TABLES LIKE 'departments'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE departments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL UNIQUE,
                    description TEXT,
                    head_id INT NULL,
                    status VARCHAR(20) DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                $db->exec($sql);
                
                // Insert default departments
                $defaultDepts = [
                    ['Human Resources', 'Manages employee relations and policies'],
                    ['Information Technology', 'Handles technology infrastructure and support'],
                    ['Finance', 'Manages financial operations and accounting'],
                    ['Marketing', 'Handles marketing and promotional activities'],
                    ['Operations', 'Manages day-to-day business operations'],
                    ['Sales', 'Handles sales and customer acquisition']
                ];
                
                $stmt = $db->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
                foreach ($defaultDepts as $dept) {
                    $stmt->execute($dept);
                }
                
                error_log('Departments table created with default data');
            }
        } catch (Exception $e) {
            error_log('ensureDepartmentsTable error: ' . $e->getMessage());
        }
    }
}
?>