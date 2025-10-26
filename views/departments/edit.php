<?php
$title = 'Edit Department';
$active_page = 'departments';
ob_start();
?>

<div class="page-header">
    <h1>Edit Department</h1>
    <a href="/ergon/departments" class="btn btn--secondary">Back to Departments</a>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Department Details</h2>
    </div>
    <div class="card__body">
        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            <div class="form-group">
                <label class="form-label">Department Name *</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($data['department']['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($data['department']['description']) ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department Head</label>
                    <select name="head_id" class="form-control">
                        <option value="">Select Department Head</option>
                        <?php foreach ($data['users'] as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= $data['department']['head_id'] == $user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= $data['department']['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $data['department']['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Update Department</button>
                <a href="/ergon/departments" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
