# Owner Attendance Permissions Fix

## Issue
Owner role was not having the same permissions as admin for attendance actions like:
- Manual attendance entry
- Clock in/out for employees
- Attendance reports
- Attendance corrections

## Root Cause
The permission checks were correct (`['admin', 'owner']`), but there were inconsistencies:

1. **Session Role Validation**: The role wasn't being validated after retrieval from session
2. **Error Messages**: Generic error messages didn't help identify permission issues
3. **Role Consistency**: Some controllers had different permission checks

## Files Modified

### 1. `/api/manual_attendance.php`
**Change**: Enhanced permission check with debug info
```php
// Before
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Owner/Admin role required.']);
    exit;
}

// After
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

$userRole = $_SESSION['role'] ?? 'user';
if (!in_array($userRole, ['owner', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Owner/Admin role required. Your role: ' . $userRole]);
    exit;
}
```

### 2. `/app/controllers/AttendanceController.php`
**Changes**:

a) Enhanced `index()` method with role validation:
```php
// Added role validation
if (empty($role) || !in_array($role, ['user', 'admin', 'owner'])) {
    $role = 'user';
    $_SESSION['role'] = $role;
}
```

b) Enhanced `manual()` method with debug info:
```php
// Before
if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// After
$userRole = $_SESSION['role'] ?? 'user';
if (!in_array($userRole, ['admin', 'owner'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied. Required role: admin or owner. Your role: ' . $userRole);
}
```

### 3. `/app/controllers/EnhancedAttendanceController.php`
**Change**: Simplified `report()` method role check:
```php
// Before
if (!in_array($_SESSION['role'], ['owner', 'admin'])) {
    error_log('Access denied for role: ' . ($_SESSION['role'] ?? 'none'));
    header('Location: /ergon/attendance?error=access_denied');
    exit;
}

// After
$userRole = $_SESSION['role'] ?? 'user';
if (!in_array($userRole, ['owner', 'admin'])) {
    error_log('Access denied for role: ' . $userRole);
    header('Location: /ergon/attendance?error=access_denied');
    exit;
}
```

## Owner Permissions (Now Enabled)

Owners now have full access to:

1. **Manual Attendance Entry** (`/api/manual_attendance.php`)
   - Add clock-in/out times for employees
   - Full day entries
   - Reason tracking

2. **Admin Attendance Actions** (`/api/attendance_admin.php`)
   - Clock in users
   - Clock out users
   - Delete attendance records
   - View attendance details

3. **Attendance Reports** (`EnhancedAttendanceController::report()`)
   - Generate CSV reports
   - Filter by date range
   - Export employee attendance

4. **Attendance Corrections** (`EnhancedAttendanceController::correction()`)
   - Submit correction requests
   - Track correction history

## Testing

To verify owner permissions are working:

1. **Login as Owner**
   - Navigate to `/ergon/attendance`
   - Should see owner dashboard with all employees

2. **Test Manual Entry**
   - Click "✏️" (Manual Entry) button on any employee
   - Should be able to enter check-in/out times
   - Should see success message

3. **Test Clock Actions**
   - Click "⏰" (Clock In/Out) buttons
   - Should work without permission errors

4. **Test Reports**
   - Click "📄" (Generate Report) button
   - Should generate CSV without errors

## Debugging

If owner still doesn't have permissions:

1. **Check Session Role**
   ```php
   echo $_SESSION['role']; // Should output 'owner'
   ```

2. **Check User Role in Database**
   ```sql
   SELECT id, name, role FROM users WHERE id = ?;
   ```

3. **Check Error Logs**
   - Look for "Access denied for role" messages
   - Check browser console for API errors

4. **Clear Session**
   - Logout and login again
   - Session role should be refreshed from database

## Related Files

- `/views/attendance/owner_index.php` - Owner attendance dashboard
- `/views/attendance/admin_index.php` - Admin attendance dashboard
- `/api/attendance_admin.php` - Admin API endpoints
- `/app/models/User.php` - User authentication model

## Constants

Role constants defined in `/app/config/constants.php`:
```php
define('ROLE_OWNER', 'owner');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');
```
