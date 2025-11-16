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
                                <a href="/ergon/users/download-document/<?= $user['id'] ?>/<?= urlencode($doc['filename']) ?>" 
                                   class="btn btn--sm btn--primary">Download</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
