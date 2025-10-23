<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ERGON</title>
    <link href="/ergon/public/assets/css/ergon.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>🔐 Reset Password</h1>
                <p>Please set a new password for your account</p>
            </div>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                    <small class="form-help">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn--primary btn--full">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>