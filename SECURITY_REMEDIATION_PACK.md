# 🛠️ ERGON SECURITY REMEDIATION PACK

## Battle-Tested Templates for Common Security Issues

This pack provides ready-to-use secure code templates for fixing the top 10 security vulnerabilities detected by the audit.

---

## 1. 🚫 **SQL Injection Prevention**

### **Prepared Statements Template:**
```php
<?php
// ❌ VULNERABLE - String concatenation
$sql = "SELECT * FROM users WHERE email = '" . $_POST['email'] . "'";
$rows = $pdo->query($sql);

// ✅ SECURE - Prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $_POST['email']]);
$rows = $stmt->fetchAll();

// ✅ SECURE - Positional parameters
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
$stmt->execute([$userId, $date]);
$attendance = $stmt->fetchAll();
?>
```

### **For Ergon Attendance Module:**
```php
<?php
// app/models/Attendance.php - Secure implementation
public function getAttendanceByUser($userId, $startDate = null, $endDate = null) {
    $sql = "SELECT * FROM attendance WHERE user_id = :user_id";
    $params = ['user_id' => $userId];
    
    if ($startDate) {
        $sql .= " AND check_in_time >= :start_date";
        $params['start_date'] = $startDate;
    }
    
    if ($endDate) {
        $sql .= " AND check_in_time <= :end_date";
        $params['end_date'] = $endDate;
    }
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
?>
```

---

## 2. 🔒 **XSS Prevention - Output Escaping**

### **Safe Output Template:**
```php
<?php
// ❌ VULNERABLE - Raw output
echo $_GET['name'];
echo $user['message'];

// ✅ SECURE - Escaped output
echo htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8');
echo htmlspecialchars($user['message'], ENT_QUOTES, 'UTF-8');

// ✅ SECURE - Helper function
function safe_echo($data) {
    echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

safe_echo($user_input);
?>
```

### **For Ergon Dashboard Views:**
```php
<!-- app/views/owner/dashboard.php - Secure implementation -->
<div class="kpi-card__value"><?= htmlspecialchars($data['stats']['total_users'], ENT_QUOTES, 'UTF-8') ?></div>
<div class="form-label"><?= htmlspecialchars($approval['type'], ENT_QUOTES, 'UTF-8') ?> Requests</div>
<p><?= htmlspecialchars($activity['description'], ENT_QUOTES, 'UTF-8') ?></p>
```

---

## 3. 🧩 **CSRF Protection Implementation**

### **Complete CSRF Protection:**
```php
<?php
// app/helpers/Security.php - CSRF Helper
class Security {
    public static function generateCSRFToken() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Usage in forms
$csrfToken = Security::generateCSRFToken();
?>

<!-- Form template -->
<form method="POST" action="/ergon/tasks/create">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <!-- form fields -->
    <button type="submit">Create Task</button>
</form>

<?php
// Controller validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF validation failed');
    }
    // Process form
}
?>
```

### **For Ergon Controllers:**
```php
<?php
// app/controllers/TasksController.php - CSRF protected
public function create() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Invalid request'], 403);
            return;
        }
        
        // Process task creation
        $taskData = [
            'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
            'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'assigned_to' => filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT)
        ];
        
        // Create task
        $this->taskModel->create($taskData);
    }
}
?>
```

---

## 4. 🧱 **Secure File Upload Handler**

