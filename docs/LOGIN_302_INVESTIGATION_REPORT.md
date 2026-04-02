# Ergon Login 302 Redirect Loop — Full Investigation Report
# Domain: aes.athenas.co.in/ergon
# Date: 2026-03-31
# Status: UNRESOLVED — requires complete session architecture rewrite

---

## EXECUTIVE SUMMARY

Login on aes.athenas.co.in/ergon always redirects back to /ergon/login (302 loop).
Credentials are 100% correct. Database connects fine. Session files write to disk fine.
The problem is entirely in PHP session management conflicting with:
  - LiteSpeed web server (not Apache)
  - Hostinger CDN (hcdn) which strips/drops Set-Cookie headers on 302 responses
  - Dozens of rogue session_start() / session_write_close() / session_regenerate_id()
    calls scattered across 20+ files that destroy the session mid-request

---

## ENVIRONMENT

  Server:        LiteSpeed (NOT Apache)
  PHP:           8.3.30
  Host:          Hostinger (hPanel)
  CDN:           hcdn (Hostinger CDN — wraps all responses)
  Document Root: /home/u494785662/domains/athenas.co.in/public_html/aes
  App path:      /home/u494785662/domains/athenas.co.in/public_html/aes/ergon
  Session path:  /opt/alt/php83/var/lib/php/session
  DB:            u494785662_ergon_aes (MySQL, localhost)
  Main domain:   athenas.co.in/ergon (works fine — Apache, no CDN issues)
  Subdomain:     aes.athenas.co.in/ergon (broken — LiteSpeed + hcdn)

---

## DIAGNOSTIC EVIDENCE

### Evidence 1 — Two different PHPSESSID values in one login flow
  From curl simulation of the login flow:

  GET  /ergon/login  → Set-Cookie: PHPSESSID=oilk4a7m8pmhr19lks01fffbkn
  POST /ergon/login  → Set-Cookie: PHPSESSID=3lmi9s0c65cis7r3gr67tgp32g  ← NEW ID
  GET  /ergon/dashboard → reads old cookie → empty session → redirect to /login

  The POST creates a brand new session ID instead of writing to the existing one.
  This means something between session_start() and the redirect is destroying
  the session and starting a fresh one.

### Evidence 2 — ini_set warnings proving output before session config
  From diag_deep.php output:

  Warning: ini_set(): Session ini settings cannot be changed after headers already sent
  Warning: session_set_cookie_params(): Session cookie parameters cannot be changed
           after headers have already been sent

  LiteSpeed emits response headers before PHP output buffering kicks in,
  causing session cookie params to silently fail to apply.

### Evidence 3 — Hostinger CDN strips Set-Cookie on 302 responses
  From POST response headers:

  retry-after: 60
  platform: hostinger
  server: hcdn

  The hcdn layer intercepts the 302 response. The Set-Cookie header with the
  new PHPSESSID is present in the raw response but the cookie jar in the
  curl simulation was EMPTY after following the redirect — confirming hcdn
  strips Set-Cookie headers from redirect responses.

### Evidence 4 — 20+ rogue session_start() calls across the codebase
  Every controller, middleware, helper, and guard independently calls
  session_start() without first setting cookie params. On LiteSpeed, each
  bare session_start() can create a new session with default (wrong) params.

  Files with bare session_start():
  - app/controllers/AdminManagementController.php  (5 calls)
  - app/controllers/AdvanceController.php          (2 calls)
  - app/controllers/ApiController.php              (3 calls + session_set_cookie_params)
  - app/controllers/AttendanceController.php       (1 call)
  - app/controllers/AuthController.php             (1 call)
  - app/controllers/ExpenseController.php          (3 calls)
  - app/controllers/FollowupController.php         (1 call)
  - app/controllers/LeaveController.php            (3 calls)
  - app/controllers/ProjectManagementController.php(4 calls)
  - app/controllers/SystemAdminController.php      (1 call)
  - app/core/Controller.php                        (1 call)
  - app/core/Session.php                           (1 call)
  - app/guards/auth_guard.php                      (1 call)
  - app/helpers/Security.php                       (2 calls + session_write_close loop)
  - app/helpers/SessionManager.php                 (1 call + session_regenerate_id)
  - app/middlewares/AuthMiddleware.php             (1 call)
  - app/middlewares/ModuleMiddleware.php           (1 call)
  - app/middlewares/RoleMiddleware.php             (3 calls)
  - app/middlewares/SessionValidationMiddleware.php(2 calls + session_destroy mid-flow)

