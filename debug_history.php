<?php
/**
 * Debug History Issue - SOP Diagnostic Script
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "✅ Database connected successfully\n\n";
    
    // Check if tables exist
    $tables = $db->query("SHOW TABLES LIKE 'followup%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "📋 Tables found:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    echo "\n";
    
    // Check followup_history table structure
    if (in_array('followup_history', $tables)) {
        echo "🔍 followup_history table structure:\n";
        $columns = $db->query("DESCRIBE followup_history")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']}\n";
        }
        echo "\n";
        
        // Check existing history records
        $count = $db->query("SELECT COUNT(*) FROM followup_history")->fetchColumn();
        echo "📊 Total history records: $count\n";
        
        if ($count > 0) {
            echo "📝 Recent history records:\n";
            $recent = $db->query("SELECT * FROM followup_history ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($recent as $record) {
                echo "  ID: {$record['id']}, Followup: {$record['followup_id']}, Action: {$record['action']}, User: {$record['created_by']}, Date: {$record['created_at']}\n";
            }
        }
        echo "\n";
    }
    
    // Check followups table
    if (in_array('followups', $tables)) {
        echo "📋 Recent followups:\n";
        $followups = $db->query("SELECT id, title, status, follow_up_date, updated_at FROM followups ORDER BY updated_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($followups as $followup) {
            echo "  ID: {$followup['id']}, Title: {$followup['title']}, Status: {$followup['status']}, Updated: {$followup['updated_at']}\n";
        }
        echo "\n";
    }
    
    // Test history insertion
    echo "🧪 Testing history insertion...\n";
    session_start();
    $_SESSION['user_id'] = 1; // Test user ID
    
    $testFollowupId = 1; // Use existing followup ID
    $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([$testFollowupId, 'test', 'old_test', 'new_test', 'Test insertion', $_SESSION['user_id']]);
    
    if ($result) {
        $insertId = $db->lastInsertId();
        echo "✅ Test insertion successful: ID $insertId\n";
        
        // Clean up test record
        $db->prepare("DELETE FROM followup_history WHERE id = ?")->execute([$insertId]);
        echo "🧹 Test record cleaned up\n";
    } else {
        echo "❌ Test insertion failed: " . implode(', ', $stmt->errorInfo()) . "\n";
    }
    
    echo "\n🔧 Diagnosis complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>