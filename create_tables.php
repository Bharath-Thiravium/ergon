<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Create circulars table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS circulars (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        posted_by INT NOT NULL,
        visible_to ENUM('All','Admin','User') DEFAULT 'All',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $conn->exec($sql);
    echo "Circulars table created successfully.\n";
    
    // Insert sample circular
    $stmt = $conn->prepare("INSERT IGNORE INTO circulars (title, message, posted_by, visible_to) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Welcome to ERGON', 'Welcome to the new employee tracking system. Please update your profile and start logging your attendance.', 1, 'All']);
    
    echo "Sample circular added.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>