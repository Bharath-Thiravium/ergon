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
        <button class="btn btn--primary" onclick="showAssignAdminModal()">
            <span>⬆️</span> Promote User
        </button>
        <button class="btn btn--secondary" onclick="exportUserList()">
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
    </div>
    <div class="card__body">
        <?php if (empty($data['users'])): ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3>No Users Found</h3>
                <p>No users are currently registered in the system.</p>
            </div>
        <?php else: ?>
            <div class="user-grid">
                <?php foreach ($data['users'] as $user): ?>
                <div class="user-card">
                    <div class="user-card__header">
                        <div class="user-avatar user-avatar--lg"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                        <div class="user-card__badges">
                            <span class="badge badge--<?= $user['role'] === 'admin' ? 'success' : 'warning' ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                            <span class="badge badge--success">
                                <?= ucfirst($user['status']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="user-card__body">
                        <h3 class="user-card__name"><?= htmlspecialchars($user['name']) ?></h3>
                        <p class="user-card__email"><?= htmlspecialchars($user['email']) ?></p>
                        <p class="user-card__role">Current Role: <?= ucfirst($user['role']) ?></p>
                    </div>
                    <div class="user-card__actions">
                        <?php if ($user['role'] === 'user'): ?>
                        <button class="btn btn--sm btn--primary" onclick="assignAdmin(<?= $user['id'] ?>)">
                            <span>⬆️</span> Make Admin
                        </button>
                        <?php elseif ($user['role'] === 'admin'): ?>
                        <button class="btn btn--sm btn--warning" onclick="removeAdmin(<?= $user['id'] ?>)">
                            <span>⬇️</span> Remove Admin
                        </button>
                        <?php endif; ?>
                        <button class="btn btn--sm btn--secondary" onclick="editUser(<?= $user['id'] ?>)">
                            <span>✏️</span> Edit
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
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
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
