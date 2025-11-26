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
        <div class="kpi-card__value"><?= count(array_filter($users ?? [], fn($u) => in_array(($u['status'] ?? 'active'), ['active', 'inactive', 'suspended', 'terminated']))) ?></div>
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
                                    <?php 
                                    $status = htmlspecialchars($user['status'] ?? 'active');
                                    $statusClass = match($status) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'suspended' => 'danger',
                                        'terminated' => 'dark',
                                        default => 'pending'
                                    };
                                    ?>
                                    <div class="status-dot status-dot--<?= $statusClass ?>"></div>
                                    <span class="modern-badge modern-badge--<?= $statusClass ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                                </div>
                            </td>
                            <td><?= isset($user['last_login']) ? date('M d, Y', strtotime($user['last_login'])) : 'Never' ?></td>
                            <td>
                                <?php 
                                $userStatus = $user['status'] ?? 'active';
                                $userId = $user['id'];
                                $userName = htmlspecialchars($user['name']);
                                ?>
                                <div class="ab-container">
                                    <!-- Always show View button -->
                                    <button class="ab-btn ab-btn--view" data-action="view" data-module="users" data-id="<?= $userId ?>" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    
                                    <?php if ($userStatus === 'terminated'): ?>
                                        <!-- Terminated Users: Only View button -->
                                        <span class="text-muted">Terminated</span>
                                    <?php elseif ($userStatus === 'suspended'): ?>
                                        <!-- Suspended Users: Make Active + Edit + Reset Password + Terminate -->
                                        <button class="ab-btn ab-btn--success" data-action="activate" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>" title="Make Active">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M9 12l2 2 4-4"/>
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--edit" data-action="edit" data-module="users" data-id="<?= $userId ?>" title="Edit User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                                <path d="M15 5l4 4"/>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--warning" data-action="reset" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>" title="Reset Password">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                <circle cx="12" cy="16" r="1"/>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--delete" data-action="terminate" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>" title="Terminate User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <line x1="15" y1="9" x2="9" y2="15"/>
                                                <line x1="9" y1="9" x2="15" y2="15"/>
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <!-- Active/Inactive Users: Full action set -->
                                        <button class="ab-btn ab-btn--edit" data-action="edit" data-module="users" data-id="<?= $userId ?>" title="Edit User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                                <path d="M15 5l4 4"/>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--warning" data-action="reset" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>" title="Reset Password">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                <circle cx="12" cy="16" r="1"/>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                            </svg>
                                        </button>
                                        <?php if ($userStatus === 'active'): ?>
                                            <button class="ab-btn ab-btn--info" data-action="inactive" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>" title="Deactivate User">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                                </svg>
                                            </button>
                                            <button class="ab-btn ab-btn--danger" data-action="suspend" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>" title="Suspend User">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <path d="M10 15l4-4 4 4"/>
                                                </svg>
                                            </button>
                                            <button class="ab-btn ab-btn--delete" data-action="terminate" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>" title="Terminate User">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <button class="ab-btn ab-btn--success" data-action="activate" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>" title="Activate User">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path d="M9 12l2 2 4-4"/>
                                                    <circle cx="12" cy="12" r="10"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
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
    } else if (action === 'suspend' && module && id && name) {
        if (confirm(`Suspend user ${name}? They will not be able to login.`)) {
            fetch(`/ergon/${module}/suspend/${id}`, { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User suspended successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Suspension failed'));
                }
            })
            .catch(error => {
                console.error('Suspend error:', error);
                alert('Error suspending user');
            });
        }
    } else if (action === 'terminate' && module && id && name) {
        if (confirm(`Terminate user ${name}? This action cannot be undone and they will not be able to login.`)) {
            fetch(`/ergon/${module}/terminate/${id}`, { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User terminated successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Termination failed'));
                }
            })
            .catch(error => {
                console.error('Terminate error:', error);
                alert('Error terminating user');
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
                    alert(data.message || 'Password reset successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Reset failed'));
                }
            })
            .catch(() => alert('Error resetting password'));
        }
    }
});

console.log('SCRIPT LOADED');
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
