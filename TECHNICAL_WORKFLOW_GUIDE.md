# ⚙️ **ERGON - Technical Implementation Workflow**

## 🎯 **Quick Start Customization Guide**

### **1. Understanding the Current Architecture**

#### **Request Flow**
```
Browser Request → index.php → Router → Middleware → Controller → Model → Database
                                ↓
                            View ← Controller ← Model ← Database Response
```

#### **Key Files to Modify**
- **Routes**: `app/config/routes.php` - Add/modify URL endpoints
- **Controllers**: `app/controllers/` - Business logic
- **Models**: `app/models/` - Database operations
- **Views**: `views/` - User interface
- **Middleware**: `app/middlewares/` - Authentication/authorization

---

## 🔧 **Common Customization Scenarios**

### **Scenario 1: Adding a New Module**

#### **Step 1: Create Database Table**
```sql
-- Example: Adding a "Projects" module
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    department_id INT,
    status ENUM('active', 'completed', 'on_hold'),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

#### **Step 2: Create Model**
```php
// app/models/Project.php
<?php
class Project {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO projects (name, description, department_id, created_by) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'], 
            $data['description'], 
            $data['department_id'], 
            $data['created_by']
        ]);
    }
    
    public function getAll() {
        $stmt = $this->pdo->query("
            SELECT p.*, d.name as department_name, u.name as creator_name
            FROM projects p
            LEFT JOIN departments d ON p.department_id = d.id
            LEFT JOIN users u ON p.created_by = u.id
            ORDER BY p.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
```

#### **Step 3: Create Controller**
```php
// app/controllers/ProjectController.php
<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Project.php';

class ProjectController extends Controller {
    private $projectModel;
    
    public function __construct() {
        $this->projectModel = new Project();
    }
    
    public function index() {
        $this->requireAuth();
        $projects = $this->projectModel->getAll();
        $this->view('projects/index', ['projects' => $projects]);
    }
    
    public function create() {
        $this->requireAuth();
        
        if ($this->isPost()) {
            $data = [
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'department_id' => $_POST['department_id'],
                'created_by' => $_SESSION['user_id']
            ];
            
            if ($this->projectModel->create($data)) {
                $this->redirect('/projects');
            }
        }
        
        $this->view('projects/create');
    }
}
?>
```

#### **Step 4: Add Routes**
```php
// Add to app/config/routes.php
$router->get('/projects', 'ProjectController', 'index');
$router->get('/projects/create', 'ProjectController', 'create');
$router->post('/projects/create', 'ProjectController', 'create');
```

#### **Step 5: Create Views**
```php
// views/projects/index.php
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
<div class="container-fluid">
    <h2>Projects</h2>
    <a href="/ergon/projects/create" class="btn btn-primary">Add Project</a>
    
    <table class="table mt-3">
        <thead>
            <tr>
                <th>Name</th>
                <th>Department</th>
                <th>Status</th>
                <th>Created By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $project): ?>
            <tr>
                <td><?= htmlspecialchars($project['name']) ?></td>
                <td><?= htmlspecialchars($project['department_name']) ?></td>
                <td><?= htmlspecialchars($project['status']) ?></td>
                <td><?= htmlspecialchars($project['creator_name']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

---

### **Scenario 2: Modifying Existing Workflows**

#### **Example: Customizing Leave Approval Process**

#### **Current Flow**: User → Admin → Owner
#### **New Flow**: User → Department Head → Admin → Owner

#### **Step 1: Modify Database**
```sql
-- Add department head approval
ALTER TABLE leaves ADD COLUMN dept_head_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';
ALTER TABLE leaves ADD COLUMN dept_head_approved_by INT;
ALTER TABLE leaves ADD COLUMN dept_head_approved_at TIMESTAMP NULL;
```

#### **Step 2: Update Model**
```php
// Modify app/models/Leave.php
public function approveDeptHead($leaveId, $userId, $status) {
    $stmt = $this->pdo->prepare("
        UPDATE leaves 
        SET dept_head_approval = ?, 
            dept_head_approved_by = ?, 
            dept_head_approved_at = NOW() 
        WHERE id = ?
    ");
    return $stmt->execute([$status, $userId, $leaveId]);
}
```

#### **Step 3: Update Controller**
```php
// Modify app/controllers/LeaveController.php
public function deptHeadApprove($id) {
    $this->requireRole('dept_head');
    
    if ($this->isPost()) {
        $status = $_POST['status']; // 'approved' or 'rejected'
        $this->leaveModel->approveDeptHead($id, $_SESSION['user_id'], $status);
        $this->redirect('/leaves');
    }
}
```

---

### **Scenario 3: Adding API Endpoints**

#### **Example: Mobile App Task API**

#### **Step 1: Add API Routes**
```php
// Add to app/config/routes.php
$router->get('/api/tasks/user/{userId}', 'ApiController', 'getUserTasks');
$router->post('/api/tasks/update-progress', 'ApiController', 'updateTaskProgress');
```

#### **Step 2: Implement API Methods**
```php
// Add to app/controllers/ApiController.php
public function getUserTasks($userId) {
    // Validate JWT token
    $token = $this->getBearerToken();
    if (!$this->validateJWT($token)) {
        $this->json(['error' => 'Invalid token'], 401);
    }
    
    $taskModel = new Task();
    $tasks = $taskModel->getByUserId($userId);
    
    $this->json(['tasks' => $tasks]);
}

public function updateTaskProgress() {
    $token = $this->getBearerToken();
    if (!$this->validateJWT($token)) {
        $this->json(['error' => 'Invalid token'], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $taskModel = new Task();
    
    if ($taskModel->updateProgress($data['task_id'], $data['progress'])) {
        $this->json(['success' => true]);
    } else {
        $this->json(['error' => 'Update failed'], 500);
    }
}
```

---

## 🔐 **Security Implementation Patterns**

### **Authentication Middleware**
```php
// app/middlewares/AuthMiddleware.php
class AuthMiddleware {
    public static function check() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
    }
    
    public static function requireRole($role) {
        self::check();
        if ($_SESSION['role'] !== $role) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
    }
}
```

### **Input Validation Helper**
```php
// app/helpers/Validator.php
class Validator {
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function required($fields, $data) {
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
    }
}
```

---

## 📊 **Database Patterns**

### **Base Model Class**
```php
// app/models/BaseModel.php
abstract class BaseModel {
    protected $pdo;
    protected $table;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $fields = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})
        ");
        
        return $stmt->execute($data);
    }
    
    public function update($id, $data) {
        $fields = implode(' = ?, ', array_keys($data)) . ' = ?';
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table} SET {$fields} WHERE id = ?
        ");
        
        return $stmt->execute([...array_values($data), $id]);
    }
}
```

---

## 🎨 **Frontend Patterns**

### **AJAX Request Helper**
```javascript
// assets/js/ergon-core.js
class ErgonAPI {
    static async request(url, options = {}) {
        const defaults = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = { ...defaults, ...options };
        
        try {
            const response = await fetch(url, config);
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
    
    static async get(url) {
        return this.request(url, { method: 'GET' });
    }
    
    static async post(url, data) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
}
```

### **Form Validation**
```javascript
// Form validation helper
function validateForm(formId, rules) {
    const form = document.getElementById(formId);
    const errors = [];
    
    for (const [field, rule] of Object.entries(rules)) {
        const input = form.querySelector(`[name="${field}"]`);
        const value = input.value.trim();
        
        if (rule.required && !value) {
            errors.push(`${field} is required`);
        }
        
        if (rule.email && value && !isValidEmail(value)) {
            errors.push(`${field} must be a valid email`);
        }
    }
    
    return errors;
}
```

---

## 🔄 **Workflow Automation**

### **Notification System**
```php
// app/helpers/NotificationHelper.php
class NotificationHelper {
    public static function send($userId, $type, $message, $data = []) {
        $notification = new Notification();
        return $notification->create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'data' => json_encode($data),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public static function sendToRole($role, $type, $message, $data = []) {
        $userModel = new User();
        $users = $userModel->getByRole($role);
        
        foreach ($users as $user) {
            self::send($user['id'], $type, $message, $data);
        }
    }
}
```

### **Task Automation**
```php
// app/helpers/TaskAutomation.php
class TaskAutomation {
    public static function createRecurringTasks() {
        $recurringModel = new RecurringTask();
        $tasks = $recurringModel->getDue();
        
        foreach ($tasks as $task) {
            $taskModel = new Task();
            $taskModel->create([
                'title' => $task['title'],
                'description' => $task['description'],
                'assigned_to' => $task['assigned_to'],
                'due_date' => self::calculateNextDue($task['frequency']),
                'created_by' => 1 // System user
            ]);
        }
    }
}
```

---

## 📈 **Performance Optimization**

### **Caching Strategy**
```php
// app/helpers/Cache.php
class Cache {
    private static $cache = [];
    
    public static function get($key) {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        $file = __DIR__ . "/../../storage/cache/{$key}.cache";
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            if ($data['expires'] > time()) {
                self::$cache[$key] = $data['value'];
                return $data['value'];
            }
        }
        
        return null;
    }
    
    public static function set($key, $value, $ttl = 3600) {
        self::$cache[$key] = $value;
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        file_put_contents(
            __DIR__ . "/../../storage/cache/{$key}.cache",
            serialize($data)
        );
    }
}
```

---

## 🧪 **Testing Patterns**

### **Unit Test Example**
```php
// tests/UserModelTest.php
class UserModelTest extends PHPUnit\Framework\TestCase {
    private $userModel;
    
    protected function setUp(): void {
        $this->userModel = new User();
    }
    
    public function testCreateUser() {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT)
        ];
        
        $result = $this->userModel->create($userData);
        $this->assertTrue($result);
    }
}
```

---

## 🚀 **Deployment Checklist**

### **Pre-Deployment**
- [ ] Run tests
- [ ] Check database migrations
- [ ] Verify environment configuration
- [ ] Test critical workflows
- [ ] Review security settings

### **Deployment Steps**
1. **Backup current system**
2. **Upload new files**
3. **Run database migrations**
4. **Clear cache**
5. **Test functionality**
6. **Monitor logs**

### **Post-Deployment**
- [ ] Verify all modules working
- [ ] Check error logs
- [ ] Test user workflows
- [ ] Monitor performance
- [ ] Update documentation

---

This technical guide provides the foundation for customizing the ergon system according to your specific requirements while maintaining security and performance standards.