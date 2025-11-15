<?php
$title = 'Edit User';
$active_page = 'users';
ob_start();
?>

<div class="page-header">
    <h1>Edit User</h1>
    <a href="<?= in_array($_SESSION['role'] ?? '', ['admin', 'owner']) ? '/ergon/admin/management' : '/ergon/users' ?>" class="btn btn--secondary">Back to Users</a>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">User Information</h2>
    </div>
    <div class="card__body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Employee ID</label>
                    <input type="text" name="employee_id" class="form-control" value="<?= htmlspecialchars($user['employee_id'] ?? '') ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">Select Department</option>
                        <?php foreach ($departments ?? [] as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($user['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-control" required>
                        <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <?php if (($_SESSION['role'] ?? '') === 'owner'): ?>
                        <option value="owner" <?= ($user['role'] ?? '') === 'owner' ? 'selected' : '' ?>>Owner</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= ($user['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="suspended" <?= ($user['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="terminated" <?= ($user['status'] ?? '') === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($user['designation'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="<?= htmlspecialchars($user['joining_date'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Salary</label>
                    <input type="number" name="salary" class="form-control" value="<?= htmlspecialchars($user['salary'] ?? '') ?>" step="0.01">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
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
                <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($user['emergency_contact'] ?? '') ?>">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">✨ Update User</button>
                <a href="<?= in_array($_SESSION['role'] ?? '', ['admin', 'owner']) ? '/ergon/admin/management' : '/ergon/users' ?>" class="btn btn--secondary">❌ Cancel</a>
            </div>
        </form>
    </div>
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