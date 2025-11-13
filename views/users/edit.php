<?php
$title = 'Edit User';
$active_page = 'users';
ob_start();
?>

<div class="compact-header">
    <h1>👥 Edit User</h1>
    <div class="header-actions">
        <a href="/ergon/users" class="btn-back">← Back</a>
    </div>
</div>

<div class="compact-form">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-section">
            <h3>👤 Basic Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="employee_id">🆔 Employee ID</label>
                    <input type="text" name="employee_id" id="employee_id" value="<?= htmlspecialchars($user['employee_id'] ?? '') ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="name">📝 Full Name *</label>
                    <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">📧 Email Address *</label>
                    <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="phone">📱 Phone Number</label>
                    <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="role">🔑 Role *</label>
                    <select name="role" id="role" required>
                        <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <?php if (($_SESSION['role'] ?? '') === 'owner'): ?>
                        <option value="owner" <?= ($user['role'] ?? '') === 'owner' ? 'selected' : '' ?>>Owner</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">🟢 Status *</label>
                    <select name="status" id="status" required>
                        <option value="active" <?= ($user['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3>🏢 Work Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="department_id">🏢 Department</label>
                    <select name="department_id" id="department_id">
                        <option value="">Select Department</option>
                        <?php foreach ($departments ?? [] as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($user['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="designation">💼 Designation</label>
                    <input type="text" name="designation" id="designation" value="<?= htmlspecialchars($user['designation'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="joining_date">📅 Joining Date</label>
                    <input type="date" name="joining_date" id="joining_date" value="<?= htmlspecialchars($user['joining_date'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="salary">💰 Salary</label>
                    <input type="number" name="salary" id="salary" value="<?= htmlspecialchars($user['salary'] ?? '') ?>" step="0.01">
                </div>
                <div class="form-group">
                    <label for="date_of_birth">🎂 Date of Birth</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="gender">🚪 Gender</label>
                    <select name="gender" id="gender">
                        <option value="">Select Gender</option>
                        <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3>📍 Contact Information</h3>
            <div class="form-group">
                <label for="address">🏠 Address</label>
                <textarea name="address" id="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="emergency_contact">🆘 Emergency Contact</label>
                <input type="text" name="emergency_contact" id="emergency_contact" value="<?= htmlspecialchars($user['emergency_contact'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                ✨ Update User
            </button>
            <a href="/ergon/users" class="btn-secondary">❌ Cancel</a>
        </div>
    </form>
</div>



<script>
function generateEmployeeId() {
    fetch('/ergon/api/generate-employee-id')
    .then(response => response.json())
    .then(data => {
        if (data.employee_id) {
            document.querySelector('input[name="employee_id"]').value = data.employee_id;
        }
    })
    .catch(error => console.error('Error:', error));
}

function deleteDocument(filename) {
    if (confirm('Are you sure you want to delete this document?')) {
        fetch('/ergon/users/delete-document/<?= $user['id'] ?>/' + filename, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete document');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete document');
        });
    }
}
</script>



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>