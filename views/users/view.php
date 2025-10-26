<?php
$title = 'View User';
$active_page = 'users';
$user = $data['user'];
$documents = $data['documents'] ?? [];
ob_start();
?>

<div class="header-actions">
    <a href="/ergon/public/users/edit/<?= $user['id'] ?>" class="btn btn--primary">Edit User</a>
    <a href="/ergon/public/users" class="btn btn--secondary">Back to Users</a>
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
                    <label>Departments</label>
                    <span>
                        <?php 
                        $departments = explode(',', $user['department'] ?? '');
                        $departments = array_filter($departments);
                        if (count($departments) > 0) {
                            foreach ($departments as $dept) {
                                echo '<span class="badge badge--info">' . htmlspecialchars(trim($dept)) . '</span> ';
                            }
                        } else {
                            echo 'N/A';
                        }
                        ?>
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
                                <a href="/ergon/public/users/download-document/<?= $user['id'] ?>/<?= urlencode($doc['filename']) ?>" 
                                   class="btn btn--sm btn--primary">Download</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.profile-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.profile-item label {
    font-weight: bold;
    color: #666;
    font-size: 14px;
}

.profile-item span {
    font-size: 16px;
}

.documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}

.document-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}

.document-icon {
    font-size: 24px;
}

.document-info {
    flex: 1;
}

.document-name {
    font-weight: bold;
    margin-bottom: 5px;
}

.document-size {
    color: #666;
    font-size: 14px;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
