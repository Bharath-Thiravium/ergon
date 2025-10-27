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
                        <button class="btn btn--sm btn--secondary" onclick="editAdmin(<?= $admin['id'] ?>)">
                            <span>✏️</span> Edit
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

<!-- Edit Admin Modal -->
<div class="modal" id="editAdminModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit System Admin</h3>
            <button class="modal-close" onclick="closeModal('editAdminModal')">&times;</button>
        </div>
        <form method="POST" action="/ergon/system-admin/edit" id="editAdminForm">
            <input type="hidden" name="admin_id" id="editAdminId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" id="editAdminName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" id="editAdminEmail" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeModal('editAdminModal')">Cancel</button>
                <button type="submit" class="btn btn--primary">Update Admin</button>
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

function editAdmin(adminId) {
    // Find admin data from the page
    const adminCards = document.querySelectorAll('.admin-card');
    let adminData = null;
    
    adminCards.forEach(card => {
        const editBtn = card.querySelector('button[onclick*="editAdmin(' + adminId + ')"]');
        if (editBtn) {
            adminData = {
                id: adminId,
                name: card.querySelector('.admin-card__name').textContent,
                email: card.querySelector('.admin-card__email').textContent
            };
        }
    });
    
    if (adminData) {
        document.getElementById('editAdminId').value = adminData.id;
        document.getElementById('editAdminName').value = adminData.name;
        document.getElementById('editAdminEmail').value = adminData.email;
        document.getElementById('editAdminModal').style.display = 'block';
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
    const editModal = document.getElementById('editAdminModal');
    
    if (event.target === createModal) {
        closeModal('createAdminModal');
    }
    if (event.target === editModal) {
        closeModal('editAdminModal');
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
