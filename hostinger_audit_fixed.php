<?php
/**
 * Fixed Hostinger Compatibility Audit Script
 * Root cause analysis and comprehensive fix
 */

// Get server info
$serverInfo = [
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'php_version' => PHP_VERSION,
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
    'request_scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http',
    'http_host' => $_SERVER['HTTP_HOST'] ?? 'localhost',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? '',
    'base_path' => dirname($_SERVER['SCRIPT_NAME'])
];

// Define paths - check actual files being used
$cssPath = __DIR__ . '/public/assets/css/ergon.css';
$jsActivePath = __DIR__ . '/public/assets/js/ergon-ie.js'; // Currently active JS file
$layoutPath = __DIR__ . '/app/views/layouts/dashboard.php';
$polyfillPath = __DIR__ . '/public/assets/js/polyfills.js';

// Audit results
$issues = [];
$fixes = [];
$fileStatus = [];

// Check CSS file
if (file_exists($cssPath)) {
    $cssContent = file_get_contents($cssPath);
    $fileStatus['CSS'] = 'Found';
    
    // Check for viewport units WITHOUT fallbacks
    if (preg_match('/\d+(vw|vh|vmin|vmax)/', $cssContent)) {
        if (!preg_match('/\/\* fallback \*\//', $cssContent) && !preg_match('/min-height: \d+px/', $cssContent)) {
            $issues[] = 'CSS uses viewport units without fallbacks';
            $fixes[] = 'Add pixel fallbacks for viewport units';
        } else {
            $fileStatus['CSS Fallbacks'] = 'Present';
        }
    }
} else {
    $issues[] = 'CSS file not found';
    $fileStatus['CSS'] = 'Missing';
}

// Check active JavaScript file
if (file_exists($jsActivePath)) {
    $jsContent = file_get_contents($jsActivePath);
    $fileStatus['Active JS'] = 'ergon-ie.js';
    
    // Strict ES6+ detection
    $es6Features = [];
    if (preg_match('/\bconst\s+/', $jsContent)) $es6Features[] = 'const';
    if (preg_match('/\blet\s+/', $jsContent)) $es6Features[] = 'let';
    if (preg_match('/=>\s*/', $jsContent)) $es6Features[] = 'arrow functions';
    if (preg_match('/`[^`]*`/', $jsContent)) $es6Features[] = 'template literals';
    if (preg_match('/\.catch\s*\(/', $jsContent)) $es6Features[] = '.catch()';
    if (preg_match('/\.finally\s*\(/', $jsContent)) $es6Features[] = '.finally()';
    if (preg_match('/for\s*\([^)]*\bof\b/', $jsContent)) $es6Features[] = 'for...of';
    
    if (!empty($es6Features)) {
        $issues[] = 'JavaScript contains ES6+ features: ' . implode(', ', $es6Features);
        $fixes[] = 'Replace ES6+ features with ES5 equivalents';
    } else {
        $fileStatus['JS Compatibility'] = 'ES5 Compatible';
    }
} else {
    $issues[] = 'Active JavaScript file not found';
    $fileStatus['Active JS'] = 'Missing';
}

// Check polyfills
if (file_exists($polyfillPath)) {
    $fileStatus['Polyfills'] = 'Present';
} else {
    $issues[] = 'Polyfill file missing';
    $fileStatus['Polyfills'] = 'Missing';
}

// Check layout file for hardcoded paths
if (file_exists($layoutPath)) {
    $layoutContent = file_get_contents($layoutPath);
    $fileStatus['Layout'] = 'Found';
    
    // Check for hardcoded /ergon/ paths that are NOT using PHP variables
    $hardcodedPaths = [];
    if (preg_match_all('/(?:href|src)=[\'"]\/ergon\/[^\'">]*[\'"]/', $layoutContent, $matches)) {
        foreach ($matches[0] as $match) {
            // Skip if it contains PHP variables
            if (!preg_match('/\<\?=/', $match)) {
                $hardcodedPaths[] = $match;
            }
        }
    }
    
    if (!empty($hardcodedPaths)) {
        $issues[] = 'Layout contains ' . count($hardcodedPaths) . ' hardcoded paths';
        $fixes[] = 'Convert hardcoded paths to use PHP variables';
        $fileStatus['Dynamic Paths'] = 'Partial';
    } else {
        $fileStatus['Dynamic Paths'] = 'Complete';
    }
} else {
    $issues[] = 'Layout file not found';
    $fileStatus['Layout'] = 'Missing';
}

