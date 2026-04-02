<?php
/**
 * Login Diagnostic Script
 * Access: aes.athenas.co.in/ergon/diag_login.php
 * DELETE THIS FILE after debugging is complete.
 */

// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];

function pass($label, $detail = '') {
    return ['status' => 'PASS', 'label' => $label, 'detail' => $detail];
}
function fail($label, $detail = '') {
    return ['status' => 'FAIL', 'label' => $label, 'detail' => $detail];
}
function info($label, $detail = '') {
    return ['status' => 'INFO', 'label' => $label, 'detail' => $detail];
}

// ── 1. SERVER ENVIRONMENT ────────────────────────────────────────────────────
$host       = $_SERVER['HTTP_HOST']          ?? 'unknown';
$serverName = $_SERVER['SERVER_NAME']        ?? 'unknown';
$docRoot    = $_SERVER['DOCUMENT_ROOT']      ?? 'unknown';
$scriptPath = $_SERVER['SCRIPT_FILENAME']    ?? 'unknown';
$requestUri = $_SERVER['REQUEST_URI']        ?? 'unknown';
$https      = $_SERVER['HTTPS']              ?? '';
$fwProto    = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
$serverPort = $_SERVER['SERVER_PORT']        ?? '';

$results['server'][] = info('HTTP_HOST',              $host);
$results['server'][] = info('SERVER_NAME',            $serverName);
$results['server'][] = info('DOCUMENT_ROOT',          $docRoot);
$results['server'][] = info('SCRIPT_FILENAME',        $scriptPath);
$results['server'][] = info('REQUEST_URI',            $requestUri);
$results['server'][] = info('HTTPS',                  $https ?: '(empty)');
$results['server'][] = info('HTTP_X_FORWARDED_PROTO', $fwProto ?: '(empty)');
$results['server'][] = info('SERVER_PORT',            $serverPort);

$isHttps = ($https && $https !== 'off') || $fwProto === 'https' || $serverPort == 443;
$results['server'][] = info('Detected protocol', $isHttps ? 'HTTPS' : 'HTTP');

// ── 2. SUBDOMAIN DETECTION ───────────────────────────────────────────────────
$isAes = strpos($host, 'aes.') === 0;
$results['subdomain'][] = $isAes
    ? pass('Host starts with "aes."', "Host: $host — will load .env.production.aes")
    : fail('Host does NOT start with "aes."', "Host: $host — will load .env.production instead of .env.production.aes");

// ── 3. ENVIRONMENT CLASS ─────────────────────────────────────────────────────
require_once __DIR__ . '/app/config/environment.php';
$env     = Environment::detect();
$baseUrl = Environment::getBaseUrl();

$results['environment'][] = info('Detected environment', $env);
$results['environment'][] = ($env === 'production')
    ? pass('Environment is production')
    : fail('Environment is NOT production', "Got: $env — DB env-file selection will use .env (dev) instead of production files");

$results['environment'][] = info('getBaseUrl()', $baseUrl);
$expectedBase = ($isHttps ? 'https' : 'http') . '://' . $host . '/ergon';
$results['environment'][] = ($baseUrl === $expectedBase)
    ? pass('Base URL matches expected', $baseUrl)
    : fail('Base URL MISMATCH', "Got: $baseUrl | Expected: $expectedBase");

// ── 4. ENV FILE SELECTION ────────────────────────────────────────────────────
$envBase    = __DIR__;
$envDevFile = $envBase . '/.env';
$envProdFile= $envBase . '/.env.production';
$envAesFile = $envBase . '/.env.production.aes';

$results['envfiles'][] = file_exists($envDevFile)  ? pass('.env exists')                : fail('.env missing');
$results['envfiles'][] = file_exists($envProdFile) ? pass('.env.production exists')     : fail('.env.production missing');
$results['envfiles'][] = file_exists($envAesFile)  ? pass('.env.production.aes exists') : fail('.env.production.aes missing — AES DB config not found');

