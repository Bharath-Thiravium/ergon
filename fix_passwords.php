<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Hash the passwords properly
    $ownerPassword = password_hash('@Athenas2025@', PASSWORD_DEFAULT);
    $adminPassword = password_hash('Admin@2025@', PASSWORD_DEFAULT);

    // Clear existing users
    $conn->exec("DELETE FROM users");

    // Insert users with properly hashed passwords
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
    
    $stmt->execute(['Athenas Owner', 'info@athenas.co.in', $ownerPassword, 'owner', 'active']);
    $stmt->execute(['Athenas Admin', 'admin@athenas.co.in', $adminPassword, 'admin', 'active']);

    echo "Users created successfully!\n";
    echo "Owner: info@athenas.co.in / @Athenas2025@\n";
    echo "Admin: admin@athenas.co.in / Admin@2025@\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>