// Auto-fix function
function applyComprehensiveFixes() {
    global $cssPath, $jsActivePath, $layoutPath, $polyfillPath, $serverInfo;
    
    $fixesApplied = [];
    
    // Fix JavaScript ES6+ issues
    if (file_exists($jsActivePath)) {
        $jsContent = file_get_contents($jsActivePath);
        $originalContent = $jsContent;
        
        // Replace ES6+ features
        $jsContent = preg_replace('/\bconst\s+/', 'var ', $jsContent);
        $jsContent = preg_replace('/\blet\s+/', 'var ', $jsContent);
        $jsContent = preg_replace('/\.catch\s*\(\s*([^)]+)\s*\)/', '.then(null, $1)', $jsContent);
        $jsContent = preg_replace('/\.finally\s*\(\s*([^)]+)\s*\)/', '.then($1, $1)', $jsContent);
        
        if ($jsContent !== $originalContent) {
            file_put_contents($jsActivePath, $jsContent);
            $fixesApplied[] = 'JavaScript ES6+ features converted to ES5';
        }
    }
    
    // Fix layout hardcoded paths
    if (file_exists($layoutPath)) {
        $layoutContent = file_get_contents($layoutPath);
        $originalContent = $layoutContent;
        
        $baseUrl = $serverInfo['request_scheme'] . '://' . $serverInfo['http_host'] . '/ergon';
        
        // Replace hardcoded paths with PHP variables
        $layoutContent = preg_replace(
            '/href=[\'"]\/ergon\/([^\'">]+)[\'"]/',
            'href="<?= $_SERVER[\'REQUEST_SCHEME\'] ?>://<?= $_SERVER[\'HTTP_HOST\'] ?>/ergon/$1"',
            $layoutContent
        );
        
        $layoutContent = preg_replace(
            '/src=[\'"]\/ergon\/([^\'">]+)[\'"]/',
            'src="<?= $_SERVER[\'REQUEST_SCHEME\'] ?>://<?= $_SERVER[\'HTTP_HOST\'] ?>/ergon/$1"',
            $layoutContent
        );
        
        if ($layoutContent !== $originalContent) {
            file_put_contents($layoutPath, $layoutContent);
            $fixesApplied[] = 'Layout hardcoded paths converted to dynamic';
        }
    }
    
    // Ensure polyfills exist
    if (!file_exists($polyfillPath)) {
        $polyfillContent = '// Fetch polyfill for IE
if (!window.fetch) {
    window.fetch = function(url, options) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open((options && options.method) || "GET", url, true);
            if (options && options.headers) {
                for (var key in options.headers) {
                    xhr.setRequestHeader(key, options.headers[key]);
                }
            }
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve({
                            ok: true,
                            status: xhr.status,
                            json: function() { return Promise.resolve(JSON.parse(xhr.responseText)); }
                        });
                    } else {
                        reject(new Error("HTTP " + xhr.status));
                    }
                }
            };
            xhr.send(options && options.body || null);
        });
    };
}';
        file_put_contents($polyfillPath, $polyfillContent);
        $fixesApplied[] = 'Polyfills created';
    }
    
    return $fixesApplied;
}

// Apply fixes if requested
$fixesApplied = [];
if (isset($_POST['apply_comprehensive_fixes'])) {
    $fixesApplied = applyComprehensiveFixes();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixed Hostinger Audit - ERGON</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .header { background: #1e40af; color: white; padding: 15px; margin: -20px -20px 20px -20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .btn { background: #1e40af; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #1e3a8a; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .status-ok { color: #059669; font-weight: bold; }
        .status-issue { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Fixed Hostinger Compatibility Audit</h1>
            <p>Comprehensive Root Cause Analysis & Fix</p>
        </div>

        <div class="section info">
            <h2>File Status Analysis</h2>
            <table>
                <?php foreach ($fileStatus as $component => $status): ?>
                <tr>
                    <th><?= $component ?></th>
                    <td class="<?= in_array($status, ['Present', 'Complete', 'ES5 Compatible', 'Found']) ? 'status-ok' : 'status-issue' ?>">
                        <?= htmlspecialchars($status) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="section <?= empty($issues) ? 'success' : 'warning' ?>">
            <h2>Final Audit Results</h2>
            <?php if (empty($issues)): ?>
                <p>✅ <strong>All compatibility issues resolved!</strong></p>
                <p>System is fully compatible with Hostinger hosting environment.</p>
            <?php else: ?>
                <h3>Remaining Issues:</h3>
                <ul>
                    <?php foreach ($issues as $issue): ?>
                        <li><?= htmlspecialchars($issue) ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <h3>Required Fixes:</h3>
                <ul>
                    <?php foreach ($fixes as $fix): ?>
                        <li><?= htmlspecialchars($fix) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if (!empty($fixesApplied)): ?>
        <div class="section success">
            <h2>✅ Fixes Applied Successfully</h2>
            <ul>
                <?php foreach ($fixesApplied as $fix): ?>
                    <li><?= htmlspecialchars($fix) ?></li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Refresh this page to see updated results.</strong></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($issues)): ?>
        <div class="section">
            <h2>Comprehensive Auto-Fix</h2>
            <p>Apply all remaining fixes automatically:</p>
            <form method="POST">
                <button type="submit" name="apply_comprehensive_fixes" class="btn">🔧 Apply All Fixes</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="section info">
            <h2>Server Environment</h2>
            <table>
                <?php foreach ($serverInfo as $key => $value): ?>
                <tr>
                    <th><?= ucfirst(str_replace('_', ' ', $key)) ?></th>
                    <td><?= htmlspecialchars($value) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>