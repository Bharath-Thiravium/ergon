<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERGON</title>
    <link href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/ergon/public/assets/css/ergon.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>🧭 ERGON</h1>
                <p>Employee Tracker & Task Manager</p>
            </div>
            
            <form id="loginForm" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= Security::escape($_SESSION['csrf_token'] ?? '') ?>">
                
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                
                <button type="submit" class="btn btn--primary">Sign In</button>
            </form>
            
            <div id="message" class="message"></div>
        </div>
    </div>

    <script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch(window.location.origin + '/ergon/login', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('message').innerHTML = '<div style="color: green;">Login successful! Redirecting...</div>';
                setTimeout(() => {
                    window.location.href = result.redirect || '/ergon/dashboard';
                }, 500);
            } else {
                document.getElementById('message').innerHTML = `<div style="color: red;">${result.error || result.message}</div>`;
            }
        } catch (error) {
            console.error('Login error:', error);
            document.getElementById('message').innerHTML = '<div style="color: red;">Connection failed. Please try again.</div>';
        }
    });
    </script>
</body>
</html>