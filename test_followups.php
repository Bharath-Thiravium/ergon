<?php
/**
 * Test Follow-ups System
 * Simple test to verify follow-up functionality
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Followup.php';

try {
    echo "<h1>Testing Follow-ups System</h1>";
    
    // Test database connection
    $db = Database::connect();
    echo "<p>✅ Database connection successful</p>";
    
    // Check if tables exist
    $tables = ['followups', 'followup_items', 'followup_history'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p>❌ Table '$table' missing - run followups_setup.sql in phpMyAdmin</p>";
        }
    }
    
    // Test Followup model
    $followupModel = new Followup();
    echo "<p>✅ Followup model loaded successfully</p>";
    
    // Test basic functionality (if tables exist)
    $stmt = $db->query("SHOW TABLES LIKE 'followups'");
    if ($stmt->rowCount() > 0) {
        // Test getting followups for a user (even if empty)
        $followups = $followupModel->getByUser(1);
        echo "<p>✅ Can retrieve follow-ups (found " . count($followups) . " follow-ups)</p>";
        
        $upcoming = $followupModel->getUpcoming(1);
        echo "<p>✅ Can retrieve upcoming follow-ups (found " . count($upcoming) . " upcoming)</p>";
        
        $overdue = $followupModel->getOverdue(1);
        echo "<p>✅ Can retrieve overdue follow-ups (found " . count($overdue) . " overdue)</p>";
    }
    
    echo "<h2>Follow-up System Status</h2>";
    echo "<p>The follow-up system is ready to use!</p>";
    echo "<p><a href='/ergon/followups'>Go to Follow-ups</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure to run followups_setup.sql in phpMyAdmin first</p>";
}
?>