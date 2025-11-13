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
        <div class="table-responsive modern-table">
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
                    <?php if (!is_array($users) || empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center">
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
                            <td>
                                <div class="cell-meta">
                                    <div class="cell-primary"><?= htmlspecialchars($user['name']) ?></div>
                                    <div class="cell-secondary">ID: <?= $user['id'] ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-meta">
                                    <div class="cell-primary"><?= htmlspecialchars($user['email']) ?></div>
                                    <div class="cell-secondary">Employee ID: <?= $user['employee_id'] ?? 'N/A' ?></div>
                                </div>
                            </td>
                            <td><span class="modern-badge modern-badge--<?= htmlspecialchars($user['role'] ?? 'user') === 'admin' ? 'warning' : (htmlspecialchars($user['role'] ?? 'user') === 'owner' ? 'danger' : 'info') ?>"><?= htmlspecialchars(ucfirst($user['role'] ?? 'user')) ?></span></td>
                            <td>
                                <div class="status-indicator">
                                    <div class="status-dot status-dot--<?= htmlspecialchars($user['status'] ?? 'active') === 'active' ? 'success' : 'pending' ?>"></div>
                                    <span class="modern-badge modern-badge--<?= htmlspecialchars($user['status'] ?? 'active') === 'active' ? 'success' : 'pending' ?>"><?= htmlspecialchars(ucfirst($user['status'] ?? 'active')) ?></span>
                                </div>
                            </td>
                            <td><?= isset($user['last_login']) ? date('M d, Y', strtotime($user['last_login'])) : 'Never' ?></td>
                            <td>
                                <div class="modern-actions">
                                    <a href="/ergon/users/view/<?= $user['id'] ?>" class="modern-btn modern-btn--primary modern-tooltip" data-tooltip="View Details">
                                        👁️
                                    </a>
                                    <button class="modern-btn modern-btn--warning modern-tooltip reset-password-btn" data-tooltip="Reset Password" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>">
                                        🔑
                                    </button>
                                    <button class="modern-btn modern-btn--danger modern-tooltip delete-user-btn" data-tooltip="Delete User" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>">
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
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('reset-password-btn')) {
            const userId = e.target.dataset.userId;
            const userName = e.target.dataset.userName;
            if (confirm(`Reset password for ${userName}?\n\nThis will generate a new random password.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/ergon/users/reset-password';
                form.innerHTML = `<input type="hidden" name="user_id" value="${userId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        if (e.target.classList.contains('delete-user-btn')) {
            const userId = e.target.dataset.userId;
            const userName = e.target.dataset.userName;
            if (confirm(`Delete ${userName}?\n\nThis action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/ergon/users/delete/${userId}`;
                document.body.appendChild(form);
                form.submit();
            }
        }
    });
});
</script>

<script src="/ergon/assets/js/table-utils.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