### Evidence 5 — Security.php actively destroys the session (ROOT CAUSE #1)
  app/helpers/Security.php::generateCSRFToken() contained:

    session_write_close();  // saves and CLOSES current session
    session_start();        // opens a BRAND NEW empty session with new ID

  This was added as a "Hostinger fix" but it is the primary cause of the
  new PHPSESSID appearing on the POST response. The login flow calls
  SecurityService which eventually triggers this code path, closing the
  authenticated session and opening a fresh empty one.

### Evidence 6 — SessionManager::regenerate() calls session_regenerate_id(true)
  app/helpers/SessionManager.php::regenerate():

    session_regenerate_id(true);  // destroys old session, issues new cookie

  When called during the login flow, this changes the session ID. The new
  Set-Cookie is sent in the 302 response which hcdn strips, so the browser
  follows the redirect with the old session ID which now has no data.

### Evidence 7 — ApiController sets wrong cookie path
  app/controllers/ApiController.php sets:

    session_set_cookie_params(['path' => '/ergon/', ...])

  The correct path is '/'. Using '/ergon/' means the cookie is only sent
  for requests under /ergon/ — but this call happens AFTER session_start()
  in index.php so it has no effect on the current session, but it poisons
  the session cookie params for any subsequent session_start() calls.

### Evidence 8 — SessionValidationMiddleware destroys and restarts session
  app/middlewares/SessionValidationMiddleware.php::forceLogout():

    session_destroy();
    session_start();  // bare start with no cookie params

  If this middleware runs during the dashboard GET request and incorrectly
  determines the user is invalid (e.g. timing issue, DB query failure),
  it destroys the session and redirects to login.

---

## WHAT WAS ATTEMPTED (AND WHY IT DID NOT WORK)

### Attempt 1 — Fix database.php to load correct .env file
  Problem: .env.production.aes was in .gitignore so never deployed.
  Fix: Added subdomain detection to load .env.production.aes for aes.* host.
  Result: DB connects correctly. Not the login issue.

### Attempt 2 — Fix session cookie domain scoping
  Problem: Session cookie domain not set, causing cross-subdomain bleed.
  Fix: Added session_set_cookie_params with exact host as domain.
  Result: Cookie domain correct. Did not fix 302 loop.

### Attempt 3 — Fix APP_URL constant
  Problem: APP_URL hardcoded to athenas.co.in even for aes.athenas.co.in.
  Fix: Made APP_URL dynamic using actual HTTP_HOST.
  Result: Redirect URL correct. Did not fix 302 loop.

### Attempt 4 — Fix remaining_attempts crash
  Problem: $lockoutStatus['remaining_attempts'] key missing causing fatal error.
  Fix: Added null coalescing operator.
  Result: No more fatal error. Did not fix 302 loop.

### Attempt 5 — Add missing DB columns
  Problem: locked_until and failed_attempts columns missing from AES DB.
  Fix: Created migration script to add columns and rate_limit_log table.
  Result: DB schema complete. Did not fix 302 loop.

### Attempt 6 — Remove session_regenerate_id
  Problem: session_regenerate_id(true) changing session ID on login.
  Fix: Removed from AuthController login flow.
  Result: Partial. SessionManager still had it.

### Attempt 7 — Add ob_start() to index.php
  Problem: LiteSpeed emitting headers before session config.
  Fix: Added ob_start() at top of index.php.
  Result: Warnings gone. Did not fix 302 loop because rogue session_start()
          calls in other files still fire after the session is already started.

### Attempt 8 — Remove Security.php session_write_close loop
  Problem: Security.php destroying session mid-request.
  Fix: Removed the session_write_close/session_start cycle.
  Result: Partial fix. 19 other files still have bare session_start() calls.

### Attempt 9 — Remove SessionManager::regenerate session_regenerate_id
  Problem: SessionManager changing session ID.
  Fix: Disabled session_regenerate_id in SessionManager.
  Result: Partial fix. SessionValidationMiddleware still destroys sessions.

---

