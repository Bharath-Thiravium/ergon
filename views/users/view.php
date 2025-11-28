<?php
$title = 'View User';
$active_page = 'users';
$user = $data['user'];
$documents = $data['documents'] ?? [];

// Check access permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['owner', 'admin'])) {
    header('Location: /ergon/login');
    exit;
}

ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👥</span> User Details</h1>
        <p>View user information and employment details</p>
    </div>
    <div class="page-actions">
        <?php 
        $userStatus = $user['status'] ?? 'active';
        $userId = $user['id'];
        $userName = htmlspecialchars($user['name']);
        ?>
        
        <?php if (($_SESSION['role'] ?? '') === 'admin' && in_array(($user['role'] ?? 'user'), ['admin', 'owner'])): ?>
            <!-- Admins cannot manage other admins/owners -->
            <span class="btn btn--secondary disabled">
                <span>🔒</span> Protected User
            </span>
        <?php else: ?>
            <?php if ($userStatus === 'terminated'): ?>
                <!-- Terminated Users: Only show terminated message -->
                <span class="btn btn--secondary disabled">
                    <span>❌</span> User Terminated
                </span>
            <?php elseif ($userStatus === 'suspended'): ?>
                <!-- Suspended Users: Activate + Edit + Reset Password + Terminate -->
                <button class="btn btn--success" data-action="activate" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>">
                    <span>✅</span> Activate
                </button>
                <a href="/ergon/users/edit/<?= $userId ?>" class="btn btn--primary">
                    <span>✏️</span> Edit
                </a>
                <button class="btn btn--warning" data-action="reset" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>">
                    <span>🔑</span> Reset Password
                </button>
                <button class="btn btn--danger" data-action="terminate" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>">
                    <span>❌</span> Terminate
                </button>
            <?php elseif ($userStatus === 'active'): ?>
                <!-- Active Users: Edit + Reset Password + Deactivate + Suspend + Terminate -->
                <a href="/ergon/users/edit/<?= $userId ?>" class="btn btn--primary">
                    <span>✏️</span> Edit
                </a>
                <button class="btn btn--warning" data-action="reset" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>">
                    <span>🔑</span> Reset Password
                </button>
                <button class="btn btn--secondary" data-action="inactive" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>">
                    <span>⏸️</span> Deactivate
                </button>
                <button class="btn btn--danger" data-action="suspend" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>">
                    <span>👥➖</span> Suspend
                </button>
                <button class="btn btn--danger" data-action="terminate" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>">
                    <span>❌</span> Terminate
                </button>
            <?php else: ?>
                <!-- Inactive Users: Edit + Reset Password + Activate -->
                <a href="/ergon/users/edit/<?= $userId ?>" class="btn btn--primary">
                    <span>✏️</span> Edit
                </a>
                <button class="btn btn--warning" data-action="reset" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>">
                    <span>🔑</span> Reset Password
                </button>
                <button class="btn btn--success" data-action="activate" data-module="users" data-id="<?= $userId ?>" data-name="<?= $userName ?>">
                    <span>✅</span> Activate
                </button>
            <?php endif; ?>
        <?php endif; ?>
        
        <a href="/ergon/users" class="btn btn--secondary">
            <span>←</span> Back to Users
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👤</div>
            <div class="kpi-card__trend">— Status</div>
        </div>
        <div class="kpi-card__value"><?= ucfirst($user['status'] ?? 'active') ?></div>
        <div class="kpi-card__label">User Status</div>
        <div class="kpi-card__status"><?= match($user['status'] ?? 'active') {
            'active' => 'Online',
            'inactive' => 'Offline', 
            'suspended' => 'Restricted',
            'terminated' => 'Disabled',
            default => 'Unknown'
        } ?></div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon"><?= match($user['role'] ?? 'user') { 'owner' => '👑', 'admin' => '🔑', default => '👤' } ?></div>
            <div class="kpi-card__trend">— Role</div>
        </div>
        <div class="kpi-card__value"><?= ucfirst($user['role'] ?? 'user') ?></div>
        <div class="kpi-card__label">Access Level</div>
        <div class="kpi-card__status"><?= match($user['role'] ?? 'user') {
            'owner' => 'Full Access',
            'admin' => 'Elevated',
            default => 'Standard'
        } ?></div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend">— Joined</div>
        </div>
        <div class="kpi-card__value"><?= $user['joining_date'] ? date('M Y', strtotime($user['joining_date'])) : 'N/A' ?></div>
        <div class="kpi-card__label">Member Since</div>
        <div class="kpi-card__status">Registered</div>
    </div>
</div>

