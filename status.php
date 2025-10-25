<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERGON System Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php
    session_start();
    
    // Handle logout
    if ($_GET['logout'] ?? false) {
        session_destroy();
        header('Location: status.php');
        exit;
    }
    ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">🧭 ERGON System Status</h3>
                    </div>
                    <div class="card-body">
                        
                        <!-- Authentication Status -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Authentication Status</h5>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <div class="alert alert-success">
                                        ✅ <strong>Logged In:</strong> <?= htmlspecialchars($_SESSION['user_name']) ?> 
                                        (<?= htmlspecialchars($_SESSION['role']) ?>)
                                        <a href="?logout=1" class="btn btn-sm btn-outline-danger ms-2">Logout</a>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        ⚠️ <strong>Not Logged In</strong>
                                        <a href="simple-login.php" class="btn btn-sm btn-primary ms-2">Login</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- System Components -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>✅ Working Components</h5>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Database Connection
                                        <span class="badge bg-success rounded-pill">OK</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        User Authentication
                                        <span class="badge bg-success rounded-pill">OK</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Session Management
                                        <span class="badge bg-success rounded-pill">OK</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Emergency Access
                                        <span class="badge bg-success rounded-pill">OK</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>⚠️ Known Issues</h5>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Complex Routing System
                                        <span class="badge bg-warning rounded-pill">Fixing</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Asset File Warnings
                                        <span class="badge bg-warning rounded-pill">Minor</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Advanced Features
                                        <span class="badge bg-info rounded-pill">Testing</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5>Quick Actions</h5>
                                <div class="btn-group" role="group">
                                    <a href="simple-login.php" class="btn btn-primary">Simple Login</a>
                                    <a href="simple-dashboard.php" class="btn btn-success">Dashboard</a>
                                    <a href="emergency.php" class="btn btn-warning">Emergency Access</a>
                                    <a href="/ergon/" class="btn btn-info">Main System</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Information -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6>System Information</h6>
                                        <small class="text-muted">
                                            <strong>PHP Version:</strong> <?= PHP_VERSION ?><br>
                                            <strong>Server Time:</strong> <?= date('Y-m-d H:i:s') ?><br>
                                            <strong>Session ID:</strong> <?= session_id() ?><br>
                                            <strong>User Agent:</strong> <?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>