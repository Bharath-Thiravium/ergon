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
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--view" data-action="view" data-module="users" data-id="<?= $user['id'] ?>" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                            <polyline points="10,9 9,9 8,9"/>
                                        </svg>
                                    </button>
                                    <?php if (($user['status'] ?? 'active') === 'active'): ?>
                                        <button class="ab-btn ab-btn--edit" data-action="edit" data-module="users" data-id="<?= $user['id'] ?>" title="Edit User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                                <path d="M15 5l4 4"/>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--reset" data-action="reset" data-module="users" data-id="<?= $user['id'] ?>" data-name="<?= htmlspecialchars($user['name']) ?>" title="Reset Password">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--warning btn-deactivate" data-action="inactive" data-module="users" data-id="<?= $user['id'] ?>" data-name="<?= htmlspecialchars($user['name']) ?>" title="Deactivate User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--delete btn-remove" data-action="delete" data-module="users" data-id="<?= $user['id'] ?>" data-name="<?= htmlspecialchars($user['name']) ?>" title="Remove User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M3 6h18"/>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                                <line x1="10" y1="11" x2="10" y2="17"/>
                                                <line x1="14" y1="11" x2="14" y2="17"/>
                                            </svg>
                                        </button>
                                    <?php elseif (($user['status'] ?? 'active') === 'inactive'): ?>
                                        <button class="ab-btn ab-btn--success" data-action="activate" data-module="users" data-id="<?= $user['id'] ?>" data-name="<?= htmlspecialchars($user['name']) ?>" title="Activate User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M9 12l2 2 4-4"/>
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--delete" data-action="delete" data-module="users" data-id="<?= $user['id'] ?>" data-name="<?= htmlspecialchars($user['name']) ?>" title="Remove User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M3 6h18"/>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                                <line x1="10" y1="11" x2="10" y2="17"/>
                                                <line x1="14" y1="11" x2="14" y2="17"/>
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">Removed</span>
                                    <?php endif; ?>
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



<script src="/ergon/assets/js/table-utils.js"></script>

<script>
// Dropdown functions
function showDropdown(element) {
    // Simple tooltip-like functionality
    const tooltip = element.getAttribute('title');
    if (tooltip) {
        element.setAttribute('data-original-title', tooltip);
        element.removeAttribute('title');
    }
}

function hideDropdown(element) {
    // Restore tooltip
    const originalTitle = element.getAttribute('data-original-title');
    if (originalTitle) {
        element.setAttribute('title', originalTitle);
        element.removeAttribute('data-original-title');
    }
}

// Global action button handler
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.ab-btn');
    if (!btn) return;
    
    const action = btn.dataset.action;
    const module = btn.dataset.module;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    
    // Debug logging
    console.log('Button clicked:', { action, module, id, name, buttonClass: btn.className });
    
    // DIAGNOSTIC: Specific remove button tracking
    if (btn.classList.contains('btn-remove')) {
        console.log('🔴 REMOVE BUTTON TRIGGERED for user ID:', id);
        console.log('🔴 Button element:', btn);
        console.log('🔴 Data attributes:', btn.dataset);
    }
    
    if (action === 'view' && module && id) {
        window.location.href = `/ergon/${module}/view/${id}`;
    } else if (action === 'edit' && module && id) {
        window.location.href = `/ergon/${module}/edit/${id}`;
    } else if (action === 'inactive' && module && id && name) {
        if (confirm(`Deactivate user ${name}? They will not be able to login.`)) {
            fetch(`/ergon/${module}/inactive/${id}`, { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User deactivated successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Deactivation failed'));
                }
            })
            .catch(error => {
                console.error('Deactivate error:', error);
                alert('Error deactivating user');
            });
        }
    } else if (action === 'activate' && module && id && name) {
        if (confirm(`Activate user ${name}?`)) {
            fetch(`/ergon/${module}/activate/${id}`, { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User activated successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Activation failed'));
                }
            })
            .catch(error => {
                console.error('Activate error:', error);
                alert('Error activating user');
            });
        }
    } else if (action === 'delete' && module && id && name) {
        console.log('🔴 DELETE ACTION CONFIRMED - calling endpoint:', `/ergon/${module}/delete/${id}`);
        if (confirm(`Remove user ${name}? This will permanently mark them as removed from the system.`)) {
            console.log('🔴 User confirmed deletion, making API call...');
            fetch(`/ergon/${module}/delete/${id}`, { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User removed successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Removal failed'));
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert('Error removing user');
            });
        }
    } else if (action === 'reset' && module && id && name) {
        if (confirm(`Reset password for ${name}?`)) {
            fetch(`/ergon/${module}/reset-password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password reset successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Reset failed'));
                }
            })
            .catch(() => alert('Error resetting password'));
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