<div class="user-compact">
    <div class="card">
        <div class="card__header">
            <div class="user-title-row">
                <h2 class="user-title">👤 <?= htmlspecialchars($user['name'] ?? 'User Profile') ?></h2>
                <div class="user-badges">
                    <?php 
                    $status = $user['status'] ?? 'active';
                    $role = $user['role'] ?? 'user';
                    
                    $statusClass = match($status) {
                        'active' => 'success',
                        'inactive' => 'warning', 
                        'suspended' => 'danger',
                        'terminated' => 'dark',
                        default => 'secondary'
                    };
                    
                    $roleClass = match($role) {
                        'owner' => 'danger',
                        'admin' => 'warning',
                        default => 'info'
                    };
                    
                    $statusIcon = match($status) {
                        'active' => '✅',
                        'inactive' => '⏸️',
                        'suspended' => '⚠️',
                        'terminated' => '❌',
                        default => '❓'
                    };
                    
                    $roleIcon = match($role) {
                        'owner' => '👑',
                        'admin' => '👔',
                        default => '👤'
                    };
                    ?>
                    <span class="badge badge--<?= $statusClass ?>"><?= $statusIcon ?> <?= ucfirst($status) ?></span>
                    <span class="badge badge--<?= $roleClass ?>"><?= $roleIcon ?> <?= ucfirst($role) ?></span>
                </div>
            </div>
        </div>
        <div class="card__body">
            <div class="details-compact">
                <div class="detail-group">
                    <h4>👤 Personal Information</h4>
                    <div class="detail-items">
                        <span><strong>Employee ID:</strong> 🆔 <?= htmlspecialchars($user['employee_id'] ?? 'N/A') ?></span>
                        <span><strong>Email:</strong> 📧 <?= htmlspecialchars($user['email'] ?? 'N/A') ?></span>
                        <span><strong>Phone:</strong> 📱 <?= htmlspecialchars($user['phone'] ?? 'N/A') ?></span>
                        <span><strong>Date of Birth:</strong> 🎂 <?= $user['date_of_birth'] ? date('M d, Y', strtotime($user['date_of_birth'])) : 'N/A' ?></span>
                        <span><strong>Gender:</strong> 👤 <?= ucfirst($user['gender'] ?? 'N/A') ?></span>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>🏢 Employment Details</h4>
                    <div class="detail-items">
                        <span><strong>Designation:</strong> 💼 <?= htmlspecialchars($user['designation'] ?? 'N/A') ?></span>
                        <span><strong>Department:</strong> 🏢 
                            <?php if (!empty($user['department_name'])): ?>
                                <span class="badge badge--info"><?= htmlspecialchars($user['department_name']) ?></span>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </span>
                        <span><strong>Joining Date:</strong> 📅 <?= $user['joining_date'] ? date('M d, Y', strtotime($user['joining_date'])) : 'N/A' ?></span>
                        <span><strong>Salary:</strong> 💰 <?= $user['salary'] ? '₹' . number_format($user['salary'], 2) : 'N/A' ?></span>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>📞 Contact Information</h4>
                    <div class="detail-items">
                        <span><strong>Address:</strong> 🏠 <?= htmlspecialchars($user['address'] ?? 'N/A') ?></span>
                        <span><strong>Emergency Contact:</strong> 🆘 <?= htmlspecialchars($user['emergency_contact'] ?? 'N/A') ?></span>
                        <span><strong>Created:</strong> 📅 <?= isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📄</span> Documents
            </h2>
        </div>
        <div class="card__body">
            <?php if (empty($documents)): ?>
                <p>No documents uploaded.</p>
            <?php else: ?>
                <div class="documents-grid">
                    <?php foreach ($documents as $doc): ?>
                        <div class="document-item">
                            <div class="document-icon">📄</div>
                            <div class="document-info">
                                <div class="document-name"><?= htmlspecialchars($doc['name']) ?></div>
                                <div class="document-size"><?= $doc['size'] ?></div>
                            </div>
                            <div class="document-actions">
                                <div class="ab-container">
                                    <a href="/ergon/public/uploads/users/<?= $user['id'] ?>/<?= urlencode($doc['filename']) ?>" 
                                       class="ab-btn ab-btn--view" 
                                       target="_blank" 
                                       title="View Document">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="/ergon/users/download-document/<?= $user['id'] ?>/<?= urlencode($doc['filename']) ?>" 
                                       class="ab-btn ab-btn--edit" 
                                       title="Download Document">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>



<style>
.user-compact {
    max-width: 1000px;
    margin: 0 auto;
}

.user-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
    gap: 1.5rem;
    min-height: 2rem;
}

.user-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1 1 auto;
    min-width: 200px;
    max-width: calc(100% - 200px);
    overflow-wrap: break-word;
    word-break: break-word;
    line-height: 1.3;
}

.user-badges {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 0 0 auto;
    min-width: 180px;
    justify-content: flex-end;
}

.details-compact {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.detail-group {
    background: var(--bg-secondary);
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.detail-group h4 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
    color: var(--primary);
    font-weight: 600;
}

.detail-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-items span {
    font-size: 0.85rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-items strong {
    color: var(--text-primary);
    min-width: 80px;
    font-size: 0.8rem;
}

.text-muted {
    color: var(--text-tertiary) !important;
    font-style: italic;
}

.documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.document-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
}

.document-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.document-info {
    flex: 1;
    min-width: 0;
}

.document-name {
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
    word-break: break-word;
}

.document-size {
    font-size: 0.8rem;
    color: var(--text-secondary);
}

.document-actions {
    flex-shrink: 0;
}

.btn.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

@media (max-width: 768px) {
    .user-title-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        min-height: auto;
    }
    
    .user-title {
        max-width: 100%;
        min-width: auto;
    }
    
    .user-badges {
        width: 100%;
        min-width: auto;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    
    .details-compact {
        grid-template-columns: 1fr;
    }
    
    .documents-grid {
        grid-template-columns: 1fr;
    }
    
    .document-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
// Global action button handler
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn[data-action]');
    if (!btn) return;
    
    const action = btn.dataset.action;
    const module = btn.dataset.module;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    
    if (action === 'inactive' && module && id && name) {
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
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
