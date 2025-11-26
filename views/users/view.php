<?php
$title = 'View User';
$active_page = 'users';
$user = $data['user'];
$documents = $data['documents'] ?? [];
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👥</span> User Details</h1>
        <p>View user information and employment details</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/users/edit/<?= $user['id'] ?>" class="btn btn--primary">
            <span>✏️</span> Edit User
        </a>
        <a href="<?= in_array($_SESSION['role'] ?? '', ['admin', 'owner']) ? '/ergon/admin/management' : '/ergon/users' ?>" class="btn btn--secondary">
            <span>←</span> Back to Users
        </a>
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
                    $statusClass = $status === 'active' ? 'success' : 'danger';
                    $roleClass = match($role) {
                        'owner' => 'danger',
                        'admin' => 'warning',
                        default => 'info'
                    };
                    $statusIcon = $status === 'active' ? '✅' : '❌';
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

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