### **Complete Upload Security:**
```php
<?php
// app/helpers/FileUpload.php - Secure upload handler
class FileUpload {
    private $allowedTypes = [
        'receipts' => ['jpg', 'jpeg', 'png', 'pdf'],
        'documents' => ['pdf', 'doc', 'docx'],
        'images' => ['jpg', 'jpeg', 'png', 'gif']
    ];
    
    private $maxSizes = [
        'receipts' => 5 * 1024 * 1024,  // 5MB
        'documents' => 10 * 1024 * 1024, // 10MB
        'images' => 2 * 1024 * 1024      // 2MB
    ];
    
    public function uploadFile($file, $type = 'receipts') {
        // Validate file
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $file['error']);
        }
        
        // Check file size
        if ($file['size'] > $this->maxSizes[$type]) {
            throw new Exception('File too large');
        }
        
        // Validate file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedTypes[$type])) {
            throw new Exception('Invalid file type');
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf'
        ];
        
        if (!isset($allowedMimes[$ext]) || $mimeType !== $allowedMimes[$ext]) {
            throw new Exception('MIME type mismatch');
        }
        
        // Generate secure filename
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $uploadPath = __DIR__ . '/../../storage/' . $type . '/' . $filename;
        
        // Ensure directory exists
        $dir = dirname($uploadPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        return $filename;
    }
}

// Usage in Expense Controller
try {
    $uploader = new FileUpload();
    $filename = $uploader->uploadFile($_FILES['receipt'], 'receipts');
    
    // Save to database
    $expenseData['receipt_path'] = $filename;
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
```

---

## 5. 🔑 **Environment Security**

### **Secure Environment Management:**
```php
<?php
// config/environment.php - Secure env loading
class Environment {
    private static $loaded = false;
    
    public static function load($path = null) {
        if (self::$loaded) return;
        
        $envFile = $path ?: __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            throw new Exception('.env file not found');
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue; // Skip comments
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$loaded = true;
    }
    
    public static function get($key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
    
    public static function required($key) {
        $value = self::get($key);
        if ($value === null) {
            throw new Exception("Required environment variable '$key' not set");
        }
        return $value;
    }
}

// Usage
Environment::load();
$dbPassword = Environment::required('DB_PASSWORD');
$jwtSecret = Environment::required('JWT_SECRET');
?>
```

### **.env Template:**
```bash
# Database Configuration
DB_HOST=localhost
DB_NAME=ergon_db
DB_USER=ergon_user
DB_PASSWORD=your-secure-password-here

# JWT Configuration
JWT_SECRET=your-jwt-secret-key-minimum-32-characters
JWT_EXPIRY=3600

# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://athenas.co.in/ergon

# Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password

# GPS Settings
GPS_ACCURACY_THRESHOLD=100
GEO_FENCE_RADIUS=500
```

---

## 6. 🔐 **Secure JWT Implementation**

### **JWT Security Template:**
```php
<?php
// app/helpers/JWTHelper.php - Secure JWT handling
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHelper {
    private static function getSecret() {
        $secret = Environment::required('JWT_SECRET');
        if (strlen($secret) < 32) {
            throw new Exception('JWT secret must be at least 32 characters');
        }
        return $secret;
    }
    
    public static function encode($payload, $expiry = 3600) {
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;
        $payload['iss'] = Environment::get('APP_URL', 'ergon');
        
        return JWT::encode($payload, self::getSecret(), 'HS256');
    }
    
    public static function decode($token) {
        try {
            return JWT::decode($token, new Key(self::getSecret(), 'HS256'));
        } catch (Exception $e) {
            throw new Exception('Invalid token: ' . $e->getMessage());
        }
    }
    
    public static function generateForUser($user) {
        return self::encode([
            'user_id' => $user['id'],
            'role' => $user['role'],
            'email' => $user['email']
        ]);
    }
}

// Usage in AuthController
$token = JWTHelper::generateForUser($user);
$decoded = JWTHelper::decode($token);
?>
```

---

## 7. 🚫 **Input Validation & Sanitization**

