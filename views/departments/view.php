<?php
$title = 'Department Details';
$active_page = 'departments';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🏢</span> Department Details</h1>
        <p>View department information and employees</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/departments" class="btn btn--secondary">
            <span>←</span> Back to Departments
        </a>
    </div>
</div>

<div class="department-compact">
    <div class="card">
        <div class="card__header">
            <div class="department-title-row">
                <h2 class="department-title">🏢 <?= htmlspecialchars($department['name'] ?? 'Department') ?></h2>
                <div class="department-badges">
                    <?php 
                    $status = $department['status'] ?? 'active';
                    $statusClass = $status === 'active' ? 'success' : 'warning';
                    $statusIcon = $status === 'active' ? '✅' : '⚠️';
                    $employeeCount = $department['employee_count'] ?? 0;
                    ?>
                    <span class="badge badge--<?= $statusClass ?>"><?= $statusIcon ?> <?= ucfirst($status) ?></span>
                    <div class="count-display">
                        <span class="count-text"><?= $employeeCount ?> employee<?= $employeeCount != 1 ? 's' : '' ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card__body">
            <?php if ($department['description']): ?>
            <div class="description-compact">
                <strong>Description:</strong> <?= nl2br(htmlspecialchars($department['description'])) ?>
            </div>
            <?php endif; ?>
            
            <div class="details-compact">
                <div class="detail-group">
                    <h4>🏢 Department Info</h4>
                    <div class="detail-items">
                        <span><strong>Name:</strong> 🏢 <?= htmlspecialchars($department['name'] ?? 'N/A') ?></span>
                        <span><strong>Head:</strong> 👤 <?= htmlspecialchars($department['head_name'] ?? 'Not Assigned') ?></span>
                        <span><strong>Status:</strong> 
                            <span class="badge badge--<?= $statusClass ?>"><?= $statusIcon ?> <?= ucfirst($status) ?></span>
                        </span>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>📊 Statistics</h4>
                    <div class="detail-items">
                        <span><strong>Employees:</strong> 👥 <?= $employeeCount ?></span>
                        <span><strong>Created:</strong> 📅 <?= date('M d, Y', strtotime($department['created_at'] ?? 'now')) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Department Members Section -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">👥 Department Members</h3>
        <span class="badge badge--info"><?= count($employees ?? []) ?> members</span>
    </div>
    <div class="card__body">
        <?php if (empty($employees)): ?>
            <div class="empty-state">
                <p>No employees assigned to this department yet.</p>
            </div>
        <?php else: ?>
            <div class="employees-grid">
                <?php foreach ($employees as $employee): ?>
                    <div class="employee-card">
                        <div class="employee-info">
                            <h4 class="employee-name"><?= htmlspecialchars($employee['name']) ?></h4>
                            <p class="employee-email"><?= htmlspecialchars($employee['email']) ?></p>
                            <?php if (!empty($employee['phone'])): ?>
                                <p class="employee-phone">📞 <?= htmlspecialchars($employee['phone']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="employee-badges">
                            <span class="badge badge--<?= $employee['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                <?= $employee['role'] === 'admin' ? '👑' : '👤' ?> <?= ucfirst($employee['role']) ?>
                            </span>
                            <span class="badge badge--<?= $employee['status'] === 'active' ? 'success' : 'warning' ?>">
                                <?= $employee['status'] === 'active' ? '✅' : '⚠️' ?> <?= ucfirst($employee['status']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.department-compact {
    max-width: 1000px;
    margin: 0 auto;
}

.department-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
    gap: 1.5rem;
    min-height: 2rem;
}

.department-title {
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

.department-badges {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 0 0 auto;
    min-width: 180px;
    justify-content: flex-end;
}

.count-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.count-text {
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary);
    background: var(--bg-secondary);
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border-color);
}

.description-compact {
    background: var(--bg-secondary);
    padding: 0.75rem;
    border-radius: 6px;
    border-left: 3px solid var(--primary);
    margin-bottom: 1rem;
    font-size: 0.9rem;
    line-height: 1.4;
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
    min-width: 60px;
    font-size: 0.8rem;
}

/* Department Members Styles */
.card:has(.employees-grid) {
    width: 1000px;
    height: 3349.05px;
    overflow-y: auto;
    margin: 0 auto;
}

.employees-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.employee-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.2s ease;
}

.employee-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: var(--primary);
}

.employee-info {
    margin-bottom: 0.75rem;
}

.employee-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.employee-email {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin: 0 0 0.25rem 0;
}

.employee-phone {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin: 0;
}

.employee-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .department-title-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        min-height: auto;
    }
    
    .department-title {
        max-width: 100%;
        min-width: auto;
    }
    
    .department-badges {
        width: 100%;
        min-width: auto;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    
    .details-compact {
        grid-template-columns: 1fr;
    }
    
    .employees-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>