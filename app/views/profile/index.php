<?php
$title = 'My Profile';
$active_page = 'profile';
ob_start();
?>



<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">Profile updated successfully!</div>
<?php endif; ?>

<div class="profile-container">
    <div class="profile-card">
        <div class="profile-avatar-section">
            <div class="profile-avatar-xl"><?= strtoupper(substr($data['user']['name'] ?? 'U', 0, 1)) ?></div>
            <h2><?= htmlspecialchars($data['user']['name'] ?? 'User') ?></h2>
            <p class="profile-role"><?= ucfirst($data['user']['role'] ?? 'User') ?></p>
        </div>
        
        <form method="POST" class="profile-form">
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($data['user']['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['user']['email'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($data['user']['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Employee ID</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['user']['employee_id'] ?? 'N/A') ?>" readonly>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Update Profile</button>
                <a href="/ergon/profile/change-password" class="btn btn--secondary">Change Password</a>
            </div>
        </form>
    </div>
    
    <div class="profile-stats">
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-value"><?= date('M d, Y', strtotime($data['user']['created_at'] ?? 'now')) ?></div>
            <div class="stat-label">Joined Date</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🕐</div>
            <div class="stat-value"><?= $data['user']['last_login'] ? date('M d, H:i', strtotime($data['user']['last_login'])) : 'Never' ?></div>
            <div class="stat-label">Last Login</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-value"><?= ucfirst($data['user']['status'] ?? 'Active') ?></div>
            <div class="stat-label">Status</div>
        </div>
    </div>
</div>

<style>
.profile-container { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; max-width: 1200px; margin: 0 auto; }
.profile-card { background: white; padding: 32px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.profile-avatar-section { text-align: center; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid #e0e0e0; }
.profile-avatar-xl { width: 80px; height: 80px; border-radius: 50%; background: #2196f3; color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; margin: 0 auto 16px; }
.profile-role { color: #666; margin-top: 4px; }
.form-section h3 { margin-bottom: 20px; color: #333; }
.profile-stats { display: flex; flex-direction: column; gap: 16px; }
.stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
.stat-icon { font-size: 24px; margin-bottom: 8px; }
.stat-value { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 4px; }
.stat-label { font-size: 12px; color: #666; text-transform: uppercase; }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>