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
                <input type="password" name="current_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
                <div class="form-help">Minimum 6 characters</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Change Password</button>
                <a href="/ergon/profile" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
        
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
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
