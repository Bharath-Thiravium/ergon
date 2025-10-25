<?php
/**
 * Cache Management Tool
 * Browser-accessible cache clearing for shared hosting
 */

require_once __DIR__ . '/app/helpers/PerformanceBooster.php';
require_once __DIR__ . '/app/helpers/SessionManager.php';

// Simple authentication - change this password
$CACHE_ADMIN_PASSWORD = 'ergon2024cache';

$action = $_GET['action'] ?? '';
$password = $_POST['password'] ?? $_GET['password'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>ERGON Cache Management</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-danger { background: #dc3545; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        input[type="password"] { padding: 8px; margin: 10px 0; width: 200px; }
    </style>
</head>
<body>
    <h1>🚀 ERGON Cache Management</h1>
    
    <?php if ($action && $password === $CACHE_ADMIN_PASSWORD): ?>
        
        <?php if ($action === 'clear'): ?>
            <?php 
            PerformanceBooster::clearCache();
            echo '<div class="success">✅ Cache cleared successfully!</div>';
            ?>
        <?php endif; ?>
        
        <?php if ($action === 'optimize'): ?>
            <?php 
            $result = PerformanceBooster::optimizeDatabase();
            if ($result) {
                echo '<div class="success">✅ Database optimized successfully!</div>';
            } else {
                echo '<div class="error">❌ Database optimization failed (check logs)</div>';
            }
            ?>
        <?php endif; ?>
        
        <?php if ($action === 'stats'): ?>
            <?php 
            $stats = PerformanceBooster::getPerformanceStats();
            echo '<div class="card">';
            echo '<h3>📊 Performance Statistics</h3>';
            echo '<p><strong>Memory Usage:</strong> ' . round($stats['memory_usage'] / 1024 / 1024, 2) . ' MB</p>';
            echo '<p><strong>Peak Memory:</strong> ' . round($stats['memory_peak'] / 1024 / 1024, 2) . ' MB</p>';
            echo '<p><strong>Execution Time:</strong> ' . round($stats['execution_time'] * 1000, 2) . ' ms</p>';
            echo '<p><strong>Cache Files:</strong> ' . $stats['cache_files'] . '</p>';
            echo '</div>';
            ?>
        <?php endif; ?>
        
    <?php elseif ($action && $password !== $CACHE_ADMIN_PASSWORD): ?>
        <div class="error">❌ Invalid password</div>
    <?php endif; ?>
    
    <div class="card">
        <h3>🛠️ Cache Operations</h3>
        <form method="post">
            <p>Password: <input type="password" name="password" placeholder="Enter cache admin password"></p>
            
            <p>
                <button type="submit" name="action" value="clear" class="btn btn-danger">🗑️ Clear All Cache</button>
                <button type="submit" name="action" value="optimize" class="btn">⚡ Optimize Database</button>
                <button type="submit" name="action" value="stats" class="btn">📊 View Stats</button>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h3>ℹ️ Instructions</h3>
        <ul>
            <li><strong>Clear Cache:</strong> Remove all cached pages (use after content updates)</li>
            <li><strong>Optimize Database:</strong> Analyze and optimize database tables</li>
            <li><strong>View Stats:</strong> Show current performance metrics</li>
        </ul>
        <p><strong>Note:</strong> Change the password in <code>clear_cache.php</code> for security.</p>
    </div>
    
    <div class="card">
        <h3>🔧 Quick Links</h3>
        <p>
            <a href="optimize_assets.php" class="btn">🎨 Re-optimize Assets</a>
            <a href="ergon_security_audit_refined.php" class="btn">🔒 Security Scan</a>
        </p>
    </div>
</body>
</html>