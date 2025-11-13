<?php
require_once __DIR__ . '/../../app/helpers/Security.php';
$title = 'Edit Department';
$active_page = 'departments';
ob_start();
?>

<div class="compact-header">
    <h1>🏢 Edit Department</h1>
    <div class="header-actions">
        <a href="/ergon/departments" class="btn-back">← Back</a>
    </div>
</div>

<div class="compact-form">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
        
        <div class="form-section">
            <div class="form-grid">
                <div class="form-group span-2">
                    <label for="name">🏢 Department Name *</label>
                    <input type="text" name="name" id="name" value="<?= htmlspecialchars($data['department']['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="status">🟢 Status</label>
                    <select name="status" id="status">
                        <option value="active" <?= $data['department']['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $data['department']['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">📝 Description</label>
                <textarea name="description" id="description" rows="4"><?= htmlspecialchars($data['department']['description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="head_id">👥 Department Head</label>
                <select name="head_id" id="head_id">
                    <option value="">Select Department Head</option>
                    <?php foreach ($data['users'] as $user): ?>
                    <option value="<?= $user['id'] ?>" <?= $data['department']['head_id'] == $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                ✨ Update Department
            </button>
            <a href="/ergon/departments" class="btn-secondary">❌ Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
