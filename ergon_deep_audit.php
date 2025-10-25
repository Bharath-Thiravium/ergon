#!/usr/bin/env php
<?php
/**
 * ergon_deep_audit.php
 * Deep audit scanner for PHP projects (tailored for Ergon blueprint)
 *
 * Usage:
 *   php ergon_deep_audit.php /path/to/project
 *
 * Outputs:
 *   - ergon-deep-audit.json
 *   - ergon-deep-audit.html
 *
 * Notes:
 *   - Avoids scanning vendor/ by default.
 *   - Uses token_get_all() to find many risky patterns reliably.
 *   - May produce false positives (human review required).
 */

declare(strict_types=1);
set_time_limit(0);

$start = microtime(true);
$root = $argv[1] ?? getcwd();
$root = realpath($root) ?: $root;

$outJson = $root . DIRECTORY_SEPARATOR . 'ergon-deep-audit.json';
$outHtml = $root . DIRECTORY_SEPARATOR . 'ergon-deep-audit.html';
$excludeDirs = ['vendor', '.git', 'node_modules', 'storage/cache', '_archived'];

$report = [
    'meta' => [
        'scanned_at' => date('c'),
        'project_root' => $root,
        'php_binary' => PHP_BINARY,
        'php_version' => PHP_VERSION,
    ],
    'summary' => [
        'files_scanned' => 0,
        'php_files' => 0,
        'issues_found' => 0,
    ],
    'issues' => [],
    'counters' => [],
];

function is_binary($path) {
    if (!is_file($path)) return false;
    $fh = fopen($path, 'rb');
    if (!$fh) return false;
    $chunk = fread($fh, 512);
    fclose($fh);
    return (strpos($chunk, "\0") !== false);
}

// walker
$rii = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        function ($current, $key, $iterator) use ($excludeDirs, $root) {
            $name = $current->getFilename();
            if ($current->isDir()) {
                foreach ($excludeDirs as $ex) {
                    if (stripos($current->getPathname(), DIRECTORY_SEPARATOR . $ex) !== false) {
                        return false;
                    }
                }
                return true;
            }
            return true;
        }
    )
);

$phpFiles = [];
$formFiles = []; // to check for CSRF forms (html/phtml/php)
$otherFiles = [];

foreach ($rii as $file) {
    $path = $file->getPathname();
    // skip unreadable or binary
    if (!is_readable($path) || is_binary($path)) continue;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, ['php','phtml','inc','tpl','html','htm','twig','vue'])) {
        $phpFiles[] = $path;
    } else {
        $otherFiles[] = $path;
    }
    // collect files likely to contain forms
    if (in_array($ext, ['php','phtml','html','htm','twig'])) $formFiles[] = $path;
}

$report['summary']['files_scanned'] = count($phpFiles) + count($otherFiles);
$report['summary']['php_files'] = count($phpFiles);

// helper to add issue
function add_issue(array &$report, string $type, string $file, int $line = 0, string $message = '', array $meta = []) {
    $report['issues'][] = [
        'type' => $type,
        'file' => $file,
        'line' => $line,
        'message' => $message,
        'meta' => $meta,
    ];
    $report['summary']['issues_found']++;
    if (!isset($report['counters'][$type])) $report['counters'][$type] = 0;
    $report['counters'][$type]++;
}

// PATTERNS & DANGEROUS FUNCTIONS (simple list)
$dangerFuncs = [
    'eval','create_function','assert','exec','system','passthru','shell_exec','popen','proc_open','pcntl_exec'
];
$obfuscationPatterns = ['base64_decode','gzinflate','gzuncompress','str_rot13','pack','preg_replace']; // preg_replace with /e considered later

