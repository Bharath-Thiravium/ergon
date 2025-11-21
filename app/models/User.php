<?php
/**
 * User Model
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table = 'users';
    
    public function __construct() {
        $this->conn = Database::connect();
    }
    
    public function authenticate($email, $password) {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            
            $stmt = $this->conn->prepare("
                SELECT id, name, email, password, role, status, is_first_login, password_reset_required 
                FROM {$this->table} 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $this->updateLastLogin($user['id']);
                unset($user['password']);
                return $user;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    public function resetPassword($userId, $newPassword) {
        try {
            // Enhanced password validation
            if (strlen($newPassword) < 8 || 
                !preg_match('/[A-Z]/', $newPassword) ||
                !preg_match('/[a-z]/', $newPassword) ||
                !preg_match('/[0-9]/', $newPassword) ||
                !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
                return false;
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Log password change for audit
            $this->logPasswordChange($userId);
            
            $stmt = $this->conn->prepare("
                UPDATE {$this->table} 
                SET password = ?, is_first_login = FALSE, password_reset_required = FALSE, 
                    temp_password = NULL, password_changed_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return false;
        }
    }
    
    public function initiatePasswordReset($email) {
        try {
            $user = $this->getUserByEmail($email);
            if (!$user) {
                return false; // Don't reveal if email exists
            }
            
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $this->conn->prepare("
                UPDATE {$this->table} 
                SET reset_token = ?, reset_token_expires = ? 
                WHERE email = ?
            ");
            $stmt->execute([$token, $expires, $email]);
            
            return $token;
        } catch (Exception $e) {
            error_log("Password reset initiation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserByEmail($email) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get user by email error: " . $e->getMessage());
            return false;
        }
    }
    

    
    private function logPasswordChange($userId) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO password_change_log (user_id, changed_at, ip_address) 
                VALUES (?, NOW(), ?)
            ");
            $stmt->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
        } catch (Exception $e) {
            error_log("Password change logging error: " . $e->getMessage());
        }
    }
    
    public function create($data) {
        try {
            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                return false;
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            
            if ($this->emailExists($data['email'])) {
                return false;
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO {$this->table} (name, email, password, role, phone, department, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            
            return $stmt->execute([
                $data['name'],
                $data['email'],
                $hashedPassword,
                $data['role'] ?? 'user',
                $data['phone'] ?? null,
                $data['department'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function createEnhanced($data) {
        try {
            $email = trim($data['email'] ?? '');
            $phone = trim($data['phone'] ?? '');
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
                throw new Exception('Phone number must be exactly 10 digits');
            }
            
            if ($this->emailExists($email)) {
                throw new Exception('Email already exists');
            }
            
            $employeeId = $this->generateEmployeeId();
            $tempPassword = $this->generateTempPassword();
            $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
            
            $stmt = $this->conn->prepare("
                INSERT INTO {$this->table} (employee_id, name, email, password, role, phone, department, temp_password, is_first_login, password_reset_required, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE, TRUE, 'active')
            ");
            
            $result = $stmt->execute([
                $employeeId,
                $data['name'],
                $email,
                $hashedPassword,
                $data['role'] ?? 'user',
                $phone,
                $data['department'] ?? null,
                $tempPassword
            ]);
            
            if ($result) {
                return [
                    'user_id' => $this->conn->lastInsertId(),
                    'employee_id' => $employeeId,
                    'temp_password' => $tempPassword
                ];
            }
            return false;
        } catch (Exception $e) {
            error_log("Enhanced user creation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAll($page = 1, $limit = 20, $role = null) {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = $role ? "WHERE role = ? AND status != 'deleted'" : "WHERE status != 'deleted'";
            $params = $role ? [$role, $limit, $offset] : [$limit, $offset];
            
            $stmt = $this->conn->prepare("
                SELECT id, name, email, role, phone, department, status, created_at, employee_id, last_login 
                FROM {$this->table} 
                {$whereClause}
                ORDER BY 
                    CASE 
                        WHEN status = 'active' THEN 1
                        WHEN status = 'inactive' THEN 2
                        WHEN status = 'suspended' THEN 3
                        WHEN status = 'terminated' THEN 4
                        ELSE 5
                    END,
                    created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('Retrieved ' . count($users) . ' users (all statuses except deleted)');
            return $users;
        } catch (Exception $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUsersByRole($roles) {
        try {
            if (empty($roles) || !is_array($roles)) {
                return [];
            }
            
            $placeholders = str_repeat('?,', count($roles) - 1) . '?';
            $stmt = $this->conn->prepare("SELECT id, name, email, role, phone, department, status, created_at FROM {$this->table} WHERE role IN ($placeholders) ORDER BY name");
            $stmt->execute($roles);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getUsersByRole error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getStatsByRole($role) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
                FROM {$this->table} WHERE role = ?
            ");
            $stmt->execute([$role]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getStatsByRole error: ' . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'inactive' => 0];
        }
    }
    
    public function getAllUsers() {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, email, role, department, status 
                FROM {$this->table} 
                WHERE status != 'deleted' 
                ORDER BY name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getAllUsers error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByDepartment($departmentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, email, role, department, status 
                FROM {$this->table} 
                WHERE department = ? AND status = 'active' 
                ORDER BY name
            ");
            $stmt->execute([$departmentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getByDepartment error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function update($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['name', 'email', 'role', 'phone', 'department', 'status'];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "{$key} = ?";
                    $params[] = $value;
                }
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                $fields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $params[] = $id;
            
            $stmt = $this->conn->prepare("
                UPDATE {$this->table} 
                SET " . implode(', ', $fields) . " 
                WHERE id = ?
            ");
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("User update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET status = 'removed' WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("User delete error: " . $e->getMessage());
            return false;
        }
    }
    
    public function emailExists($email, $excludeId = null) {
        try {
            $whereClause = $excludeId ? "WHERE email = ? AND id != ?" : "WHERE email = ?";
            $params = $excludeId ? [$email, $excludeId] : [$email];
            
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM {$this->table} {$whereClause}");
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Email check error: " . $e->getMessage());
            return false;
        }
    }
    
    private function updateLastLogin($id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE {$this->table} 
                SET last_login = NOW(), last_ip = ? 
                WHERE id = ?
            ");
            $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $id]);
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    private function generateTempPassword() {
        return 'EMP' . rand(1000, 9999) . chr(rand(65, 90));
    }
    
    private function generateEmployeeId() {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) + 1 as next_num FROM {$this->table} WHERE employee_id IS NOT NULL");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = str_pad($result['next_num'], 3, '0', STR_PAD_LEFT);
            
            return 'EMP' . $nextNum;
        } catch (Exception $e) {
            return 'EMP' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
    }
}
?>
