<?php
$title = 'User Admin Management';
$active_page = 'admin';

ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👥</span> User Admin Management</h1>
        <p>Manage user roles and administrative permissions</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/users/create" class="btn btn--primary">
            <span>➕</span> Add User
        </a>
        <button class="btn btn--secondary" onclick="showAssignAdminModal()">
            <span>⬆️</span> Promote User
        </button>
        <button class="btn btn--accent" onclick="exportUserList()">
            <span>📊</span> Export
        </button>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">↗ Total</div>
        </div>
        <div class="kpi-card__value"><?= count($data['users'] ?? []) ?></div>
        <div class="kpi-card__label">Total Users</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🔑</div>
            <div class="kpi-card__trend">↗ Admins</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['users'] ?? [], fn($u) => $u['role'] === 'admin')) ?></div>
        <div class="kpi-card__label">Admin Users</div>
        <div class="kpi-card__status">Elevated</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👤</div>
            <div class="kpi-card__trend">— Regular</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['users'] ?? [], fn($u) => $u['role'] === 'user')) ?></div>
        <div class="kpi-card__label">Regular Users</div>
        <div class="kpi-card__status">Standard</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">User Management</h2>
        <div class="card__actions">
            <button class="btn btn--sm btn--secondary" onclick="toggleView()">
                <span id="viewToggle">🔲</span> <span id="viewText">Grid View</span>
            </button>
        </div>
    </div>
    <div class="card__body">
        <?php if (empty($data['users'])): ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3>No Users Found</h3>
                <p>No users are currently registered in the system.</p>
            </div>
        <?php else: ?>
            <div id="listView" class="table-responsive view--active">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['users'] as $user): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                                    <div>
                                        <strong><?= htmlspecialchars($user['name']) ?></strong>
                                        <br><small class="text-muted">ID: <?= $user['id'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td data-sort-value="<?= $user['email'] ?>"><?= htmlspecialchars($user['email']) ?></td>
                            <td data-sort-value="<?= $user['role'] ?>"><span class="badge badge--<?= $user['role'] === 'admin' ? 'success' : 'info' ?>"><?= ucfirst($user['role']) ?></span></td>
                            <td data-sort-value="<?= $user['status'] ?>"><span class="badge badge--success"><?= ucfirst($user['status']) ?></span></td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($user['role'] === 'user'): ?>
                                    <button class="btn btn--sm btn--primary" onclick="assignAdmin(<?= $user['id'] ?>)" title="Make Admin">
                                        ⬆️
                                    </button>
                                    <?php elseif ($user['role'] === 'admin'): ?>
                                    <button class="btn btn--sm btn--warning" onclick="removeAdmin(<?= $user['id'] ?>)" title="Remove Admin">
                                        ⬇️
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn--sm btn--secondary" onclick="editUser(<?= $user['id'] ?>)" title="Edit User">
                                        ✏️
                                    </button>
                                    <button class="btn btn--sm btn--info" onclick="changePassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" title="Change Password">
                                        🔑
                                    </button>
                                    <?php if ($user['role'] !== 'owner'): ?>
                                    <button class="btn btn--sm btn--danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" title="Delete User">
                                        🗑️
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="gridView" class="user-grid view--hidden">
                <?php foreach ($data['users'] as $user): ?>
                <div class="user-card">
                    <div class="user-card__header">
                        <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                        <div class="user-card__badges">
                            <span class="badge badge--<?= $user['role'] === 'admin' ? 'success' : 'info' ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </div>
                    </div>
                    <h3 class="user-card__name"><?= htmlspecialchars($user['name']) ?></h3>
                    <p class="user-card__email"><?= htmlspecialchars($user['email']) ?></p>
                    <p class="user-card__role"><?= ucfirst($user['role']) ?></p>
                    <div class="user-card__actions">
                        <?php if ($user['role'] === 'user'): ?>
                        <button class="btn btn--sm btn--primary" onclick="assignAdmin(<?= $user['id'] ?>)">
                            ⬆️ Make Admin
                        </button>
                        <?php elseif ($user['role'] === 'admin'): ?>
                        <button class="btn btn--sm btn--warning" onclick="removeAdmin(<?= $user['id'] ?>)">
                            ⬇️ Remove Admin
                        </button>
                        <?php endif; ?>
                        <button class="btn btn--sm btn--secondary" onclick="editUser(<?= $user['id'] ?>)">
                            ✏️ Edit
                        </button>
                        <button class="btn btn--sm btn--info" onclick="changePassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')">
                            🔑 Password
                        </button>
                        <?php if ($user['role'] !== 'owner'): ?>
                        <button class="btn btn--sm btn--danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')">
                            🗑️ Delete
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>



<script>
let currentView = 'list';

window.toggleView = function() {
    const listView = document.getElementById('listView');
    const gridView = document.getElementById('gridView');
    const toggleIcon = document.getElementById('viewToggle');
    const toggleText = document.getElementById('viewText');
    
    if (currentView === 'list') {
        listView.classList.remove('view--active');
        listView.classList.add('view--hidden');
        gridView.classList.remove('view--hidden');
        gridView.classList.add('view--active');
        toggleIcon.textContent = '🔲';
        toggleText.textContent = 'List View';
        currentView = 'grid';
    } else {
        listView.classList.remove('view--hidden');
        listView.classList.add('view--active');
        gridView.classList.remove('view--active');
        gridView.classList.add('view--hidden');
        toggleIcon.textContent = '📋';
        toggleText.textContent = 'Grid View';
        currentView = 'list';
    }
}

function showAssignAdminModal() {
    alert('Please use the individual "Make Admin" buttons on each user card to promote users to admin role.');
}

function assignAdmin(userId) {
    if (confirm('Are you sure you want to assign admin role to this user?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/ergon/admin/assign';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'user_id';
        input.value = userId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function removeAdmin(adminId) {
    if (confirm('Are you sure you want to remove admin role from this user?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/ergon/admin/remove';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'admin_id';
        input.value = adminId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function editUser(userId) {
    window.location.href = '/ergon/users/edit/' + userId;
}

function exportUserList() {
    window.location.href = '/ergon/admin/export';
}

function changePassword(userId, userName) {
    const newPassword = prompt(`Enter new password for ${userName}:`);
    if (!newPassword) return;
    
    if (newPassword.length < 6) {
        alert('Password must be at least 6 characters long.');
        return;
    }
    
    const confirmPassword = prompt('Confirm new password:');
    if (newPassword !== confirmPassword) {
        alert('Passwords do not match.');
        return;
    }
    
    if (confirm(`Are you sure you want to change password for ${userName}?`)) {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('new_password', newPassword);
        
        fetch('/ergon/admin/change-password', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password changed successfully!');
            } else {
                alert('Error: ' + (data.error || 'Failed to change password'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Server error occurred');
        });
    }
}

function deleteUser(userId, userName) {
    if (confirm(`Are you sure you want to permanently delete user "${userName}"? This action cannot be undone and will remove all associated data.`)) {
        const formData = new FormData();
        formData.append('user_id', userId);
        
        fetch('/ergon/admin/delete-user', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete user. Please try again.');
        });
    }
}
</script>

<script src="/ergon/assets/js/table-utils.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
