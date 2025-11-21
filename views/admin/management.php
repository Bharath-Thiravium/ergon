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
                            <td data-sort-value="<?= $user['status'] ?>"><span class="badge badge--<?= $user['status'] === 'inactive' ? 'inactive' : ($user['status'] === 'suspended' ? 'suspended' : ($user['status'] === 'terminated' ? 'terminated' : 'success')) ?>"><?= ucfirst($user['status']) ?></span></td>
                            <td>
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--view" onclick="viewUser(<?= $user['id'] ?>)" data-tooltip="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    <?php if ($user['role'] === 'user'): ?>
                                    <button class="ab-btn ab-btn--progress" onclick="assignAdmin(<?= $user['id'] ?>)" data-tooltip="Make Admin">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                    </button>
                                    <?php elseif ($user['role'] === 'admin'): ?>
                                    <button class="ab-btn ab-btn--progress" onclick="removeAdmin(<?= $user['id'] ?>)" data-tooltip="Remove Admin">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <line x1="22" y1="11" x2="16" y2="11"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    <button class="ab-btn ab-btn--edit" onclick="editUser(<?= $user['id'] ?>)" data-tooltip="Edit User">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </button>
                                    <button class="ab-btn ab-btn--progress" onclick="changePassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" data-tooltip="Change Password">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                        </svg>
                                    </button>
                                    <?php if ($user['role'] !== 'owner'): ?>
                                    <button class="ab-btn ab-btn--delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" data-tooltip="Delete User">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M3 6h18"/>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
                                        </svg>
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
                    <div class="ab-container">
                        <button class="ab-btn ab-btn--view" onclick="viewUser(<?= $user['id'] ?>)" data-tooltip="View Details">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                        <?php if ($user['role'] === 'user'): ?>
                        <button class="ab-btn ab-btn--progress" onclick="assignAdmin(<?= $user['id'] ?>)" data-tooltip="Make Admin">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </button>
                        <?php elseif ($user['role'] === 'admin'): ?>
                        <button class="ab-btn ab-btn--progress" onclick="removeAdmin(<?= $user['id'] ?>)" data-tooltip="Remove Admin">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <line x1="22" y1="11" x2="16" y2="11"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                        <button class="ab-btn ab-btn--edit" onclick="editUser(<?= $user['id'] ?>)" data-tooltip="Edit User">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                <path d="M15 5l4 4"/>
                            </svg>
                        </button>
                        <button class="ab-btn ab-btn--progress" onclick="changePassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" data-tooltip="Change Password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </button>
                        <?php if ($user['role'] !== 'owner'): ?>
                        <button class="ab-btn ab-btn--delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" data-tooltip="Delete User">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M3 6h18"/>
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
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

function viewUser(userId) {
    window.location.href = '/ergon/users/view/' + userId;
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



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
