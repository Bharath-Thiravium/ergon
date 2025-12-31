<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::connect();
    
    // Get all tables
    $tables_result = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
    $tables = $tables_result->fetchAll(PDO::FETCH_COLUMN);
    
    $structure = [];
    
    foreach ($tables as $table) {
        // Get columns
        $cols_result = $db->query("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' ORDER BY ORDINAL_POSITION");
        $columns = $cols_result->fetchAll(PDO::FETCH_ASSOC);
        
        // Get indexes
        $idx_result = $db->query("SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' ORDER BY INDEX_NAME, SEQ_IN_INDEX");
        $indexes = $idx_result->fetchAll(PDO::FETCH_ASSOC);
        
        $structure[$table] = [
            'columns' => $columns,
            'indexes' => $indexes,
            'row_count' => $db->query("SELECT COUNT(*) FROM $table")->fetchColumn()
        ];
    }
    
    echo json_encode(['success' => true, 'database' => DATABASE(), 'tables' => $structure], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
