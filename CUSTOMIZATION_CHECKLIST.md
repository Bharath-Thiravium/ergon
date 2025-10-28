# ✅ **ERGON - Customization Checklist**

## 🎯 **Quick Start Configuration**

### **Phase 1: Basic Setup (30 minutes)**

#### **Database Configuration**
- [ ] Update `app/config/database.php` with your database credentials
- [ ] Import schema from `database/schema.sql`
- [ ] Run `database/dummy_data.sql` for test data (optional)
- [ ] Verify database connection

#### **Environment Setup**
- [ ] Configure `app/config/constants.php` for your environment
- [ ] Set correct base URLs and paths
- [ ] Configure file upload directories
- [ ] Set timezone and locale settings

#### **Basic Security**
- [ ] Change default admin password
- [ ] Configure session settings
- [ ] Set up HTTPS (production)
- [ ] Configure error logging

---

### **Phase 2: Business Logic Customization (1-2 hours)**

#### **User Roles & Permissions**
- [ ] Review existing roles (Owner, Admin, User)
- [ ] Add custom roles if needed
- [ ] Modify permission levels
- [ ] Update role-based redirects

#### **Workflow Modifications**
- [ ] Customize leave approval process
- [ ] Modify expense approval limits
- [ ] Adjust task assignment rules
- [ ] Configure notification triggers

#### **Company-Specific Settings**
- [ ] Update company information
- [ ] Configure office locations for GPS
- [ ] Set working hours and holidays
- [ ] Customize leave types and policies

---

### **Phase 3: Feature Customization (2-4 hours)**

#### **Attendance System**
- [ ] Configure GPS accuracy requirements
- [ ] Set geo-fence boundaries
- [ ] Customize attendance rules
- [ ] Add shift management (if needed)

#### **Task Management**
- [ ] Define task categories
- [ ] Set priority levels
- [ ] Configure SLA rules
- [ ] Add custom task fields

#### **Reporting & Analytics**
- [ ] Customize dashboard widgets
- [ ] Add business-specific reports
- [ ] Configure export formats
- [ ] Set up automated reports

---

## 🔧 **Common Customization Scenarios**

### **Scenario 1: Adding Department-Specific Workflows**

#### **Files to Modify:**
```
app/models/Department.php          # Add department logic
app/controllers/DepartmentController.php  # Department management
views/departments/                 # Department views
app/config/routes.php             # Add department routes
```

#### **Steps:**
1. **Database Changes**
   ```sql
   ALTER TABLE departments ADD COLUMN workflow_type VARCHAR(50);
   ALTER TABLE tasks ADD COLUMN department_workflow JSON;
   ```

2. **Model Updates**
   ```php
   // app/models/Department.php
   public function getWorkflowRules($departmentId) {
       // Custom workflow logic
   }
   ```

3. **Controller Logic**
   ```php
   // app/controllers/TasksController.php
   public function create() {
       $department = $this->getDepartment($_SESSION['department_id']);
       $workflowRules = $department->getWorkflowRules();
       // Apply department-specific rules
   }
   ```

---

### **Scenario 2: Custom Approval Hierarchies**

#### **Current Flow:** User → Admin → Owner
#### **Custom Flow:** User → Team Lead → Department Head → Admin → Owner

#### **Implementation Steps:**
1. **Database Schema**
   ```sql
   ALTER TABLE users ADD COLUMN team_lead_id INT;
   ALTER TABLE departments ADD COLUMN head_id INT;
   ALTER TABLE leaves ADD COLUMN team_lead_approval ENUM('pending','approved','rejected');
   ALTER TABLE leaves ADD COLUMN dept_head_approval ENUM('pending','approved','rejected');
   ```

2. **Model Updates**
   ```php
   // app/models/Leave.php
   public function getApprovalChain($userId) {
       // Return approval hierarchy for user
   }
   ```

3. **Controller Logic**
   ```php
   // app/controllers/LeaveController.php
   public function processApproval($leaveId) {
       $leave = $this->leaveModel->find($leaveId);
       $chain = $this->leaveModel->getApprovalChain($leave['user_id']);
       // Process next approval level
   }
   ```

---

### **Scenario 3: Integration with External Systems**

#### **Common Integrations:**
- **Payroll Systems**: Export attendance data
- **HR Systems**: Sync employee data
- **Email Systems**: Send notifications
- **SMS Gateways**: Mobile alerts

#### **Implementation Pattern:**
```php
// app/helpers/IntegrationHelper.php
class IntegrationHelper {
    public static function syncWithPayroll($attendanceData) {
        // API call to payroll system
    }
    
    public static function sendSMS($phone, $message) {
        // SMS gateway integration
    }
}
```

---

## 🎨 **UI/UX Customization**

### **Branding & Styling**

#### **Files to Modify:**
```
assets/css/ergon.css              # Main stylesheet
views/layouts/dashboard.php       # Main layout
assets/images/                    # Logo and images
```

#### **Quick Changes:**
- [ ] Replace logo in `assets/images/logo.png`
- [ ] Update company colors in CSS variables
- [ ] Modify header and footer content
- [ ] Customize dashboard layout

#### **CSS Variables to Update:**
```css
:root {
    --primary-color: #your-brand-color;
    --secondary-color: #your-secondary-color;
    --logo-url: url('path/to/your/logo.png');
}
```

