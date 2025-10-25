<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERGON Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header text-center">
                        <h3>🧭 ERGON Login</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        session_start();
                        
                        if ($_POST['login'] ?? false) {
                            require_once __DIR__ . '/app/models/User.php';
                            $user = new User();
                            $result = $user->authenticate($_POST['email'] ?? '', $_POST['password'] ?? '');
                            
                            if ($result) {
                                $_SESSION['user_id'] = $result['id'];
                                $_SESSION['user_name'] = $result['name'];
                                $_SESSION['user_email'] = $result['email'];
                                $_SESSION['role'] = $result['role'];
                                $_SESSION['login_time'] = time();
                                
                                // Redirect based on role
                                $redirect = '/ergon/simple-dashboard.php';
                                echo '<script>window.location.href="' . $redirect . '";</script>';
                                exit;
                            } else {
                                echo '<div class="alert alert-danger">Invalid email or password</div>';
                            }
                        }
                        ?>
                        
                        <form method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" name="login" value="1" class="btn btn-primary w-100">Login</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">Default: info@athenas.co.in / admin123</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>