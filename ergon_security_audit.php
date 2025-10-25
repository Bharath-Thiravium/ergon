<?php
/**
 * ERGON Security Audit Tool
 * Web-based PHP Security Scanner with One-Click Fixes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = __DIR__;
$results = [];
$fixApplied = false;
$scanComplete = false;

// Security patterns to detect
$securityPatterns = [
    'sql_injection' => [
        'pattern' => '/mysql_query|mysqli_query\s*\(\s*["\'].*\$_(GET|POST|REQUEST)/i',
        'type' => 'SQL Injection Risk',
        'desc' => 'Raw SQL query with user input',
        'severity' => 'Critical'
    ],
    'xss_output' => [
        'pattern' => '/echo\s+\$_(GET|POST|REQUEST)|print\s+\$_(GET|POST|REQUEST)/i',
        'type' => 'XSS Risk',
        'desc' => 'Unescaped output of user input',
        'severity' => 'High'
    ],
    'eval_usage' => [
        'pattern' => '/eval\s*\(/i',
        'type' => 'Code Execution',
        'desc' => 'Use of eval() is dangerous',
        'severity' => 'Critical'
    ],
    'system_call' => [
        'pattern' => '/shell_exec|exec|system|passthru\s*\(/i',
        'type' => 'System Command',
        'desc' => 'Shell command execution detected',
        'severity' => 'High'
    ],
    'missing_csrf' => [
        'pattern' => '/\$_POST\[.*\].*(?!.*csrf_token)/i',
        'type' => 'Missing CSRF',
        'desc' => 'POST request without CSRF protection',
        'severity' => 'Medium'
    ],
    'unsafe_include' => [
        'pattern' => '/include|require.*\$_(GET|POST|REQUEST)/i',
        'type' => 'File Inclusion',
        'desc' => 'Dynamic file inclusion with user input',
        'severity' => 'Critical'
    ],
    'weak_session' => [
        'pattern' => '/session_start\(\)(?!.*session_regenerate_id)/i',
        'type' => 'Weak Session',
        'desc' => 'Session without regeneration',
        'severity' => 'Medium'
    ]
];

function scanPhpFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            // Skip vendor, cache, and backup directories
            if (!preg_match('/^(vendor|storage|cache|backups|_archived)\//i', $relativePath)) {
                $files[] = $file->getPathname();
            }
        }
    }
    return $files;
}

function analyzeFile($path, $patterns) {
    $issues = [];
    $content = file_get_contents($path);
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    
    foreach ($lines as $lineNum => $line) {
        foreach ($patterns as $patternKey => $patternData) {
            if (preg_match($patternData['pattern'], $line)) {
                $issues[] = [
                    'line' => $lineNum + 1,
                    'type' => $patternData['type'],
                    'desc' => $patternData['desc'],
                    'severity' => $patternData['severity'],
                    'pattern' => $patternKey,
                    'code' => trim($line)
                ];
            }
        }
    }
    
    return $issues;
}

function applyFix($file, $line, $pattern) {
    // Create backup
    $backupDir = __DIR__ . '/security_backups/' . date('Ymd_His');
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    copy($file, $backupDir . '/' . basename($file));
    
    $lines = file($file);
    $originalLine = $lines[$line - 1];
    
    switch ($pattern) {
        case 'xss_output':
            $lines[$line - 1] = preg_replace(
                '/echo\s+\$_(GET|POST|REQUEST)\[(.*?)\]/i',
                'echo htmlspecialchars($_\\1[\\2], ENT_QUOTES, "UTF-8")',
                $lines[$line - 1]
            );
            break;
            
        case 'eval_usage':
            $lines[$line - 1] = '// [SECURITY FIX] Removed eval() for safety: ' . trim($originalLine) . "\n";
            break;
            
        case 'system_call':
            $lines[$line - 1] = '// [SECURITY FIX] Removed system command for safety: ' . trim($originalLine) . "\n";
            break;
            
        case 'sql_injection':
            $lines[$line - 1] = '// [SECURITY FIX] TODO: Use prepared statements: ' . trim($originalLine) . "\n";
            break;
            
        case 'missing_csrf':
            // Add CSRF validation before the line
            $csrfCheck = "        // CSRF Protection\n";
            $csrfCheck .= "        if (!Security::validateCSRFToken(\$_POST['csrf_token'] ?? '')) {\n";
            $csrfCheck .= "            http_response_code(403);\n";
            $csrfCheck .= "            die('CSRF validation failed');\n";
            $csrfCheck .= "        }\n";
            $lines[$line - 1] = $csrfCheck . $lines[$line - 1];
            break;
            
        case 'unsafe_include':
            $lines[$line - 1] = '// [SECURITY FIX] Removed unsafe include: ' . trim($originalLine) . "\n";
            break;
            
        case 'weak_session':
            $lines[$line - 1] = str_replace(
                'session_start()',
                "session_start();\nsession_regenerate_id(true);",
                $lines[$line - 1]
            );
            break;
    }
    
    file_put_contents($file, implode('', $lines));
    return $backupDir . '/' . basename($file);
}

// Handle fix all request
if (isset($_GET['fix_all']) && $_GET['fix_all'] === '1') {
    $phpFiles = scanPhpFiles($baseDir);
    $fixedCount = 0;
    $backupDir = __DIR__ . '/security_backups/' . date('Ymd_His_all_fixes');
    
    foreach ($phpFiles as $file) {
        $issues = analyzeFile($file, $securityPatterns);
        if (!empty($issues)) {
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            copy($file, $backupDir . '/' . basename($file));
            
            foreach ($issues as $issue) {
                applyFix($file, $issue['line'], $issue['pattern']);
                $fixedCount++;
            }
        }
    }
    
    $fixApplied = "⚠️ MASS FIX APPLIED: Fixed {$fixedCount} issues. Many may be false positives. Check functionality! Backups: {$backupDir}";
}

// Handle single fix request
if (isset($_GET['fix']) && $_GET['fix'] === '1') {
    $file = $_GET['file'] ?? '';
    $line = (int)($_GET['line'] ?? 0);
    $pattern = $_GET['pattern'] ?? '';
    
    if ($file && $line && $pattern && file_exists($file)) {
        $backupPath = applyFix($file, $line, $pattern);
        $fixApplied = "Fixed {$pattern} in " . basename($file) . " line {$line}. Backup: {$backupPath}";
    }
}

// Scan files
if (isset($_GET['scan']) || isset($_GET['fix'])) {
    $phpFiles = scanPhpFiles($baseDir);
    foreach ($phpFiles as $file) {
        $issues = analyzeFile($file, $securityPatterns);
        if (!empty($issues)) {
            $results[str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file)] = $issues;
        }
    }
    $scanComplete = true;
}

$totalIssues = 0;
foreach ($results as $issues) {
    $totalIssues += count($issues);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔒 ERGON Security Audit Tool</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; text-align: center; }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.1em; opacity: 0.9; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; margin-bottom: 5px; }
        .critical { color: #dc3545; }
        .high { color: #fd7e14; }
        .medium { color: #ffc107; }
        .low { color: #28a745; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: 500; transition: all 0.3s; }
        .btn:hover { background: #0056b3; transform: translateY(-2px); }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 6px 12px; font-size: 0.9em; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .results-table { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px 15px; border-bottom: 1px solid #dee2e6; vertical-align: top; }
        tr:hover { background: #f8f9fa; }
        .severity-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: 500; color: white; }
        .code-preview { font-family: 'Courier New', monospace; background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 0.9em; max-width: 300px; overflow: hidden; text-overflow: ellipsis; }
        .no-issues { text-align: center; padding: 60px; color: #6c757d; }
        .no-issues h3 { color: #28a745; margin-bottom: 10px; }
        .scan-section { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 ERGON Security Audit Tool</h1>
            <p>Comprehensive PHP Security Scanner with One-Click Fixes</p>
        </div>

        <?php if ($fixApplied): ?>
            <div class="alert alert-success">
                ✅ <strong>Fix Applied Successfully!</strong><br>
                <?= htmlspecialchars($fixApplied) ?>
            </div>
        <?php endif; ?>

        <?php if (!$scanComplete): ?>
            <div class="scan-section">
                <h2>Ready to Scan Your ERGON Project</h2>
                <p style="margin: 20px 0; color: #6c757d;">This tool will scan all PHP files for security vulnerabilities and provide one-click fixes.</p>
                <a href="?scan=1" class="btn btn-success">🔍 Start Security Scan</a>
            </div>
        <?php else: ?>
            
            <?php if ($totalIssues > 0): ?>
                <div style="text-align: center; margin-bottom: 30px;">
                    <a href="?fix_all=1" class="btn btn-danger" onclick="return confirm('⚠️ WARNING: This will attempt to fix ALL 552 issues (including false positives). Many fixes may break functionality. Proceed only if you understand the risks!')" style="background: #e74c3c; font-size: 1.2em; padding: 20px 40px;">
                        🔧 Fix All Issues (<?= $totalIssues ?>) - USE WITH CAUTION
                    </a>
                    <p style="color: #e74c3c; margin-top: 10px; font-weight: bold;">⚠️ Many issues are false positives. Use the refined scanner for better results.</p>
                </div>

                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?= $totalIssues ?></div>
                        <div>Total Issues</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number critical">
                            <?php 
                            $critical = 0;
                            foreach ($results as $issues) {
                                foreach ($issues as $issue) {
                                    if ($issue['severity'] === 'Critical') $critical++;
                                }
                            }
                            echo $critical;
                            ?>
                        </div>
                        <div>Critical</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number high">
                            <?php 
                            $high = 0;
                            foreach ($results as $issues) {
                                foreach ($issues as $issue) {
                                    if ($issue['severity'] === 'High') $high++;
                                }
                            }
                            echo $high;
                            ?>
                        </div>
                        <div>High</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number medium">
                            <?php 
                            $medium = 0;
                            foreach ($results as $issues) {
                                foreach ($issues as $issue) {
                                    if ($issue['severity'] === 'Medium') $medium++;
                                }
                            }
                            echo $medium;
                            ?>
                        </div>
                        <div>Medium</div>
                    </div>
                </div>

                <div class="results-table">
                    <table>
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Line</th>
                                <th>Severity</th>
                                <th>Issue Type</th>
                                <th>Description</th>
                                <th>Code Preview</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $file => $issues): ?>
                                <?php foreach ($issues as $issue): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($file) ?></strong></td>
                                        <td><?= $issue['line'] ?></td>
                                        <td>
                                            <span class="severity-badge <?= strtolower($issue['severity']) ?>">
                                                <?= $issue['severity'] ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($issue['type']) ?></td>
                                        <td><?= htmlspecialchars($issue['desc']) ?></td>
                                        <td>
                                            <div class="code-preview">
                                                <?= htmlspecialchars($issue['code']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="?fix=1&file=<?= urlencode($baseDir . DIRECTORY_SEPARATOR . $file) ?>&line=<?= $issue['line'] ?>&pattern=<?= urlencode($issue['pattern']) ?>" 
                                               class="btn btn-danger btn-small"
                                               onclick="return confirm('Apply security fix to <?= htmlspecialchars($file) ?> line <?= $issue['line'] ?>? A backup will be created.')">
                                                🔧 Fix Now
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-issues">
                    <h3>🎉 No Security Issues Found!</h3>
                    <p>Your ERGON project appears to be secure. Great job!</p>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 30px;">
                <a href="?scan=1" class="btn">🔄 Rescan Project</a>
                <a href="?" class="btn">🏠 Back to Home</a>
            </div>

        <?php endif; ?>

        <div style="margin-top: 40px; padding: 20px; background: white; border-radius: 8px; font-size: 0.9em; color: #6c757d;">
            <h4>🛡️ Security Patterns Detected:</h4>
            <ul style="margin-top: 10px; padding-left: 20px;">
                <li><strong>SQL Injection:</strong> Raw SQL queries with user input</li>
                <li><strong>XSS Vulnerabilities:</strong> Unescaped output of user data</li>
                <li><strong>Code Execution:</strong> Use of eval() and system commands</li>
                <li><strong>CSRF Protection:</strong> Missing CSRF tokens in forms</li>
                <li><strong>File Inclusion:</strong> Dynamic includes with user input</li>
                <li><strong>Session Security:</strong> Weak session management</li>
            </ul>
            <p style="margin-top: 15px;"><strong>Note:</strong> All fixes create automatic backups in the <code>security_backups/</code> directory.</p>
        </div>
    </div>
</body>
</html>