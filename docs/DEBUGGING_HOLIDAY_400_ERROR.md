# Debugging the 400 Bad Request Error

## What We Know
- ✅ Routes are configured correctly
- ✅ HolidayController exists
- ✅ Holiday model exists with all required methods
- ✅ API endpoint is being called (`POST /ergon/holiday/create`)
- ❌ Getting 400 Bad Request (validation failing)

## Debug Steps

### Step 1: Check Server Error Logs
The most detailed error information will be in the server logs.

**Find the log file:**
```bash
# Linux/Mac
tail -f /storage/logs/error.log
tail -f /var/log/php-fpm.log

# Windows (if available)
dir storage\logs\
type storage\logs\error.log
```

**Check for messages like:**
- "Holiday create request - POST data:"
- "Holiday data prepared:"
- "Holiday create error:"

### Step 2: Verify Database Table Exists
The `holidays` table must exist.

**In MySQL:**
```sql
SHOW TABLES LIKE 'holidays';
DESCRIBE holidays;
```

**If table doesn't exist, create it:**
```sql
CREATE TABLE holidays (
  id INT PRIMARY KEY AUTO_INCREMENT,
  holiday_date DATE NOT NULL UNIQUE,
  holiday_name VARCHAR(255) NOT NULL,
  holiday_type VARCHAR(50),
  description TEXT,
  applies_to VARCHAR(50),
  department_id INT,
  repeat_yearly BOOLEAN DEFAULT 0,
  created_by INT,
  is_active BOOLEAN DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id),
  FOREIGN KEY (department_id) REFERENCES departments(id)
);
```

### Step 3: Check FormData Compatibility
The JavaScript sends FormData, which should work with PHP $_POST.

**To verify it's being received, add this temporary debug:**

Edit `app/controllers/HolidayController.php`, in the `create()` method, after `$this->requireRole(['admin', 'owner']);` add:

```php
// Temporary debug
file_put_contents('/tmp/holiday_debug.log', "Request received at " . date('Y-m-d H:i:s') . "\n" . print_r($_POST, true) . "\n", FILE_APPEND);
```

Then check `/tmp/holiday_debug.log` to see what's actually being received.

### Step 4: Test with cURL
Call the API directly with curl to isolate the issue:

```bash
curl -X POST http://localhost:8000/ergon/holiday/create \
  -d "holiday_date=2026-06-15" \
  -d "holiday_name=Test Holiday" \
  -d "holiday_type=National" \
  -d "description=Test" \
  -d "applies_to=All" \
  -H "Cookie: PHPSESSID=<your_session_id>"
```

### Step 5: Check Browser Network Tab
1. Open DevTools (F12)
2. Click "Network" tab
3. Submit the holiday form
4. Click on the POST request to `/ergon/holiday/create`
5. Check "Request" tab:
   - Headers
   - Cookies (should have PHPSESSID)
   - Form Data (should show holiday_date, holiday_name, etc.)
6. Check "Response" tab - should show the error message

### Step 6: Verify User Session
The requireRole() check needs a valid session.

Add this debug code temporarily:

```php
public function create() {
    $this->requireAuth();
    $this->requireRole(['admin', 'owner']);
    
    // Debug session
    file_put_contents('/tmp/session_debug.log', 
        "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n" .
        "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n",
        FILE_APPEND
    );
    ...
}
```

## Common Issues & Solutions

### Issue 1: Table Doesn't Exist
**Error:** "Base table or view not found"
**Solution:** Create the `holidays` table using SQL above

### Issue 2: Missing Database Connection
**Error:** "Call to undefined method"
**Solution:** Verify `Database::connect()` is working in Holiday model

### Issue 3: Session Not Valid
**Error:** 403 Forbidden in response
**Solution:** 
- Make sure you're logged in as admin/owner
- Check cookies are being sent

### Issue 4: Date Format Issue
**Error:** "Invalid holiday date format"
**Solution:** 
- HTML5 date input sends format `YYYY-MM-DD`
- Verify this matches validation in controller

### Issue 5: Field Values Empty
**Error:** "Holiday date/name is required"
**Solution:**
- Check form fields have values before submitting
- Verify JavaScript is reading from correct element IDs

## Quick Diagnostic Script

Create a temporary file `e:\ergon\debug-holiday.php`:

```php
<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

echo "<h2>Holiday Debug Info</h2>";

// Check session
echo "<h3>Session</h3>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";

// Check database connection
echo "<h3>Database</h3>";
try {
    $db = Database::connect();
    echo "✓ Database connected<br>";
    
    // Check if holidays table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'holidays'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "✓ Holidays table exists<br>";
        
        // Count holidays
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM holidays");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  Holiday count: " . $result['count'] . "<br>";
    } else {
        echo "✗ Holidays table NOT found<br>";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

// Check Holiday model
echo "<h3>Holiday Model</h3>";
require_once __DIR__ . '/app/models/Holiday.php';
if (class_exists('Holiday')) {
    echo "✓ Holiday class exists<br>";
    $holiday = new Holiday();
    echo "✓ Holiday instance created<br>";
    
    $methods = ['create', 'getAll', 'isDuplicate', 'getById'];
    foreach ($methods as $method) {
        if (method_exists($holiday, $method)) {
            echo "  ✓ Method: $method<br>";
        } else {
            echo "  ✗ Method missing: $method<br>";
        }
    }
} else {
    echo "✗ Holiday class NOT found<br>";
}

// Check Holiday controller
echo "<h3>Holiday Controller</h3>";
require_once __DIR__ . '/app/controllers/HolidayController.php';
if (class_exists('HolidayController')) {
    echo "✓ HolidayController class exists<br>";
    
    $methods = ['create', 'update', 'delete', 'getById'];
    foreach ($methods as $method) {
        if (method_exists('HolidayController', $method)) {
            echo "  ✓ Method: $method<br>";
        } else {
            echo "  ✗ Method missing: $method<br>";
        }
    }
} else {
    echo "✗ HolidayController class NOT found<br>";
}

echo "<h3>Files</h3>";
$files = [
    '/app/models/Holiday.php',
    '/app/controllers/HolidayController.php',
    '/app/config/routes.php'
];
foreach ($files as $file) {
    $path = __DIR__ . $file;
    echo ($file_exists($path) ? "✓" : "✗") . " " . $file . "<br>";
}
?>
```

**Access it:**
```
http://localhost:8000/ergon/debug-holiday.php
```

This will show you what's missing!

## Next Steps

1. Run debug-holiday.php and report what's missing
2. Check server error logs for specific error messages
3. Verify the holidays table exists
4. Ensure you're logged in as admin/owner
5. Try submitting again with proper debugging

---

**Status:** Investigating 400 error
**Last Check:** Added detailed error logging
**Next:** Review server logs and database

