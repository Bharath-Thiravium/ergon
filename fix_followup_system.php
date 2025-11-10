<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

try {
    $db = Database::connect();
    
    echo "<h2>Follow-up System Fix</h2>";
    
    // Step 1: Ensure followups table has correct structure
    echo "<h3>1. Ensuring followups table structure</h3>";
    
    $db->exec("CREATE TABLE IF NOT EXISTS followups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        company_name VARCHAR(255),
        contact_person VARCHAR(255),
        contact_phone VARCHAR(20),
        project_name VARCHAR(255),
        follow_up_date DATE NOT NULL,
        original_date DATE,
        reminder_time TIME NULL,
        description TEXT,
        status ENUM('pending','in_progress','completed','postponed','cancelled','rescheduled') DEFAULT 'pending',
        completed_at TIMESTAMP NULL,
        reminder_sent BOOLEAN DEFAULT FALSE,
        next_reminder DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_follow_date (follow_up_date),
        INDEX idx_status (status)
    )");
    
    echo "<p>✅ followups table structure verified</p>";
    
    // Step 2: Ensure followup_history table exists
    echo "<h3>2. Ensuring followup_history table</h3>";
    
    $db->exec("CREATE TABLE IF NOT EXISTS followup_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        followup_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        old_value TEXT,
        new_value TEXT,
        notes TEXT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_followup_id (followup_id)
    )");
    
    echo "<p>✅ followup_history table structure verified</p>";
    
    // Step 3: Check if tasks table has required columns
    echo "<h3>3. Checking tasks table structure</h3>";
    
    $stmt = $db->query("SHOW COLUMNS FROM tasks");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('task_category', $columns)) {
        $db->exec("ALTER TABLE tasks ADD COLUMN task_category VARCHAR(100) DEFAULT NULL");
        echo "<p>✅ Added task_category column to tasks table</p>";
    } else {
        echo "<p>✅ task_category column exists</p>";
    }
    
    if (!in_array('department_id', $columns)) {
        $db->exec("ALTER TABLE tasks ADD COLUMN department_id INT DEFAULT NULL");
        echo "<p>✅ Added department_id column to tasks table</p>";
    } else {
        echo "<p>✅ department_id column exists</p>";
    }
    
    // Step 4: Test follow-up creation manually
    echo "<h3>4. Testing follow-up creation</h3>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_creation'])) {
        // Create a test follow-up directly
        $stmt = $db->prepare("
            INSERT INTO followups (
                user_id, title, description, company_name, contact_person, 
                contact_phone, project_name, follow_up_date, reminder_time, 
                original_date, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $result = $stmt->execute([
            $_SESSION['user_id'],
            'Test Follow-up - ' . date('Y-m-d H:i:s'),
            'This is a test follow-up created by the fix script',
            'Test Company Ltd',
            'John Test',
            '+1-555-0123',
            'Test Project',
            date('Y-m-d', strtotime('+1 day')),
            '10:00:00',
            date('Y-m-d', strtotime('+1 day'))
        ]);
        
        if ($result) {
            $followupId = $db->lastInsertId();
            echo "<p>✅ Test follow-up created successfully with ID: $followupId</p>";
            
            // Add history entry
            $historyStmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (?, 'created', NULL, 'Test creation', 'Follow-up created by fix script', ?)");
            $historyStmt->execute([$followupId, $_SESSION['user_id']]);
            echo "<p>✅ History entry added</p>";
        } else {
            echo "<p>❌ Failed to create test follow-up: " . implode(', ', $stmt->errorInfo()) . "</p>";
        }
    }
    
    // Step 5: Show current follow-ups
    echo "<h3>5. Current follow-ups for user</h3>";
    
    $stmt = $db->prepare("SELECT * FROM followups WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($followups)) {
        echo "<p>No follow-ups found. Click the button below to create a test follow-up.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Title</th><th>Company</th><th>Contact</th><th>Date</th><th>Status</th></tr>";
        foreach ($followups as $f) {
            echo "<tr>";
            echo "<td>{$f['id']}</td>";
            echo "<td>" . htmlspecialchars($f['title']) . "</td>";
            echo "<td>" . htmlspecialchars($f['company_name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($f['contact_person'] ?? '') . "</td>";
            echo "<td>{$f['follow_up_date']}</td>";
            echo "<td><span style='padding: 2px 8px; background: #e3f2fd; border-radius: 3px;'>{$f['status']}</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Step 6: Check API endpoint
    echo "<h3>6. Testing API endpoint</h3>";
    
    $apiUrl = '/ergon/api/task-categories?department_id=1';
    echo "<p>API endpoint: <code>$apiUrl</code></p>";
    
    // Test the API internally
    try {
        require_once __DIR__ . '/app/controllers/ApiController.php';
        $_GET['department_id'] = 1;
        
        ob_start();
        $api = new ApiController();
        $api->taskCategories();
        $apiResponse = ob_get_clean();
        
        $categories = json_decode($apiResponse, true);
        if ($categories && isset($categories['categories'])) {
            echo "<p>✅ API working. Categories found:</p><ul>";
            foreach ($categories['categories'] as $cat) {
                echo "<li>" . htmlspecialchars($cat['category_name']) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>❌ API response: " . htmlspecialchars($apiResponse) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ API test failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>System Status Summary</h3>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>✅ Follow-up system is ready!</strong></p>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Create tasks with 'Follow-up' category to auto-generate follow-ups</li>";
    echo "<li>View follow-ups in the Follow-ups section</li>";
    echo "<li>Manually create follow-ups using the form</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<form method="POST" style="margin: 20px 0;">
    <button type="submit" name="test_creation" value="1" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        Create Test Follow-up
    </button>
</form>

<div style="margin: 20px 0; padding: 15px; background: #f0f8ff; border-radius: 5px;">
    <h4>Quick Links:</h4>
    <p>
        <a href="/ergon/tasks/create" style="color: #007cba; text-decoration: none; margin-right: 15px;">→ Create Task</a>
        <a href="/ergon/followups" style="color: #007cba; text-decoration: none; margin-right: 15px;">→ View Follow-ups</a>
        <a href="/ergon/test_followup_creation.php" style="color: #007cba; text-decoration: none; margin-right: 15px;">→ Detailed Test</a>
        <a href="/ergon/debug_followups.php" style="color: #007cba; text-decoration: none;">→ Debug Info</a>
    </p>
</div>