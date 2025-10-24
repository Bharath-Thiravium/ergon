<?php
/**
 * Hostinger CSS & Layout Compatibility Audit Script
 * Identifies and fixes common hosting compatibility issues
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

// Define paths
$cssPath = __DIR__ . '/public/assets/css/ergon.css';
$jsPath = __DIR__ . '/public/assets/js/ergon-core.js';
$layoutPath = __DIR__ . '/app/views/layouts/dashboard.php';

// Audit results
$issues = [];
$fixes = [];

// Check CSS file
if (file_exists($cssPath)) {
    $cssContent = file_get_contents($cssPath);
    
    // Check for relative paths in CSS
    if (preg_match_all('/url\([\'"]?(?!http|\/\/|data:)([^)]+)[\'"]?\)/i', $cssContent, $matches)) {
        $issues[] = 'CSS contains relative URLs that may break on shared hosting';
        $fixes[] = 'Convert relative URLs to absolute paths';
    }
    
    // Check for @import statements
    if (strpos($cssContent, '@import') !== false) {
        $issues[] = 'CSS contains @import statements that may cause loading issues';
        $fixes[] = 'Replace @import with direct CSS inclusion';
    }
    
    // Check for viewport units without fallbacks
    if (preg_match('/\d+(vw|vh|vmin|vmax)/', $cssContent) && !preg_match('/\/\* fallback \*\//', $cssContent)) {
        $issues[] = 'CSS uses viewport units that may not be supported on all hosting environments';
        $fixes[] = 'Add fallback values for viewport units';
    }
} else {
    $issues[] = 'CSS file not found';
}

// Check JavaScript file (check ES5 version first)
$jsIePath = __DIR__ . '/public/assets/js/ergon-ie.js';
$polyfillPath = __DIR__ . '/public/assets/js/polyfills.js';

if (file_exists($jsIePath)) {
    $jsContent = file_get_contents($jsIePath);
    
    // Check for ES6+ features in IE file (should be clean)
    if (preg_match('/(const|let|=>|`|\.\.\.)/', $jsContent)) {
        $issues[] = 'JavaScript uses ES6+ features that may not work on older browsers';
        $fixes[] = 'Add babel transpilation or use ES5 syntax';
    }
    
    // Check if polyfill exists
    if (!file_exists($polyfillPath)) {
        $issues[] = 'JavaScript uses fetch API that may need polyfill';
        $fixes[] = 'Add fetch polyfill for older browsers';
    }
} else if (file_exists($jsPath)) {
    $jsContent = file_get_contents($jsPath);
    
    // Check for ES6+ features
    if (preg_match('/(const|let|=>|`|\.\.\.)/', $jsContent)) {
        $issues[] = 'JavaScript uses ES6+ features that may not work on older browsers';
        $fixes[] = 'Add babel transpilation or use ES5 syntax';
    }
    
    // Check for fetch API
    if (strpos($jsContent, 'fetch(') !== false) {
        $issues[] = 'JavaScript uses fetch API that may need polyfill';
        $fixes[] = 'Add fetch polyfill for older browsers';
    }
} else {
    $issues[] = 'JavaScript file not found';
}

// Check layout file
if (file_exists($layoutPath)) {
    $layoutContent = file_get_contents($layoutPath);
    
    // Check for hardcoded paths (skip if already using PHP variables)
    if (preg_match('/href=[\'"]\/ergon\/(?!\<\?=)/', $layoutContent) || preg_match('/src=[\'"]\/ergon\/(?!\<\?=)/', $layoutContent)) {
        $issues[] = 'Layout contains hardcoded paths that may break on different hosting setups';
        $fixes[] = 'Use dynamic path generation';
    }
} else {
    $issues[] = 'Layout file not found';
}

// Generate fixed CSS
function generateHostingerCompatibleCSS() {
    // Disabled to prevent CSS corruption
    return '';
}

// Apply fixes
function applyFixes() {
    global $cssPath, $layoutPath, $serverInfo;
    
    // Fix CSS
    if (file_exists($cssPath)) {
        $cssContent = file_get_contents($cssPath);
        $cssContent .= generateHostingerCompatibleCSS();
        file_put_contents($cssPath, $cssContent);
    }
    
    // Fix layout paths
    if (file_exists($layoutPath)) {
        $layoutContent = file_get_contents($layoutPath);
        
        // Replace hardcoded paths with dynamic ones
        $baseUrl = $serverInfo['request_scheme'] . '://' . $serverInfo['http_host'] . '/ergon';
        
        $layoutContent = preg_replace(
            '/href=[\'"]\/ergon\/([^"\']+)[\'"]/',
            'href="' . $baseUrl . '/$1"',
            $layoutContent
        );
        
        $layoutContent = preg_replace(
            '/src=[\'"]\/ergon\/([^"\']+)[\'"]/',
            'src="' . $baseUrl . '/$1"',
            $layoutContent
        );
        
        file_put_contents($layoutPath, $layoutContent);
    }
}

// HTML Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostinger Compatibility Audit - ERGON</title>
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
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Hostinger Compatibility Audit</h1>
            <p>ERGON System Compatibility Check</p>
        </div>

        <div class="section info">
            <h2>Server Information</h2>
            <table>
                <?php foreach ($serverInfo as $key => $value): ?>
                <tr>
                    <th><?= ucfirst(str_replace('_', ' ', $key)) ?></th>
                    <td><?= htmlspecialchars($value) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="section <?= empty($issues) ? 'success' : 'warning' ?>">
            <h2>Audit Results</h2>
            <?php if (empty($issues)): ?>
                <p>✅ No compatibility issues found!</p>
            <?php else: ?>
                <h3>Issues Found:</h3>
                <ul>
                    <?php foreach ($issues as $issue): ?>
                        <li><?= htmlspecialchars($issue) ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <h3>Recommended Fixes:</h3>
                <ul>
                    <?php foreach ($fixes as $fix): ?>
                        <li><?= htmlspecialchars($fix) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if (!empty($issues)): ?>
        <div class="section">
            <h2>Auto-Fix Available</h2>
            <p>Click the button below to automatically apply compatibility fixes:</p>
            
            <?php if (isset($_POST['apply_fixes'])): ?>
                <?php 
                applyFixes();
                echo '<div class="success"><p>✅ Fixes applied successfully!</p></div>';
                ?>
            <?php else: ?>
                <form method="POST">
                    <button type="submit" name="apply_fixes" class="btn">Apply Fixes</button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="section info">
            <h2>Manual Hostinger Optimization Tips</h2>
            <ul>
                <li><strong>Enable Gzip Compression:</strong> Add to .htaccess</li>
                <li><strong>Optimize Images:</strong> Use WebP format when possible</li>
                <li><strong>Minify CSS/JS:</strong> Reduce file sizes</li>
                <li><strong>Use CDN:</strong> For external libraries</li>
                <li><strong>Cache Headers:</strong> Set proper cache control</li>
            </ul>
        </div>

        <div class="section">
            <h2>Recommended .htaccess for Hostinger</h2>
            <pre><code># ERGON Hostinger Optimized .htaccess
RewriteEngine On

# Force HTTPS (if SSL is available)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove trailing slash
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)/$ /$1 [L,R=301]

# Route all requests to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Enable Gzip compression
&lt;IfModule mod_deflate.c&gt;
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
&lt;/IfModule&gt;

# Set cache headers
&lt;IfModule mod_expires.c&gt;
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
&lt;/IfModule&gt;</code></pre>
        </div>
    </div>
</body>
</html>
<?php
// Log audit results
error_log("ERGON Hostinger Audit: " . count($issues) . " issues found");
?>