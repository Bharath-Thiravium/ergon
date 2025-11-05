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
        <a href="/ergon/users" class="btn btn--secondary">
            <span>←</span> Back to Users
        </a>
    </div>
</div>

<div class="user-profile">
    <div class="card">
        <div class="card__header">
            <h2>Personal Information</h2>
        </div>
        <div class="card__body">
            <div class="profile-grid">
                <div class="profile-item">
                    <label>Full Name</label>
                    <span><?= htmlspecialchars($user['name'] ?? 'N/A') ?></span>
                </div>
                <div class="profile-item">
                    <label>Employee ID</label>
                    <span><?= htmlspecialchars($user['employee_id'] ?? 'N/A') ?></span>
                </div>
                <div class="profile-item">
                    <label>Email</label>
                    <span><?= htmlspecialchars($user['email'] ?? 'N/A') ?></span>
                </div>
                <div class="profile-item">
                    <label>Phone</label>
                    <span><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></span>
                </div>
                <div class="profile-item">
                    <label>Date of Birth</label>
                    <span><?= $user['date_of_birth'] ? date('M d, Y', strtotime($user['date_of_birth'])) : 'N/A' ?></span>
                </div>
                <div class="profile-item">
                    <label>Gender</label>
                    <span><?= ucfirst($user['gender'] ?? 'N/A') ?></span>
                </div>
                <div class="profile-item">
                    <label>Address</label>
                    <span><?= htmlspecialchars($user['address'] ?? 'N/A') ?></span>
                </div>
                <div class="profile-item">
                    <label>Emergency Contact</label>
                    <span><?= htmlspecialchars($user['emergency_contact'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <h2>Employment Details</h2>
        </div>
        <div class="card__body">
            <div class="profile-grid">
                <div class="profile-item">
                    <label>Designation</label>
                    <span><?= htmlspecialchars($user['designation'] ?? 'N/A') ?></span>
                </div>
                <div class="profile-item">
                    <label>Joining Date</label>
                    <span><?= $user['joining_date'] ? date('M d, Y', strtotime($user['joining_date'])) : 'N/A' ?></span>
                </div>
                <div class="profile-item">
                    <label>Salary</label>
                    <span><?= $user['salary'] ? '₹' . number_format($user['salary'], 2) : 'N/A' ?></span>
                </div>
                <div class="profile-item">
                    <label>Role</label>
                    <span class="badge badge--<?= $user['role'] === 'admin' ? 'warning' : 'info' ?>">
                        <?= ucfirst($user['role'] ?? 'N/A') ?>
                    </span>
                </div>
                <div class="profile-item">
                    <label>Status</label>
                    <span class="badge badge--<?= $user['status'] === 'active' ? 'success' : 'error' ?>">
                        <?= ucfirst($user['status'] ?? 'N/A') ?>
                    </span>
                </div>
                <div class="profile-item">
                    <label>Department</label>
                    <span>
                        <?php if (!empty($user['department_name'])): ?>
                            <span class="badge badge--info"><?= htmlspecialchars($user['department_name']) ?></span>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <h2>Documents</h2>
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

<style>
.documents-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

@media (max-width: 900px) {
    .documents-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .documents-grid {
        grid-template-columns: 1fr;
    }
}

.document-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
    text-align: center;
    min-height: 140px;
}

.document-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
}

.document-info {
    flex: 1;
    margin-bottom: 1rem;
}

.document-name {
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.document-size {
    font-size: 0.8rem;
    color: #6b7280;
}

.document-actions {
    width: 100%;
}

.document-actions .btn {
    width: 100%;
    justify-content: center;
}
</style>
            <?php endif; ?>
        </div>
    </div>
</div>



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
