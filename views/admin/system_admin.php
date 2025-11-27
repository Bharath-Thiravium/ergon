<?php
$title = 'System Admins';
$active_page = 'system-admin';

ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🔧</span> System Administrators</h1>
        <p>Manage system-level administrators and their permissions</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--primary" onclick="document.getElementById('createAdminModal').style.display='block';" id="addAdminBtn" type="button">
            <span>➕</span> Add Admin
        </button>
        <button class="btn btn--secondary" onclick="exportAdmins()">
            <span>📊</span> Export
        </button>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert--error">
    <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">
    <?= htmlspecialchars($_GET['success']) ?>
</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">↗ Active</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['admins'] ?? [], fn($a) => $a['status'] === 'active')) ?></div>
        <div class="kpi-card__label">Active Admins</div>
        <div class="kpi-card__status">Online</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🔧</div>
            <div class="kpi-card__trend">— Total</div>
        </div>
        <div class="kpi-card__value"><?= count($data['admins'] ?? []) ?></div>
        <div class="kpi-card__label">Total Admins</div>
        <div class="kpi-card__status">System</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚡</div>
            <div class="kpi-card__trend">↗ Recent</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['admins'] ?? [], fn($a) => strtotime($a['created_at']) > strtotime('-30 days'))) ?></div>
        <div class="kpi-card__label">New This Month</div>
        <div class="kpi-card__status">Added</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Administrator List</h2>
    </div>
    <div class="card__body">
        <?php if (empty($data['admins'])): ?>
            <div class="empty-state">
                <div class="empty-icon">🔧</div>
                <h3>No System Administrators</h3>
                <p>Create your first system administrator to get started.</p>
                <button class="btn btn--primary" onclick="showCreateAdminModal()">
                    <span>➕</span> Create First Admin
                </button>
            </div>
        <?php else: ?>
            <div class="admin-grid">
                <?php foreach ($data['admins'] as $admin): ?>
                <div class="admin-card">
                    <div class="admin-card__header">
                        <div class="user-avatar user-avatar--lg"><?= strtoupper(substr($admin['name'], 0, 1)) ?></div>
                        <div class="admin-card__status">
                            <label class="toggle-switch">
                                <input type="checkbox" <?= $admin['status'] === 'active' ? 'checked' : '' ?> 
                                       <?= $admin['status'] === 'terminated' ? 'disabled' : '' ?>
                                       onchange="toggleStatus(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['name']) ?>', this.checked, '<?= $admin['status'] ?>')">
                                <span class="toggle-slider <?= $admin['status'] === 'terminated' ? 'toggle-slider--disabled' : '' ?>"></span>
                                <span class="toggle-label"><?= ucfirst($admin['status']) ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="admin-card__body">
                        <h3 class="admin-card__name"><?= htmlspecialchars($admin['name']) ?></h3>
                        <p class="admin-card__email"><?= htmlspecialchars($admin['email']) ?></p>
                        <p class="admin-card__role">System Administrator</p>
                        <p class="admin-card__date">Created: <?= date('M d, Y', strtotime($admin['created_at'])) ?></p>
                    </div>
                    <div class="admin-card__actions">
                        <button class="btn btn--sm btn--secondary" onclick="changePassword(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['name']) ?>')">
                            <span>🔑</span> Change Password
                        </button>
                        <button class="btn btn--sm btn--delete" onclick="deleteAdmin(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['name']) ?>')">
                            <span>🗑️</span> Delete
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.btn--delete {
    background: #f3f4f6 !important;
    color: #dc2626 !important;
    border-color: #e5e7eb !important;
}
.btn--delete:hover {
    background: #fef2f2 !important;
    border-color: #fecaca !important;
    color: #b91c1c !important;
}
.toggle-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-slider {
    position: relative;
    width: 44px;
    height: 24px;
    background-color: #ccc;
    border-radius: 24px;
    transition: 0.3s;
    cursor: pointer;
}
.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: 0.3s;
}
.toggle-switch input:checked + .toggle-slider {
    background-color: #10b981;
}
.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}
.toggle-label {
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
}
.toggle-slider--disabled {
    background-color: #f3f4f6 !important;
    cursor: not-allowed !important;
}
.toggle-slider--disabled:before {
    background-color: #d1d5db !important;
}
.toggle-switch input:disabled + .toggle-slider {
    background-color: #f3f4f6 !important;
    cursor: not-allowed !important;
}
.toggle-switch input:disabled + .toggle-slider:before {
    background-color: #d1d5db !important;
}
#createAdminModal {
    position: fixed !important;
    top: 110px !important;
    left: 0 !important;
    width: 100% !important;
    height: calc(100% - 110px) !important;
    background: rgba(0, 0, 0, 0.5) !important;
    z-index: 1000 !important;
    display: none !important;
}
#createAdminModal[style*="display: block"] {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}
#createAdminModal .modal-content {
    position: relative !important;
    background: white !important;
    margin: 5% auto !important;
    padding: 0 !important;
    width: 90% !important;
    max-width: 500px !important;
    border-radius: 8px !important;
    z-index: 1001 !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
}
.modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}
.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-close:hover {
    color: #374151;
}
.modal-body {
    padding: 1.5rem;
}
.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}
.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}
.admin-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.admin-card__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
.user-avatar--lg {
    width: 50px;
    height: 50px;
}
.admin-card__body {
    margin-bottom: 1rem;
}
.admin-card__name {
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
}
.admin-card__email {
    margin: 0 0 0.25rem 0;
    color: #6b7280;
}
.admin-card__role {
    margin: 0 0 0.25rem 0;
    font-size: 0.875rem;
    color: #059669;
    font-weight: 500;
}
.admin-card__date {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
}
.admin-card__actions {
    display: flex;
    gap: 0.5rem;
}
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}
.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}
.password-input-container {
    position: relative;
    display: flex;
    align-items: center;
}
.password-input-container .form-control {
    padding-right: 45px;
}
.password-toggle {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    color: #6b7280;
    font-size: 16px;
    z-index: 10;
}
.password-toggle:hover {
    color: #374151;
}
.password-toggle-icon {
    display: inline-block;
    transition: opacity 0.2s;
}

