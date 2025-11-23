<?php
/**
 * Test Follow-up System
 * This script tests the follow-up creation and retrieval functionality
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $pdo = Database::connect();
    
    echo "<h2>Testing Follow-up System</h2>";
    
    // Create tables if they don't exist
    echo "<p>Creating database tables...</p>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        email VARCHAR(255),
        company VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS followups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        contact_id INT,
        task_id INT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        followup_type ENUM('standalone', 'task') DEFAULT 'standalone',
        follow_up_date DATE NOT NULL,
        status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_contact_id (contact_id),
        INDEX idx_task_id (task_id),
        INDEX idx_follow_up_date (follow_up_date),
        INDEX idx_status (status)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS followup_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        followup_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        old_value TEXT,
        notes TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_followup_id (followup_id)
    )");
    
    echo "<p style='color: green;'>✅ Database tables created successfully!</p>";
    
    // Test creating a sample contact
    $stmt = $pdo->prepare("INSERT IGNORE INTO contacts (name, phone, email, company) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Test Contact', '+1234567890', 'test@example.com', 'Test Company']);
    $contact_id = $pdo->lastInsertId() ?: 1;
    
    echo "<p>✅ Sample contact created (ID: $contact_id)</p>";
    
    // Test creating a sample follow-up
    $stmt = $pdo->prepare("INSERT INTO followups (user_id, contact_id, title, description, followup_type, follow_up_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        1, // user_id
        $contact_id,
        'Test Follow-up',
        'This is a test follow-up to verify the system is working',
        'standalone',
        date('Y-m-d'),
        'pending'
    ]);
    
    if ($result) {
        $followup_id = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Sample follow-up created successfully (ID: $followup_id)</p>";
        
        // Test retrieving follow-ups
        $stmt = $pdo->prepare("
            SELECT f.*, c.name as contact_name, c.company as contact_company 
            FROM followups f 
            LEFT JOIN contacts c ON f.contact_id = c.id 
            ORDER BY f.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color: green;'>✅ Retrieved " . count($followups) . " follow-ups from database</p>";
        
        if (!empty($followups)) {
            echo "<h3>Recent Follow-ups:</h3>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Contact</th><th>Date</th><th>Status</th><th>Type</th></tr>";
            
            foreach ($followups as $followup) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($followup['id']) . "</td>";
                echo "<td>" . htmlspecialchars($followup['title']) . "</td>";
                echo "<td>" . htmlspecialchars($followup['contact_name'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($followup['follow_up_date']) . "</td>";
                echo "<td>" . htmlspecialchars($followup['status']) . "</td>";
                echo "<td>" . htmlspecialchars($followup['followup_type']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<h3 style='color: green;'>🎉 Follow-up System Test Completed Successfully!</h3>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ul>";
        echo "<li>Visit <a href='/ergon/followups/create' target='_blank'>/ergon/followups/create</a> to create a new follow-up</li>";
        echo "<li>Visit <a href='/ergon/followups' target='_blank'>/ergon/followups</a> to view all follow-ups</li>";
        echo "<li>Visit <a href='/ergon/contacts/followups/create' target='_blank'>/ergon/contacts/followups/create</a> for contact-centric follow-ups</li>";
        echo "<li>Visit <a href='/ergon/contacts/followups/view' target='_blank'>/ergon/contacts/followups/view</a> to view contact follow-ups</li>";
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to create sample follow-up</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>