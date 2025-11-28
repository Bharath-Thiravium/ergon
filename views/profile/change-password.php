<?php
$title = 'Change Password';
$active_page = 'profile';
ob_start();
?>

<div class="page-header">
    <h1>🔒 Change Password</h1>
    <a href="/ergon/profile" class="btn btn--secondary">Back to Profile</a>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">Password changed successfully!</div>
<?php endif; ?>

<?php if (isset($data['error'])): ?>
<div class="alert alert--error"><?= htmlspecialchars($data['error']) ?></div>
<?php endif; ?>

<div class="password-change-container">
    <div class="password-card">
        <div class="card-header">
            <h2>Update Your Password</h2>
            <p>Choose a strong password to keep your account secure</p>
        </div>
        
        <form method="POST" class="password-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <div class="password-input-wrapper">
                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                        <i class="bi bi-eye" id="current_password_icon"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">New Password</label>
                <div class="password-input-wrapper">
                    <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                        <i class="bi bi-eye" id="new_password_icon"></i>
                    </button>
                </div>
                <div class="form-help">Minimum 6 characters</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <div class="password-input-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                        <i class="bi bi-eye" id="confirm_password_icon"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary" id="submitBtn">Change Password</button>
                <a href="/ergon/profile" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
        
        <div id="messageContainer" style="display: none; margin-top: 1rem;"></div>
        
        <div class="password-tips">
            <h4>Password Tips:</h4>
            <ul>
                <li>Use at least 6 characters</li>
                <li>Include uppercase and lowercase letters</li>
                <li>Add numbers and special characters</li>
                <li>Avoid common words or personal information</li>
            </ul>
        </div>
    </div>
</div>

<style>
.password-change-container { max-width: 500px; margin: 0 auto; }
.password-card { 
    background: white; 
    padding: 32px; 
    border-radius: 12px; 
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
.card-header { text-align: center; margin-bottom: 32px; }
.card-header h2 { color: #333; margin-bottom: 8px; }
.card-header p { color: #666; }
.form-help { font-size: 12px; color: #666; margin-top: 4px; }
.password-tips { margin-top: 24px; padding-top: 24px; border-top: 1px solid #e0e0e0; }
.password-tips h4 { color: #333; margin-bottom: 12px; }
.password-tips ul { margin: 0; padding-left: 20px; }
.password-tips li { color: #666; margin-bottom: 4px; }

.password-input-wrapper {
    position: relative;
    display: block;
}

.password-input-wrapper input {
    padding-right: 50px !important;
    width: 100%;
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent !important;
    border: none !important;
    cursor: pointer;
    color: #666 !important;
    font-size: 16px;
    padding: 6px;
    z-index: 100;
    display: flex !important;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.password-toggle:hover {
    color: #333 !important;
    background-color: rgba(0,0,0,0.05) !important;
}

.password-toggle:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* Fallback for Bootstrap Icons */
.password-toggle::before {
    content: '👁️';
    font-size: 16px;
    display: block;
}

.password-toggle.show-password::before {
    content: '🙈';
}

/* Hide fallback when Bootstrap Icons load */
.password-toggle i {
    pointer-events: none;
    font-size: 16px;
}

.password-toggle:not(:empty) i + *::before,
.password-toggle i:not(:empty) ~ *::before {
    display: none;
}

/* Dark Theme Support */
[data-theme="dark"] .password-card {
    background: #1f2937 !important;
    border: 1px solid #374151;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}

[data-theme="dark"] .card-header h2 {
    color: #f9fafb !important;
}

[data-theme="dark"] .card-header p {
    color: #d1d5db !important;
}

[data-theme="dark"] .form-label {
    color: #f9fafb !important;
}

[data-theme="dark"] .form-help {
    color: #9ca3af !important;
}

[data-theme="dark"] .password-tips {
    border-top-color: #374151 !important;
}

[data-theme="dark"] .password-tips h4 {
    color: #f9fafb !important;
}

[data-theme="dark"] .password-tips li {
    color: #d1d5db !important;
}

[data-theme="dark"] .password-toggle {
    color: #9ca3af !important;
}

[data-theme="dark"] .password-toggle:hover {
    color: #f9fafb !important;
    background-color: rgba(255,255,255,0.1) !important;
}

[data-theme="dark"] .form-control {
    background-color: #374151 !important;
    border-color: #4b5563 !important;
    color: #f9fafb !important;
}

[data-theme="dark"] .form-control:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 14px;
}

.alert--success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert--error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

[data-theme="dark"] .alert--success {
    background-color: #064e3b;
    border-color: #065f46;
    color: #a7f3d0;
}

[data-theme="dark"] .alert--error {
    background-color: #7f1d1d;
    border-color: #991b1b;
    color: #fca5a5;
}
</style>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.parentElement.querySelector('.password-toggle');
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        button.classList.add('show-password');
        if (icon) {
            icon.className = 'bi bi-eye-slash';
        }
    } else {
        field.type = 'password';
        button.classList.remove('show-password');
        if (icon) {
            icon.className = 'bi bi-eye';
        }
    }
}

function showMessage(message, type) {
    const container = document.getElementById('messageContainer');
    container.innerHTML = `<div class="alert alert--${type}">${message}</div>`;
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.password-form');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const newPassword = formData.get('new_password');
        const confirmPassword = formData.get('confirm_password');
        
        if (newPassword !== confirmPassword) {
            showMessage('New passwords do not match!', 'error');
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Changing Password...';
        
        fetch('/ergon/profile/change-password', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showMessage(data.message || 'Password changed successfully!', 'success');
                form.reset();
                setTimeout(() => {
                    window.location.href = '/ergon/profile';
                }, 2000);
            } else {
                showMessage(data.message || 'Failed to change password', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Change Password';
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