// Simulate database.php selection logic
if ($env === 'production') {
    if ($isAes && file_exists($envAesFile)) {
        $selectedEnv = $envAesFile;
        $results['envfiles'][] = pass('Correct env file selected', '.env.production.aes');
    } elseif (file_exists($envProdFile)) {
        $selectedEnv = $envProdFile;
        $results['envfiles'][] = $isAes
            ? fail('Wrong env file selected', '.env.production loaded instead of .env.production.aes')
            : pass('Correct env file selected', '.env.production');
    } else {
        $selectedEnv = $envDevFile;
        $results['envfiles'][] = fail('Fallback to .env (dev) — no production env file found');
    }
} else {
    $selectedEnv = $envDevFile;
    $results['envfiles'][] = fail('Using .env (dev) because environment is not production');
}
$results['envfiles'][] = info('Selected env file', $selectedEnv);

// Parse selected env file
$envVars = [];
if (file_exists($selectedEnv)) {
    foreach (file($selectedEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$k, $v] = explode('=', $line, 2);
            $envVars[trim($k)] = trim($v);
        }
    }
}

$results['envfiles'][] = info('DB_NAME from selected file', $envVars['DB_NAME'] ?? '(not set)');
$results['envfiles'][] = info('DB_USER from selected file', $envVars['DB_USER'] ?? '(not set)');
$results['envfiles'][] = info('DB_HOST from selected file', $envVars['DB_HOST'] ?? '(not set)');

// ── 5. DATABASE CONNECTION ───────────────────────────────────────────────────
$dbHost = $envVars['DB_HOST'] ?? 'localhost';
$dbName = $envVars['DB_NAME'] ?? '';
$dbUser = $envVars['DB_USER'] ?? '';
$dbPass = $envVars['DB_PASS'] ?? '';