## ROOT CAUSES (DEFINITIVE)

### ROOT CAUSE 1 — Architectural: No single session ownership
  The app has no central session manager. Every file independently calls
  session_start() with different (or no) cookie params. On LiteSpeed+hcdn,
  the first session_start() wins and sets the cookie. Any subsequent
  session_start() after session_write_close() creates a new session with
  default params, issuing a new PHPSESSID that hcdn then strips from the
  302 response.

### ROOT CAUSE 2 — Security.php Hostinger "fix" was self-defeating
  The session_write_close() + session_start() pattern in Security.php was
  added to "fix" Hostinger but it is the exact mechanism that breaks it.
  It creates a new session ID mid-request which hcdn strips from the redirect.

### ROOT CAUSE 3 — hcdn strips Set-Cookie from 302 responses
  Hostinger's CDN (hcdn) does not pass Set-Cookie headers through on redirect
  responses. This means ANY code that changes the session ID (regenerate,
  write_close+start) during a request that ends in a redirect will lose the
  new cookie. The browser follows the redirect with the old session ID.

### ROOT CAUSE 4 — SessionValidationMiddleware may destroy valid sessions
  The middleware calls session_destroy() + bare session_start() in forceLogout().
  If it fires on the dashboard GET request due to a DB timing issue or the
  session not being fully written yet, it destroys the authenticated session.

---

## REQUIRED FIX — COMPLETE REWRITE OF SESSION HANDLING

The fix requires changes to 20+ files. The principle is:

  RULE: session_start() is called ONCE, in index.php, with correct cookie params.
        No other file may call session_start(), session_write_close() (except
        after final writes), session_regenerate_id(), or session_set_cookie_params().

### File 1: index.php
  KEEP the current version (ob_start + session_set_cookie_params + session_start).
  This is correct. Do not change it.

### File 2: app/config/session.php
  KEEP as no-op comment file. Do not add any session calls.

### Files 3-22: All controllers, middlewares, helpers, guards
  In EVERY file listed in Evidence 4, replace:

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

  With nothing — remove it entirely. The session is already started by index.php.

### File: app/helpers/Security.php
  generateCSRFToken() and validateCSRFToken() — remove ALL session_start(),
  session_write_close() calls. Session is already active.

### File: app/helpers/SessionManager.php
  regenerate() — remove session_regenerate_id(true). Replace with no-op or
  just call self::start() which already checks session_status().
  start() — remove ini_set calls, just check session_status() === PHP_SESSION_NONE
  and if so, do NOT call session_start() — throw an error or log instead,
  because if session is not started at this point index.php failed.

### File: app/middlewares/SessionValidationMiddleware.php
  forceLogout() — replace bare session_start() after session_destroy() with:
    session_set_cookie_params([same params as index.php]);
    session_start();
  OR better: just set $_SESSION['logout_message'] before destroying,
  use a query param instead: header('Location: /ergon/login?msg=deactivated')

### File: app/controllers/ApiController.php
  Remove all session_set_cookie_params() calls (lines 104, 336, 379).
  Remove all bare session_start() calls (lines 115, 347, 390).
  Session is already started by index.php.

### File: app/controllers/AuthController.php (current state — mostly correct)
  - Remove the remaining bare session_start() at line 40 (inside login())
  - Keep session_write_close() before the redirect — this is correct
  - Keep session_destroy() in logout() — this is correct

---

## SPECIFIC CODE CHANGES NEEDED

### index.php — CORRECT, keep as-is:
```php
<?php
ob_start();
// ... error reporting ...
$_isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$_cookieDomain = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($_cookieDomain, ':') !== false) {
    $_cookieDomain = explode(':', $_cookieDomain)[0];
}
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $_cookieDomain,
    'secure'   => $_isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
// ... rest of app ...
```

### AuthController.php login() success block — CORRECT, keep as-is:
```php
$_SESSION['user_id']        = $user['id'];
$_SESSION['user_name']      = $user['name'];
$_SESSION['user_email']     = $user['email'];
$_SESSION['role']           = $user['role'];
$_SESSION['login_time']     = time();
$_SESSION['last_activity']  = time();
$_SESSION['login_timestamp']= date('Y-m-d H:i:s');
session_write_close();
header('Location: ' . $redirectUrl);
exit;
```