// Inspect PHP files with token_get_all
foreach ($phpFiles as $file) {
    $report['meta']['last_scanned_file'] = $file;
    $content = file_get_contents($file);
    if ($content === false) continue;
    $report['meta']['last_scanned_time'] = time();
    $tokens = token_get_all($content);
    $report['summary']['tokenized_files'] = ($report['summary']['tokenized_files'] ?? 0) + 1;

    $line = 1;
    $prevT = null;
    $openPhpEchoes = 0;
    $has_htmlspecialchars = false;
    $has_echo_unescaped = false;

    // state for SQL detection: find mysql/mysqli/pdo query or ->query with non-literal SQL
    $possibleSqlBuilds = []; // list of arrays with file,line,code snippet
    $lastStringConcat = null;
    $lastTString = null;

    // iterate tokens for function calls, eval, echo, concatenation
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];
        if (is_array($tok)) {
            $tname = token_name($tok[0]);
            $ttext = $tok[1];
            $line = $tok[2];
            
            // Dangerous functions
            if ($tok[0] === T_STRING) {
                $fnLower = strtolower($ttext);
                if (in_array($fnLower, $dangerFuncs, true)) {
                    // next non-whitespace token '(' ?
                    $j = $i+1;
                    while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
                    if ($j < $count && $tokens[$j] === '(') {
                        add_issue($report, 'dangerous_function', $file, $line, "Call to dangerous function: {$ttext}()", ['function' => $ttext]);
                    }
                }
                // obfuscation
                if (in_array($fnLower, $obfuscationPatterns, true)) {
                    add_issue($report, 'obfuscation_call', $file, $line, "Use of possible obfuscation function: {$ttext}()", ['function' => $ttext]);
                }
            }

            // echo / print tokens -> potential XSS, check for htmlspecialchars nearby
            if ($tok[0] === T_ECHO || $tok[0] === T_PRINT) {
                // naive: search next few tokens for htmlspecialchars usage
                $window = '';
                for ($k = $i; $k < min($i+20, $count); $k++) {
                    $window .= is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
                }
                if (stripos($window, 'htmlspecialchars(') === false && stripos($window, 'htmlentities(') === false) {
                    add_issue($report, 'unescaped_output', $file, $line, "Echo/print without htmlspecialchars/htmlentities; review for XSS.", ['snippet' => trim(substr($window,0,200))]);
                }
            }

            // short echo <?= ... ?> detection (these appear as T_OPEN_TAG_WITH_ECHO in some PHP builds)
            if ($tok[0] === T_OPEN_TAG_WITH_ECHO) {
                // approximate: find if htmlspecialchars used within same file near position
                add_issue($report, 'short_tag_echo', $file, $line, "Short echo tag '<?= ... ?>' detected — ensure output is escaped.", []);
            }

            // preg_replace with /e usage
            if ($tok[0] === T_STRING && strtolower($tok[1]) === 'preg_replace') {
                // check within parentheses for /e or callback usage - approximate: look ahead 200 chars
                $snippet = substr($content, max(0, strpos($content, $ttext, strpos($content, $ttext)) - 10), 300);
                if (preg_match('/preg_replace\s*\(\s*[\'"].+[\/][e][\'"]/i', $snippet)) {
                    add_issue($report, 'preg_replace_e', $file, $line, "preg_replace with /e modifier (deprecated & dangerous).", ['snippet' => $snippet]);
                }
            }

            // move_uploaded_file usage -> check for validation (heuristic)
            if ($tok[0] === T_STRING && strtolower($tok[1]) === 'move_uploaded_file') {
                add_issue($report, 'file_upload_move', $file, $line, "move_uploaded_file used — ensure thorough validation (mime/extension/size).", []);
            }

            // PDO/mysqli query detection - look for ->query( or query( usage with variable concatenation
            if ($tok[0] === T_STRING && in_array(strtolower($tok[1]), ['query','exec','mysqli_query','mysqli::query','pdo::query'], true)) {
                // inspect around this index for concatenation operator or variable in string (heuristic)
                $look = '';
                for ($k = max(0, $i-6); $k < min($count, $i+10); $k++) {
                    $look .= is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
                }
                if (preg_match('/\.\s*\$|"\s*\.\s*\$|\$[A-Za-z0-9_]+\s*\.\s*"/', $look)) {
                    add_issue($report, 'sql_concatenation', $file, $line, "SQL string appears to be built via concatenation — risk of SQL injection. Use prepared statements.", ['snippet' => trim($look)]);
                } else {
                    // also flag if function call receives a non-literal expression (simple heuristic)
                    if (preg_match('/\(\s*\$[A-Za-z_]/', $look)) {
                        add_issue($report, 'sql_dynamic', $file, $line, "Query called with dynamic variable — verify prepared statements and parameter binding.", ['snippet' => trim($look)]);
                    }
                }
            }

            // JWT secret detection heuristics
            if ($tok[0] === T_STRING && preg_match('/jwt|secret|key/i', $tok[1])) {
                // scan a short window for assignment of a long string literal
                $window = '';
                for ($k = $i; $k < min($i+12, $count); $k++) {
                    $window .= is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
                }
                if (preg_match("/['\"[A-Za-z0-9\-\_]{12,}['\"]/", $window)) {
                    add_issue($report, 'possible_hardcoded_secret', $file, $line, "Possible hard-coded secret (JWT/API) near $tok[1].", ['snippet' => trim($window)]);
                }
            }

            // detect include/require of remote URLs (allow-list: none)
            if ($tok[0] === T_REQUIRE || $tok[0] === T_INCLUDE || $tok[0] === T_REQUIRE_ONCE || $tok[0] === T_INCLUDE_ONCE) {
                // look ahead for string with http:// or https://
                $window = '';
                for ($k = $i; $k < min($count, $i+8); $k++) $window .= is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
                if (preg_match('/https?:\/\/[^\s\'"]+/i', $window)) {
                    add_issue($report, 'remote_include', $file, $line, "Include/require from remote URL detected (RCE risk).", ['snippet' => trim($window)]);
                }
            }

            // detect raw SQL strings in code (naive check for "SELECT " / "INSERT " occurrences that include concatenation)
            if ($tok[0] === T_CONSTANT_ENCAPSED_STRING && preg_match('/\b(SELECT|INSERT|UPDATE|DELETE)\b/i', $tok[1])) {
                // find if adjacent tokens include '.' concatenation or variable
                $left = ($i>0 && is_array($tokens[$i-1]) ? $tokens[$i-1][1] : ($i>0 ? $tokens[$i-1] : ''));
                $right = ($i<$count-1 && is_array($tokens[$i+1]) ? $tokens[$i+1][1] : ($i<$count-1 ? $tokens[$i+1] : ''));
                if ($left === '.' || $right === '.') {
                    add_issue($report, 'sql_string_concat', $file, $line, "SQL string with concatenation detected — potential injection risk.", ['snippet' => trim($left . $tok[1] . $right)]);
                }
            }

            // detect $_GET/$_POST usage without sanitization (heuristic)
            if ($tok[0] === T_VARIABLE && in_array($tok[1], ['$_GET','$_POST','$_REQUEST','$_COOKIE'], true)) {
                // look ahead for array access and see if htmlspecialchars/filter_var is nearby
                $window = '';
                for ($k = $i; $k < min($count, $i+15); $k++) {
                    $window .= is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
                }
                if (stripos($window, 'htmlspecialchars') === false && stripos($window, 'filter_var') === false && stripos($window, 'filter_input') === false) {
                    add_issue($report, 'unsanitized_input', $file, $line, "Superglobal {$tok[1]} used without apparent sanitization.", ['snippet' => trim(substr($window, 0, 100))]);
                }
            }

            $prevT = $tok;
        } else {
            // non-array token (single character)
            if ($tok === "\n") $line++;
        }
    }
}

