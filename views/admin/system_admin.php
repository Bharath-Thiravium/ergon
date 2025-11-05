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
        <button class="btn btn--primary" onclick="showCreateAdminModal()">
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
                                       onchange="toggleStatus(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['name']) ?>', this.checked)">
                                <span class="toggle-slider"></span>
                                <span class="toggle-label"><?= $admin['status'] === 'active' ? 'Active' : 'Inactive' ?></span>
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
                        <button class="btn btn--sm btn--secondary" onclick="changePassword(<?= $admin['id'] ?>)">
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
.modal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0, 0, 0, 0.5) !important;
    z-index: 10000 !important;
    display: none !important;
}
.modal-content {
    position: relative !important;
    background: white !important;
    margin: 5% auto !important;
    padding: 0 !important;
    width: 90% !important;
    max-width: 500px !important;
    border-radius: 8px !important;
    z-index: 10001 !important;
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
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeModal('createAdminModal')">Cancel</button>
                <button type="submit" class="btn btn--primary">Create Admin</button>
            </div>
        </form>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal" id="changePasswordModal" style="z-index: 10001 !important;">
    <div class="modal-content" style="z-index: 10002 !important;">
        <div class="modal-header">
            <h3>Change Admin Password</h3>
            <button class="modal-close" onclick="closeModal('changePasswordModal')">&times;</button>
        </div>
        <form method="POST" action="/ergon/system-admin/change-password" id="changePasswordForm">
            <input type="hidden" name="admin_id" id="changePasswordId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Admin Name</label>
                    <input type="text" id="changePasswordName" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeModal('changePasswordModal')">Cancel</button>
                <button type="submit" class="btn btn--primary">Change Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateAdminModal() {
    document.getElementById('createAdminModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function changePassword(adminId) {
    const adminCards = document.querySelectorAll('.admin-card');
    let adminData = null;
    
    adminCards.forEach(card => {
        const btn = card.querySelector('button[onclick*="changePassword(' + adminId + ')"]');
        if (btn) {
            adminData = {
                id: adminId,
                name: card.querySelector('.admin-card__name').textContent
            };
        }
    });
    
    if (adminData) {
        document.getElementById('changePasswordId').value = adminData.id;
        document.getElementById('changePasswordName').value = adminData.name;
        document.getElementById('changePasswordModal').style.display = 'block';
    }
}

function deleteAdmin(adminId, adminName) {
    if (confirm('Are you sure you want to permanently delete admin "' + adminName + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/ergon/system-admin/delete';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'admin_id';
        input.value = adminId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleStatus(adminId, adminName, isActive) {
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
    const form = document.getElementById('createAdminForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Let the form submit normally
            console.log('Form submitting...');
        });
    }
});

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const createModal = document.getElementById('createAdminModal');
    const passwordModal = document.getElementById('changePasswordModal');
    
    if (event.target === createModal) {
        closeModal('createAdminModal');
    }
    if (event.target === passwordModal) {
        closeModal('changePasswordModal');
    }
});

// Add password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const passwordForm = document.getElementById('changePasswordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const password = this.querySelector('input[name="password"]').value;
            const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
