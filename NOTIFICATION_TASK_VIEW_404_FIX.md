# Notification Task View 404 Error Fix

## Issue Description
When clicking the "View" option in task notifications from the notification module, the system was redirecting to a 404 error page with the URL `https://bkgreenenergy.com/ergon/system`.

## Root Cause Analysis
The issue was in the notification view (`views/notifications/index.php`) where the URL generation logic was creating invalid URLs for 'system' type notifications. When `$referenceType` was 'system', the code was generating `/ergon/system` which doesn't exist in the routing configuration.

## Fix Applied

### File Modified: `views/notifications/index.php`

**Problem Areas Fixed:**

1. **Module URL mapping fallback:**
```php
// BEFORE (causing 404):
$viewUrl = $moduleUrls[$referenceType] ?? "/ergon/{$referenceType}";

// AFTER (fixed):
$viewUrl = $moduleUrls[$referenceType] ?? "/ergon/dashboard";
```

2. **Added 'system' to module URL mapping:**
```php
$moduleUrls = [
    'leave' => '/ergon/leaves',
    'expense' => '/ergon/expenses', 
    'advance' => '/ergon/advances',
    'task' => '/ergon/tasks',
    'system' => '/ergon/dashboard'  // Added this line
];
```

3. **Added 'system' case to switch statement:**
```php
case 'system':
    $viewUrl = "/ergon/dashboard";
    break;
default:
    $viewUrl = "/ergon/dashboard";  // Changed from dynamic URL
```

## How the Fix Works

### Before Fix:
- System notifications with `reference_type = 'system'` generated URLs like `/ergon/system`
- This URL doesn't exist in routes.php, causing 404 errors
- Fallback logic also created invalid dynamic URLs

### After Fix:
- System notifications now redirect to `/ergon/dashboard`
- All unknown reference types default to dashboard instead of generating invalid URLs
- Proper fallback handling prevents 404 errors

## Testing
1. **Access notifications** from the header notification icon
2. **Click "View" on any task notification** - should redirect to appropriate task/dashboard page
3. **Click "View" on system notifications** - should redirect to dashboard
4. **No more 404 errors** when viewing notifications

## Expected Result
- ✅ Task notifications redirect to correct task details page
- ✅ System notifications redirect to dashboard
- ✅ No more 404 errors from notification "View" links
- ✅ All notification types have valid redirect URLs

The fix is minimal and targeted, ensuring that all notification types have valid redirect destinations while maintaining existing functionality for other notification types.
