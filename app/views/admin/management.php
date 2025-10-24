<?php 
$title = 'Admin Management';
$active_page = 'admin';
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">👥 Admin Position Management</h3>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#assignAdminModal">
                        <i class="fas fa-plus"></i> Assign Admin Position
                    </button>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Admin Positions:</strong> Admins are assigned roles to manage specific departments and functions. 
                        They can be promoted from regular users and demoted back when needed.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Admin Name</th>
                                    <th>Email</th>
                                    <th>Assigned Department</th>
                                    <th>Permissions</th>
                                    <th>Admin Since</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data['admins'])): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No admin positions assigned</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data['admins'] as $admin): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <?= strtoupper(substr($admin['name'], 0, 2)) ?>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($admin['name']) ?></strong>
                                                        <br><small class="text-muted">ID: <?= $admin['id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($admin['email']) ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?= htmlspecialchars($admin['assigned_department'] ?? 'All Departments') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $permissions = json_decode($admin['permissions'] ?? '[]', true);
                                                if (empty($permissions)): ?>
                                                    <span class="badge badge-secondary">Standard</span>
                                                <?php else: ?>
                                                    <?php foreach ($permissions as $perm): ?>
                                                        <span class="badge badge-success"><?= htmlspecialchars($perm) ?></span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($admin['admin_since'] ?? $admin['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editAdmin(<?= $admin['id'] ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="removeAdmin(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['name']) ?>')">
                                                    <i class="fas fa-user-minus"></i> Remove
                                                </button>
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

<!-- Assign Admin Modal -->
<div class="modal fade" id="assignAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Admin Position</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="assignAdminForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select User to Promote</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">Choose a user...</option>
                            <?php foreach ($data['available_users'] as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Assign to Department</label>
                        <select name="department" class="form-control">
                            <option value="">All Departments</option>
                            <?php foreach ($data['departments'] as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['name']) ?>">
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Admin Permissions</label>
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="user_management" class="form-check-input">
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Admin Position</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('assignAdminForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/ergon/admin/assign', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Admin position assigned successfully!');
            location.reload();
        } else {
            alert('Failed to assign admin position');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
});

function removeAdmin(userId, userName) {
    if (confirm(`Remove admin position from ${userName}? They will be demoted to regular user.`)) {
        const formData = new FormData();
        formData.append('user_id', userId);
        
        fetch('/ergon/admin/remove', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Admin position removed successfully!');
                location.reload();
            } else {
                alert('Failed to remove admin position');
            }
        });
    }
}

function editAdmin(userId) {
    // TODO: Implement edit functionality
    alert('Edit functionality coming soon');
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>