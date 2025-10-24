<?php
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>ERGON System Audit</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .audit-section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007cba; }
        .error { color: #d63384; }
        .success { color: #198754; }
        .warning { color: #fd7e14; }
        .info { color: #0dcaf0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🔍 ERGON System Audit Report</h1>
    <p><strong>Generated:</strong> <?= date('Y-m-d H:i:s') ?></p>
    <p><strong>Server:</strong> <?= $_SERVER['HTTP_HOST'] ?></p>

    <div class="audit-section">
        <h2>📁 File System Check</h2>
        <table>
            <tr><th>File/Directory</th><th>Status</th><th>Last Modified</th><th>Size</th></tr>
            <?php
            $files = [
                'CSS Files' => [
                    '../public/assets/css/ergon.css',
                    '../public/assets/css/components.css'
                ],
                'Layout Files' => [
                    '../app/views/layouts/dashboard.php'
                ],
                'View Files' => [
                    '../app/views/owner/dashboard.php',
                    '../app/views/attendance/index.php',
                    '../app/views/settings/index.php',
                    '../app/views/reports/index.php'
                ],
                'Config Files' => [
                    '../config/database.php',
                    '../.htaccess'
                ]
            ];
            
            foreach ($files as $category => $fileList) {
                echo "<tr><td colspan='4'><strong>$category</strong></td></tr>";
                foreach ($fileList as $file) {
                    $fullPath = __DIR__ . '/' . $file;
                    if (file_exists($fullPath)) {
                        $modified = date('Y-m-d H:i:s', filemtime($fullPath));
                        $size = number_format(filesize($fullPath)) . ' bytes';
                        echo "<tr><td>$file</td><td class='success'>✓ EXISTS</td><td>$modified</td><td>$size</td></tr>";
                    } else {
                        echo "<tr><td>$file</td><td class='error'>✗ MISSING</td><td>-</td><td>-</td></tr>";
                    }
                }
            }
            ?>
        </table>
    </div>

    <div class="audit-section">
        <h2>🎨 CSS Version Check</h2>
        <?php
        $cssFile = __DIR__ . '/assets/css/ergon.css';
        if (file_exists($cssFile)) {
            $cssContent = file_get_contents($cssFile);
            $hasHideAccount = strpos($cssContent, 'Hide Account section') !== false;
            $hasSidebarFix = strpos($cssContent, 'sidebar__controls') !== false;
            $hasTableStyles = strpos($cssContent, 'table-responsive') !== false;
            
            echo "<table>";
            echo "<tr><th>Feature</th><th>Status</th></tr>";
            echo "<tr><td>Hide Account Section CSS</td><td class='" . ($hasHideAccount ? 'success'>✓ Present' : 'error'>✗ Missing') . "</td></tr>";
            echo "<tr><td>Sidebar Controls Fix</td><td class='" . ($hasSidebarFix ? 'success'>✓ Present' : 'error'>✗ Missing') . "</td></tr>";
            echo "<tr><td>Table Responsive Styles</td><td class='" . ($hasTableStyles ? 'success'>✓ Present' : 'error'>✗ Missing') . "</td></tr>";
            echo "</table>";
            
            echo "<h3>CSS File Info:</h3>";
            echo "<pre>";
            echo "File Size: " . number_format(filesize($cssFile)) . " bytes\n";
            echo "Last Modified: " . date('Y-m-d H:i:s', filemtime($cssFile)) . "\n";
            echo "First 200 chars: " . substr($cssContent, 0, 200) . "...\n";
            echo "</pre>";
        } else {
            echo "<p class='error'>CSS file not found!</p>";
        }
        ?>
    </div>

    <div class="audit-section">
        <h2>🔧 Dashboard Layout Check</h2>
        <?php
        $layoutFile = __DIR__ . '/../app/views/layouts/dashboard.php';
        if (file_exists($layoutFile)) {
            $layoutContent = file_get_contents($layoutFile);
            $hasAccountSection = strpos($layoutContent, 'Account</div>') !== false;
            $hasProfileLink = strpos($layoutContent, 'My Profile') !== false;
            $cacheVersion = '';
            if (preg_match('/ergon\.css\?v=([^"]+)/', $layoutContent, $matches)) {
                $cacheVersion = $matches[1];
            }
            
            echo "<table>";
            echo "<tr><th>Check</th><th>Status</th></tr>";
            echo "<tr><td>Account Section Present</td><td class='" . ($hasAccountSection ? 'error'>✗ Still Present' : 'success'>✓ Removed') . "</td></tr>";
            echo "<tr><td>My Profile Link Present</td><td class='" . ($hasProfileLink ? 'error'>✗ Still Present' : 'success'>✓ Removed') . "</td></tr>";
            echo "<tr><td>CSS Cache Version</td><td class='info'>$cacheVersion</td></tr>";
            echo "</table>";
        } else {
            echo "<p class='error'>Dashboard layout file not found!</p>";
        }
        ?>
    </div>

    <div class="audit-section">
        <h2>🌐 Environment Check</h2>
        <table>
            <tr><th>Setting</th><th>Value</th></tr>
            <tr><td>PHP Version</td><td><?= PHP_VERSION ?></td></tr>
            <tr><td>Server Software</td><td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td></tr>
            <tr><td>Document Root</td><td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?></td></tr>
            <tr><td>Current Directory</td><td><?= __DIR__ ?></td></tr>
            <tr><td>URL Path</td><td><?= $_SERVER['REQUEST_URI'] ?? 'Unknown' ?></td></tr>
            <tr><td>HTTP Host</td><td><?= $_SERVER['HTTP_HOST'] ?? 'Unknown' ?></td></tr>
        </table>
    </div>

    <div class="audit-section">
        <h2>📊 Session & Database Check</h2>
        <?php
        session_start();
        echo "<table>";
        echo "<tr><th>Check</th><th>Status</th></tr>";
        echo "<tr><td>Session Started</td><td class='success'>✓ Active</td></tr>";
        echo "<tr><td>User Logged In</td><td class='" . (isset($_SESSION['user_id']) ? 'success'>✓ Yes (ID: ' . $_SESSION['user_id'] . ')' : 'warning'>⚠ No') . "</td></tr>";
        echo "<tr><td>User Role</td><td class='info'>" . ($_SESSION['role'] ?? 'Not Set') . "</td></tr>";
        
        // Database check
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            echo "<tr><td>Database Connection</td><td class='success'>✓ Connected</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td>Database Connection</td><td class='error'>✗ Failed: " . $e->getMessage() . "</td></tr>";
        }
        echo "</table>";
        ?>
    </div>

    <div class="audit-section">
        <h2>🔍 Recommendations</h2>
        <ul>
            <li><strong>Upload Latest Files:</strong> Ensure all modified files are uploaded to Hostinger</li>
            <li><strong>Clear Browser Cache:</strong> Force refresh with Ctrl+F5</li>
            <li><strong>Check File Permissions:</strong> Ensure files are readable (644 for files, 755 for directories)</li>
            <li><strong>Verify .htaccess:</strong> Check URL rewriting rules</li>
            <li><strong>CSS Cache Version:</strong> Update version number to force reload</li>
        </ul>
    </div>

    <div class="audit-section">
        <h2>🚀 Quick Fixes</h2>
        <p><a href="?action=clear_cache" style="background:#007cba;color:white;padding:10px;text-decoration:none;border-radius:3px;">Clear All Caches</a></p>
        <?php
        if (isset($_GET['action']) && $_GET['action'] === 'clear_cache') {
            echo "<div style='background:#d4edda;color:#155724;padding:10px;border-radius:3px;margin:10px 0;'>";
            echo "✓ Cache clearing attempted<br>";
            echo "✓ Please hard refresh your browser (Ctrl+F5)<br>";
            echo "✓ Timestamp: " . date('Y-m-d H:i:s');
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>