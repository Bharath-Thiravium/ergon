<?php
$title = 'User Management';
$active_page = 'users';

// Prevent caching of users list
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

ob_start();
?>

<?php
// Display success/error messages
if (isset($_GET['success'])): ?>
<div class="alert alert--success">
    <i class="bi bi-check-circle-fill"></i>
    <?= htmlspecialchars($_GET['success']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert--error">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👥</span> User Management</h1>
        <p>Manage user roles and administrative permissions</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--primary" onclick="showAddUserModal()">
            <span>➕</span> Add User
        </button>
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
        <div class="kpi-card__value"><?= $total_users_kpi ?? 0 ?></div>
        <div class="kpi-card__label">Total Users</div>
        <div class="kpi-card__status">All Roles</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👑</div>
            <div class="kpi-card__trend">↗ Owners</div>
        </div>
        <div class="kpi-card__value"><?= $owner_count ?? 0 ?></div>
        <div class="kpi-card__label">Company Owners</div>
        <div class="kpi-card__status">Top Level</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🛡️</div>
            <div class="kpi-card__trend">↗ Admins</div>
        </div>
        <div class="kpi-card__value"><?= $admin_count ?? 0 ?></div>
        <div class="kpi-card__label">Admin Users</div>
        <div class="kpi-card__status">Elevated</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👨💼</div>
            <div class="kpi-card__trend">↗ HR</div>
        </div>
        <div class="kpi-card__value"><?= $hr_count ?? 0 ?></div>
        <div class="kpi-card__label">HR Users</div>
        <div class="kpi-card__status">Support</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👤</div>
            <div class="kpi-card__trend">— Employees</div>
        </div>
        <div class="kpi-card__value"><?= $employee_count ?? 0 ?></div>
        <div class="kpi-card__label">Regular Employees</div>
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
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
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
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['department_name'] ?? '-') ?></td>
                                <td>
                                    <?php 
                                    $roleBadgeClass = 'badge-info';
                                    $roleIcon = '👤';
                                    $roleDisplay = ucfirst($user['role']);
                                    
                                    switch($user['role']) {
                                        case 'company_owner':
                                        case 'owner':
                                            $roleBadgeClass = 'badge-danger';
                                            $roleIcon = '👑';
                                            $roleDisplay = $user['role'] === 'company_owner' ? 'Company Owner' : 'Owner';
                                            break;
                                        case 'admin':
                                            $roleBadgeClass = 'badge-success';
                                            $roleIcon = '🛡️';
                                            break;
                                        case 'hr':
                                            $roleBadgeClass = 'badge-primary';
                                            $roleIcon = '👨💼';
                                            $roleDisplay = 'HR';
                                            break;
                                        case 'user':
                                        default:
                                            $roleBadgeClass = 'badge-info';
                                            $roleIcon = '👤';
                                            $roleDisplay = 'Employee';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?= $roleBadgeClass ?>"><?= $roleIcon ?> <?= $roleDisplay ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $statusBadgeClass = 'badge-success';
                                    switch($user['status']) {
                                        case 'active':
                                            $statusBadgeClass = 'badge-success';
                                            break;
                                        case 'inactive':
                                            $statusBadgeClass = 'badge-secondary';
                                            break;
                                        case 'suspended':
                                            $statusBadgeClass = 'badge-warning';
                                            break;
                                        case 'terminated':
                                            $statusBadgeClass = 'badge-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?= $statusBadgeClass ?>"><?= ucfirst($user['status']) ?></span>
                                </td>
                                <td>
                                    <div class="ab-container">
                                        <button class="ab-btn ab-btn--view" onclick="viewUser(<?= $user['id'] ?>)" data-tooltip="View Details">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                        <?php if (($_SESSION['role'] ?? '') === 'owner'): ?>
                                            <button class="ab-btn ab-btn--edit" onclick="editUser(<?= $user['id'] ?>)" data-tooltip="Edit User">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="M15 5l4 4"/></svg>
                                            </button>
                                            <button class="ab-btn ab-btn--progress" onclick="resetPassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" data-tooltip="Reset Password">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5"/></svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showDropdown(element) {
    const tooltip = element.getAttribute('title');
    if (tooltip) {
        element.setAttribute('data-original-title', tooltip);
        element.removeAttribute('title');
    }
}

function hideDropdown(element) {
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

window.editUser = function(userId) {
    showEditUserModal(userId);
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

function showAddUserModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.setAttribute('data-visible', 'true');
    modal.innerHTML = `
        <div class="modal-content" style="width: 600px;">
                <div class="modal-header">
                <h3>👤 Add New User</h3>
                <button class="modal-close" onclick="hideClosestModal(this)">&times;</button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label>Full Name *</label>
                            <input type="text" name="name" class="form-input" required>
                        </div>
                        <div>
                            <label>Email *</label>
                            <input type="email" name="email" class="form-input" required>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label>Phone</label>
                            <input type="tel" name="phone" class="form-input">
                        </div>
                        <div>
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-input">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label>Gender</label>
                            <select name="gender" class="form-input">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label>Role</label>
                            <select name="role" class="form-input">
                                <option value="user">Employee</option>
                                <option value="hr">HR</option>
                                <option value="admin">Admin</option>
                                <option value="owner">Owner</option>
                                <option value="company_owner">Company Owner</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label>Department</label>
                            <select name="department_id" class="form-input">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div>
                            <label>Designation</label>
                            <input type="text" name="designation" class="form-input">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label>Joining Date</label>
                            <input type="date" name="joining_date" class="form-input">
                        </div>
                        <div>
                            <label>Salary</label>
                            <input type="number" name="salary" class="form-input" step="0.01">
                        </div>
                    </div>
                    <div>
                        <label>Address</label>
                        <textarea name="address" class="form-input" rows="2"></textarea>
                    </div>
                    <div>
                        <label>Emergency Contact</label>
                        <input type="text" name="emergency_contact" class="form-input">
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status" class="form-input">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                    <div>
                        <label>Documents</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label style="font-size: 0.9em; color: #666;">Passport Photo</label>
                                <input type="file" name="passport_photo" class="form-input" accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div>
                                <label style="font-size: 0.9em; color: #666;">Aadhar Card</label>
                                <input type="file" name="aadhar" class="form-input" accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div>
                                <label style="font-size: 0.9em; color: #666;">PAN Card</label>
                                <input type="file" name="pan" class="form-input" accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div>
                                <label style="font-size: 0.9em; color: #666;">Resume</label>
                                <input type="file" name="resume" class="form-input" accept=".pdf,.doc,.docx">
                            </div>
                            <div>
                                <label style="font-size: 0.9em; color: #666;">Education Docs</label>
                                <input type="file" name="education_docs[]" class="form-input" multiple accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div>
                                <label style="font-size: 0.9em; color: #666;">Experience Certs</label>
                                <input type="file" name="experience_certs[]" class="form-input" multiple accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>
                        <small style="color: #666; font-size: 0.8em;">Max 5MB per file. JPG/PNG for photos, PDF/DOC for documents.</small>
                    </div>

                </form>
            </div>
                <div class="modal-footer">
                <button class="btn btn--secondary" onclick="hideClosestModal(this)">Cancel</button>
                <button class="btn btn--primary" onclick="submitUserForm()">Add User</button>
            </div>
        </div>
    `;
    
    if (!document.getElementById('modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'modal-styles';
        styles.textContent = `
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10001;
            }
            .modal-content {
                background: white;
                border-radius: 8px;
                width: 700px;
                max-width: 95vw;
                max-height: 90vh;
                overflow-y: auto;
            }
            .modal-header {
                padding: 16px;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .modal-body {
                padding: 16px;
            }
            .modal-body label {
                display: block;
                margin-bottom: 4px;
                font-weight: 500;
            }
            .modal-body .form-input {
                width: 100%;
                margin-bottom: 12px;
                padding: 8px;
                border: 1px solid #d1d5db;
                border-radius: 4px;
                box-sizing: border-box;
            }
            .modal-body textarea.form-input {
                resize: vertical;
                min-height: 60px;
            }
            .modal-body input[type="file"] {
                padding: 4px;
                font-size: 0.9em;
            }
            .modal-footer {
                padding: 16px;
                border-top: 1px solid #e5e7eb;
                display: flex;
                gap: 8px;
                justify-content: flex-end;
            }
            .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #6b7280;
            }
            .project-checkbox {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 4px 0;
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(modal);
    loadDepartments();
}

window.showEditUserModal = function(userId) {
    if (!document.getElementById('modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'modal-styles';
        styles.textContent = `.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10001}.modal-content{background:white;border-radius:8px;width:700px;max-width:95vw;max-height:90vh;overflow-y:auto}.modal-header{padding:16px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center}.modal-body{padding:16px}.modal-body label{display:block;margin-bottom:4px;font-weight:500}.modal-body .form-input{width:100%;margin-bottom:12px;padding:8px;border:1px solid #d1d5db;border-radius:4px;box-sizing:border-box}.modal-footer{padding:16px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end}.modal-close{background:none;border:none;font-size:24px;cursor:pointer}`;
        document.head.appendChild(styles);
    }
    
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.setAttribute('data-visible', 'true');
    modal.innerHTML = `<div class="modal-content" style="width:600px"><div class="modal-header"><h3>✏️ Edit User</h3><button class="modal-close" onclick="hideClosestModal(this)">&times;</button></div><div class="modal-body"><div style="text-align:center;padding:20px">Loading...</div></div><div class="modal-footer"><button class="btn btn--secondary" onclick="hideClosestModal(this)">Cancel</button></div></div>`;
    document.body.appendChild(modal);
    
    fetch(`/ergon/api/users/${userId}`)
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response:', text);
            throw new Error('Invalid JSON response');
        }
    })
        .then(data => {
        if (!data.success) {
            if (typeof hideClosestModal === 'function') hideClosestModal(modal); else if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
            alert('Failed to load user data');
            return;
        }
        
        const user = data.user;
        modal.querySelector('.modal-body').innerHTML = `<form id="userForm"><input type="hidden" name="user_id" value="${user.id}"><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div><label>Full Name *</label><input type="text" name="name" class="form-input" value="${user.name||''}" required></div><div><label>Email *</label><input type="email" name="email" class="form-input" value="${user.email||''}" required></div></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div><label>Phone</label><input type="tel" name="phone" class="form-input" value="${user.phone||''}"></div><div><label>Date of Birth</label><input type="date" name="date_of_birth" class="form-input" value="${user.date_of_birth||''}"></div></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div><label>Gender</label><select name="gender" class="form-input"><option value="">Select Gender</option><option value="male" ${user.gender==='male'?'selected':''}>Male</option><option value="female" ${user.gender==='female'?'selected':''}>Female</option><option value="other" ${user.gender==='other'?'selected':''}>Other</option></select></div><div><label>Role</label><select name="role" class="form-input"><option value="user" ${user.role==='user'?'selected':''}>Employee</option><option value="hr" ${user.role==='hr'?'selected':''}>HR</option><option value="admin" ${user.role==='admin'?'selected':''}>Admin</option><option value="owner" ${user.role==='owner'?'selected':''}>Owner</option><option value="company_owner" ${user.role==='company_owner'?'selected':''}>Company Owner</option></select></div></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div><label>Department</label><select name="department_id" class="form-input"><option value="">Loading...</option></select></div><div><label>Designation</label><input type="text" name="designation" class="form-input" value="${user.designation||''}"></div></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div><label>Joining Date</label><input type="date" name="joining_date" class="form-input" value="${user.joining_date||''}"></div><div><label>Salary</label><input type="number" name="salary" class="form-input" step="0.01" value="${user.salary||''}"></div></div><div><label>Address</label><textarea name="address" class="form-input" rows="2">${user.address||''}</textarea></div><div><label>Emergency Contact</label><input type="text" name="emergency_contact" class="form-input" value="${user.emergency_contact||''}"></div><div><label>Status</label><select name="status" class="form-input"><option value="active" ${user.status==='active'?'selected':''}>Active</option><option value="inactive" ${user.status==='inactive'?'selected':''}>Inactive</option><option value="suspended" ${user.status==='suspended'?'selected':''}>Suspended</option><option value="terminated" ${user.status==='terminated'?'selected':''}>Terminated</option></select></div></form>`;
        modal.querySelector('.modal-footer').innerHTML = `<button class="btn btn--secondary" onclick="hideClosestModal(this)">Cancel</button><button class="btn btn--primary" onclick="submitUserForm(true)">Update User</button>`;
        loadDepartments(user.department_id);
    })
    .catch(error => {
        if (typeof hideClosestModal === 'function') hideClosestModal(modal); else if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
        alert('Failed to load user data');
    });
};

