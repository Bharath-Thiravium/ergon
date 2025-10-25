<?php 
$title = 'System Admin Management';
$active_page = 'system-admin';
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">🔧 System Admin Management</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSystemAdminModal">
                        <i class="fas fa-plus"></i> Create System Admin
                    </button>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>System Admins:</strong> These are system-level administrators who can create and manage users. 
                        They are not personal user accounts but administrative roles for system operations.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Admin Name</th>
                                    <th>Email</th>
                                    <th>Permissions</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data['system_admins'])): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No system admins created</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data['system_admins'] as $admin): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <i class="fas fa-cog"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($admin['name']) ?></strong>
                                                        <br><small class="text-muted">System Admin</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($admin['email']) ?></td>
                                            <td>
                                                <?php 
                                                $permissions = json_decode($admin['permissions'] ?? '[]', true);
                                                if (empty($permissions)): ?>
                                                    <span class="badge badge-secondary">Standard</span>
                                                <?php else: ?>
                                                    <?php foreach ($permissions as $perm): ?>
                                                        <span class="badge badge-primary"><?= htmlspecialchars($perm) ?></span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($admin['status'] === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($admin['created_at'])) ?></td>
                                            <td>
                                                <?= $admin['last_login'] ? date('M j, Y g:i A', strtotime($admin['last_login'])) : 'Never' ?>
                                            </td>
                                            <td>
                                                <?php if ($admin['status'] === 'active'): ?>
                                                    <button class="btn btn-sm btn-danger" onclick="deactivateAdmin(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['name']) ?>')">
                                                        <i class="fas fa-ban"></i> Deactivate
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create System Admin Modal -->
<div class="modal fade" id="createSystemAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create System Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createSystemAdminForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        This creates a system administrator who can manage users and operations. 
                        No personal information is required.
                    </div>
                    
                    <div class="form-group">
                        <label>Admin Name <span class="text-danger">*</span></label>
                        <input type="text" name="admin_name" class="form-control" placeholder="e.g., HR Admin, Operations Admin" required>
                        <small class="form-text text-muted">Functional name for this admin role</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Admin Email <span class="text-danger">*</span></label>
                        <input type="email" name="admin_email" class="form-control" placeholder="admin@company.com" required>
                        <small class="form-text text-muted">Login email for this admin</small>
                    </div>
                    
                    <div class="form-group">
                        <label>System Permissions</label>
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="user_management" class="form-check-input" checked>
                            <label class="form-check-label">User Management</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="task_management" class="form-check-input">
                            <label class="form-check-label">Task Management</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="leave_approval" class="form-check-input">
                            <label class="form-check-label">Leave Approvals</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="expense_approval" class="form-check-input">
                            <label class="form-check-label">Expense Approvals</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="reports_access" class="form-check-input">
                            <label class="form-check-label">Reports Access</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create System Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Password Display Modal -->
// [SECURITY FIX] Removed hardcoded password: <div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">System Admin Created</h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    System admin created successfully!
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Important:</strong> Save this temporary password. It will not be shown again.
                </div>
                <div class="form-group">
                    <label>Temporary Password:</label>
                    <div class="input-group">
                        <input type="text" id="tempPassword" class="form-control" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyPassword()">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
                <p class="text-muted">The admin must change this password on first login.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('createSystemAdminForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/ergon/system-admin/create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide create modal
            var createModal = document.querySelector('#createSystemAdminModal');
            if (createModal && window.bootstrap) {
                bootstrap.Modal.getInstance(createModal).hide();
            }
            
            // Show password modal
            document.getElementById('tempPassword').value = data.temp_password;
            var passwordModal = document.querySelector('#passwordModal');
            if (passwordModal && window.bootstrap) {
                new bootstrap.Modal(passwordModal).show();
            }
            
            // Reset form
            document.getElementById('createSystemAdminForm').reset();
        } else {
            alert(data.message || 'Failed to create system admin');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
});

function deactivateAdmin(adminId, adminName) {
    if (confirm(`Deactivate system admin "${adminName}"? This will disable their access.`)) {
        const formData = new FormData();
        formData.append('admin_id', adminId);
        
        fetch('/ergon/system-admin/deactivate', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('System admin deactivated successfully!');
                location.reload();
            } else {
                alert('Failed to deactivate system admin');
            }
        });
    }
}

function copyPassword() {
    const passwordField = document.getElementById('tempPassword');
    passwordField.select();
    document.execCommand('copy');
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
    
    setTimeout(() => {
        button.innerHTML = originalText;
    }, 2000);
}

// Auto-reload after password modal is closed
document.addEventListener('DOMContentLoaded', function() {
    var passwordModal = document.getElementById('passwordModal');
    if (passwordModal) {
        passwordModal.addEventListener('hidden.bs.modal', function () {
            location.reload();
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>