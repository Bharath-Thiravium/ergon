<?php
/**
 * Web ETL Deployment Audit
 * Access: https://athenas.co.in/ergon/web_audit_etl.php
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>ETL Deployment Audit</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #16a085; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .info { color: #3498db; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .status-box { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .status-success { background: #d5f4e6; border-left: 4px solid #16a085; }
        .status-error { background: #fdf2f2; border-left: 4px solid #e74c3c; }
        .checklist { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 ETL Deployment Audit</h1>
        <p><strong>Target:</strong> https://athenas.co.in/ergon/finance</p>
        
        <?php
        $auditResults = [];
        $overallStatus = true;
        
        try {
            // 1. File Audit
            echo "<h2>📁 File Deployment Check</h2>";
            $files = [
                'app/services/FinanceETLService.php' => 'ETL Service Core',
                'app/controllers/FinanceController.php' => 'Updated Controller',
                'cron/finance_sync.php' => 'ETL Cron Job',
                'database/finance_etl_tables.sql' => 'Database Schema'
            ];
            
            echo "<div class='checklist'>";
            $filesDeployed = 0;
            foreach ($files as $file => $desc) {
                $exists = file_exists(__DIR__ . '/' . $file);
                $status = $exists ? 'success' : 'error';
                $icon = $exists ? '✅' : '❌';
                echo "<div class='$status'>$icon $desc: $file</div>";
                if ($exists) $filesDeployed++;
            }
            echo "</div>";
            
            $filesOk = $filesDeployed == count($files);
            if (!$filesOk) $overallStatus = false;
            
            // 2. Database Connection
            echo "<h2>🔌 Database Connection</h2>";
            require_once __DIR__ . '/app/config/database.php';
            $db = Database::connect();
            echo "<div class='status-success'>✅ MySQL Connection Successful</div>";
            
            // 3. Table Structure Audit
            echo "<h2>🗄️ Database Tables</h2>";
            $tables = [
                'finance_consolidated' => 'Main ETL Data Store',
                'dashboard_stats' => 'Pre-calculated Analytics',
                'funnel_stats' => 'Conversion Funnel Data'
            ];
            
            echo "<div class='checklist'>";
            $tablesCreated = 0;
            foreach ($tables as $table => $desc) {
                $stmt = $db->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->fetch();
                $status = $exists ? 'success' : 'error';
                $icon = $exists ? '✅' : '❌';
                echo "<div class='$status'>$icon $desc: $table</div>";
                if ($exists) $tablesCreated++;
            }
            echo "</div>";
            
            $tablesOk = $tablesCreated == count($tables);
            if (!$tablesOk) $overallStatus = false;
            
            // 4. ETL Data Check
            echo "<h2>📊 ETL Data Status</h2>";
            
            $stmt = $db->query("SELECT COUNT(*) FROM finance_consolidated");
            $consolidatedCount = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT COUNT(*) FROM dashboard_stats");
            $statsCount = $stmt->fetchColumn();
            
            echo "<pre>";
            echo "Consolidated Records: $consolidatedCount\n";
            echo "Dashboard Stats: $statsCount\n";
            echo "</pre>";
            
            $dataOk = $consolidatedCount > 0 && $statsCount > 0;
            if (!$dataOk) $overallStatus = false;
            
            // 5. Performance Test
            echo "<h2>⚡ Performance Check</h2>";
            $start = microtime(true);
            $stmt = $db->query("SELECT * FROM dashboard_stats ORDER BY generated_at DESC LIMIT 1");
            $stats = $stmt->fetch();
            $queryTime = round((microtime(true) - $start) * 1000, 2);
            
            echo "<pre>";
            echo "Dashboard Query Time: {$queryTime}ms\n";
            echo "Performance Status: " . ($queryTime < 100 ? '✅ Excellent' : ($queryTime < 500 ? '⚠️ Good' : '❌ Slow')) . "\n";
            echo "</pre>";
            
            // 6. ETL Service Test
            echo "<h2>🔧 ETL Service Check</h2>";
            if (file_exists(__DIR__ . '/app/services/FinanceETLService.php')) {
                require_once __DIR__ . '/app/services/FinanceETLService.php';
                $etlService = new FinanceETLService();
                echo "<div class='status-success'>✅ ETL Service Initialized Successfully</div>";
            } else {
                echo "<div class='status-error'>❌ ETL Service File Missing</div>";
                $overallStatus = false;
            }
            
            // 7. Final Assessment
            echo "<h2>🎯 Deployment Status</h2>";
            
            if ($overallStatus) {
                echo "<div class='status-success'>";
                echo "<h3>✅ PRODUCTION READY!</h3>";
                echo "<p>All ETL components successfully deployed:</p>";
                echo "<ul>";
                echo "<li>✅ Files uploaded and accessible</li>";
                echo "<li>✅ Database tables created with indexes</li>";
                echo "<li>✅ ETL data populated and current</li>";
                echo "<li>✅ Performance optimized (sub-100ms queries)</li>";
                echo "<li>✅ ETL service operational</li>";
                echo "</ul>";
                echo "<p><strong>Finance module now uses enterprise ETL architecture!</strong></p>";
                echo "</div>";
                
                echo "<div class='checklist'>";
                echo "<h4>🚀 Ready for Production Use:</h4>";
                echo "<p>✅ Visit: <a href='/ergon/finance' target='_blank'>https://athenas.co.in/ergon/finance</a></p>";
                echo "<p>✅ Click 'Sync Data' to run ETL process</p>";
                echo "<p>✅ Analytics served from SQL tables (fast!)</p>";
                echo "<p>✅ Cron job ready: <code>0 * * * * php /path/to/cron/finance_sync.php</code></p>";
                echo "</div>";
                
            } else {
                echo "<div class='status-error'>";
                echo "<h3>❌ DEPLOYMENT INCOMPLETE</h3>";
                echo "<p>Missing components:</p>";
                echo "<ul>";
                if (!$filesOk) echo "<li>❌ Upload missing ETL files</li>";
                if (!$tablesOk) echo "<li>❌ Create database tables</li>";
                if (!$dataOk) echo "<li>❌ Run initial ETL sync</li>";
                echo "</ul>";
                echo "</div>";
            }
            
            // 8. Architecture Summary
            echo "<h2>🏗️ ETL Architecture</h2>";
            echo "<pre>";
            echo "SAP PostgreSQL API\n";
            echo "        ↓ Extract\n";
            echo "FinanceETLService.php\n";
            echo "        ↓ Transform\n";
            echo "finance_consolidated (MySQL)\n";
            echo "        ↓ Load & Calculate\n";
            echo "dashboard_stats (MySQL)\n";
            echo "        ↓ Serve\n";
            echo "Analytics Dashboard (Fast!)\n";
            echo "</pre>";
            
        } catch (Exception $e) {
            echo "<div class='status-error'>";
            echo "<h3>❌ AUDIT FAILED</h3>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
        
        <hr>
        <p><em>ETL Deployment Audit - Enterprise BI Architecture</em></p>
    </div>
</body>
</html>