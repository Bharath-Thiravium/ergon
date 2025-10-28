<?php
$title = 'Edit User';
$active_page = 'users';
ob_start();
?>

<div class="page-header">
    <h1>Edit User</h1>
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
                    <label class="form-label">Employee ID</label>
                    <input type="text" name="employee_id" class="form-control" 
                           value="<?= htmlspecialchars($user['employee_id'] ?? '') ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-row">
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
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="active" <?= ($user['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="departments[]" class="form-control" multiple>
                        <?php 
                        require_once __DIR__ . '/../../app/models/Department.php';
                        $departmentModel = new Department();
                        $departments = $departmentModel->getAll();
                        $userDepts = isset($user['department']) ? explode(',', $user['department']) : [];
                        foreach ($departments as $dept): 
                        ?>
                        <option value="<?= htmlspecialchars($dept['name']) ?>" 
                                <?= in_array($dept['name'], $userDepts) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text">Hold Ctrl/Cmd to select multiple departments</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control" 
                           value="<?= htmlspecialchars($user['designation'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" 
                           value="<?= htmlspecialchars($user['joining_date'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Salary</label>
                    <input type="number" name="salary" class="form-control" 
                           value="<?= htmlspecialchars($user['salary'] ?? '') ?>" step="0.01">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" 
                           value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
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
                <input type="text" name="emergency_contact" class="form-control" 
                       value="<?= htmlspecialchars($user['emergency_contact'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Documents</label>
                <div class="document-upload">
                    <?php
                    $documentsPath = __DIR__ . '/../../public/uploads/users/' . ($user['id'] ?? 0);
                    $docTypes = ['passport_photo', 'aadhar', 'pan', 'resume', 'education_docs', 'experience_certs'];
                    $docLabels = ['Passport Photo', 'Aadhar Card', 'PAN Card', 'Resume', 'Education Documents', 'Experience Certificates'];
                    
                    foreach ($docTypes as $index => $docType) {
                        echo '<div class="document-category">';
                        echo '<label>' . $docLabels[$index] . '</label>';
                        
                        // Show existing files for this category
                        if (is_dir($documentsPath)) {
                            $pattern = $documentsPath . '/' . $docType . '_*';
                            $files = glob($pattern);
                            if (!empty($files)) {
                                echo '<div class="existing-files">';
                                foreach ($files as $file) {
                                    $filename = basename($file);
                                    $filePath = '/ergon/users/download-document/' . $user['id'] . '/' . $filename;
                                    echo '<div class="document-item">';
                                    echo '<a href="' . $filePath . '" target="_blank">' . htmlspecialchars($filename) . '</a>';
                                    echo '<button type="button" onclick="deleteDocument(\'' . $filename . '\')" class="btn btn--sm btn--danger">×</button>';
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                        }
                        
                        // Upload input
                        $accept = in_array($docType, ['passport_photo']) ? '.jpg,.jpeg,.png' : 
                                 ($docType === 'resume' ? '.pdf,.doc,.docx' : '.pdf,.jpg,.jpeg,.png');
                        $multiple = in_array($docType, ['education_docs', 'experience_certs']) ? 'multiple' : '';
                        $inputName = in_array($docType, ['education_docs', 'experience_certs']) ? $docType . '[]' : $docType;
                        
                        echo '<input type="file" name="' . $inputName . '" class="form-control" accept="' . $accept . '" ' . $multiple . '>';
                        echo '</div>';
                    }
                    ?>
                    <small class="form-text">Max 5MB per file. JPG/PNG for photos, PDF/DOC for documents.</small>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Update User</button>
                <a href="/ergon/users" class="btn btn--secondary">Cancel</a>
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
.existing-files {
    margin-bottom: 0.5rem;
}
.document-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0.5rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 3px;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}
.document-item a {
    color: #2563eb;
    text-decoration: none;
}
.document-item a:hover {
    text-decoration: underline;
}
</style>

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