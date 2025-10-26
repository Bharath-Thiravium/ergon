<?php
$title = 'Create Department';
$active_page = 'departments';
ob_start();
?>

<div class="page-header">
    <h1>Create New Department</h1>
    <a href="/Ergon/departments" class="btn btn--secondary">Back to Departments</a>
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
                <input type="text" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department Head</label>
                    <select name="head_id" class="form-control">
                        <option value="">Select Department Head</option>
                        <?php foreach ($data['users'] as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Create Department</button>
                <a href="/Ergon/departments" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
