<?php
$title = 'User Management';
$active_page = 'users';

ob_start();
?>

<div class="page-header">
    <h1><?= ucfirst($data['manageable_role']) ?> Management</h1>
    <div class="header-actions">
        <?php if (isset($_SESSION['temp_password'])): ?>
        <a href="/ergon/users/download-credentials" class="btn btn--success">📥 Download Credentials</a>
        <?php endif; ?>
        <?php if ($data['manageable_role'] === 'admin'): ?>
            <a href="/ergon/admin/management" class="btn btn--primary">👥 Manage Admin Positions</a>
        <?php else: ?>
            <a href="/ergon/users/create" class="btn btn--primary">Add New <?= ucfirst($data['manageable_role']) ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['total_users'] ?></div>
        <div class="kpi-card__label">Total <?= ucfirst($data['manageable_role']) ?>s</div>
        <div class="kpi-card__status kpi-card__status--active">Registered</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +12%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['active_users'] ?></div>
        <div class="kpi-card__label">Active <?= ucfirst($data['manageable_role']) ?>s</div>
        <div class="kpi-card__status kpi-card__status--active">Online</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🔑</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['admin_count'] ?></div>
        <div class="kpi-card__label">Admins</div>
        <div class="kpi-card__status kpi-card__status--pending">Privileged</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👤</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +15%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['user_count'] ?></div>
        <div class="kpi-card__label">Employees</div>
        <div class="kpi-card__status kpi-card__status--info">Standard</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">All <?= ucfirst($data['manageable_role']) ?>s</h2>
    </div>
    <div class="card__body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['users'] as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="badge badge--<?= $user['role'] === 'owner' ? 'error' : ($user['role'] === 'admin' ? 'warning' : 'info') ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $departments = explode(',', $user['department'] ?? '');
                            $departments = array_filter($departments);
                            if (count($departments) > 0) {
                                echo htmlspecialchars($departments[0]);
                                if (count($departments) > 1) {
                                    echo ' <span class="badge badge--info">+' . (count($departments) - 1) . '</span>';
                                }
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>
                            <span class="badge badge--<?= $user['status'] === 'active' ? 'success' : 'error' ?>">
                                <?= ucfirst($user['status']) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="/ergon/users/view/<?= $user['id'] ?>" class="btn btn--info btn--sm" title="View User">👁️</a>
                                <a href="/ergon/users/edit/<?= $user['id'] ?>" class="btn btn--secondary btn--sm" title="Edit User">✏️</a>
                                <button onclick="resetPassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" class="btn btn--warning btn--sm" title="Reset Password">🔑</button>
                                <button onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" class="btn btn--danger btn--sm" title="Delete User">🗑️</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function resetPassword(userId, userName) {
    if (confirm(`Reset password for ${userName}? This will generate a new temporary password.`)) {
        fetch('/ergon/users/reset-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Password reset successful! New password: ${data.temp_password}`);
                // Auto-download credentials
                const element = document.createElement('a');
                const content = `ERGON Password Reset\n===================\n\nUser: ${userName}\nNew Password: ${data.temp_password}\n\nInstructions:\n1. User must login and reset password on first login\n2. Generated on: ${new Date().toLocaleString()}`;
                element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
                element.setAttribute('download', `password_reset_${userName.replace(/\s+/g, '_')}.txt`);
                element.style.display = 'none';
                document.body.appendChild(element);
                element.click();
                document.body.removeChild(element);
            } else {
                alert('Password reset failed: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}

function deleteUser(userId, userName) {
    const action = prompt(`Choose action for ${userName}:\n\n1. Type 'inactive' to mark as inactive (resigned)\n2. Type 'delete' to permanently delete (mistaken entry)\n\nEnter your choice:`);
    
    if (action === 'inactive') {
        if (confirm(`Mark ${userName} as inactive? This will disable their access but keep their data.`)) {
            fetch('/ergon/users/inactive/' + userId, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User marked as inactive!');
                    location.reload();
                } else {
                    alert('Failed: ' + data.error);
                }
            });
        }
    } else if (action === 'delete') {
        if (confirm(`PERMANENTLY DELETE ${userName}? This cannot be undone and will remove all their data.`)) {
            fetch('/ergon/users/delete/' + userId, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User permanently deleted!');
                    location.reload();
                } else {
                    alert('Delete failed: ' + data.error);
                }
            });
        }
    }
}
</script>

<style>
.action-buttons {
    display: flex;
    gap: 5px;
    align-items: center;
}

.action-buttons .btn {
    min-width: 32px;
    height: 32px;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.action-buttons .btn:hover {
    transform: scale(1.1);
    transition: transform 0.2s;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>