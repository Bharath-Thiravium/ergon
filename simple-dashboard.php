<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERGON Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php
    session_start();
    
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        header('Location: simple-login.php');
        exit;
    }
    
    // Handle logout
    if ($_GET['logout'] ?? false) {
        session_destroy();
        header('Location: simple-login.php');
        exit;
    }
    ?>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">🧭 ERGON</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
                </span>
                <a class="btn btn-outline-light btn-sm" href="?logout=1">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-success">
                    <h4>✅ System Working!</h4>
                    <p>You have successfully logged into ERGON. The core authentication system is functional.</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">👥 Users</h5>
                        <p class="card-text">Manage employees</p>
                        <a href="#" class="btn btn-primary btn-sm">View</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">✅ Tasks</h5>
                        <p class="card-text">Task management</p>
                        <a href="#" class="btn btn-primary btn-sm">View</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">📍 Attendance</h5>
                        <p class="card-text">GPS tracking</p>
                        <a href="#" class="btn btn-primary btn-sm">View</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">📊 Reports</h5>
                        <p class="card-text">Analytics</p>
                        <a href="#" class="btn btn-primary btn-sm">View</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>✅ Working Components:</h6>
                                <ul>
                                    <li>Database connection</li>
                                    <li>User authentication</li>
                                    <li>Session management</li>
                                    <li>Basic routing</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>⚠️ Known Issues:</h6>
                                <ul>
                                    <li>Complex routing system needs fixes</li>
                                    <li>Some asset file warnings</li>
                                    <li>Advanced features need testing</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Quick Links</h5>
                    </div>
                    <div class="card-body">
                        <a href="/ergon/" class="btn btn-outline-primary me-2">Main System</a>
                        <a href="/ergon/emergency.php" class="btn btn-outline-secondary me-2">Emergency Access</a>
                        <a href="/ergon/login" class="btn btn-outline-info">Standard Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>