---

### **Dashboard Customization**

#### **Widget Configuration:**
```php
// views/dashboard/user.php
$widgets = [
    'attendance_summary' => true,
    'task_progress' => true,
    'recent_requests' => true,
    'notifications' => true,
    'custom_widget' => true  // Add your custom widget
];
```

#### **Custom Widget Example:**
```php
// views/shared/custom_widget.php
<div class="col-md-6">
    <div class="card">
        <div class="card-header">
            <h5>Custom Business Metric</h5>
        </div>
        <div class="card-body">
            <!-- Your custom content -->
        </div>
    </div>
</div>
```

---

## 📱 **Mobile App Customization**

### **API Endpoints to Customize**

#### **Authentication:**
```php
// app/controllers/ApiController.php
public function mobileLogin() {
    // Custom mobile login logic
    // Add device registration
    // Implement push notification tokens
}
```

#### **Offline Sync:**
```php
public function syncOfflineData() {
    // Handle offline attendance records
    // Sync task updates
    // Resolve data conflicts
}
```

---

## 🔐 **Security Customization**

### **Authentication Methods**

#### **Current:** Email/Password
#### **Options to Add:**
- [ ] LDAP/Active Directory integration
- [ ] OAuth (Google, Microsoft)
- [ ] Two-factor authentication
- [ ] Biometric authentication (mobile)

#### **Implementation Example:**
```php
// app/helpers/AuthHelper.php
class AuthHelper {
    public static function authenticateLDAP($username, $password) {
        // LDAP authentication logic
    }
    
    public static function verifyTwoFactor($userId, $code) {
        // 2FA verification logic
    }
}
```

---

## 📊 **Reporting Customization**

### **Custom Reports**

#### **Steps to Add New Report:**
1. **Create Report Model**
   ```php
   // app/models/CustomReport.php
   class CustomReport {
       public function getBusinessMetrics($dateRange) {
           // Custom query logic
       }
   }
   ```

2. **Add Controller Method**
   ```php
   // app/controllers/ReportsController.php
   public function customReport() {
       $report = new CustomReport();
       $data = $report->getBusinessMetrics($_GET['date_range']);
       $this->view('reports/custom', ['data' => $data]);
   }
   ```

3. **Create View**
   ```php
   // views/reports/custom.php
   <!-- Custom report template -->
   ```

4. **Add Route**
   ```php
   // app/config/routes.php
   $router->get('/reports/custom', 'ReportsController', 'customReport');
   ```

---

## 🚀 **Performance Optimization**

### **Database Optimization**
- [ ] Add indexes for frequently queried columns
- [ ] Implement query caching
- [ ] Optimize slow queries
- [ ] Set up database connection pooling

### **Application Optimization**
- [ ] Implement Redis/Memcached caching
- [ ] Optimize file uploads
- [ ] Minify CSS/JavaScript
- [ ] Enable gzip compression

### **Example Caching Implementation:**
```php
// app/helpers/Cache.php
public function getOrSet($key, $callback, $ttl = 3600) {
    $cached = $this->get($key);
    if ($cached !== null) {
        return $cached;
    }
    
    $value = $callback();
    $this->set($key, $value, $ttl);
    return $value;
}
```

---

## 🧪 **Testing Your Customizations**

### **Testing Checklist**
- [ ] User authentication works
- [ ] All user roles function correctly
- [ ] GPS attendance tracking works
- [ ] Task assignment and updates work
- [ ] Approval workflows function
- [ ] Reports generate correctly
- [ ] Mobile API endpoints work
- [ ] File uploads work
- [ ] Email notifications send
- [ ] Database queries are optimized

### **Test Data Setup**
```sql
-- Create test users for each role
INSERT INTO users (name, email, password, role) VALUES
('Test Owner', 'owner@test.com', '$2y$10$...', 'owner'),
('Test Admin', 'admin@test.com', '$2y$10$...', 'admin'),
('Test User', 'user@test.com', '$2y$10$...', 'user');
```

---

## 📋 **Deployment Checklist**

### **Pre-Deployment**
- [ ] Backup current system
- [ ] Test all customizations locally
- [ ] Update database schema
- [ ] Check file permissions
- [ ] Verify environment configuration

### **Deployment**
- [ ] Upload modified files
- [ ] Run database migrations
- [ ] Clear application cache
- [ ] Test critical workflows
- [ ] Monitor error logs

### **Post-Deployment**
- [ ] Verify all features work
- [ ] Check performance metrics
- [ ] Test user access
- [ ] Monitor system logs
- [ ] Update documentation

---

## 🎯 **Priority Customization Order**

### **High Priority (Must Do)**
1. Database configuration
2. Basic security setup
3. Company information update
4. User role configuration
5. GPS location setup

### **Medium Priority (Should Do)**
1. Workflow customizations
2. UI branding
3. Custom reports
4. Email notifications
5. Mobile API setup

### **Low Priority (Nice to Have)**
1. Advanced integrations
2. Performance optimizations
3. Custom widgets
4. Advanced analytics
5. Third-party integrations

---

This checklist provides a structured approach to customizing the ergon system according to your specific business requirements while maintaining system integrity and performance.