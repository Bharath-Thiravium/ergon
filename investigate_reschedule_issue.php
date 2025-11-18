<?php
/**
 * Comprehensive investigation script for reschedule follow-up issue
 * This script will identify the root cause of "Error: Follow-up not found or no changes made"
 */

require_once __DIR__ . '/app/config/database.php';

// Start session to simulate logged-in user
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

echo "<h1>Reschedule Follow-up Investigation</h1>";
echo "<hr>";

try {
    $db = Database::connect();
    
    // 1. Check database structure
    echo "<h2>1. Database Structure Analysis</h2>";
    
    // Check if followups table exists
    $stmt = $db->query("SHOW TABLES LIKE 'followups'");
    if ($stmt->rowCount() == 0) {
        echo "❌ <strong>CRITICAL:</strong> followups table does not exist!<br>";
        echo "Creating followups table...<br>";
        
        $createTable = "CREATE TABLE `followups` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `contact_id` int(11) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `title` varchar(255) NOT NULL,
            `description` text DEFAULT NULL,
            `follow_up_date` date NOT NULL,
            `status` enum('pending','in_progress','completed','postponed','cancelled') NOT NULL DEFAULT 'pending',
            `completed_at` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_contact_id` (`contact_id`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_follow_up_date` (`follow_up_date`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($createTable);
        echo "✅ followups table created<br>";
    } else {
        echo "✅ followups table exists<br>";
    }
    
    // Check followups table structure
    $stmt = $db->query("DESCRIBE followups");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<strong>Followups table columns:</strong><br>";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}<br>";
    }
    
    // Check if contacts table exists
    $stmt = $db->query("SHOW TABLES LIKE 'contacts'");
    if ($stmt->rowCount() == 0) {
        echo "❌ <strong>CRITICAL:</strong> contacts table does not exist!<br>";
        echo "Creating contacts table...<br>";
        
        $createContacts = "CREATE TABLE `contacts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `email` varchar(255) DEFAULT NULL,
            `company` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($createContacts);
        echo "✅ contacts table created<br>";
    } else {
        echo "✅ contacts table exists<br>";
    }
    
    // 2. Check existing data
    echo "<h2>2. Data Analysis</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) FROM contacts");
    $contactCount = $stmt->fetchColumn();
    echo "Total contacts: $contactCount<br>";
    
    $stmt = $db->query("SELECT COUNT(*) FROM followups");
    $followupCount = $stmt->fetchColumn();
    echo "Total followups: $followupCount<br>";
    
    // 3. Create test data if needed
    if ($contactCount == 0) {
        echo "<h3>Creating test contact...</h3>";
        $stmt = $db->prepare("INSERT INTO contacts (name, phone, email, company) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Test Contact', '+1234567890', 'test@example.com', 'Test Company']);
        $contactId = $db->lastInsertId();
        echo "✅ Test contact created with ID: $contactId<br>";
    } else {
        $stmt = $db->query("SELECT id FROM contacts LIMIT 1");
        $contactId = $stmt->fetchColumn();
        echo "Using existing contact ID: $contactId<br>";
    }
    
    if ($followupCount == 0) {
        echo "<h3>Creating test followup...</h3>";
        $stmt = $db->prepare("INSERT INTO followups (contact_id, user_id, title, description, follow_up_date, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $contactId, 
            1, 
            'Test Reschedule Followup', 
            'This is a test followup for reschedule investigation', 
            date('Y-m-d', strtotime('+1 day')), 
            'pending'
        ]);
        $followupId = $db->lastInsertId();
        echo "✅ Test followup created with ID: $followupId<br>";
    } else {
        $stmt = $db->query("SELECT id FROM followups WHERE status IN ('pending', 'in_progress') LIMIT 1");
        $followupId = $stmt->fetchColumn();
        if (!$followupId) {
            $stmt = $db->query("SELECT id FROM followups LIMIT 1");
            $followupId = $stmt->fetchColumn();
        }
        echo "Using existing followup ID: $followupId<br>";
    }
    
    // 4. Test the reschedule functionality step by step
    echo "<h2>3. Reschedule Functionality Test</h2>";
    
    if ($followupId) {
        // Get current followup data
        $stmt = $db->prepare("SELECT * FROM followups WHERE id = ?");
        $stmt->execute([$followupId]);
        $followup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($followup) {
            echo "<h3>Current followup data:</h3>";
            echo "ID: {$followup['id']}<br>";
            echo "Title: {$followup['title']}<br>";
            echo "Current Date: {$followup['follow_up_date']}<br>";
            echo "Status: {$followup['status']}<br>";
            
            // Test reschedule logic
            $newDate = date('Y-m-d', strtotime('+5 days'));
            $reason = 'Investigation test reschedule';
            
            echo "<h3>Testing reschedule to: $newDate</h3>";
            
            // Step 1: Check if followup can be rescheduled
            if (in_array($followup['status'], ['completed', 'cancelled'])) {
                echo "❌ Cannot reschedule {$followup['status']} follow-up<br>";
            } else {
                echo "✅ Follow-up status allows rescheduling<br>";
                
                // Step 2: Get old date for history
                $oldDate = $followup['follow_up_date'];
                echo "Old date: $oldDate<br>";
                
                // Step 3: Perform the update
                echo "<h4>Executing UPDATE query...</h4>";
                $stmt = $db->prepare("UPDATE followups SET follow_up_date = ?, status = 'postponed', updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$newDate, $followupId]);
                
                echo "Query executed: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "<br>";
                echo "Rows affected: " . $stmt->rowCount() . "<br>";
                
                if ($result && $stmt->rowCount() > 0) {
                    echo "✅ <strong>Reschedule successful!</strong><br>";
                    
                    // Verify the change
                    $stmt = $db->prepare("SELECT follow_up_date, status, updated_at FROM followups WHERE id = ?");
                    $stmt->execute([$followupId]);
                    $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo "<h4>Verification:</h4>";
                    echo "New Date: {$updated['follow_up_date']}<br>";
                    echo "New Status: {$updated['status']}<br>";
                    echo "Updated At: {$updated['updated_at']}<br>";
                    
                    if ($updated['follow_up_date'] === $newDate && $updated['status'] === 'postponed') {
                        echo "✅ <strong>Verification successful!</strong><br>";
                    } else {
                        echo "❌ <strong>Verification failed!</strong><br>";
                    }
                    
                } else {
                    echo "❌ <strong>ISSUE IDENTIFIED:</strong> Update query returned no affected rows<br>";
                    echo "<strong>Possible causes:</strong><br>";
                    echo "1. Follow-up ID does not exist<br>";
                    echo "2. Database connection issue<br>";
                    echo "3. Table structure problem<br>";
                    echo "4. Permission issue<br>";
                }
            }
        } else {
            echo "❌ <strong>CRITICAL:</strong> Follow-up with ID $followupId not found in database<br>";
        }
    }
    
    // 5. Test the controller method directly
    echo "<h2>4. Controller Method Test</h2>";
    
    // Simulate POST request
    $_POST['new_date'] = date('Y-m-d', strtotime('+7 days'));
    $_POST['reason'] = 'Controller test reschedule';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    
    echo "Simulating reschedule request...<br>";
    echo "Follow-up ID: $followupId<br>";
    echo "New Date: {$_POST['new_date']}<br>";
    echo "Reason: {$_POST['reason']}<br>";
    
    // Test the controller logic manually
    try {
        $newDate = $_POST['new_date'] ?? null;
        $reason = trim($_POST['reason'] ?? 'No reason provided');
        
        if (!$newDate) {
            echo "❌ New date required<br>";
        } else {
            // Get old date first
            $stmt = $db->prepare("SELECT follow_up_date, status FROM followups WHERE id = ?");
            $stmt->execute([$followupId]);
            $followupData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($followupData) {
                $oldDate = $followupData['follow_up_date'];
                echo "Found followup - Old date: $oldDate, Status: {$followupData['status']}<br>";
                
                // Update followup
                $stmt = $db->prepare("UPDATE followups SET follow_up_date = ?, status = 'postponed' WHERE id = ?");
                $result = $stmt->execute([$newDate, $followupId]);
                
                echo "Update result: " . ($result ? "SUCCESS" : "FAILED") . "<br>";
                echo "Rows affected: " . $stmt->rowCount() . "<br>";
                
                if ($result && $stmt->rowCount() > 0) {
                    echo "✅ <strong>Controller logic works correctly!</strong><br>";
                } else {
                    echo "❌ <strong>Controller logic issue: No rows affected</strong><br>";
                    
                    // Additional debugging
                    echo "<h4>Debug Information:</h4>";
                    echo "SQL: UPDATE followups SET follow_up_date = ?, status = 'postponed' WHERE id = ?<br>";
                    echo "Parameters: [$newDate, $followupId]<br>";
                    
                    // Check if the record still exists
                    $stmt = $db->prepare("SELECT * FROM followups WHERE id = ?");
                    $stmt->execute([$followupId]);
                    $checkRecord = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($checkRecord) {
                        echo "Record exists: YES<br>";
                        echo "Current status: {$checkRecord['status']}<br>";
                        echo "Current date: {$checkRecord['follow_up_date']}<br>";
                    } else {
                        echo "Record exists: NO - This is the problem!<br>";
                    }
                }
            } else {
                echo "❌ <strong>CRITICAL ISSUE:</strong> Follow-up not found in database<br>";
                echo "This is likely the root cause of the error message<br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Controller test error: " . $e->getMessage() . "<br>";
    }
    
    // 6. Route testing
    echo "<h2>5. Route Analysis</h2>";
    
    $expectedRoute = "/ergon/contacts/followups/reschedule/$followupId";
    echo "Expected route: $expectedRoute<br>";
    
    // Check if route exists in routes.php
    $routesContent = file_get_contents(__DIR__ . '/app/config/routes.php');
    if (strpos($routesContent, 'reschedule/{id}') !== false) {
        echo "✅ Reschedule route found in routes.php<br>";
    } else {
        echo "❌ Reschedule route NOT found in routes.php<br>";
    }
    
    // 7. Summary and recommendations
    echo "<h2>6. Summary and Recommendations</h2>";
    
    echo "<h3>Potential Issues Identified:</h3>";
    echo "<ol>";
    echo "<li><strong>Database Structure:</strong> Check if all required tables and columns exist</li>";
    echo "<li><strong>Data Integrity:</strong> Verify follow-up records exist and are accessible</li>";
    echo "<li><strong>Controller Logic:</strong> Ensure proper error handling and validation</li>";
    echo "<li><strong>Route Configuration:</strong> Verify routes are properly configured</li>";
    echo "<li><strong>Session Management:</strong> Check if user session is valid</li>";
    echo "</ol>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Run the database fix script: <code>php quick_reschedule_fix.php</code></li>";
    echo "<li>Test the web interface at: <a href='/ergon/contacts/followups/view'>/ergon/contacts/followups/view</a></li>";
    echo "<li>Check browser console for JavaScript errors</li>";
    echo "<li>Verify network requests in browser developer tools</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "❌ <strong>CRITICAL ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<br>Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>Investigation completed.</strong> Review the results above to identify the root cause.</p>";
?>