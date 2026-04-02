<?php
/**
 * Deep Login Audit - Server-side curl simulation + session inspection
 * https://aes.athenas.co.in/ergon/diag_deep.php
 * DELETE after debugging.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host    = $_SERVER['HTTP_HOST'] ?? '';
$baseUrl = 'https://' . $host . '/ergon';
$email   = $_GET['email']    ?? '';
$pass    = $_GET['password'] ?? '';

// ── helpers ──────────────────────────────────────────────────────────────────
function row($status, $label, $val = '') {
    $colors = ['PASS'=>'#86efac','FAIL'=>'#fca5a5','INFO'=>'#93c5fd','WARN'=>'#fcd34d'];
    $bg     = ['PASS'=>'#14532d','FAIL'=>'#7f1d1d','INFO'=>'#1e3a5f','WARN'=>'#78350f'];
    $c = $colors[$status] ?? '#e2e8f0';
    $b = $bg[$status]     ?? '#1e293b';
    echo "<div class='row'><span class='badge' style='background:$b;color:$c'>$status</span>"
       . "<span class='lbl'>" . htmlspecialchars($label) . "</span>"
       . "<span class='det'>" . htmlspecialchars((string)$val) . "</span></div>";
}
function sec($title) {
    echo "<h2>$title</h2>";
}

?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Deep Login Audit</title>
<style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem}
h1{color:#f8fafc}h2{color:#7dd3fc;margin:1.5rem 0 .4rem;border-bottom:1px solid #1e3a5f;padding-bottom:3px;font-size:.95rem}
.row{display:flex;gap:.75rem;padding:4px 0;font-size:.82rem;border-bottom:1px solid #0f172a}
.badge{min-width:44px;text-align:center;border-radius:4px;padding:1px 5px;font-weight:bold;font-size:.72rem;flex-shrink:0}
.lbl{color:#cbd5e1;min-width:260px;flex-shrink:0}.det{color:#94a3b8;word-break:break-all}
.warn{background:#78350f;color:#fcd34d;padding:.6rem;border-radius:6px;margin-top:1rem;font-size:.8rem}
pre{background:#1e293b;padding:1rem;border-radius:6px;font-size:.78rem;overflow-x:auto;white-space:pre-wrap;word-break:break-all}
</style>
</head>
<body>
<h1>🔬 Deep Login Audit</h1>
<p style="color:#94a3b8;font-size:.85rem">Host: <?=htmlspecialchars($host)?> | <?=date('Y-m-d H:i:s')?></p>

<?php

// ── 1. PHP & SERVER INFO ─────────────────────────────────────────────────────
sec('1. PHP &amp; Server');
row('INFO','PHP version', PHP_VERSION);
row('INFO','Session handler', ini_get('session.save_handler'));
row('INFO','Session save path', ini_get('session.save_path'));
row('INFO','session.cookie_domain (php.ini)', ini_get('session.cookie_domain') ?: '(empty)');
row('INFO','session.cookie_secure (php.ini)', ini_get('session.cookie_secure'));
row('INFO','session.cookie_samesite (php.ini)', ini_get('session.cookie_samesite') ?: '(empty)');
row('INFO','session.use_cookies', ini_get('session.use_cookies'));
row('INFO','session.use_only_cookies', ini_get('session.use_only_cookies'));
row('INFO','output_buffering', ini_get('output_buffering'));
row('INFO','DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT'] ?? '');
row('INFO','SERVER_SOFTWARE', $_SERVER['SERVER_SOFTWARE'] ?? '');

$savePath = ini_get('session.save_path') ?: session_save_path();
row(is_writable($savePath) ? 'PASS' : 'FAIL', 'Session save_path writable', $savePath);

// ── 2. CURRENT COOKIES FROM BROWSER ─────────────────────────────────────────
sec('2. Cookies Browser Sent to This Page');
if (empty($_COOKIE)) {
    row('INFO','No cookies received','(fresh browser or cookies blocked)');
} else {
    foreach ($_COOKIE as $k => $v) {
        row('INFO', $k, substr($v, 0, 80));
    }
}

// ── 3. SIMULATE SESSION WRITE + READ ────────────────────────────────────────
sec('3. Session Write/Read on This Request');
require_once __DIR__ . '/app/config/session.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['audit_test'] = 'ok_' . time();
row('INFO','Session ID', session_id());
row('INFO','Session data written', json_encode($_SESSION));
$sf = ($savePath ?: '/tmp') . '/sess_' . session_id();
row(file_exists($sf) ? 'PASS' : 'FAIL', 'Session file on disk', $sf);

// Check what Set-Cookie header PHP will send
$cookieParams = session_get_cookie_params();
row('INFO','cookie_params domain',   $cookieParams['domain']);
row('INFO','cookie_params secure',   $cookieParams['secure'] ? 'true' : 'false');
row('INFO','cookie_params httponly', $cookieParams['httponly'] ? 'true' : 'false');
row('INFO','cookie_params samesite', $cookieParams['samesite'] ?? '(not set)');
row('INFO','cookie_params path',     $cookieParams['path']);

// ── 4. CURL SIMULATION OF FULL LOGIN FLOW ────────────────────────────────────
sec('4. Curl Login Simulation (server-to-server)');

if (empty($email) || empty($pass)) {
    row('WARN','Skipped','Add ?email=X&password=Y to URL to run curl simulation');
} elseif (!function_exists('curl_init')) {
    row('FAIL','curl not available','Cannot simulate login flow');
} else {
    $cookieJar = tempnam(sys_get_temp_dir(), 'ergon_cookie_');

    // ── 4a. GET login page (get initial session cookie) ──────────────────────
    $ch = curl_init($baseUrl . '/login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR      => $cookieJar,
        CURLOPT_COOKIEFILE     => $cookieJar,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp1   = curl_exec($ch);
    $info1   = curl_getinfo($ch);
    $err1    = curl_error($ch);
    curl_close($ch);

    row($err1 ? 'FAIL' : 'PASS', 'GET /ergon/login status', $info1['http_code'] . ($err1 ? " — $err1" : ''));

    // Extract Set-Cookie from GET response
    $getHeaders1 = substr($resp1, 0, $info1['header_size']);
    preg_match_all('/Set-Cookie:\s*([^\r\n]+)/i', $getHeaders1, $setCookies1);
    foreach ($setCookies1[1] as $sc) {
        row('INFO','GET Set-Cookie', $sc);
    }

    // ── 4b. POST login ────────────────────────────────────────────────────────
    $ch2 = curl_init($baseUrl . '/login');
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['email' => $email, 'password' => $pass]),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR      => $cookieJar,
        CURLOPT_COOKIEFILE     => $cookieJar,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp2  = curl_exec($ch2);
    $info2  = curl_getinfo($ch2);
    $err2   = curl_error($ch2);
    curl_close($ch2);

    $postHeaders = substr($resp2, 0, $info2['header_size']);
    $postBody    = substr($resp2, $info2['header_size']);

    row($err2 ? 'FAIL' : 'PASS', 'POST /ergon/login status', $info2['http_code'] . ($err2 ? " — $err2" : ''));

    // Location header
    preg_match('/Location:\s*([^\r\n]+)/i', $postHeaders, $loc);
    $locationUrl = trim($loc[1] ?? '');
    row($locationUrl ? 'INFO' : 'FAIL', 'POST Location header', $locationUrl ?: '(none — no redirect issued!)');

    // Set-Cookie on POST response
    preg_match_all('/Set-Cookie:\s*([^\r\n]+)/i', $postHeaders, $setCookies2);
    if (empty($setCookies2[1])) {
        row('WARN','POST Set-Cookie','(none) — session cookie NOT re-issued on login POST');
    } else {
        foreach ($setCookies2[1] as $sc) {
            row('INFO','POST Set-Cookie', $sc);
        }
    }

    // Full POST response headers
    row('INFO','POST response headers (raw)', str_replace(["\r\n","\r","\n"], ' | ', trim($postHeaders)));

    // POST body (first 300 chars — may show PHP error)
    $bodyPreview = trim(strip_tags($postBody));
    if ($bodyPreview) {
        row('WARN','POST body (unexpected output)', substr($bodyPreview, 0, 300));
    }

    // ── 4c. Follow redirect to dashboard ─────────────────────────────────────
    if ($locationUrl) {
        $ch3 = curl_init($locationUrl);
        curl_setopt_array($ch3, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_COOKIEJAR      => $cookieJar,
            CURLOPT_COOKIEFILE     => $cookieJar,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp3  = curl_exec($ch3);
        $info3  = curl_getinfo($ch3);
        $err3   = curl_error($ch3);
        curl_close($ch3);

        $dashHeaders = substr($resp3, 0, $info3['header_size']);
        row($err3 ? 'FAIL':'INFO', 'GET dashboard status', $info3['http_code'] . ($err3 ? " — $err3" : ''));

        preg_match('/Location:\s*([^\r\n]+)/i', $dashHeaders, $loc3);
        $loc3url = trim($loc3[1] ?? '');

        if ($info3['http_code'] == 302 && strpos($loc3url, '/login') !== false) {
            row('FAIL','Dashboard redirected BACK to login',
                $loc3url . ' — session not persisted across redirect');
        } elseif ($info3['http_code'] == 302) {
            row('INFO','Dashboard redirected to', $loc3url);
        } else {
            row('PASS','Dashboard loaded (no redirect to login)', 'HTTP ' . $info3['http_code']);
        }

        // Cookies sent TO dashboard
        $cookiesSent = file_get_contents($cookieJar);
        row('INFO','Cookie jar contents', str_replace("\n",' | ', trim($cookiesSent)));

        // ── 4d. Read session file after POST ─────────────────────────────────
        sec('5. Session File Contents After Login POST');
        // Extract PHPSESSID from cookie jar
        preg_match('/PHPSESSID\s+(\S+)/i', $cookiesSent, $sidMatch);
        $loginSid = $sidMatch[1] ?? '';
        if ($loginSid) {
            $loginSf = $savePath . '/sess_' . $loginSid;
            row('INFO','Login session ID (from cookie jar)', $loginSid);
            if (file_exists($loginSf)) {
                row('PASS','Session file exists', $loginSf);
                $sfContents = file_get_contents($loginSf);
                row(strpos($sfContents,'user_id') !== false ? 'PASS':'FAIL',
                    'user_id in session file',
                    strpos($sfContents,'user_id') !== false ? 'YES' : 'NO — session written but user_id missing');
                row('INFO','Session file raw', substr($sfContents, 0, 300));
            } else {
                row('FAIL','Session file NOT found', $loginSf . ' — session_write_close() may not have flushed');
            }
        } else {
            row('FAIL','Could not extract PHPSESSID from cookie jar','');
        }
    }

    @unlink($cookieJar);
}

// ── 6. INDEX.PHP LOAD ORDER AUDIT ────────────────────────────────────────────
sec('6. index.php Load Order Audit');
$indexFile = __DIR__ . '/index.php';
$indexSrc  = file_get_contents($indexFile);

$checks = [
    'session.php included'          => strpos($indexSrc, 'session.php') !== false,
    'session_start() called'        => strpos($indexSrc, 'session_start()') !== false,
    'session.php before session_start' =>
        strpos($indexSrc, 'session.php') < strpos($indexSrc, 'session_start()'),
    'environment.php included'      => strpos($indexSrc, 'environment.php') !== false,
    'database.php included'         => strpos($indexSrc, 'database.php') !== false,
    'Router included'               => strpos($indexSrc, 'Router.php') !== false,
];
foreach ($checks as $label => $ok) {
    row($ok ? 'PASS' : 'FAIL', $label);
}

// ── 7. AUTHCONTROLLER SESSION WRITE AUDIT ────────────────────────────────────
sec('7. AuthController Session Write Audit');
$authSrc = file_get_contents(__DIR__ . '/app/controllers/AuthController.php');

$authChecks = [
    'session_regenerate_id removed'  => strpos($authSrc, 'session_regenerate_id') === false,
    'session_write_close() present'  => strpos($authSrc, 'session_write_close') !== false,
    '$_SESSION[user_id] set'         => strpos($authSrc, "\$_SESSION['user_id']") !== false,
    'Location header uses baseUrl'   => strpos($authSrc, 'Location: ' . "'" . $baseUrl) !== false
                                     || strpos($authSrc, '$redirectUrl') !== false,
];
foreach ($authChecks as $label => $ok) {
    row($ok ? 'PASS' : 'WARN', $label);
}

// Show the exact login success block
preg_match('/if \(\$user\) \{(.+?)\/\/ Record failed/s', $authSrc, $loginBlock);
if ($loginBlock) {
    echo "<pre>" . htmlspecialchars(substr($loginBlock[1], 0, 800)) . "</pre>";
}

?>

<div class="warn">⚠️ DELETE after debugging: <b>ergon/diag_deep.php</b></div>
</body>
</html>