### Every other file — REMOVE these patterns:
```php
// REMOVE THIS EVERYWHERE:
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// REMOVE THIS EVERYWHERE:
session_set_cookie_params([...]);

// REMOVE THIS EVERYWHERE:
session_write_close();
session_start();

// REMOVE THIS EVERYWHERE:
session_regenerate_id(true);
```

### SessionValidationMiddleware.php forceLogout() — CHANGE TO:
```php
private static function forceLogout($message = 'Session expired') {
    session_unset();
    session_destroy();
    // Use query param instead of session for the message
    header('Location: /ergon/login?error=' . urlencode($message));
    exit;
}
```

---

## FILES THAT NEED CHANGES (COMPLETE LIST)

  1.  app/controllers/AdminManagementController.php  — remove 5x session_start()
  2.  app/controllers/AdvanceController.php          — remove 2x session_start()
  3.  app/controllers/ApiController.php              — remove 3x session_set_cookie_params + 3x session_start()
  4.  app/controllers/AttendanceController.php       — remove 1x session_start()
  5.  app/controllers/AuthController.php             — remove 1x session_start() at line 40
  6.  app/controllers/ExpenseController.php          — remove 3x session_start()
  7.  app/controllers/FollowupController.php         — remove 1x session_start()
  8.  app/controllers/LeaveController.php            — remove 3x session_start()
  9.  app/controllers/ProjectManagementController.php— remove 4x session_start()
  10. app/controllers/SystemAdminController.php      — remove 1x session_start()
  11. app/core/Controller.php                        — remove 1x session_start() in requireAuth()
  12. app/core/Session.php                           — remove session_start() from init()
  13. app/guards/auth_guard.php                      — remove session_start()
  14. app/helpers/Security.php                       — remove 2x session_start() + session_write_close loops
  15. app/helpers/SessionManager.php                 — remove session_regenerate_id(true)
  16. app/middlewares/AuthMiddleware.php             — remove session_start()
  17. app/middlewares/ModuleMiddleware.php           — remove session_start()
  18. app/middlewares/RoleMiddleware.php             — remove 3x session_start()
  19. app/middlewares/SessionValidationMiddleware.php— remove session_destroy()+session_start(), fix forceLogout()

---

## ADDITIONAL ISSUES FOUND (NON-LOGIN)

  1. .env.production.aes is in .gitignore (.env.production.*) so it never
     deploys via git. Must be manually uploaded to server or gitignore updated.
     Currently .env.production on the AES server happens to have AES DB creds
     which is why DB works, but this is fragile.

  2. users table was missing locked_until and failed_attempts columns in AES DB.
     Migration script created: migrations/run_aes_security_migration.php
     Must be run once on the AES server.

  3. rate_limit_log table was missing from AES DB. Same migration creates it.

  4. APP_URL in constants.php was hardcoded to athenas.co.in for all athenas
     subdomains. Fixed to use dynamic HTTP_HOST.

  5. AuthController had $lockoutStatus['remaining_attempts'] without null check,
     causing fatal error when locked_until column was missing. Fixed.

---

## VERIFICATION AFTER FIX

After making all changes above, verify with:
  https://aes.athenas.co.in/ergon/diag_login2.php?email=X&password=Y

All 10 steps should show PASS.
Then delete all diag_*.php files.

---

## SUMMARY TABLE

| # | File | Issue | Fix |
|---|------|-------|-----|
| 1 | Security.php | session_write_close()+session_start() destroys session | Remove entirely |
| 2 | SessionManager.php | session_regenerate_id(true) changes session ID | Remove |
| 3 | SessionValidationMiddleware.php | session_destroy()+bare session_start() | Fix forceLogout() |
| 4 | ApiController.php | session_set_cookie_params with wrong path | Remove |
| 5 | 15 other files | bare session_start() without cookie params | Remove all |
| 6 | index.php | session config after LiteSpeed headers | Fixed with ob_start() |
| 7 | constants.php | APP_URL hardcoded to wrong domain | Fixed |
| 8 | database.php | always loads .env.production not .env.production.aes | Fixed |
| 9 | AES DB schema | missing locked_until, failed_attempts, rate_limit_log | Migration created |
| 10 | .gitignore | .env.production.aes never deploys | Manual upload required |