</style>

<!-- Create Admin Modal -->
<div class="modal" id="createAdminModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create System Admin</h3>
            <button class="modal-close" onclick="closeModal('createAdminModal')">&times;</button>
        </div>
        <form method="POST" action="/ergon/system-admin/create" id="createAdminForm">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Initial Password</label>
                    <div class="password-input-container">
                        <input type="password" name="password" class="form-control" id="adminPassword" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('adminPassword', this)">
                            <span class="password-toggle-icon">👁️</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeModal('createAdminModal')">Cancel</button>
                <button type="submit" class="btn btn--primary">Create Admin</button>
            </div>
        </form>
    </div>
</div>



<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('.password-toggle-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
    } else {
        input.type = 'password';
        icon.textContent = '👁️';
    }
}

function showCreateAdminModal() {
    console.log('Modal function called');
    const modal = document.getElementById('createAdminModal');
    console.log('Modal element:', modal);
    if (modal) {
        modal.style.display = 'block';
        modal.style.visibility = 'visible';
        document.body.style.overflow = 'hidden';
        console.log('Modal should be visible now');
    } else {
        console.error('Modal element not found');
    }
    return false;
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
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
        formData.append('admin_id', userId);
        formData.append('password', newPassword);
        formData.append('confirm_password', newPassword);
        
        fetch('/ergon/system-admin/change-password', {
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
            alert('Failed to change password. Please try again.');
        });
    }
}

function deleteAdmin(adminId, adminName) {
    if (confirm(`Are you sure you want to permanently delete admin "${adminName}"? This action cannot be undone and will remove all associated data.`)) {
        const formData = new FormData();
        formData.append('admin_id', adminId);
        
        fetch('/ergon/system-admin/delete-admin', {
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
            alert('Failed to delete admin. Please try again.');
        });
    }
}

function toggleStatus(adminId, adminName, isActive, currentStatus) {
    // Check if user is terminated
    if (currentStatus === 'terminated') {
        alert('This user is terminated and cannot be reactivated.');
        event.target.checked = false;
        return;
    }
    
    const action = isActive ? 'activate' : 'deactivate';
    const message = `Are you sure you want to ${action} admin "${adminName}"?`;
    
    if (confirm(message)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/ergon/system-admin/toggle-status';
        
        const adminIdInput = document.createElement('input');
        adminIdInput.type = 'hidden';
        adminIdInput.name = 'admin_id';
        adminIdInput.value = adminId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = isActive ? 'active' : 'inactive';
        
        form.appendChild(adminIdInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    } else {
        // Revert toggle if cancelled
        event.target.checked = !isActive;
    }
}

function exportAdmins() {
    window.location.href = '/ergon/system-admin/export';
}

// Ensure form submission works
document.addEventListener('DOMContentLoaded', function() {
    // Add fallback event listener for Add Admin button
    const addAdminBtn = document.getElementById('addAdminBtn');
    if (addAdminBtn) {
        addAdminBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showCreateAdminModal();
        });
    }
    
    const form = document.getElementById('createAdminForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const name = form.querySelector('input[name="name"]').value.trim();
            const email = form.querySelector('input[name="email"]').value.trim();
            const password = form.querySelector('input[name="password"]').value;
            
            if (!name || !email || !password) {
                e.preventDefault();
                alert('All fields are required.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
            
            console.log('Form submitting...', {
                name: name,
                email: email,
                passwordLength: password.length
            });
        });
    }
});

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const createModal = document.getElementById('createAdminModal');
    
    if (event.target === createModal) {
        closeModal('createAdminModal');
    }
});


</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
