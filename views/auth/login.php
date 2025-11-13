<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ergon</title>
    <link rel="stylesheet" href="/ergon/assets/css/ergon.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1><i class="fas fa-users-cog"></i> ergon</h1>
                    <p>Employee Tracker & Task Manager</p>
                </div>
                
                <form id="loginForm" class="auth-form">
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div style="position: relative;">
                            <input type="password" class="form-control" id="password" name="password" required style="padding-right: 45px;">
                            <button type="button" id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b;">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn--primary" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                    
                    <div style="text-align: center;">
                        <a href="/ergon/auth/forgot-password" class="btn btn--secondary" style="width: 100%;">
                            <i class="fas fa-key"></i> Forgot Password?
                        </a>
                    </div>
                </form>
                
                <div id="message"></div>
            </div>
        </div>
    </div>

    <script>
    // Password visibility toggle
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordField = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            eyeIcon.className = 'fas fa-eye-slash';
        } else {
            passwordField.type = 'password';
            eyeIcon.className = 'fas fa-eye';
        }
    });
    
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        submitBtn.disabled = true;
        
        const formData = new FormData(this);
        const messageDiv = document.getElementById('message');
        
        fetch('/ergon/login', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.innerHTML = '<div class="alert alert--success"><i class="fas fa-check-circle"></i> Login successful! Redirecting...</div>';
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            } else {
                messageDiv.innerHTML = '<div class="alert alert--error"><i class="fas fa-exclamation-triangle"></i> ' + data.error + '</div>';
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            messageDiv.innerHTML = '<div class="alert alert--error"><i class="fas fa-exclamation-triangle"></i> Login failed. Please try again.</div>';
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    </script>
</body>
</html>
