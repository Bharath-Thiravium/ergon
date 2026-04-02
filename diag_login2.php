<?php
/**
 * Login Flow Tracer
 * Access: https://aes.athenas.co.in/ergon/diag_login2.php?email=YOUR_EMAIL&password=YOUR_PASS
 * DELETE after debugging.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$steps = [];
$email    = $_GET['email']    ?? '';
$password = $_GET['password'] ?? '';

function step($n, $label, $status, $detail = '') {
    global $steps;
    $steps[] = ['n' => $n, 'label' => $label, 'status' => $status, 'detail' => $detail];
}

// ── STEP 1: session start ────────────────────────────────────────────────────
try {
    require_once __DIR__ . '/app/config/session.php';
    if (session_status() === PHP_SESSION_NONE) session_start();
    step(1, 'Session start', 'PASS', 'ID: ' . session_id());
} catch (Throwable $e) {
    step(1, 'Session start', 'FAIL', $e->getMessage()); goto render;
}

// ── STEP 2: environment ──────────────────────────────────────────────────────
try {
    require_once __DIR__ . '/app/config/environment.php';
    $env     = Environment::detect();
    $baseUrl = Environment::getBaseUrl();
    step(2, 'Environment detect', 'PASS', "env=$env  baseUrl=$baseUrl");
} catch (Throwable $e) {
    step(2, 'Environment detect', 'FAIL', $e->getMessage()); goto render;
}

// ── STEP 3: database load ────────────────────────────────────────────────────
try {
    require_once __DIR__ . '/app/config/database.php';
    $pdo = Database::connect();
    $dbName = $_ENV['DB_NAME'] ?? '(unknown)';
    step(3, 'Database connect', 'PASS', "DB: $dbName");
} catch (Throwable $e) {
    step(3, 'Database connect', 'FAIL', $e->getMessage()); goto render;
}

// ── STEP 4: check users table columns ───────────────────────────────────────
try {
    $cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_diff(['locked_until', 'failed_attempts'], $cols);
    if ($missing) {
        step(4, 'users table columns', 'FAIL',
            'Missing columns: ' . implode(', ', $missing) .
            ' — run migrations/run_aes_security_migration.php first');
        goto render;
    }
    step(4, 'users table columns', 'PASS', 'locked_until + failed_attempts present');
} catch (Throwable $e) {
    step(4, 'users table columns', 'FAIL', $e->getMessage()); goto render;
}

// ── STEP 5: check rate_limit_log table ──────────────────────────────────────
try {
    $pdo->query("SELECT 1 FROM rate_limit_log LIMIT 1");
    step(5, 'rate_limit_log table', 'PASS');
} catch (Throwable $e) {
    step(5, 'rate_limit_log table', 'FAIL',
        'Table missing — run migrations/run_aes_security_migration.php  |  ' . $e->getMessage());
    goto render;
}

// ── STEP 6: SecurityService::checkRateLimit ──────────────────────────────────
try {
    require_once __DIR__ . '/app/services/SecurityService.php';
    $ss      = new SecurityService();
    $allowed = $ss->checkRateLimit('127.0.0.1', 'login');
    step(6, 'checkRateLimit()', $allowed ? 'PASS' : 'FAIL',
        $allowed ? 'not rate-limited' : 'IP is rate-limited — all logins blocked');
    if (!$allowed) goto render;
} catch (Throwable $e) {
    step(6, 'checkRateLimit()', 'FAIL', $e->getMessage()); goto render;
}

// ── STEP 7: SecurityService::checkAccountLockout ────────────────────────────
if (empty($email)) {
    step(7, 'checkAccountLockout()', 'SKIP', 'No ?email= provided in URL');
} else {
    try {
        $lockout = $ss->checkAccountLockout($email);
        $hasKey  = array_key_exists('remaining_attempts', $lockout);
        step(7, 'checkAccountLockout()', 'PASS',
            'locked=' . ($lockout['locked'] ? 'YES ← BLOCKED' : 'no') .
            '  remaining_attempts key=' . ($hasKey ? $lockout['remaining_attempts'] : 'MISSING ← will crash AuthController'));
        if ($lockout['locked']) {
            step(7, 'Account lockout', 'FAIL', $lockout['message'] ?? 'Account is locked'); goto render;
        }
    } catch (Throwable $e) {
        step(7, 'checkAccountLockout()', 'FAIL', $e->getMessage()); goto render;
    }
}

// ── STEP 8: User::authenticate ───────────────────────────────────────────────
if (empty($email) || empty($password)) {
    step(8, 'User::authenticate()', 'SKIP', 'Provide ?email=&password= in URL to test');
} else {
    try {
        require_once __DIR__ . '/app/core/Controller.php';
        require_once __DIR__ . '/app/models/User.php';
        $userModel = new User();

        // First check if user exists at all (ignore status)
        $stmtAny = $pdo->prepare("SELECT id, email, status, password FROM users WHERE email = ?");
        $stmtAny->execute([$email]);
        $anyUser = $stmtAny->fetch(PDO::FETCH_ASSOC);

        if (!$anyUser) {
            step(8, 'User lookup', 'FAIL', "No user found with email: $email");
            goto render;
        }
        step(8, 'User exists', 'PASS', "status={$anyUser['status']}  has_password=" . (!empty($anyUser['password']) ? 'yes' : 'NO ← empty password hash'));

        if ($anyUser['status'] !== 'active') {
            step(8, 'User status', 'FAIL', "User status is '{$anyUser['status']}' — must be 'active' to login");
            goto render;
        }
        step(8, 'User status', 'PASS', 'active');

        // Verify password
        $pwMatch = password_verify($password, $anyUser['password']);
        step(8, 'password_verify()', $pwMatch ? 'PASS' : 'FAIL',
            $pwMatch ? 'Password matches' : 'Password does NOT match hash in DB');
        if (!$pwMatch) goto render;

        // Full authenticate()
        $user = $userModel->authenticate($email, $password);
        step(8, 'User::authenticate()', $user ? 'PASS' : 'FAIL',
            $user ? "role={$user['role']}  name={$user['name']}" : 'returned false (check error_log)');
        if (!$user) goto render;

    } catch (Throwable $e) {
        step(8, 'User::authenticate()', 'FAIL', $e->getMessage()); goto render;
    }

    // ── STEP 9: session write ────────────────────────────────────────────────
    try {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        step(9, 'Session write', 'PASS', 'user_id=' . $user['id'] . '  role=' . $user['role']);
    } catch (Throwable $e) {
        step(9, 'Session write', 'FAIL', $e->getMessage()); goto render;
    }

    // ── STEP 10: redirect URL ────────────────────────────────────────────────
    require_once __DIR__ . '/app/config/constants.php';
    $redirectUrl = $baseUrl . '/dashboard';
    step(10, 'Redirect URL', 'PASS', $redirectUrl);
    step(10, 'APP_URL constant', 'INFO', APP_URL);
}

render:
$fails = array_filter($steps, fn($s) => $s['status'] === 'FAIL');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login Tracer</title>
<style>
  body  { font-family: monospace; background:#0f172a; color:#e2e8f0; padding:2rem; }
  h1    { color:#f8fafc; }
  .sub  { color:#94a3b8; margin-bottom:2rem; font-size:.85rem; }
  .row  { display:flex; gap:1rem; padding:5px 0; font-size:.85rem; border-bottom:1px solid #1e293b; }
  .n    { color:#475569; min-width:24px; }
  .badge{ min-width:44px; text-align:center; border-radius:4px; padding:1px 6px; font-weight:bold; font-size:.75rem; }
  .PASS { background:#14532d; color:#86efac; }
  .FAIL { background:#7f1d1d; color:#fca5a5; }
  .SKIP { background:#1e3a5f; color:#93c5fd; }
  .INFO { background:#1e3a5f; color:#93c5fd; }
  .lbl  { color:#cbd5e1; min-width:260px; }
  .det  { color:#94a3b8; word-break:break-all; }
  .sum  { margin-top:1.5rem; padding:1rem; border-radius:8px; }
  .ok   { background:#14532d; color:#86efac; }
  .bad  { background:#7f1d1d; color:#fca5a5; }
  .hint { background:#1e293b; color:#94a3b8; margin-top:1rem; padding:.75rem; border-radius:6px; font-size:.8rem; }
</style>
</head>
<body>
<h1>🔬 Login Flow Tracer</h1>
<div class="sub">
  Host: <?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '') ?> &nbsp;|&nbsp; <?= date('Y-m-d H:i:s') ?><br>
  <?php if (empty($email)): ?>
  <b style="color:#fcd34d">⚠ Add credentials to URL to test full flow:<br>
  ?email=user@example.com&amp;password=YourPassword</b>
  <?php else: ?>
  Testing login for: <b><?= htmlspecialchars($email) ?></b>
  <?php endif; ?>
</div>

<?php foreach ($steps as $s): ?>
<div class="row">
  <span class="n"><?= $s['n'] ?></span>
  <span class="badge <?= $s['status'] ?>"><?= $s['status'] ?></span>
  <span class="lbl"><?= htmlspecialchars($s['label']) ?></span>
  <span class="det"><?= htmlspecialchars($s['detail']) ?></span>
</div>
<?php endforeach; ?>

<div class="sum <?= empty($fails) ? 'ok' : 'bad' ?>">
  <?= empty($fails)
    ? '✅ All steps passed — login should work. If still failing, check browser console for JS errors on the login page.'
    : '❌ Failed at: ' . htmlspecialchars(array_values($fails)[0]['label']) . ' — ' . htmlspecialchars(array_values($fails)[0]['detail']) ?>
</div>

<div class="hint">⚠️ DELETE this file after debugging: <b>ergon/diag_login2.php</b></div>
</body>
</html>
