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
.password-card { background: white; padding: 32px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
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
    display: flex;
    align-items: center;
}

.password-input-wrapper input {
    padding-right: 45px;
}

.password-toggle {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
    font-size: 16px;
    padding: 5px;
}

.password-toggle:hover {
    color: #333;
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
</style>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
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
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Password changed successfully!', 'success');
                form.reset();
                setTimeout(() => {
                    window.location.href = '/ergon/profile';
                }, 2000);
            } else {
                showMessage(data.message || 'Failed to change password', 'error');
            }
        })
        .catch(error => {
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
