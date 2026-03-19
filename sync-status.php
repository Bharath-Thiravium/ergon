<?php
/**
 * PostgreSQL Sync Status API
 * GET /sync-status.php - Check sync health and last sync times
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/app/config/database.php';

try {
    $mysql = Database::connect();
    
    // Get sync history
    $stmt = $mysql->prepare("
        SELECT 
            table_name,
            records_synced,
            sync_status,
            sync_started_at,
            sync_completed_at,
            error_message
        FROM sync_log 
        ORDER BY sync_started_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $syncHistory = $stmt->fetchAll();
    
    // Get latest sync status for each table
    $stmt = $mysql->prepare("
        SELECT 
            table_name,
            MAX(sync_started_at) as last_sync,
            sync_status,
            records_synced,
            error_message
        FROM sync_log 
        GROUP BY table_name
        ORDER BY last_sync DESC
    ");
    $stmt->execute();
    $tableStatus = $stmt->fetchAll();
    
    // Check if finance tables exist
    $stmt = $mysql->query("SHOW TABLES LIKE 'finance_%'");
    $financeTables = $stmt->fetchAll();
    
    // Test PostgreSQL connection
    $pgStatus = 'unknown';
    $pgError = null;
    try {
        $config = Database::getPostgreSQLConfig();
        $pg = $config['postgresql'];
        
        $pdo = new PDO(
            "pgsql:host={$pg['host']};port={$pg['port']};dbname={$pg['database']}",
            $pg['username'],
            $pg['password'],
            [PDO::ATTR_TIMEOUT => 5]
        );
        $pgStatus = 'connected';
    } catch (Exception $e) {
        $pgStatus = 'failed';
        $pgError = $e->getMessage();
    }
    
    // Calculate overall health
    $recentSyncs = array_filter($syncHistory, function($sync) {
        return strtotime($sync['sync_started_at']) > (time() - 3600); // Last hour
    });
    
    $failedSyncs = array_filter($recentSyncs, function($sync) {
        return $sync['sync_status'] === 'failed';
    });
    
    $health = 'healthy';
    if ($pgStatus === 'failed') {
        $health = 'critical';
    } elseif (count($failedSyncs) > 0) {
        $health = 'warning';
    } elseif (empty($recentSyncs)) {
        $health = 'stale';
    }
    
    echo json_encode([
        'success' => true,
        'health' => $health,
        'postgresql_status' => $pgStatus,
        'postgresql_error' => $pgError,
        'finance_tables_count' => count($financeTables),
        'table_status' => $tableStatus,
        'recent_syncs' => count($recentSyncs),
        'failed_syncs' => count($failedSyncs),
        'sync_history' => $syncHistory,
        'recommendations' => getRecommendations($health, $pgStatus, $tableStatus)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'health' => 'critical'
    ]);
}

function getRecommendations($health, $pgStatus, $tableStatus) {
    $recommendations = [];
    
    if ($pgStatus === 'failed') {
        $recommendations[] = 'Check PostgreSQL server connectivity and credentials';
        $recommendations[] = 'Verify firewall settings and network access';
        $recommendations[] = 'Run test_postgres_connection.php for detailed diagnostics';
    }
    
    if ($health === 'stale') {
        $recommendations[] = 'Set up cron job to run sync automatically';
        $recommendations[] = 'Run manual_sync.php to trigger sync manually';
    }
    
    if (empty($tableStatus)) {
        $recommendations[] = 'Run initial sync to populate finance tables';
        $recommendations[] = 'Check if PostgreSQL source tables have data';
    }
    
    $failedTables = array_filter($tableStatus, function($table) {
        return $table['sync_status'] === 'failed';
    });
    
    if (!empty($failedTables)) {
        $recommendations[] = 'Check error messages for failed table syncs';
        $recommendations[] = 'Verify table schemas match between PostgreSQL and MySQL';
    }
    
    return $recommendations;
}
?>