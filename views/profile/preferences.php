<?php
$title = 'My Preferences';
$active_page = 'profile';
ob_start();
?>

<div class="page-header">
    <h1>⚙️ My Preferences</h1>
    <a href="/ergon/profile" class="btn btn--secondary">Back to Profile</a>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">Preferences updated successfully!</div>
<?php endif; ?>

<?php if (isset($data['error'])): ?>
<div class="alert alert--error"><?= htmlspecialchars($data['error']) ?></div>
<?php endif; ?>

<div class="preferences-container">
    <form method="POST" class="preferences-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
        <div class="preferences-grid">
            <div class="preference-section">
                <h3>🎨 Appearance</h3>
                <div class="form-group">
                    <label class="form-label">Theme</label>
                    <select name="theme" class="form-control">
                        <option value="light" <?= ($data['preferences']['theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light</option>
                        <option value="dark" <?= ($data['preferences']['theme'] ?? 'light') === 'dark' ? 'selected' : '' ?>>Dark</option>
                        <option value="auto" <?= ($data['preferences']['theme'] ?? 'light') === 'auto' ? 'selected' : '' ?>>Auto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Dashboard Layout</label>
                    <select name="dashboard_layout" class="form-control">
                        <option value="default" <?= ($data['preferences']['dashboard_layout'] ?? 'default') === 'default' ? 'selected' : '' ?>>Default</option>
                        <option value="compact" <?= ($data['preferences']['dashboard_layout'] ?? 'default') === 'compact' ? 'selected' : '' ?>>Compact</option>
                        <option value="expanded" <?= ($data['preferences']['dashboard_layout'] ?? 'default') === 'expanded' ? 'selected' : '' ?>>Expanded</option>
                    </select>
                </div>
            </div>

            <div class="preference-section">
                <h3>🌍 Language & Region</h3>
                <div class="form-group">
                    <label class="form-label">Language</label>
                    <select name="language" class="form-control">
                        <option value="en" <?= ($data['preferences']['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                        <option value="hi" <?= ($data['preferences']['language'] ?? 'en') === 'hi' ? 'selected' : '' ?>>Hindi</option>
                        <option value="es" <?= ($data['preferences']['language'] ?? 'en') === 'es' ? 'selected' : '' ?>>Spanish</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <select name="timezone" class="form-control">
                        <option value="UTC" <?= ($data['preferences']['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                        <option value="Asia/Kolkata" <?= ($data['preferences']['timezone'] ?? 'UTC') === 'Asia/Kolkata' ? 'selected' : '' ?>>India (IST)</option>
                        <option value="America/New_York" <?= ($data['preferences']['timezone'] ?? 'UTC') === 'America/New_York' ? 'selected' : '' ?>>Eastern Time</option>
                        <option value="Europe/London" <?= ($data['preferences']['timezone'] ?? 'UTC') === 'Europe/London' ? 'selected' : '' ?>>London</option>
                    </select>
                </div>
            </div>

            <div class="preference-section">
                <h3>🔔 Notifications</h3>
                <div class="form-group">
                    <label class="checkbox-container">
                        <input type="checkbox" name="notifications_email" <?= ($data['preferences']['notifications_email'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Email Notifications
                    </label>
                    <small class="form-help">Receive notifications via email</small>
                </div>
                <div class="form-group">
                    <label class="checkbox-container">
                        <input type="checkbox" name="notifications_browser" <?= ($data['preferences']['notifications_browser'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Browser Notifications
                    </label>
                    <small class="form-help">Show desktop notifications</small>
                </div>
            </div>

            <div class="preference-section">
                <h3>🔒 Privacy & Security</h3>
                <div class="info-box">
                    <p><strong>Account Security:</strong></p>
                    <ul>
                        <li>Last login: <?= date('M d, Y H:i') ?></li>
                        <li>Password last changed: Recently</li>
                        <li>Two-factor authentication: Not enabled</li>
                    </ul>
                    <a href="/ergon/profile/change-password" class="btn btn--sm btn--secondary">Change Password</a>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Save Preferences</button>
            <a href="/ergon/profile" class="btn btn--secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
.preferences-container { max-width: 1000px; margin: 0 auto; }
.preferences-form { background: white; padding: 32px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.preferences-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px; margin-bottom: 32px; }
.preference-section h3 { color: #333; margin-bottom: 20px; padding-bottom: 8px; border-bottom: 2px solid #f0f0f0; }
.checkbox-container { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.checkbox-container input[type="checkbox"] { margin: 0; }
.form-help { color: #666; font-size: 12px; margin-top: 4px; }
.info-box { background: #f8f9fa; padding: 16px; border-radius: 8px; border-left: 4px solid #2196f3; }
.info-box ul { margin: 8px 0; padding-left: 20px; }
.info-box li { margin-bottom: 4px; color: #666; }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
