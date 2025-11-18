<?php
/**
 * Test script for reschedule follow-up functionality
 * This script tests the reschedule functionality without requiring a web interface
 */

require_once __DIR__ . '/app/config/database.php';

// Simulate session
session_start();
$_SESSION['user_id'] = 1; // Assuming user ID 1 exists
$_SESSION['role'] = 'admin';

try {
    $db = Database::connect();
    echo "Testing Reschedule Follow-up Functionality\n";
    echo "==========================================\n\n";
    
    // 1. Check if we have any followups
    $stmt = $db->query("SELECT id, title, follow_up_date, status FROM followups WHERE status != 'completed' AND status != 'cancelled' LIMIT 1");
    $followup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$followup) {
        echo "No active follow-ups found. Creating test data...\n";
        
        // Create test contact
        $db->exec("INSERT IGNORE INTO contacts (name, phone, email, company) VALUES 
                   ('Test Contact for Reschedule', '+1234567890', 'reschedule.test@example.com', 'Test Company')");
        
        $contactId = $db->lastInsertId();
        if (!$contactId) {
            $stmt = $db->query("SELECT id FROM contacts WHERE email = 'reschedule.test@example.com'");
            $contactId = $stmt->fetchColumn();
        }
        
        // Create test followup
        $db->exec("INSERT INTO followups (contact_id, user_id, title, description, follow_up_date, status) VALUES 
                   ($contactId, 1, 'Test Reschedule Follow-up', 'This is a test follow-up for reschedule functionality', '" . date('Y-m-d', strtotime('+1 day')) . "', 'pending')");
        
        $followupId = $db->lastInsertId();
        
        $stmt = $db->prepare("SELECT id, title, follow_up_date, status FROM followups WHERE id = ?");
        $stmt->execute([$followupId]);
        $followup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "✓ Created test follow-up: ID {$followup['id']}\n\n";
    }
    
    echo "Testing with follow-up:\n";
    echo "ID: {$followup['id']}\n";
    echo "Title: {$followup['title']}\n";
    echo "Current Date: {$followup['follow_up_date']}\n";
    echo "Status: {$followup['status']}\n\n";
    
    // 2. Test reschedule functionality
    $newDate = date('Y-m-d', strtotime('+3 days'));
    $reason = 'Testing reschedule functionality';
    
    echo "Attempting to reschedule to: $newDate\n";
    echo "Reason: $reason\n\n";
    
    // Simulate the reschedule process
    $stmt = $db->prepare("SELECT follow_up_date, contact_id, status FROM followups WHERE id = ?");
    $stmt->execute([$followup['id']]);
    $followupData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($followupData) {
        // Check if it can be rescheduled
        if (in_array($followupData['status'], ['completed', 'cancelled'])) {
            echo "❌ Cannot reschedule {$followupData['status']} follow-up\n";
        } else {
            // Perform reschedule
            $stmt = $db->prepare("UPDATE followups SET follow_up_date = ?, status = 'postponed', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$newDate, $followup['id']]);
            
            if ($result) {
                echo "✅ Reschedule successful!\n";
                
                // Log history
                $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, notes, created_by) VALUES (?, ?, ?, ?, ?)");
                $historyResult = $stmt->execute([
                    $followup['id'], 
                    'rescheduled', 
                    $followupData['follow_up_date'], 
                    "Rescheduled from {$followupData['follow_up_date']} to {$newDate}. Reason: {$reason}",
                    $_SESSION['user_id']
                ]);
                
                if ($historyResult) {
                    echo "✅ History logged successfully!\n";
                } else {
                    echo "⚠ Reschedule successful but history logging failed\n";
                }
                
                // Verify the change
                $stmt = $db->prepare("SELECT follow_up_date, status FROM followups WHERE id = ?");
                $stmt->execute([$followup['id']]);
                $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "\nVerification:\n";
                echo "New Date: {$updated['follow_up_date']}\n";
                echo "New Status: {$updated['status']}\n";
                
                if ($updated['follow_up_date'] === $newDate && $updated['status'] === 'postponed') {
                    echo "✅ Verification successful!\n";
                } else {
                    echo "❌ Verification failed!\n";
                }
                
            } else {
                echo "❌ Reschedule failed!\n";
            }
        }
    } else {
        echo "❌ Follow-up not found!\n";
    }
    
    echo "\n==========================================\n";
    echo "Test completed. Check the results above.\n";
    echo "You can now test the web interface at:\n";
    echo "http://localhost/ergon/contacts/followups/view\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>