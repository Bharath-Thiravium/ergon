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
        .password-requirements { font-size: 12px; margin-top: 5px; }
        .requirement { color: #c33; margin: 2px 0; }
        .requirement.valid { color: #3c3; }
        .password-strength { margin-top: 5px; height: 4px; background: #eee; border-radius: 2px; }
        .strength-bar { height: 100%; border-radius: 2px; transition: all 0.3s; }
        .strength-weak { width: 25%; background: #ff4444; }
        .strength-fair { width: 50%; background: #ffaa00; }
        .strength-good { width: 75%; background: #00aa00; }
        .strength-strong { width: 100%; background: #008800; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>🔒 Reset Password</h1>
            <p>Create a strong password for your account</p>
        </div>
        
        <?php if (isset($data['error'])): ?>
        <div class="error"><?= htmlspecialchars($data['error']) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required>
                <div class="password-requirements">
                    <div id="length" class="requirement">✗ At least 8 characters</div>
                    <div id="uppercase" class="requirement">✗ One uppercase letter</div>
                    <div id="lowercase" class="requirement">✗ One lowercase letter</div>
                    <div id="number" class="requirement">✗ One number</div>
                    <div id="special" class="requirement">✗ One special character</div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            
            <div class="password-strength">
                <div class="strength-bar" id="strengthBar"></div>
            </div>
            
            <button type="submit" class="btn" id="submitBtn" disabled>Update Password</button>
        </form>
    </div>
    
    <script>
    const passwordInput = document.getElementById('new_password');
    const confirmInput = document.querySelector('input[name="confirm_password"]');
    const submitBtn = document.getElementById('submitBtn');
    const strengthBar = document.getElementById('strengthBar');
    
    const requirements = {
        length: { regex: /.{8,}/, element: document.getElementById('length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('number') },
        special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('special') }
    };
    
    function validatePassword() {
        const password = passwordInput.value;
        let validCount = 0;
        
        Object.keys(requirements).forEach(key => {
            const req = requirements[key];
            const isValid = req.regex.test(password);
            
            if (isValid) {
                req.element.classList.add('valid');
                req.element.innerHTML = req.element.innerHTML.replace('✗', '✓');
                validCount++;
            } else {
                req.element.classList.remove('valid');
                req.element.innerHTML = req.element.innerHTML.replace('✓', '✗');
            }
        });
        
        // Update strength bar
        strengthBar.className = 'strength-bar';
        if (validCount >= 5) strengthBar.classList.add('strength-strong');
        else if (validCount >= 4) strengthBar.classList.add('strength-good');
        else if (validCount >= 2) strengthBar.classList.add('strength-fair');
        else if (validCount >= 1) strengthBar.classList.add('strength-weak');
        
        checkFormValidity();
    }
    
    function checkFormValidity() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        const allValid = Object.keys(requirements).every(key => requirements[key].regex.test(password));
        const passwordsMatch = password === confirm && password.length > 0;
        
        submitBtn.disabled = !(allValid && passwordsMatch);
    }
    
    passwordInput.addEventListener('input', validatePassword);
    confirmInput.addEventListener('input', checkFormValidity);
    </script>
</body>
</html>
