<?php
$title = 'User Management';
$active_page = 'users';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👥</span> User Management</h1>
        <p>Manage user roles and administrative permissions</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/users/create" class="btn btn--primary">
            <span>➕</span> Add User
        </a>
        <button class="btn btn--accent" onclick="exportUserList()">
            <span>📊</span> Export
        </button>
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
            <div class="kpi-card__trend">↗ Total</div>
        </div>
        <div class="kpi-card__value"><?= count($users ?? []) ?></div>
        <div class="kpi-card__label">Total Users</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🔑</div>
            <div class="kpi-card__trend">↗ Admins</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($users ?? [], fn($u) => ($u['role'] ?? 'user') === 'admin')) ?></div>
        <div class="kpi-card__label">Admin Users</div>
        <div class="kpi-card__status">Elevated</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👤</div>
            <div class="kpi-card__trend">— Regular</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($users ?? [], fn($u) => ($u['role'] ?? 'user') === 'user')) ?></div>
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
            <div id="listView" class="table-responsive view--active">
                <?php if (!is_array($users) || empty($users)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <h3>No Users Found</h3>
                        <p>No users have been registered yet.</p>
                    </div>
                <?php else: ?>
                    <?php if ($_SESSION['role'] === 'owner'): ?>
                        <?php 
                        $owners = array_filter($users, fn($u) => $u['role'] === 'owner');
                        $admins = array_filter($users, fn($u) => $u['role'] === 'admin');
                        $regularUsers = array_filter($users, fn($u) => $u['role'] === 'user');
                        ?>
                        
                        <?php if (!empty($owners)): ?>
                        <h3 class="section-title">👑 Owners</h3>
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
                                <?php foreach ($owners as $user): ?>
                                    <?php include 'user_row.php'; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                        
                        <?php if (!empty($admins)): ?>
                        <h3 class="section-title">🔑 Administrators</h3>
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
                                <?php foreach ($admins as $user): ?>
                                    <?php include 'user_row.php'; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                        
                        <?php if (!empty($regularUsers)): ?>
                        <h3 class="section-title">👤 Regular Users</h3>
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
                                <?php foreach ($regularUsers as $user): ?>
                                    <?php include 'user_row.php'; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    <?php else: ?>
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
                                <?php foreach ($users as $user): 
                                    // Hide owners and other admins from admin users
                                    if ($_SESSION['role'] === 'admin' && in_array($user['role'], ['owner', 'admin'])) {
                                        continue;
                                    }
                                ?>
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
                                <?php 
                                $userStatus = $user['status'] ?? 'active';
                                $userId = $user['id'];
                                $userName = htmlspecialchars($user['name']);
                                ?>
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--view" onclick="viewUser(<?= $user['id'] ?>)" data-tooltip="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    <?php if (($_SESSION['role'] ?? '') === 'admin' && in_array(($user['role'] ?? 'user'), ['admin', 'owner'])): ?>
                                        <!-- Admins cannot manage other admins/owners -->
                                        <span class="text-muted">Protected</span>
                                    <?php elseif ($userStatus === 'terminated'): ?>
                                        <!-- Terminated Users: Only view for admins, owners can reactivate -->
                                        <?php if (($_SESSION['role'] ?? '') === 'owner'): ?>
                                        <button class="ab-btn ab-btn--success" onclick="activateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Reactivate User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M9 12l2 2 4-4"/>
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                        <span class="text-muted">Terminated</span>
                                    <?php elseif (($_SESSION['role'] ?? '') === 'owner' || (($_SESSION['role'] ?? '') === 'admin' && ($user['role'] ?? 'user') === 'user')): ?>
                                        <!-- Status-based buttons for manageable users -->
                                        <?php if ($userStatus === 'suspended'): ?>
                                            <button class="ab-btn ab-btn--success" onclick="activateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Activate User">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path d="M9 12l2 2 4-4"/>
                                                    <circle cx="12" cy="12" r="10"/>
                                                </svg>
                                            </button>
                                        <?php elseif ($userStatus === 'inactive'): ?>
                                            <button class="ab-btn ab-btn--success" onclick="activateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Activate User">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path d="M9 12l2 2 4-4"/>
                                                    <circle cx="12" cy="12" r="10"/>
                                                </svg>
                                            </button>
                                        <?php elseif ($userStatus === 'active'): ?>
                                            <button class="ab-btn ab-btn--warning" onclick="deactivateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Deactivate User">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                                </svg>
                                            </button>
                                            <button class="ab-btn ab-btn--danger" onclick="suspendUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Suspend User">
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
                                        <button class="ab-btn ab-btn--progress" onclick="resetPassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" data-tooltip="Reset Password">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/>
                                                <circle cx="16.5" cy="7.5" r=".5"/>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" data-tooltip="Terminate User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                                <circle cx="9" cy="7" r="4"/>
                                                <line x1="17" y1="8" x2="22" y2="13"/>
                                                <line x1="22" y1="8" x2="17" y2="13"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                                <?php include 'user_row.php'; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <div id="gridView" class="user-grid view--hidden">
            <?php foreach ($users as $user): 
                // Hide owners and other admins from admin users
                if ($_SESSION['role'] === 'admin' && in_array($user['role'], ['owner', 'admin'])) {
                    continue;
                }
            ?>
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
                    <?php 
                    $userStatus = $user['status'] ?? 'active';
                    $userName = htmlspecialchars($user['name']);
                    ?>
                    <?php if (($_SESSION['role'] ?? '') === 'admin' && in_array(($user['role'] ?? 'user'), ['admin', 'owner'])): ?>
                        <span class="text-muted">Protected</span>
                    <?php elseif ($userStatus === 'terminated'): ?>
                        <?php if (($_SESSION['role'] ?? '') === 'owner'): ?>
                        <button class="ab-btn ab-btn--success" onclick="activateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Reactivate User">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M9 12l2 2 4-4"/>
                                <circle cx="12" cy="12" r="10"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                        <span class="text-muted">Terminated</span>
                    <?php elseif (($_SESSION['role'] ?? '') === 'owner' || (($_SESSION['role'] ?? '') === 'admin' && ($user['role'] ?? 'user') === 'user')): ?>
                        <?php if ($userStatus === 'suspended'): ?>
                            <button class="ab-btn ab-btn--success" onclick="activateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Activate User">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M9 12l2 2 4-4"/>
                                    <circle cx="12" cy="12" r="10"/>
                                </svg>
                            </button>
                        <?php elseif ($userStatus === 'inactive'): ?>
                            <button class="ab-btn ab-btn--success" onclick="activateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Activate User">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M9 12l2 2 4-4"/>
                                    <circle cx="12" cy="12" r="10"/>
                                </svg>
                            </button>
                        <?php elseif ($userStatus === 'active'): ?>
                            <button class="ab-btn ab-btn--warning" onclick="deactivateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Deactivate User">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                </svg>
                            </button>
                            <button class="ab-btn ab-btn--danger" onclick="suspendUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Suspend User">
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
                        <button class="ab-btn ab-btn--progress" onclick="resetPassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" data-tooltip="Reset Password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/>
                                <circle cx="16.5" cy="7.5" r=".5"/>
                            </svg>
                        </button>
                        <button class="ab-btn ab-btn--delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" data-tooltip="Terminate User">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <line x1="17" y1="8" x2="22" y2="13"/>
                                <line x1="22" y1="8" x2="17" y2="13"/>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
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

function exportUserList() {
    window.location.href = '/ergon/users/export';
}

function viewUser(userId) {
    window.location.href = '/ergon/users/view/' + userId;
}

function editUser(userId) {
    window.location.href = '/ergon/users/edit/' + userId;
}

function resetPassword(userId, userName) {
    if (confirm(`Are you sure you want to reset password for ${userName}? A new temporary password will be generated and available for download.`)) {
        const formData = new FormData();
        formData.append('user_id', userId);
        
        fetch('/ergon/users/reset-password', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password reset successfully! The page will reload to show the download credentials button.');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to reset password'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Server error occurred');
        });
    }
}