// Check for CSRF protection in forms
foreach ($formFiles as $file) {
    $content = file_get_contents($file);
    if ($content === false) continue;
    
    // Simple regex to find <form> tags
    if (preg_match_all('/<form[^>]*>/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $formTag = $match[0];
            $offset = $match[1];
            $line = substr_count(substr($content, 0, $offset), "\n") + 1;
            
            // Look for CSRF token in the form (within next 1000 chars)
            $formContent = substr($content, $offset, 1000);
            if (stripos($formContent, 'csrf') === false && stripos($formContent, '_token') === false) {
                add_issue($report, 'missing_csrf', $file, $line, "Form without apparent CSRF protection.", ['form_tag' => trim($formTag)]);
            }
        }
    }
}

// Check .env files for sensitive data exposure
$envFiles = ['.env', 'public/.env', '.env.example'];
foreach ($envFiles as $envFile) {
    $envPath = $root . DIRECTORY_SEPARATOR . $envFile;
    if (file_exists($envPath)) {
        $content = file_get_contents($envPath);
        if ($content !== false) {
            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                if (preg_match('/^(DB_PASSWORD|JWT_SECRET|APP_KEY|API_KEY)=(.+)$/i', trim($line), $matches)) {
                    $key = $matches[1];
                    $value = $matches[2];
                    if (strlen($value) > 0 && $value !== 'your-secret-here' && $value !== 'changeme') {
                        add_issue($report, 'env_secret_exposure', $envPath, $lineNum + 1, "Sensitive key '$key' with actual value in .env file.", ['key' => $key]);
                    }
                }
            }
        }
        
        // Check if .env is in public directory
        if (strpos($envFile, 'public/') === 0) {
            add_issue($report, 'env_in_webroot', $envPath, 0, "CRITICAL: .env file in web-accessible directory!", []);
        }
    }
}

// Check for PHP files in uploads directory
$uploadsDir = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
if (is_dir($uploadsDir)) {
    $uploadFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($uploadFiles as $uploadFile) {
        if ($uploadFile->isFile()) {
            $ext = strtolower(pathinfo($uploadFile->getFilename(), PATHINFO_EXTENSION));
            if (in_array($ext, ['php', 'phtml', 'php3', 'php4', 'php5', 'phar'])) {
                add_issue($report, 'php_in_uploads', $uploadFile->getPathname(), 0, "PHP file in uploads directory - RCE risk!", ['filename' => $uploadFile->getFilename()]);
            }
        }
    }
}

