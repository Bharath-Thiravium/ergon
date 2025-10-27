<?php
$title = 'Create User';
$active_page = 'users';
ob_start();
?>

<div class="page-header">
    <h1>Create New User</h1>
    <a href="/ergon/users" class="btn btn--secondary">Back to Users</a>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">User Information</h2>
    </div>
    <div class="card__body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($old_data['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($old_data['email'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($old_data['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="departments[]" class="form-control" multiple>
                        <?php 
                        $selectedDepts = isset($old_data['departments']) ? (is_array($old_data['departments']) ? $old_data['departments'] : explode(',', $old_data['departments'])) : [];
                        foreach ($departments as $dept): 
                        ?>
                        <option value="<?= htmlspecialchars($dept['name']) ?>" 
                                <?= in_array($dept['name'], $selectedDepts) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text">Hold Ctrl/Cmd to select multiple departments</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-control" required>
                        <option value="user" <?= ($old_data['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= ($old_data['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <?php if (($_SESSION['role'] ?? '') === 'owner'): ?>
                        <option value="owner" <?= ($old_data['role'] ?? '') === 'owner' ? 'selected' : '' ?>>Owner</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= ($old_data['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($old_data['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($old_data['designation'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="<?= htmlspecialchars($old_data['joining_date'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Salary</label>
                    <input type="number" name="salary" class="form-control" value="<?= htmlspecialchars($old_data['salary'] ?? '') ?>" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($old_data['date_of_birth'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-control">
                    <option value="">Select Gender</option>
                    <option value="male" <?= ($old_data['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= ($old_data['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                    <option value="other" <?= ($old_data['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($old_data['address'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Emergency Contact</label>
                <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($old_data['emergency_contact'] ?? '') ?>">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Create User</button>
                <a href="/ergon/users" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