### **Comprehensive Input Handler:**
```php
<?php
// app/helpers/InputValidator.php
class InputValidator {
    public static function sanitizeString($input, $maxLength = 255) {
        $clean = filter_var($input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        return substr(trim($clean), 0, $maxLength);
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function validateInt($input, $min = null, $max = null) {
        $options = ['options' => []];
        if ($min !== null) $options['options']['min_range'] = $min;
        if ($max !== null) $options['options']['max_range'] = $max;
        
        return filter_var($input, FILTER_VALIDATE_INT, $options);
    }
    
    public static function validateFloat($input, $min = null, $max = null) {
        $value = filter_var($input, FILTER_VALIDATE_FLOAT);
        if ($value === false) return false;
        
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        
        return $value;
    }
    
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    public static function validateGPSCoordinate($coord) {
        $lat = filter_var($coord['lat'], FILTER_VALIDATE_FLOAT);
        $lng = filter_var($coord['lng'], FILTER_VALIDATE_FLOAT);
        
        if ($lat === false || $lng === false) return false;
        if ($lat < -90 || $lat > 90) return false;
        if ($lng < -180 || $lng > 180) return false;
        
        return ['lat' => $lat, 'lng' => $lng];
    }
}

// Usage in controllers
$taskData = [
    'title' => InputValidator::sanitizeString($_POST['title'], 100),
    'description' => InputValidator::sanitizeString($_POST['description'], 1000),
    'assigned_to' => InputValidator::validateInt($_POST['assigned_to'], 1),
    'due_date' => InputValidator::validateDate($_POST['due_date'])
];

if (!$taskData['assigned_to']) {
    throw new Exception('Invalid user ID');
}
?>
```

---

## 8. 🧰 **Server Configuration Security**

### **.htaccess Security (Apache):**
```apache
# .htaccess - Root directory security
RewriteEngine On

# Block access to sensitive files
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(sql|log|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Disable server signature
ServerTokens Prod
ServerSignature Off

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### **public/uploads/.htaccess:**
```apache
# Prevent execution of PHP files in uploads
<Files "*.php">
    Order allow,deny
    Deny from all
</Files>

<Files "*.phtml">
    Order allow,deny
    Deny from all
</Files>

<Files "*.phar">
    Order allow,deny
    Deny from all
</Files>

# Only allow specific file types
<FilesMatch "\.(jpg|jpeg|png|gif|pdf|doc|docx)$">
    Order allow,deny
    Allow from all
</FilesMatch>
```

---

## 9. 🔒 **Session Security**

### **Secure Session Management:**
```php
<?php
// app/helpers/SessionManager.php
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        
        // Secure session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        // IP validation
        if (!isset($_SESSION['ip'])) {
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        } elseif ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
            self::destroy();
            throw new Exception('Session IP mismatch');
        }
    }
    
    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
            session_write_close();
            setcookie(session_name(), '', 0, '/');
        }
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['role']);
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /ergon/login');
            exit;
        }
    }
}
?>
```

---

## 10. 🧍♂️ **File Permissions Script**

### **Security Permissions Setup:**
```bash
#!/bin/bash
# scripts/set_permissions.sh - Secure file permissions

echo "Setting secure file permissions for Ergon..."

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Secure sensitive files
chmod 600 .env
chmod 600 config/*.php

# Storage directories
chmod -R 750 storage/
chmod -R 755 storage/cache/
chmod -R 755 storage/logs/

# Public directory
chmod -R 755 public/
chmod -R 644 public/assets/

# Uploads directory
chmod 755 public/uploads/
find public/uploads/ -type f -exec chmod 644 {} \;
find public/uploads/ -type d -exec chmod 755 {} \;

# Make scripts executable
chmod +x scripts/*.sh

echo "Permissions set successfully!"
```

---

## 🎯 **Quick Implementation Checklist**

### **Priority 1 - Critical Security:**
- [ ] Implement prepared statements for all database queries
- [ ] Add CSRF protection to all forms
- [ ] Escape all output with `htmlspecialchars()`
- [ ] Secure file upload validation
- [ ] Move `.env` outside webroot

### **Priority 2 - Enhanced Security:**
- [ ] Implement JWT with secure secrets
- [ ] Add input validation to all controllers
- [ ] Configure secure session management
- [ ] Set proper file permissions
- [ ] Add security headers

### **Priority 3 - Monitoring:**
- [ ] Set up GitHub Actions workflow
- [ ] Configure automated security scans
- [ ] Implement audit logging
- [ ] Regular dependency updates
- [ ] Security training for team

---

**Remediation Pack Version:** 1.0  
**Compatible with:** Ergon v1.0+, PHP 8.0+  
**Last Updated:** 2024-12-20  
**Usage:** Copy templates and adapt to your specific needs