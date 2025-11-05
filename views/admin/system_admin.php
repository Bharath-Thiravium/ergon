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
                            <span class="badge badge--<?= $admin['status'] === 'active' ? 'success' : 'warning' ?>">
                                <?= ucfirst($admin['status']) ?>
                            </span>
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
                        <button class="btn btn--sm btn--danger" onclick="deleteAdmin(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['name']) ?>')">
                            <span>🗑️</span> Delete
                        </button>
                        <?php if ($admin['status'] === 'active'): ?>
                        <button class="btn btn--sm btn--warning" onclick="deactivateAdmin(<?= $admin['id'] ?>)">
                            <span>⏸️</span> Deactivate
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.btn--danger {
    background: #dc2626 !important;
    color: #ffffff !important;
    border-color: #dc2626 !important;
}
.btn--danger:hover {
    background: #b91c1c !important;
    border-color: #b91c1c !important;
    color: #ffffff !important;
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

function deactivateAdmin(adminId) {
    if (confirm('Are you sure you want to deactivate this admin?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/ergon/system-admin/deactivate';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'admin_id';
        input.value = adminId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
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
