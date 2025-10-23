<?php
class EmployeeHelper {
    
    public static function generateEmployeeId($companyName) {
        try {
            // Generate prefix from company name
            $prefix = self::generatePrefix($companyName);
            
            // Get next employee number
            require_once __DIR__ . '/../../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            $stmt = $conn->prepare("SELECT COUNT(*) + 1 as next_id FROM users WHERE employee_id IS NOT NULL AND employee_id != ''");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextId = str_pad($result['next_id'], 3, '0', STR_PAD_LEFT);
            
            return $prefix . $nextId;
        } catch (Exception $e) {
            error_log('Employee ID generation error: ' . $e->getMessage());
            return 'EMP001'; // Fallback
        }
    }
    
    private static function generatePrefix($companyName) {
        $words = preg_split('/[\s\-_]+/', strtoupper($companyName));
        $prefix = '';
        
        foreach ($words as $word) {
            if (preg_match('/^\d+$/', $word)) continue; // Skip numbers
            if (strlen($word) >= 2) {
                $prefix .= substr($word, 0, 2);
            } else {
                $prefix .= $word;
            }
            if (strlen($prefix) >= 4) break;
        }
        
        return substr($prefix, 0, 4);
    }
    
    public static function generateCredentials($userData) {
        $password = self::generatePassword();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        return [
            'password' => $password,
            'hashed_password' => $hashedPassword
        ];
    }
    
    private static function generatePassword() {
        return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    }
    
    public static function createCredentialsPDF($userData, $password) {
        $content = "
        <h2>Employee Login Credentials</h2>
        <p><strong>Company:</strong> " . htmlspecialchars($userData['company_name']) . "</p>
        <p><strong>Employee ID:</strong> " . htmlspecialchars($userData['employee_id']) . "</p>
        <p><strong>Name:</strong> " . htmlspecialchars($userData['name']) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($userData['email']) . "</p>
        <p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>
        <p><strong>Login URL:</strong> " . $_SERVER['HTTP_HOST'] . "/ergon/login</p>
        <hr>
        <p><small>Please change your password after first login.</small></p>
        ";
        
        return $content;
    }
}
?>