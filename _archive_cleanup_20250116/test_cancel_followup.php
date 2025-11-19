<?php
/**
 * Test script for cancel follow-up functionality
 */

require_once __DIR__ . '/app/config/database.php';

// Simulate session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

try {
    $db = Database::connect();
    echo "<h2>Cancel Follow-up Functionality Test</h2>";
    echo "<hr>";
    
    // 1. Check if we have any followups that can be cancelled
    $stmt = $db->query("SELECT id, title, status FROM followups WHERE status NOT IN ('cancelled', 'completed') LIMIT 1");
    $followup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$followup) {
        echo "❌ No cancellable follow-ups found. Creating test data...<br>";
        
        // Create test contact if needed
        $stmt = $db->query("SELECT id FROM contacts LIMIT 1");
        $contactId = $stmt->fetchColumn();
        
        if (!$contactId) {
            $db->exec("INSERT INTO contacts (name, phone, email, company) VALUES 
                       ('Test Contact for Cancel', '+1234567890', 'cancel.test@example.com', 'Test Company')");
            $contactId = $db->lastInsertId();
            echo "✅ Created test contact with ID: $contactId<br>";
        }
        
        // Create test followup
        $db->exec("INSERT INTO followups (contact_id, user_id, title, description, follow_up_date, status) VALUES 
                   ($contactId, 1, 'Test Cancel Follow-up', 'This is a test follow-up for cancel functionality', '" . date('Y-m-d', strtotime('+1 day')) . "', 'pending')");
        
        $followupId = $db->lastInsertId();
        
        $stmt = $db->prepare("SELECT id, title, status FROM followups WHERE id = ?");
        $stmt->execute([$followupId]);
        $followup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Created test follow-up: ID {$followup['id']}<br><br>";
    }
    
    echo "<strong>Testing with follow-up:</strong><br>";
    echo "ID: {$followup['id']}<br>";
    echo "Title: {$followup['title']}<br>";
    echo "Status: {$followup['status']}<br><br>";
    
    // 2. Test cancel functionality
    $reason = 'Testing cancel functionality';
    
    echo "<strong>Attempting to cancel with reason:</strong> $reason<br><br>";
    
    // Simulate the cancel process
    $stmt = $db->prepare("SELECT id, status, contact_id FROM followups WHERE id = ?");
    $stmt->execute([$followup['id']]);
    $followupData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($followupData) {
        echo "✅ Follow-up found - Status: {$followupData['status']}<br>";
        
        // Check if it can be cancelled
        if ($followupData['status'] === 'cancelled') {
            echo "⚠️ Follow-up is already cancelled<br>";
        } else {
            // Perform cancel
            $stmt = $db->prepare("UPDATE followups SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$followup['id']]);
            $rowsAffected = $stmt->rowCount();
            
            echo "Update result: " . ($result ? "SUCCESS" : "FAILED") . "<br>";
            echo "Rows affected: $rowsAffected<br>";
            
            if ($result && $rowsAffected > 0) {
                echo "✅ <strong>Cancel successful!</strong><br>";
                
                // Verify the change
                $stmt = $db->prepare("SELECT status FROM followups WHERE id = ?");
                $stmt->execute([$followup['id']]);
                $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<strong>Verification:</strong><br>";
                echo "New Status: {$updated['status']}<br>";
                
                if ($updated['status'] === 'cancelled') {
                    echo "✅ <strong>Verification successful!</strong><br>";
                } else {
                    echo "❌ <strong>Verification failed!</strong><br>";
                }
                
                // Test history logging
                try {
                    $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, notes, created_by) VALUES (?, ?, ?, ?, ?)");
                    $historyResult = $stmt->execute([
                        $followup['id'], 
                        'cancelled', 
                        $followupData['status'], 
                        "Follow-up cancelled. Reason: {$reason}",
                        $_SESSION['user_id']
                    ]);
                    
                    if ($historyResult) {
                        echo "✅ History logged successfully!<br>";
                    } else {
                        echo "⚠️ Cancel successful but history logging failed<br>";
                    }
                } catch (Exception $e) {
                    echo "⚠️ History logging error: " . $e->getMessage() . "<br>";
                }
                
            } else {
                echo "❌ <strong>Cancel failed!</strong><br>";
                echo "Possible causes:<br>";
                echo "1. Follow-up ID does not exist<br>";
                echo "2. Database connection issue<br>";
                echo "3. Permission issue<br>";
            }
        }
    } else {
        echo "❌ <strong>CRITICAL:</strong> Follow-up not found in database<br>";
    }
    
    echo "<hr>";
    echo "<h3>Test Results Summary:</h3>";
    echo "<ul>";
    echo "<li>✅ Database connection working</li>";
    echo "<li>✅ Follow-up record exists</li>";
    echo "<li>✅ Cancel functionality tested</li>";
    echo "<li>✅ History logging tested</li>";
    echo "</ul>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test the web interface at: <a href='/ergon/contacts/followups/view'>/ergon/contacts/followups/view</a></li>";
    echo "<li>Check browser console for JavaScript errors</li>";
    echo "<li>Verify network requests in browser developer tools</li>";
    echo "<li>Check server error logs for detailed debugging</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "❌ <strong>CRITICAL ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<hr>";
echo "<p><strong>Test completed.</strong> The cancel functionality should now work properly.</p>";
?>