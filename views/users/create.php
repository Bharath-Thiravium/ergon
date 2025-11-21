<?php
$title = 'Create User';
$active_page = 'users';
ob_start();
?>

<div class="page-header">
    <h1>Create New User</h1>
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
                    <select name="department_id" class="form-control">
                        <option value="">Select Department</option>
                        <?php foreach ($departments ?? [] as $dept): ?>
                        <option value="<?= $dept['id'] ?>" 
                                <?= ($old_data['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
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
                        <option value="suspended" <?= ($old_data['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="terminated" <?= ($old_data['status'] ?? '') === 'terminated' ? 'selected' : '' ?>>Terminated</option>
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
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($old_data['date_of_birth'] ?? '') ?>">
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
            
            <div class="form-group">
                <label class="form-label">Documents</label>
                <div class="document-upload">
                    <div class="document-category">
                        <label>Passport Size Photo</label>
                        <input type="file" name="passport_photo" class="form-control" accept=".jpg,.jpeg,.png">
                    </div>
                    <div class="document-category">
                        <label>Aadhar Card</label>
                        <input type="file" name="aadhar" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="document-category">
                        <label>PAN Card</label>
                        <input type="file" name="pan" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="document-category">
                        <label>Resume</label>
                        <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
                    </div>
                    <div class="document-category">
                        <label>Education Documents</label>
                        <input type="file" name="education_docs[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="document-category">
                        <label>Experience Certificates</label>
                        <input type="file" name="experience_certs[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <small class="form-text">Max 5MB per file. JPG/PNG for photos, PDF/DOC for documents.</small>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">✨ Create User</button>
                <a href="<?= in_array($_SESSION['role'] ?? '', ['admin', 'owner']) ? '/ergon/admin/management' : '/ergon/users' ?>" class="btn btn--secondary">❌ Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.document-upload {
    border: 1px solid #ddd;
    padding: 1rem;
    border-radius: 4px;
    background: #f9f9f9;
}
.document-category {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: white;
    border-radius: 4px;
    border: 1px solid #eee;
}
.document-category label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    display: block;
}
</style>

<script>
// calculate max selectable date = today - 17 years
(function(){
    const el = document.getElementById('date_of_birth');
    if (!el) return;
    const d = new Date();
    d.setFullYear(d.getFullYear() - 17);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const maxDate = `${yyyy}-${mm}-${dd}`;
    el.max = maxDate;

    // Optional: if current value is after max, clear it
    if (el.value && el.value > maxDate) {
        el.value = '';
    }
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
