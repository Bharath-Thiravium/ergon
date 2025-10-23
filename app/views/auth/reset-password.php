<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ERGON</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .reset-container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .reset-header { text-align: center; margin-bottom: 30px; }
        .reset-header h1 { color: #333; margin-bottom: 10px; }
        .reset-header p { color: #666; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e1e5e9; border-radius: 6px; font-size: 16px; transition: border-color 0.3s; }
        .form-control:focus { outline: none; border-color: #667eea; }
        .btn { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #5a6fd8; }
        .error { background: #fee; color: #c33; padding: 10px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #fcc; }
        .password-requirements { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>🔒 Reset Password</h1>
            <p>Please set a new password for your account</p>
        </div>
        
        <?php if (isset($data['error'])): ?>
        <div class="error"><?= htmlspecialchars($data['error']) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
                <div class="password-requirements">Minimum 6 characters</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn">Update Password</button>
        </form>
    </div>
</body>
</html>