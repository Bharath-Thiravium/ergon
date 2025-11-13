<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ergon</title>
    <link rel="stylesheet" href="/ergon/assets/css/ergon.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1><i class="fas fa-key"></i> Forgot Password</h1>
                    <p>Enter your email to receive reset instructions</p>
                </div>
                
                <form id="forgotForm" class="auth-form">
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <button type="submit" class="btn btn--primary" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>
                    
                    <div style="text-align: center;">
                        <a href="/ergon/login" class="btn btn--secondary" style="width: 100%;">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                    </div>
                </form>
                
                <div id="message"></div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('forgotForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        submitBtn.disabled = true;
        
        const formData = new FormData(this);
        const messageDiv = document.getElementById('message');
        
        fetch('/ergon/auth/forgot-password', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.innerHTML = '<div class="alert alert--success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
                this.reset();
            } else {
                messageDiv.innerHTML = '<div class="alert alert--error"><i class="fas fa-exclamation-triangle"></i> ' + data.error + '</div>';
            }
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        })
        .catch(error => {
            messageDiv.innerHTML = '<div class="alert alert--error"><i class="fas fa-exclamation-triangle"></i> Failed to send reset email. Please try again.</div>';
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    </script>
</body>
</html>