function deleteUser(userId, userName) {
    if (confirm(`Are you sure you want to terminate user "${userName}"? This will set their status to terminated and disable their access.`)) {
        fetch(`/ergon/users/terminate/${userId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User terminated successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to terminate user'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to terminate user. Please try again.');
        });
    }
}

function activateUser(userId, userName) {
    if (confirm(`Activate user ${userName}?`)) {
        fetch(`/ergon/users/activate/${userId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User activated successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to activate user'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to activate user. Please try again.');
        });
    }
}

function deactivateUser(userId, userName) {
    if (confirm(`Deactivate user ${userName}? They will not be able to login.`)) {
        fetch(`/ergon/users/inactive/${userId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User deactivated successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to deactivate user'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to deactivate user. Please try again.');
        });
    }
}

function suspendUser(userId, userName) {
    if (confirm(`Suspend user ${userName}? They will not be able to login.`)) {
        fetch(`/ergon/users/suspend/${userId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User suspended successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to suspend user'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to suspend user. Please try again.');
        });
    }
}
</script>

<style>
.table th:nth-child(3), /* Role column */
.table th:nth-child(4) { /* Status column */
    width: 130px;
    min-width: 130px;
}

.table th:nth-child(5) { /* Actions column */
    width: 300px;
    min-width: 300px;
}

.table td:nth-child(3),
.table td:nth-child(4) {
    width: 130px;
    min-width: 130px;
}

.table td:nth-child(5) {
    width: 300px;
    min-width: 300px;
}

.section-title {
    margin: 2rem 0 1rem 0;
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 4px solid #007bff;
    border-radius: 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    color: #495057;
}

.badge--primary {
    background-color: #007bff;
    color: white;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
