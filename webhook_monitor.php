<?php
/**
 * Webhook Deployment Monitor
 * Monitors deployment status and provides webhook information
 */

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Webhook Monitor</title>";
echo "<style>body{font-family:monospace;margin:20px;} .status{padding:10px;margin:10px 0;border-radius:5px;} .success{background:#d4edda;color:#155724;} .warning{background:#fff3cd;color:#856404;} .info{background:#d1ecf1;color:#0c5460;}</style>";
echo "</head><body>";

echo "<h1>🔗 Webhook Deployment Monitor</h1>";

// Check deployment indicators
$deploymentTime = filemtime('.') ?? time();
$lastModified = date('Y-m-d H:i:s', $deploymentTime);

echo "<div class='status info'>";
echo "<h2>📊 Deployment Status</h2>";
echo "<p><strong>Last Directory Update:</strong> $lastModified</p>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";

// Check critical files modification times
$criticalFiles = [
    'public/assets/css/ergon.css',
    'public/assets/css/components.css',
    'app/views/layouts/dashboard.php',
    '.htaccess'
];

echo "<div class='status info'>";
echo "<h2>📁 File Modification Times</h2>";
foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $modTime = date('Y-m-d H:i:s', filemtime($file));
        echo "<p><strong>$file:</strong> $modTime</p>";
    } else {
        echo "<p><strong>$file:</strong> <span style='color:red;'>Missing</span></p>";
    }
}
echo "</div>";

// Webhook configuration guide
echo "<div class='status warning'>";
echo "<h2>⚙️ Webhook Configuration</h2>";
echo "<p><strong>Repository:</strong> Your GitHub repository</p>";
echo "<p><strong>Webhook URL:</strong> https://athenas.co.in/webhook-endpoint</p>";
echo "<p><strong>Events:</strong> Push events on main branch</p>";
echo "<p><strong>Content Type:</strong> application/json</p>";
echo "</div>";

// Deployment verification
echo "<div class='status info'>";
echo "<h2>✅ Verification Steps</h2>";
echo "<ol>";
echo "<li>Check if CSS files are accessible:</li>";
echo "<ul>";
echo "<li><a href='/ergon/public/assets/css/ergon.css' target='_blank'>ergon.css</a></li>";
echo "<li><a href='/ergon/public/assets/css/components.css' target='_blank'>components.css</a></li>";
echo "</ul>";
echo "<li>Run deployment audit: <a href='/ergon/deployment_audit.php' target='_blank'>Audit Report</a></li>";
echo "<li>Test main application: <a href='/ergon/' target='_blank'>ERGON App</a></li>";
echo "</ol>";
echo "</div>";

// Recent deployment check
if (file_exists('.git/logs/HEAD')) {
    $gitLog = file('.git/logs/HEAD');
    $recentCommits = array_slice($gitLog, -5);
    
    echo "<div class='status success'>";
    echo "<h2>📝 Recent Deployments</h2>";
    foreach (array_reverse($recentCommits) as $commit) {
        $parts = explode("\t", $commit);
        if (count($parts) >= 2) {
            $timestamp = isset($parts[0]) ? explode(" ", $parts[0])[4] ?? '' : '';
            $message = isset($parts[1]) ? trim($parts[1]) : '';
            if ($timestamp) {
                $date = date('Y-m-d H:i:s', (int)$timestamp);
                echo "<p><strong>$date:</strong> $message</p>";
            }
        }
    }
    echo "</div>";
}

// Auto-refresh
echo "<script>";
echo "setTimeout(function(){ location.reload(); }, 30000);"; // Refresh every 30 seconds
echo "console.log('Webhook monitor - auto-refresh in 30s');";
echo "</script>";

echo "<p><em>Page auto-refreshes every 30 seconds</em></p>";
echo "</body></html>";
?>