<?php
$title = 'Edit Employee';
$active_page = 'users';
ob_start();

// Check if user data exists
if (!$data['user']) {
    $content = '<div class="alert alert--danger">User not found.</div>';
    include __DIR__ . '/../layouts/dashboard.php';
    exit;
}

$user = $data['user'];
?>

<div class="page-header">
    <h1>Edit Employee</h1>
    <a href="/ergon/users" class="btn btn--secondary">Back to Users</a>
</div>

<form method="POST" enctype="multipart/form-data" class="user-form">
    <div class="form-sections">
        <!-- Personal Information -->
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Personal Information</h2>
            </div>
            <div class="card__body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Employee ID *</label>
                        <input type="text" name="employee_id" class="form-control" id="employeeId" value="<?= htmlspecialchars($user['employee_id'] ?? '') ?>" required>
                        <?php if (empty($user['employee_id'])): ?>
                        <button type="button" class="btn btn--sm btn--secondary" onclick="generateEmployeeId()">Generate ID</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?= $user['date_of_birth'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Emergency Contact</label>
                    <input type="tel" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($user['emergency_contact'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Employment Details -->
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Employment Details</h2>
            </div>
            <div class="card__body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Designation *</label>
                        <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($user['designation'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Joining Date *</label>
                        <input type="date" name="joining_date" class="form-control" value="<?= $user['joining_date'] ?? '' ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Salary (₹)</label>
                        <input type="number" name="salary" class="form-control" step="0.01" value="<?= $user['salary'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>Employee</option>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
                            <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="active" <?= ($user['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Departments *</label>
                    <div class="checkbox-group">
                        <?php foreach ($data['departments'] ?? [] as $dept): ?>
                        <label class="checkbox-item">
                            <input type="checkbox" name="departments[]" value="<?= $dept['id'] ?>" 
                                   <?= in_array($dept['id'], $data['user_departments'] ?? []) ? 'checked' : '' ?>>
                            <span class="checkbox-label"><?= htmlspecialchars($dept['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Update Employee</button>
        <button type="button" class="btn btn--warning" onclick="resetPassword()">Reset Password</button>
        <a href="/ergon/users" class="btn btn--secondary">Cancel</a>
    </div>
</form>

<script>
function generateEmployeeId() {
    fetch('/ergon/api/generate-employee-id')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('employeeId').value = data.employee_id;
            }
        });
}

function resetPassword() {
    if (confirm('Reset password for this employee?')) {
        const userId = <?= json_encode($user['id'] ?? 0) ?>;
        fetch('/ergon/users/reset-password', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_id: userId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Password reset! New password: ' + data.temp_password);
                // Download credentials
                downloadCredentials(userId, data.temp_password);
            } else {
                alert('Error: ' + data.error);
            }
        });
    }
}

function downloadCredentials(userId, password) {
    const user = <?= json_encode($user) ?>;
    const content = `Employee Login Credentials\n` +
                   `================================\n\n` +
                   `Employee ID: ${user.employee_id || 'N/A'}\n` +
                   `Name: ${user.name}\n` +
                   `Email: ${user.email}\n` +
                   `New Password: ${password}\n\n` +
                   `Login URL: ${window.location.origin}/ergon/login\n` +
                   `Generated: ${new Date().toLocaleString()}`;
    
    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `credentials_${user.employee_id || user.id}.txt`;
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>