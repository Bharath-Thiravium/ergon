<?php
/**
 * User Model
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/Security.php';

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
            $stmt = $this->conn->prepare("
                SELECT id, name, email, password, role, status, is_first_login, password_reset_required 
                FROM {$this->table} 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && Security::verifyPassword($password, $user['password'])) {
                // Update last login
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
            $hashedPassword = Security::hashPassword($newPassword);
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
            
            $hashedPassword = Security::hashPassword($data['password']);
            
            return $stmt->execute([
                $data['name'],
                $data['email'],
                $hashedPassword,
                $data['role'] ?? ROLE_USER,
                $data['phone'] ?? null,
                $data['department'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create enhanced user with departments
     */
    public function createEnhanced($data) {
        try {
            // Server-side validation
            $email = trim($data['email'] ?? '');
            $phone = trim($data['phone'] ?? '');
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            // Validate phone number (10 digits)
            if (!preg_match('/^[0-9]{10}$/', $phone)) {
                throw new Exception('Phone number must be exactly 10 digits');
            }
            
            // Check if email already exists
            if ($this->emailExists($email)) {
                throw new Exception('Email already exists');
            }
            
            // Generate employee ID and temporary password
            $employeeId = $this->generateEmployeeId();
            $tempPassword = $this->generateTempPassword();
            $hashedPassword = Security::hashPassword($tempPassword);
            
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
     * Generate temporary password
     */
    private function generateTempPassword() {
        return 'EMP' . rand(1000, 9999) . chr(rand(65, 90));
    }
    
    /**
     * Generate employee ID based on company name
     */
    private function generateEmployeeId() {
        // Get company name from settings
        $stmt = $this->conn->prepare("SELECT company_name FROM settings LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch();
        $companyName = $settings['company_name'] ?? 'Company';
        
        // Generate prefix based on company name logic
        $prefix = $this->getCompanyPrefix($companyName);
        
        // Get next employee number
        $stmt = $this->conn->prepare("SELECT COUNT(*) + 1 as next_num FROM users WHERE employee_id IS NOT NULL");
        $stmt->execute();
        $result = $stmt->fetch();
        $nextNum = str_pad($result['next_num'], 3, '0', STR_PAD_LEFT);
        
        return $prefix . $nextNum;
    }
    
    /**
     * Get company prefix from company name
     */
    private function getCompanyPrefix($companyName) {
        $words = explode(' ', strtoupper($companyName));
        $prefix = '';
        
        foreach ($words as $word) {
            // Skip common words
            if (in_array($word, ['THE', 'AND', 'OF', 'FOR', 'TO', 'IN', 'ON', 'AT', 'BY'])) {
                continue;
            }
            
            // Extract letters and numbers only
            $cleanWord = preg_replace('/[^A-Z0-9]/', '', $word);
            
            if (strlen($cleanWord) >= 2) {
                $prefix .= substr($cleanWord, 0, 2);
            } elseif (strlen($cleanWord) == 1) {
                $prefix .= $cleanWord;
            }
        }
        
        // Fallback to first 2 characters if no valid prefix
        if (empty($prefix)) {
            $prefix = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($companyName)), 0, 2);
        }
        
        return $prefix;
    }
    
    /**
     * Update enhanced user
     */
    public function updateEnhanced($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = [
                'name', 'email', 'phone', 'department', 'status', 'role',
                'employee_id', 'designation', 'joining_date', 'salary',
                'date_of_birth', 'gender', 'address', 'emergency_contact'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
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
            error_log("Enhanced user update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM {$this->table} WHERE id = ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug logging
            error_log("getById($id) result: " . ($result ? 'FOUND' : 'NOT FOUND'));
            if ($result) {
                error_log("User data: " . json_encode($result));
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all users with pagination
     */
    public function getAll($page = 1, $limit = RECORDS_PER_PAGE, $role = null) {
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
                $params[] = Security::hashPassword($data['password']);
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
            $stmt = $this->conn->prepare("
                UPDATE {$this->table} 
                SET status = 'inactive' 
                WHERE id = ?
            ");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("User delete error: " . $e->getMessage());
            return false;
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
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        try {
            $whereClause = $excludeId ? "WHERE email = ? AND id != ?" : "WHERE email = ?";
            $params = $excludeId ? [$email, $excludeId] : [$email];
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM {$this->table} 
                {$whereClause}
            ");
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Email check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get total users count
     */
    public function getTotalUsers() {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM {$this->table}");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'];
        } catch (Exception $e) {
            error_log("Get total users error: " . $e->getMessage());
            return 0;
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
     * Get users by role
     */
    public function getUsersByRole($roles) {
        try {
            $placeholders = str_repeat('?,', count($roles) - 1) . '?';
            $stmt = $this->conn->prepare("
                SELECT id, name, email, role, phone, department, status, created_at 
                FROM {$this->table} 
                WHERE role IN ($placeholders) 
                ORDER BY created_at DESC
            ");
            $stmt->execute($roles);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get users by role error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get statistics for specific role
     */
    public function getStatsByRole($role) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
                    0 as admin_count,
                    COUNT(*) as user_count
                FROM {$this->table}
                WHERE role = ?
            ");
            $stmt->execute([$role]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get stats by role error: " . $e->getMessage());
            return [];
        }
    }
}
?>