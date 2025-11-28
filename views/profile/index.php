<?php
$title = 'Profile Settings';
$active_page = 'profile';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👤</span> Profile Settings</h1>
        <p>Manage your account information and security settings</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/dashboard" class="btn btn--secondary">
            <span>←</span> Back to Dashboard
        </a>
    </div>
</div>

<div class="profile-grid">
    <div class="profile-main">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">
                    <span>📝</span> Profile Information
                </h2>
            </div>
            <div class="card__body">
                <form id="profileForm" class="form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+1 (555) 123-4567">
                        </div>
                        <div class="form-group">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department" value="<?= htmlspecialchars($user['department'] ?? 'General') ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">
                            <span>💾</span> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="profile-sidebar">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">
                    <span>🔒</span> Change Password
                </h2>
            </div>
            <div class="card__body">
                <form id="passwordForm" class="form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <div class="password-input-wrapper">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                👁️
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password *</label>
                        <div class="password-input-wrapper">
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                👁️
                            </button>
                        </div>
                        <small class="form-text">Minimum 8 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <div class="password-input-wrapper">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                👁️
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn--warning">
                            <span>🔑</span> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">
                    <span>📊</span> Account Stats
                </h2>
            </div>
            <div class="card__body">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?= ucfirst($user['role'] ?? 'User') ?></div>
                        <div class="stat-label">Role</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= ucfirst($user['status'] ?? 'Active') ?></div>
                        <div class="stat-label">Status</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= date('M Y', strtotime($user['created_at'] ?? 'now')) ?></div>
                        <div class="stat-label">Member Since</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-top: 24px;
}

.profile-main {
    display: flex;
    flex-direction: column;
}

.profile-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}

.stat-item {
    text-align: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.stat-value {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-text {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}

.password-input-wrapper {
    position: relative;
    display: block;
}

.password-input-wrapper input {
    padding-right: 50px !important;
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent !important;
    border: none !important;
    cursor: pointer;
    font-size: 16px;
    padding: 6px;
    z-index: 100;
    width: 30px;
    height: 30px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.password-toggle:hover {
    background-color: rgba(0,0,0,0.05) !important;
}

@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<script>
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>⏳</span> Updating...';
    
    fetch('/ergon/profile', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Profile updated successfully!');
        } else {
            alert('❌ Error: ' + (data.error || 'Failed to update profile'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Network error occurred');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        alert('❌ Passwords do not match!');
        return;
    }
    
    if (formData.get('new_password').length < 8) {
        alert('❌ Password must be at least 8 characters long!');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>⏳</span> Changing...';
    
    fetch('/ergon/profile/change-password', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Password changed successfully!');
            this.reset();
        } else {
            alert('❌ Error: ' + (data.message || data.error || 'Failed to change password'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Network error occurred');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.parentElement.querySelector('.password-toggle');
    
    if (field.type === 'password') {
        field.type = 'text';
        button.innerHTML = '🙈';
    } else {
        field.type = 'password';
        button.innerHTML = '👁️';
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