function loadDepartments(selectedDept = null) {
    fetch('/ergon/api/departments')
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            const deptSelect = document.querySelector('select[name="department_id"]');
            if (data.success && data.departments) {
                deptSelect.innerHTML = '<option value="">Select Department</option>';
                data.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = dept.name;
                    if (selectedDept == dept.id) option.selected = true;
                    deptSelect.appendChild(option);
                });
            } else {
                deptSelect.innerHTML = '<option value="">Error loading departments</option>';
            }
        } catch (e) {
            console.error('JSON parse error:', e, 'Response:', text);
            const deptSelect = document.querySelector('select[name="department_id"]');
            if (deptSelect) {
                deptSelect.innerHTML = '<option value="">Error loading departments</option>';
            }
        }
    })
    .catch(error => {
        console.error('Error loading departments:', error);
        const deptSelect = document.querySelector('select[name="department_id"]');
        if (deptSelect) {
            deptSelect.innerHTML = '<option value="">Error: ' + error.message + '</option>';
        }
    });
}

function hideClosestModal(element) {
    const modal = element.closest('.modal-overlay');
    if (modal && modal.parentNode) {
        modal.parentNode.removeChild(modal);
    }
}

function submitUserForm(isEdit = false) {
    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    
    const selectedProjects = Array.from(document.querySelectorAll('input[name="projects[]"]:checked')).map(cb => cb.value);
    formData.delete('projects[]');
    selectedProjects.forEach(projectId => formData.append('projects[]', projectId));
    
    formData.append('ajax', '1');
    
    const url = isEdit ? '/ergon/users/edit' : '/ergon/users/create';
    
    fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
    .then(function(response) { return response.text(); })
    .then(function(text) {
        var data;
        try { data = JSON.parse(text); } catch(e) {
            console.error('Non-JSON response:', text.substring(0, 300));
            alert('Server error. Please refresh and try again.');
            return;
        }
        if (data.success) {
            const __existingModal = document.querySelector('.modal-overlay');
            if(__existingModal && typeof hideClosestModal === 'function') hideClosestModal(__existingModal);
            alert(isEdit ? 'User updated successfully!' : 'User created successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.error || data.message || 'Failed to save user'));
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        alert('Failed to save user. Please try again.');
    });
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

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.alert--success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert--error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert i {
    font-size: 16px;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
