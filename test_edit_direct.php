<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';

require_once __DIR__ . '/config/database.php';

$id = 13;

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle form submission
        $departments = isset($_POST['departments']) ? implode(',', $_POST['departments']) : '';
        
        $sql = "UPDATE users SET 
                name = ?, email = ?, phone = ?, role = ?, status = ?,
                department = ?, designation = ?, joining_date = ?, salary = ?,
                date_of_birth = ?, gender = ?, address = ?, emergency_contact = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            $_POST['name'] ?? '',
            $_POST['email'] ?? '',
            $_POST['phone'] ?? '',
            $_POST['role'] ?? 'user',
            $_POST['status'] ?? 'active',
            $departments,
            $_POST['designation'] ?? '',
            $_POST['joining_date'] ?? null,
            $_POST['salary'] ?? null,
            $_POST['date_of_birth'] ?? null,
            $_POST['gender'] ?? null,
            $_POST['address'] ?? '',
            $_POST['emergency_contact'] ?? '',
            $id
        ]);
        
        if ($result) {
            echo "User updated successfully!";
            exit;
        }
    }
    
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "User not found!";
        exit;
    }
    
    // Include the edit form
    $data = ['user' => $user];
    include __DIR__ . '/app/views/users/edit.php';
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>