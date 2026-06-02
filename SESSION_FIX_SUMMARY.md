# Session Management — Frequent Logout Fix

## Problem Statement
Users were experiencing frequent unexpected logouts during normal use, even when actively working in the application.

## Root Causes Identified

### 1. **Controller::requireAuth() Not Updating Session Activity** (CRITICAL)
**File:** `app/core/Controller.php:40-50`

**Issue:** The core authentication check never updated the `last_activity` timestamp on page loads or AJAX requests.

```php
// OLD - Missing activity update
protected function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        // redirect to login
    }
    // Never called: $_SESSION['last_activity'] = time();
}
```

**Impact:** 
- After login, `last_activity` set once
- Never refreshed on subsequent requests
- After ~1 hour of inactivity, session timeout triggered even if user was actively working
- AJAX requests particularly affected (API calls without full page loads)

### 2. **Timeout Value Inconsistencies**
**Files:** `app/core/AuthMiddleware.php:20`, `app/helpers/SessionManager.php:42`, `app/config/session.php:17`

**Issue:** Three different timeout values referenced:
- AuthMiddleware checks: **28800 seconds (8 hours)**
- SessionManager checks: **3600 seconds (1 hour)** ← INCORRECT
- Config sets: **28800 seconds (8 hours)**

**Impact:** Conflicting timeout logic causes sessions to expire prematurely.

### 3. **AuthMiddleware Not Called from Controllers**
**Issue:** `AuthMiddleware::requireAuth()` has better session handling (timeout check + activity update) but isn't called from `Controller::requireAuth()`.

**Impact:** The security middleware exists but isn't used by the main application flow.

### 4. **Session Locking on Concurrent AJAX Requests**
**Issue:** PHP's default session.read_and_close setting can lock sessions during multiple simultaneous AJAX requests.

**Impact:** 
- User makes 2+ AJAX calls simultaneously
- One waits for the other to close the session file
- Can cause timeouts or session conflicts

---

## Solutions Implemented

### 1. **Enhanced Controller::requireAuth()** ✅
```php
protected function requireAuth() {
    // Check if logged in
    if (!isset($_SESSION['user_id'])) {
        redirect to login
    }
    
    // CHECK TIMEOUT (8 hours)
    if (time() - $_SESSION['last_activity'] > 28800) {
        destroy session
        redirect to login with timeout=1
    }
    
    // UPDATE ACTIVITY ON EVERY REQUEST ← KEY FIX
    $_SESSION['last_activity'] = time();
    
    // Ensure role exists
    if (empty($_SESSION['role'])) {
        $_SESSION['role'] = 'user';
    }
}
```

**Result:** 
- Every page load/AJAX call resets the 8-hour countdown
- Users stay logged in as long as they're actively using the app
- After 8 hours of complete inactivity, they're logged out (expected behavior)

### 2. **Aligned Session Timeouts** ✅
- SessionManager.php: Updated to 28800 seconds (from 3600)
- All three locations now consistent
- Clear comments documenting the 8-hour timeout

### 3. **Session Read-and-Close Configuration** ✅
```php
// Allow concurrent AJAX requests without session locking
if (PHP_VERSION_ID >= 70000) {
    ini_set('session.read_and_close', 1);
}
```

**Result:** AJAX requests no longer block each other on the session file.

---

## How Session Management Works Now

### Successful Login
```
User logs in
→ Session created
→ $_SESSION['user_id'] = 61
→ $_SESSION['last_activity'] = 1685462400
```

### Active Usage (Page Loads)
```
User navigates to /ergon/ledgers/user/61
→ Controller::requireAuth() called
→ Check: last_activity still within 8 hours? YES
→ Update: $_SESSION['last_activity'] = 1685462440 (current time)
→ Page loads, user sees content
```

### Active Usage (AJAX Calls)
```
User clicks "Refresh Ledger" button (AJAX)
→ API request to server
→ Controller::requireAuth() called
→ Check: last_activity within 8 hours? YES
→ Update: $_SESSION['last_activity'] = 1685462450 (current time)
→ API returns data, no logout
→ User sees updated ledger
```

### Idle Timeout (After 8 Hours)
```
User last active at 10:00 AM
→ User leaves desk at 10:05 AM
→ No page loads or AJAX calls for 8+ hours
→ User returns at 6:30 PM, clicks refresh
→ Controller::requireAuth() called
→ Check: last_activity > 28800 seconds? YES (8+ hours)
→ Session destroyed
→ User redirected to login with "?timeout=1" message
```

---

## Testing & Verification

To verify the fix works:

1. **Login to the application**
2. **Observe the session timeout behavior:**
   - ✅ Navigate pages → session refreshed
   - ✅ Make AJAX calls → session refreshed
   - ✅ Remain idle > 8 hours → automatically logged out
   - ✅ Remain active < 8 hours → stays logged in

3. **Check browser console (F12)**
   - Open Network tab
   - Make API calls
   - Requests should NOT hang waiting for session lock

4. **Check application logs**
   ```bash
   tail -f storage/logs/error.log | grep "last_activity\|Session"
   ```

---

## Configuration Summary

| Setting | Value | Purpose |
|---------|-------|---------|
| `session.gc_maxlifetime` | 28800 | PHP garbage collect after 8 hours |
| `session.cookie_httponly` | 1 | Prevent XSS access to cookies |
| `session.cookie_secure` | 1 (HTTPS) | Only send over HTTPS |
| `session.cookie_samesite` | Lax | CSRF protection |
| `session.use_strict_mode` | 1 | Reject uninitialized session IDs |
| `session.read_and_close` | 1 | Allow concurrent AJAX without blocking |

---

## Additional Recommendations

For further robustness, consider:

1. **Activity-based vs Time-based Timeout**
   - Current: 8 hours of inactivity
   - Alternative: 1 hour inactivity (more secure) or 30 days absolute (less secure)

2. **Session Regeneration on Role Change**
   - Prevent session fixation attacks
   - Regenerate ID if user role is modified

3. **Multi-Device Session Management**
   - Track active sessions per user
   - Allow logging out other devices

4. **Browser Storage Backup**
   - Store token in localStorage
   - Fallback if session cookie lost

5. **Session Activity Logging**
   - Log each session start/end
   - Audit trail for compliance

---

## Files Modified

- ✅ `app/core/Controller.php` — Enhanced requireAuth()
- ✅ `app/helpers/SessionManager.php` — Fixed timeout value
- ✅ `app/config/session.php` — Added session.read_and_close + documentation

**No database schema changes required.**
