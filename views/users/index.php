<?php
$title = 'User Management';
$active_page = 'users';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👥</span> User Management</h1>
        <p>Manage system users, roles, and permissions</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/users/create" class="btn btn--primary">
            <span>➕</span> Add User
        </a>
        <?php if (isset($_SESSION['new_credentials']) || isset($_SESSION['reset_credentials'])): ?>
        <a href="/ergon/users/download-credentials" class="btn btn--success">
            <span>📥</span> Download Credentials
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">↗ +7%</div>
        </div>
        <div class="kpi-card__value"><?= count($users ?? []) ?></div>
        <div class="kpi-card__label">Total Users</div>
        <div class="kpi-card__status">Registered</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($users ?? [], fn($u) => ($u['status'] ?? 'active') === 'active')) ?></div>
        <div class="kpi-card__label">Active Users</div>
        <div class="kpi-card__status">Online</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🔒</div>
            <div class="kpi-card__trend">↗ +2%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($users ?? [], fn($u) => ($u['role'] ?? 'user') === 'admin')) ?></div>
        <div class="kpi-card__label">Admin Users</div>
        <div class="kpi-card__status">Privileged</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>👥</span> System Users
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users ?? [])): ?>
                    <tr>
                        <td colspan="6" class="text-center" style="color: var(--text-muted); padding: 2rem;">
                            <div class="empty-state">
                                <div class="empty-icon">👥</div>
                                <h3>No Users Found</h3>
                                <p>No users have been registered yet.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><span class="badge badge--success"><?= ucfirst($user['role']) ?></span></td>
                            <td><span class="badge badge--success"><?= ucfirst($user['status']) ?></span></td>
                            <td><?= isset($user['last_login']) ? date('M d, Y', strtotime($user['last_login'])) : 'Never' ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="/ergon/users/view/<?= $user['id'] ?>" class="btn btn--sm btn--primary btn-icon" title="View Details">
                                        👁️
                                    </a>
                                    <button onclick="resetPassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" class="btn btn--sm btn--warning btn-icon" title="Reset Password">
                                        🔑
                                    </button>
                                    <button onclick="deleteRecord('users', <?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" class="btn btn--sm btn--danger btn-icon" title="Delete User">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function resetPassword(userId, userName) {
    if (confirm(`Reset password for ${userName}?\n\nThis will generate a new random password.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/ergon/users/reset-password';
        form.innerHTML = `<input type="hidden" name="user_id" value="${userId}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteRecord(type, id, name) {
    if (confirm(`Delete ${name}?\n\nThis action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/ergon/${type}/delete/${id}`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