// Final summary
$report['meta']['scan_duration'] = round(microtime(true) - $start, 2);
$report['meta']['memory_peak'] = memory_get_peak_usage(true);

// Write JSON report
file_put_contents($outJson, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Generate HTML report
$html = generateHtmlReport($report);
file_put_contents($outHtml, $html);

echo "Deep audit completed!\n";
echo "Files scanned: {$report['summary']['files_scanned']}\n";
echo "PHP files: {$report['summary']['php_files']}\n";
echo "Issues found: {$report['summary']['issues_found']}\n";
echo "Duration: {$report['meta']['scan_duration']}s\n";
echo "Reports saved:\n";
echo "  JSON: $outJson\n";
echo "  HTML: $outHtml\n";

function generateHtmlReport(array $report): string {
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ergon Deep Audit Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .header { background: #1e40af; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; }
        .stat-value { font-size: 2em; font-weight: bold; color: #1e40af; }
        .stat-label { color: #666; font-size: 0.9em; }
        .issue { margin: 10px 0; padding: 15px; border-left: 4px solid #dc3545; background: #fff5f5; border-radius: 4px; }
        .issue-critical { border-left-color: #dc3545; background: #fff5f5; }
        .issue-high { border-left-color: #fd7e14; background: #fff8f0; }
        .issue-medium { border-left-color: #ffc107; background: #fffbf0; }
        .issue-low { border-left-color: #28a745; background: #f0fff4; }
        .issue-type { font-weight: bold; color: #dc3545; }
        .issue-file { font-family: monospace; color: #666; font-size: 0.9em; }
        .issue-message { margin: 5px 0; }
        .snippet { background: #f8f9fa; padding: 8px; border-radius: 4px; font-family: monospace; font-size: 0.85em; margin: 5px 0; }
        .counters { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin: 20px 0; }
        .counter { background: #e9ecef; padding: 10px; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Ergon Deep Audit Report</h1>
            <p>Generated: ' . $report['meta']['scanned_at'] . '</p>
            <p>Project: ' . htmlspecialchars($report['meta']['project_root']) . '</p>
        </div>

        <div class="summary">
            <div class="stat-card">
                <div class="stat-value">' . $report['summary']['files_scanned'] . '</div>
                <div class="stat-label">Files Scanned</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">' . $report['summary']['php_files'] . '</div>
                <div class="stat-label">PHP Files</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">' . $report['summary']['issues_found'] . '</div>
                <div class="stat-label">Issues Found</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">' . $report['meta']['scan_duration'] . 's</div>
                <div class="stat-label">Scan Duration</div>
            </div>
        </div>';

    if (!empty($report['counters'])) {
        $html .= '<h2>Issue Types</h2><div class="counters">';
        foreach ($report['counters'] as $type => $count) {
            $html .= '<div class="counter"><strong>' . $count . '</strong><br>' . htmlspecialchars($type) . '</div>';
        }
        $html .= '</div>';
    }

    $html .= '<h2>Issues Found</h2>';
    
    if (empty($report['issues'])) {
        $html .= '<p style="color: green; font-weight: bold;">✅ No issues found!</p>';
    } else {
        $criticalTypes = ['env_in_webroot', 'php_in_uploads', 'dangerous_function', 'remote_include'];
        $highTypes = ['sql_concatenation', 'unescaped_output', 'env_secret_exposure'];
        
        foreach ($report['issues'] as $issue) {
            $severity = 'low';
            if (in_array($issue['type'], $criticalTypes)) $severity = 'critical';
            elseif (in_array($issue['type'], $highTypes)) $severity = 'high';
            elseif (in_array($issue['type'], ['missing_csrf', 'unsanitized_input', 'file_upload_move'])) $severity = 'medium';
            
            $html .= '<div class="issue issue-' . $severity . '">';
            $html .= '<div class="issue-type">' . strtoupper($severity) . ': ' . htmlspecialchars($issue['type']) . '</div>';
            $html .= '<div class="issue-file">' . htmlspecialchars($issue['file']) . ':' . $issue['line'] . '</div>';
            $html .= '<div class="issue-message">' . htmlspecialchars($issue['message']) . '</div>';
            
            if (!empty($issue['meta']['snippet'])) {
                $html .= '<div class="snippet">' . htmlspecialchars($issue['meta']['snippet']) . '</div>';
            }
            
            $html .= '</div>';
        }
    }

    $html .= '</div></body></html>';
    
    return $html;
}
?>