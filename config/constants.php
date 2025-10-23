<?php
/**
 * Application Constants
 * ERGON - Employee Tracker & Task Manager
 */

// Application Settings
define('APP_NAME', 'ERGON');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/ergon');

// Security Settings
define('JWT_SECRET', 'your-secret-key-change-in-production');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File Upload Settings
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// GPS Settings
define('DEFAULT_ATTENDANCE_RADIUS', 200); // meters
define('GPS_ACCURACY_THRESHOLD', 50); // meters

// Pagination
define('RECORDS_PER_PAGE', 20);

// Email Settings (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

// Roles
define('ROLE_OWNER', 'owner');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

// Status Constants
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');

// Task Status
define('TASK_ASSIGNED', 'assigned');
define('TASK_IN_PROGRESS', 'in_progress');
define('TASK_COMPLETED', 'completed');
define('TASK_BLOCKED', 'blocked');

// Attendance Status
define('ATTENDANCE_PRESENT', 'present');
define('ATTENDANCE_ABSENT', 'absent');
define('ATTENDANCE_MANUAL', 'manual');

// Error Messages
define('ERROR_UNAUTHORIZED', 'Unauthorized access');
define('ERROR_INVALID_INPUT', 'Invalid input provided');
define('ERROR_DATABASE', 'Database operation failed');
define('ERROR_FILE_UPLOAD', 'File upload failed');

// Success Messages
define('SUCCESS_LOGIN', 'Login successful');
define('SUCCESS_LOGOUT', 'Logout successful');
define('SUCCESS_SAVE', 'Data saved successfully');
define('SUCCESS_UPDATE', 'Data updated successfully');
define('SUCCESS_DELETE', 'Data deleted successfully');
?>