<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERGON</title>
    <?php require_once dirname(__DIR__, 3) . '/config/environment.php'; ?>
    <link href="<?= Environment::getBaseUrl() ?>/public/assets/css/ergon.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>🧭 ERGON</h1>
                <p>Employee Tracker & Task Manager</p>
            </div>
            
            <form id="loginForm" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
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
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.location.href = result.redirect;
            } else {
                document.getElementById('message').innerHTML = `<div class="error">${result.error || result.message}</div>`;
            }
        } catch (error) {
            document.getElementById('message').innerHTML = '<div class="error">Login failed. Please try again.</div>';
        }
    });
    </script>
</body>
</html>