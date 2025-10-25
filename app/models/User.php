<?php
/**
 * User Model
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../../config/database.php';

class User {
    private $conn;
    private $table = 'users';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Authenticate user
     */
    public function authenticate($email, $password) {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            
            $stmt = $this->conn->prepare("
                SELECT id, name, email, password, role, status, is_first_login, password_reset_required 
                FROM {$this->table} 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
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
    
    /**
     * Reset password after first login
     */
    public function resetPassword($userId, $newPassword) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $this->conn->prepare("
                UPDATE {$this->table} 
                SET password = ?, is_first_login = FALSE, password_reset_required = FALSE, temp_password = NULL 
                WHERE id = ?
            ");
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO {$this->table} (name, email, password, role, phone, department) 
                VALUES (?, ?, ?, ?, ?, ?)
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
    
    /**
     * Create enhanced user with employee ID
     */
    public function createEnhanced($data) {
        try {
            $email = trim($data['email'] ?? '');
            $phone = trim($data['phone'] ?? '');
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            if (!preg_match('/^[0-9]{10}$/', $phone)) {
                throw new Exception('Phone number must be exactly 10 digits');
            }
            
            if ($this->emailExists($email)) {
                throw new Exception('Email already exists');
            }
            
            $employeeId = $this->generateEmployeeId();
            $tempPassword = $this->generateTempPassword();
            $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
            
            $stmt = $this->conn->prepare("
                INSERT INTO {$this->table} (employee_id, name, email, password, role, phone, department, temp_password, is_first_login, password_reset_required) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE, TRUE)
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
            return false;
        }
    }
    
    /**
     * Get user by ID
     */
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
    
    /**
     * Get all users with pagination
     */
    public function getAll($page = 1, $limit = 20, $role = null) {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = $role ? "WHERE role = ?" : "";
            $params = $role ? [$role, $limit, $offset] : [$limit, $offset];
            
            $stmt = $this->conn->prepare("
                SELECT id, name, email, role, phone, department, status, created_at 
                FROM {$this->table} 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update user
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                if ($key !== 'id' && $key !== 'password') {
                    $fields[] = "{$key} = ?";
                    $params[] = $value;
                }
            }
            
            if (isset($data['password'])) {
                $fields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
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
    
    /**
     * Delete user (soft delete)
     */
    public function delete($id) {
        try {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET status = 'inactive' WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("User delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        try {
            $whereClause = $excludeId ? "WHERE email = ? AND id != ?" : "WHERE email = ?";
            $params = $excludeId ? [$email, $excludeId] : [$email];
            
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM {$this->table} {$whereClause}");
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Email check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user statistics
     */
    public function getStats() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count
                FROM {$this->table}
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("User stats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update last login
     */
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
    
    /**
     * Generate temporary password
     */
    private function generateTempPassword() {
        return 'EMP' . rand(1000, 9999) . chr(rand(65, 90));
    }
    
    /**
     * Generate employee ID
     */
    private function generateEmployeeId() {
        try {
            $stmt = $this->conn->prepare("SELECT company_name FROM settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch();
            $companyName = $settings['company_name'] ?? 'Company';
            
            $prefix = $this->getCompanyPrefix($companyName);
            
            $stmt = $this->conn->prepare("SELECT COUNT(*) + 1 as next_num FROM users WHERE employee_id IS NOT NULL");
            $stmt->execute();
            $result = $stmt->fetch();
            $nextNum = str_pad($result['next_num'], 3, '0', STR_PAD_LEFT);
            
            return $prefix . $nextNum;
        } catch (Exception $e) {
            return 'EMP' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Get company prefix from company name
     */
    private function getCompanyPrefix($companyName) {
        $words = explode(' ', strtoupper($companyName));
        $prefix = '';
        
        foreach ($words as $word) {
            if (in_array($word, ['THE', 'AND', 'OF', 'FOR', 'TO', 'IN', 'ON', 'AT', 'BY'])) {
                continue;
            }
            
            $cleanWord = preg_replace('/[^A-Z0-9]/', '', $word);
            
            if (strlen($cleanWord) >= 2) {
                $prefix .= substr($cleanWord, 0, 2);
            } elseif (strlen($cleanWord) == 1) {
                $prefix .= $cleanWord;
            }
        }
        
        if (empty($prefix)) {
            $prefix = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($companyName)), 0, 2);
        }
        
        return $prefix ?: 'CO';
    }
}
?>