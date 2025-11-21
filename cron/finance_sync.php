<?php
/**
 * Automated PostgreSQL to MySQL Finance Sync
 * Run this via cron job on Hostinger
 */

require_once __DIR__ . '/../app/config/database.php';

function syncFinanceData() {
    try {
        // PostgreSQL connection
        $conn = pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango");
        
        if (!$conn) {
            throw new Exception('PostgreSQL connection failed');
        }
        
        // MySQL connection
        $db = Database::connect();
        
        // Create tables if not exist
        $db->exec("CREATE TABLE IF NOT EXISTS finance_tables (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100) UNIQUE,
            record_count INT,
            last_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE IF NOT EXISTS finance_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100),
            data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(table_name)
        )");
        
        // Get PostgreSQL tables
        $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        
        $syncCount = 0;
        while ($row = pg_fetch_assoc($result)) {
            $tableName = $row['table_name'];
            
            // Get table data
            $dataResult = pg_query($conn, "SELECT * FROM $tableName LIMIT 1000");
            
            // Clear existing MySQL data
            $stmt = $db->prepare("DELETE FROM finance_data WHERE table_name = ?");
            $stmt->execute([$tableName]);
            
            $recordCount = 0;
            while ($dataRow = pg_fetch_assoc($dataResult)) {
                $stmt = $db->prepare("INSERT INTO finance_data (table_name, data) VALUES (?, ?)");
                $stmt->execute([$tableName, json_encode($dataRow)]);
                $recordCount++;
            }
            
            // Update table info
            $stmt = $db->prepare("INSERT INTO finance_tables (table_name, record_count) VALUES (?, ?) 
                                 ON DUPLICATE KEY UPDATE record_count = ?, last_sync = NOW()");
            $stmt->execute([$tableName, $recordCount, $recordCount]);
            
            $syncCount++;
            echo "Synced table: $tableName ($recordCount records)\n";
        }
        
        pg_close($conn);
        echo "Finance sync completed: $syncCount tables\n";
        
    } catch (Exception $e) {
        echo "Sync error: " . $e->getMessage() . "\n";
    }
}

// Run if called directly
if (php_sapi_name() === 'cli') {
    syncFinanceData();
}
?>