if (empty($dbName) || empty($dbUser)) {
    $results['database'][] = fail('DB credentials empty', 'DB_NAME or DB_USER not loaded from env file');
} else {
    try {
        $pdo = new PDO(
            "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
            $dbUser, $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        $results['database'][] = pass('Database connection successful', "Host: $dbHost | DB: $dbName");

        // Check users table exists
        $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
        if ($tables) {
            $results['database'][] = pass('users table exists');
            $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $results['database'][] = info('User count in DB', $count);

            // Check for active users
            try {
                $active = $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
                $results['database'][] = info('Active users', $active);
            } catch (Exception $e) {
                $results['database'][] = info('Could not count active users', $e->getMessage());
            }

            // Check login_attempts table (used by SecurityService)
            $la = $pdo->query("SHOW TABLES LIKE 'login_attempts'")->fetchAll();
            $results['database'][] = $la
                ? pass('login_attempts table exists')
                : fail('login_attempts table MISSING', 'SecurityService::checkRateLimit() will throw an exception and block all logins');

            // Check rate_limits table
            $rl = $pdo->query("SHOW TABLES LIKE 'rate_limits'")->fetchAll();
            $results['database'][] = $rl
                ? pass('rate_limits table exists')
                : fail('rate_limits table MISSING', 'SecurityService::checkRateLimit() may fail');

        } else {
            $results['database'][] = fail('users table MISSING', 'Database exists but has no users table — schema not imported');
        }

    } catch (PDOException $e) {
        $results['database'][] = fail('Database connection FAILED', $e->getMessage());
        $results['database'][] = info('Tried connecting to', "Host: $dbHost | DB: $dbName | User: $dbUser");
    }
}

// ── 6. SESSION CONFIGURATION ─────────────────────────────────────────────────
$cookieDomain = $host;
if (strpos($cookieDomain, ':') !== false) {
    $cookieDomain = explode(':', $cookieDomain)[0];
}
$results['session'][] = info('session.cookie_domain will be set to', $cookieDomain);
$results['session'][] = info('session.cookie_secure will be', $isHttps ? '1 (HTTPS)' : '0 (HTTP)');
$results['session'][] = info('PHP session.save_path', ini_get('session.save_path') ?: '(default)');

// Test session write
session_name('diag_test_session');
@session_start();
$_SESSION['diag_test'] = 'ok';
$results['session'][] = (session_status() === PHP_SESSION_ACTIVE)
    ? pass('Session started successfully', 'Session ID: ' . session_id())
    : fail('Session failed to start');
session_destroy();

// ── 7. HTACCESS / REWRITE ────────────────────────────────────────────────────
$htaccess = __DIR__ . '/.htaccess';
if (file_exists($htaccess)) {
    $content = file_get_contents($htaccess);
    $results['htaccess'][] = pass('.htaccess exists');

    preg_match('/RewriteBase\s+(\S+)/', $content, $m);
    $rewriteBase = $m[1] ?? '(not set)';
    $results['htaccess'][] = info('RewriteBase', $rewriteBase);

    $results['htaccess'][] = ($rewriteBase === '/ergon/' || $rewriteBase === '/ergon')
        ? pass('RewriteBase is /ergon/')
        : fail('RewriteBase is NOT /ergon/', "Got: $rewriteBase — routing will break if app is not at /ergon/");

    $results['htaccess'][] = strpos($content, 'mod_rewrite') !== false || strpos($content, 'RewriteEngine') !== false
        ? pass('mod_rewrite rules present')
        : fail('mod_rewrite rules missing');
} else {
    $results['htaccess'][] = fail('.htaccess MISSING', 'All routes will 404');
}

// ── 8. APP_URL CONSTANT ──────────────────────────────────────────────────────
require_once __DIR__ . '/app/config/constants.php';
$results['constants'][] = info('APP_URL defined as', APP_URL);
$expectedAppUrl = ($isHttps ? 'https' : 'http') . '://' . $host . '/ergon';
$results['constants'][] = (APP_URL === $expectedAppUrl)
    ? pass('APP_URL matches current host', APP_URL)
    : fail('APP_URL MISMATCH', "Got: " . APP_URL . " | Expected: $expectedAppUrl — post-login redirects will go to wrong domain");

// ── 9. SECURITY SERVICE ──────────────────────────────────────────────────────
try {
    require_once __DIR__ . '/app/services/SecurityService.php';
    $ss = new SecurityService();
    $results['security'][] = pass('SecurityService loaded');

    // Test rate limit check (uses DB)
    try {
        $allowed = $ss->checkRateLimit('127.0.0.1', 'login');
        $results['security'][] = $allowed
            ? pass('checkRateLimit() returned true (not rate-limited)')
            : fail('checkRateLimit() returned false', 'IP 127.0.0.1 is currently rate-limited — all logins blocked');
    } catch (Exception $e) {
        $results['security'][] = fail('checkRateLimit() threw exception', $e->getMessage() . ' — this will block ALL login attempts');
    }

    // Test account lockout check
    try {
        $lockout = $ss->checkAccountLockout('test@test.com');
        $results['security'][] = pass('checkAccountLockout() works', 'locked: ' . ($lockout['locked'] ? 'yes' : 'no'));
    } catch (Exception $e) {
        $results['security'][] = fail('checkAccountLockout() threw exception', $e->getMessage());
    }

} catch (Exception $e) {
    $results['security'][] = fail('SecurityService failed to load', $e->getMessage());
}

// ── 10. USER MODEL AUTH TEST ─────────────────────────────────────────────────
// Only test if DB connected
if (!empty($dbName) && !empty($dbUser)) {
    try {
        require_once __DIR__ . '/app/config/database.php';
        require_once __DIR__ . '/app/models/User.php';
        $userModel = new User();
        $results['usermodel'][] = pass('User model loaded');

        // Check password_hash column exists
        try {
            $pdo2 = new PDO(
                "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
                $dbUser, $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $cols = $pdo2->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
            $results['usermodel'][] = info('users table columns', implode(', ', $cols));

            $hasPasswordCol = in_array('password', $cols) || in_array('password_hash', $cols);
            $results['usermodel'][] = $hasPasswordCol
                ? pass('Password column exists')
                : fail('No password/password_hash column in users table');

            $hasStatusCol = in_array('status', $cols);
            $results['usermodel'][] = $hasStatusCol
                ? pass('status column exists')
                : fail('No status column — active user check may fail');

        } catch (Exception $e) {
            $results['usermodel'][] = fail('Could not describe users table', $e->getMessage());
        }

    } catch (Exception $e) {
        $results['usermodel'][] = fail('User model failed to load', $e->getMessage());
    }
}

// ── RENDER ───────────────────────────────────────────────────────────────────
$sectionLabels = [
    'server'      => '1. Server Environment',
    'subdomain'   => '2. Subdomain Detection',
    'environment' => '3. Environment Class',
    'envfiles'    => '4. Env File Selection & DB Credentials',
    'database'    => '5. Database Connection & Schema',
    'session'     => '6. Session Configuration',
    'htaccess'    => '7. .htaccess / Rewrite',
    'constants'   => '8. APP_URL Constant',
    'security'    => '9. SecurityService',
    'usermodel'   => '10. User Model',
];

$totalFails = 0;
foreach ($results as $items) {
    foreach ($items as $r) {
        if ($r['status'] === 'FAIL') $totalFails++;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Ergon Login Diagnostics</title>
<style>
  body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; }
  h1   { color: #f8fafc; margin-bottom: 0.25rem; }
  .sub { color: #94a3b8; margin-bottom: 2rem; font-size: 0.9rem; }
  h2   { color: #7dd3fc; margin: 1.5rem 0 0.5rem; font-size: 1rem; border-bottom: 1px solid #1e3a5f; padding-bottom: 4px; }
  .row { display: flex; gap: 1rem; padding: 4px 0; font-size: 0.85rem; }
  .badge { min-width: 44px; text-align: center; border-radius: 4px; padding: 1px 6px; font-weight: bold; font-size: 0.75rem; }
  .PASS { background: #14532d; color: #86efac; }
  .FAIL { background: #7f1d1d; color: #fca5a5; }
  .INFO { background: #1e3a5f; color: #93c5fd; }
  .label { color: #cbd5e1; min-width: 280px; }
  .detail { color: #94a3b8; word-break: break-all; }
  .summary { margin-top: 2rem; padding: 1rem; border-radius: 8px; font-size: 1rem; }
  .ok  { background: #14532d; color: #86efac; }
  .bad { background: #7f1d1d; color: #fca5a5; }
  .warn { background: #78350f; color: #fcd34d; margin-top: 1rem; padding: 0.75rem; border-radius: 6px; font-size: 0.8rem; }
</style>
</head>
<body>
<h1>🔍 Ergon Login Diagnostics</h1>
<div class="sub">Host: <?= htmlspecialchars($host) ?> &nbsp;|&nbsp; <?= date('Y-m-d H:i:s') ?></div>

<?php foreach ($sectionLabels as $key => $label): ?>
  <?php if (!isset($results[$key])) continue; ?>
  <h2><?= $label ?></h2>
  <?php foreach ($results[$key] as $r): ?>
  <div class="row">
    <span class="badge <?= $r['status'] ?>"><?= $r['status'] ?></span>
    <span class="label"><?= htmlspecialchars($r['label']) ?></span>
    <span class="detail"><?= htmlspecialchars($r['detail']) ?></span>
  </div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="summary <?= $totalFails === 0 ? 'ok' : 'bad' ?>">
  <?= $totalFails === 0
    ? '✅ All checks passed — login issue may be browser/cookie related. Clear cookies and retry.'
    : "❌ $totalFails check(s) FAILED — fix the red items above to resolve the login issue." ?>
</div>

<div class="warn">
  ⚠️ <strong>DELETE this file after debugging:</strong> rm ergon/diag_login.php
</div>
</body>
</html>
