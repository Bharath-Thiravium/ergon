<?php
require_once 'app/config/database.php';

// Simple password reset utility
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $new_password = $_POST['password'] ?? '';
    
    if ($identifier && $new_password) {
        try {
            $db = Database::connect();
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ? OR employee_id = ?");
            $result = $stmt->execute([$hashed_password, $identifier, $identifier]);
            
            if ($stmt->rowCount() > 0) {
                echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px;'>Password updated successfully for: $identifier</div>";
            } else {
                echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>User not found: $identifier</div>";
            }
        } catch (Exception $e) {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Show existing users
try {
    $db = Database::connect();
    $stmt = $db->query("SELECT id, employee_id, name, email, role FROM users ORDER BY id");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>Database error: " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Utility</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        label { display: block; margin-bottom: 5px; }
        input { padding: 8px; width: 300px; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Password Reset Utility</h1>
    
    <form method="POST">
        <div class="form-group">
            <label>Email or Employee ID:</label>
            <input type="text" name="identifier" required>
        </div>
        <div class="form-group">
            <label>New Password:</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Reset Password</button>
    </form>
    
    <h2>Existing Users</h2>
    <?php if (!empty($users)): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
            </tr>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['employee_id']) ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No users found or database connection failed.</p>
    <?php endif; ?>
    
    <p><strong>Note:</strong> Delete this file after use for security.</p>
</body>
</html>