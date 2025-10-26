<?php
$title = 'Edit User';
$active_page = 'users';

ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>✏️</span> Edit User</h1>
        <p>Update user information and settings</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/users" class="btn btn--secondary">
            <span>←</span> Back to Users
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">User Information</h2>
    </div>
    <div class="card__body">
        <form method="POST" enctype="multipart/form-data" class="form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-input" 
                           value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-input" 
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="role" class="form-label">Role *</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <?php if (($_SESSION['role'] ?? '') === 'owner'): ?>
                        <option value="owner" <?= ($user['role'] ?? '') === 'owner' ? 'selected' : '' ?>>Owner</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label">Status *</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="active" <?= ($user['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="designation" class="form-label">Designation</label>
                    <input type="text" id="designation" name="designation" class="form-input" 
                           value="<?= htmlspecialchars($user['designation'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="joining_date" class="form-label">Joining Date</label>
                    <input type="date" id="joining_date" name="joining_date" class="form-input" 
                           value="<?= htmlspecialchars($user['joining_date'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="salary" class="form-label">Salary</label>
                    <input type="number" id="salary" name="salary" class="form-input" 
                           value="<?= htmlspecialchars($user['salary'] ?? '') ?>" step="0.01">
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-input" 
                           value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="gender" class="form-label">Gender</label>
                    <select id="gender" name="gender" class="form-select">
                        <option value="">Select Gender</option>
                        <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group form-group--full">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" name="address" class="form-textarea" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group form-group--full">
                    <label for="emergency_contact" class="form-label">Emergency Contact</label>
                    <input type="text" id="emergency_contact" name="emergency_contact" class="form-input" 
                           value="<?= htmlspecialchars($user['emergency_contact'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <span>💾</span> Update User
                </button>
                <a href="/ergon/users" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>