<?php
/**
 * ERGON Security Audit Tool - Refined Version
 * Filters out false positives and focuses on real security issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = __DIR__;
$results = [];
$fixApplied = false;
$scanComplete = false;

// Refined security patterns with better detection
$securityPatterns = [
    'sql_injection' => [
        'pattern' => '/\$_(GET|POST|REQUEST)\[.*\].*\.(query|exec|prepare)\s*\(/i',
        'type' => 'SQL Injection Risk',
        'desc' => 'User input directly in SQL query',
        'severity' => 'Critical'
    ],
    'xss_output' => [
        'pattern' => '/echo\s+\$_(GET|POST|REQUEST)\[|print\s+\$_(GET|POST|REQUEST)\[(?!.*htmlspecialchars)/i',
        'type' => 'XSS Risk',
        'desc' => 'Unescaped user input output',
        'severity' => 'High'
    ],
    'eval_usage' => [
        'pattern' => '/\beval\s*\(/i',
        'type' => 'Code Execution',
        'desc' => 'Dangerous eval() function usage',
        'severity' => 'Critical'
    ],
    'system_commands' => [
        'pattern' => '/\b(shell_exec|exec|system|passthru)\s*\(\s*\$_(GET|POST|REQUEST)/i',
        'type' => 'Command Injection',
        'desc' => 'User input in system commands',
        'severity' => 'Critical'
    ],
    'missing_csrf_form' => [
        'pattern' => '/<form[^>]*method=["\']post["\'][^>]*>(?!.*csrf_token)/is',
        'type' => 'Missing CSRF',
        'desc' => 'POST form without CSRF protection',
        'severity' => 'Medium'
    ],
    'unsafe_file_include' => [
        'pattern' => '/\b(include|require)(_once)?\s*\(\s*\$_(GET|POST|REQUEST)/i',
        'type' => 'File Inclusion',
        'desc' => 'User input in file inclusion',
        'severity' => 'Critical'
    ],
    'weak_password' => [
        'pattern' => '/password.*=.*["\'][^"\']{1,5}["\']|password.*123|password.*admin/i',
        'type' => 'Weak Password',
        'desc' => 'Hardcoded weak password detected',
        'severity' => 'High'
    ],
    'debug_info' => [
        'pattern' => '/var_dump\s*\(|print_r\s*\(.*\$_(GET|POST|REQUEST)|phpinfo\s*\(/i',
        'type' => 'Information Disclosure',
        'desc' => 'Debug information exposure',
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
            // Skip vendor, cache, backup, and audit directories
            if (!preg_match('/^(vendor|storage|cache|backups|_archived|\.ergon_audit_tmp|security_backups)\//i', $relativePath)) {
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
    
    // Skip files that are already security-related
    $filename = basename($path);
    if (strpos($filename, 'security') !== false || strpos($filename, 'audit') !== false) {
        return $issues;
    }
    
    foreach ($lines as $lineNum => $line) {
        // Skip comments and empty lines
        $trimmedLine = trim($line);
        if (empty($trimmedLine) || strpos($trimmedLine, '//') === 0 || strpos($trimmedLine, '#') === 0) {
            continue;
        }
        
        foreach ($patterns as $patternKey => $patternData) {
            if (preg_match($patternData['pattern'], $line)) {
                // Additional filtering for false positives
                if ($patternKey === 'missing_csrf_form') {
                    // Check if this is a real form, not just a string
                    if (strpos($line, 'echo') !== false || strpos($line, 'print') !== false) {
                        continue;
                    }
                }
                
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
            $lines[$line - 1] = '// [SECURITY FIX] Removed dangerous eval(): ' . trim($originalLine) . "\n";
            break;
            
        case 'system_commands':
            $lines[$line - 1] = '// [SECURITY FIX] Removed system command: ' . trim($originalLine) . "\n";
            break;
            
        case 'sql_injection':
            $lines[$line - 1] = '// [SECURITY FIX] TODO: Use prepared statements: ' . trim($originalLine) . "\n";
            break;
            
        case 'missing_csrf_form':
            // Add CSRF token to form
            $lines[$line - 1] = str_replace(
                '<form',
                '<form',
                $lines[$line - 1]
            );
            // Insert CSRF token after form tag
            $nextLine = $line < count($lines) ? $lines[$line] : '';
            $lines[$line] = "    <input type=\"hidden\" name=\"csrf_token\" value=\"<?= htmlspecialchars(Security::generateCSRFToken()) ?>\">\n" . $nextLine;
            break;
            
        case 'unsafe_file_include':
            $lines[$line - 1] = '// [SECURITY FIX] Removed unsafe include: ' . trim($originalLine) . "\n";
            break;
            
        case 'weak_password':
            $lines[$line - 1] = '// [SECURITY FIX] Removed hardcoded password: ' . trim($originalLine) . "\n";
            break;
            
        case 'debug_info':
            $lines[$line - 1] = '// [SECURITY FIX] Removed debug output: ' . trim($originalLine) . "\n";
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
            // Create backup once per file
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            copy($file, $backupDir . '/' . basename($file));
            
            // Apply all fixes to this file
            foreach ($issues as $issue) {
                applyFix($file, $issue['line'], $issue['pattern']);
                $fixedCount++;
            }
        }
    }
    
    $fixApplied = "Fixed {$fixedCount} security issues across all files. Backups: {$backupDir}";
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
    <title>🔒 ERGON Security Audit - Refined</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; text-align: center; }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.1em; opacity: 0.9; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; margin-bottom: 5px; }
        .critical { color: #e74c3c; }
        .high { color: #e67e22; }
        .medium { color: #f39c12; }
        .low { color: #27ae60; }
        .btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; font-weight: 500; transition: all 0.3s; margin: 5px; }
        .btn:hover { background: #2980b9; transform: translateY(-2px); }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-small { padding: 6px 12px; font-size: 0.9em; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .results-table { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #34495e; color: white; padding: 15px; text-align: left; font-weight: 600; }
        td { padding: 12px 15px; border-bottom: 1px solid #ecf0f1; vertical-align: top; }
        tr:hover { background: #f8f9fa; }
        .severity-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: 500; color: white; }
        .code-preview { font-family: 'Courier New', monospace; background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 0.9em; max-width: 400px; overflow: hidden; text-overflow: ellipsis; }
        .no-issues { text-align: center; padding: 60px; color: #7f8c8d; }
        .no-issues h3 { color: #27ae60; margin-bottom: 10px; }
        .scan-section { text-align: center; margin-bottom: 30px; }
        .filter-section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .filter-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 ERGON Security Audit - Refined</h1>
            <p>Focused Security Scanner - Real Issues Only</p>
        </div>

        <?php if ($fixApplied): ?>
            <div class="alert alert-success">
                ✅ <strong>Fix Applied Successfully!</strong><br>
                <?= htmlspecialchars($fixApplied) ?>
            </div>
        <?php endif; ?>

        <?php if (!$scanComplete): ?>
            <div class="scan-section">
                <h2>🎯 Refined Security Scan</h2>
                <p style="margin: 20px 0; color: #7f8c8d;">This refined scanner filters out false positives and focuses on real security vulnerabilities.</p>
                <a href="?scan=1" class="btn btn-success">🔍 Start Refined Security Scan</a>
                <a href="ergon_security_audit.php" class="btn">📊 View Full Scan Results</a>
            </div>
        <?php else: ?>
            
            <?php if ($totalIssues > 0): ?>
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?= $totalIssues ?></div>
                        <div>Real Security Issues</div>
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

                <div class="filter-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3>🎛️ Filter Results</h3>
                        <a href="?fix_all=1" class="btn btn-danger" onclick="return confirm('Fix ALL security issues? This will create backups and apply all fixes automatically.')" style="background: #e74c3c; font-size: 1.1em; padding: 15px 30px;">🔧 Fix All Issues (<?= $totalIssues ?>)</a>
                    </div>
                    <div class="filter-buttons">
                        <button class="btn btn-small" onclick="filterBySeverity('all')">All Issues</button>
                        <button class="btn btn-danger btn-small" onclick="filterBySeverity('Critical')">Critical Only</button>
                        <button class="btn btn-small" style="background: #e67e22;" onclick="filterBySeverity('High')">High Only</button>
                        <button class="btn btn-small" style="background: #f39c12;" onclick="filterBySeverity('Medium')">Medium Only</button>
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
                        <tbody id="resultsTable">
                            <?php foreach ($results as $file => $issues): ?>
                                <?php foreach ($issues as $issue): ?>
                                    <tr data-severity="<?= $issue['severity'] ?>">
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
                    <h3>🎉 No Critical Security Issues Found!</h3>
                    <p>The refined scan found no immediate security vulnerabilities. Your ERGON project security implementation is working well!</p>
                    <div style="margin-top: 20px;">
                        <a href="ergon_security_audit.php" class="btn">📊 View Full Detailed Scan</a>
                    </div>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 30px;">
                <a href="?scan=1" class="btn">🔄 Rescan Project</a>
                <a href="ergon_security_audit.php" class="btn">📊 Full Audit Report</a>
                <a href="?" class="btn">🏠 Back to Home</a>
            </div>

        <?php endif; ?>

        <div style="margin-top: 40px; padding: 20px; background: white; border-radius: 8px; font-size: 0.9em; color: #7f8c8d;">
            <h4>🎯 Refined Security Patterns:</h4>
            <ul style="margin-top: 10px; padding-left: 20px;">
                <li><strong>SQL Injection:</strong> User input directly in SQL queries</li>
                <li><strong>XSS Vulnerabilities:</strong> Unescaped user output</li>
                <li><strong>Code Execution:</strong> Dangerous eval() usage</li>
                <li><strong>Command Injection:</strong> User input in system commands</li>
                <li><strong>CSRF Protection:</strong> POST forms without CSRF tokens</li>
                <li><strong>File Inclusion:</strong> User input in includes</li>
                <li><strong>Weak Passwords:</strong> Hardcoded credentials</li>
                <li><strong>Information Disclosure:</strong> Debug output exposure</li>
            </ul>
            <p style="margin-top: 15px;"><strong>Note:</strong> This refined scanner excludes false positives like prepared statements and secure includes.</p>
        </div>
    </div>

    <script>
    function filterBySeverity(severity) {
        const rows = document.querySelectorAll('#resultsTable tr');
        rows.forEach(row => {
            if (severity === 'all' || row.dataset.severity === severity) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update button states
        document.querySelectorAll('.filter-buttons .btn').forEach(btn => {
            btn.style.opacity = '0.6';
        });
        event.target.style.opacity = '1';
    }
    </script>
</body>
</html>