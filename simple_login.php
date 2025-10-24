<!DOCTYPE html>
<html>
<head>
    <title>Simple Login - ERGON</title>
</head>
<body>
    <h2>ERGON Login</h2>
    
    <?php
    session_start();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/config/database.php';
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            echo "<p style='color:red'>Email and password required</p>";
        } else {
            try {
                $database = new Database();
                $conn = $database->getConnection();
                
                $stmt = $conn->prepare("SELECT id, name, email, role, status FROM users WHERE email = ? AND status = 'active'");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['user_name'] = $user['name'];
                    
                    echo "<p style='color:green'>Login successful! <a href='/ergon/user/dashboard'>Go to Dashboard</a></p>";
                } else {
                    echo "<p style='color:red'>Invalid email or password</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
            }
        }
    }
    ?>
    
    <form method="POST">
        <p>
            <label>Email:</label><br>
            <input type="email" name="email" value="ilayaraja@athenas.co.in" required>
        </p>
        <p>
            <label>Password:</label><br>
            <input type="password" name="password" value="password" required>
        </p>
        <p>
            <button type="submit">Login</button>
        </p>
    </form>
    
    <p><strong>Test Credentials:</strong></p>
    <p>Email: ilayaraja@athenas.co.in<br>Password: password</p>
</body>
</html>