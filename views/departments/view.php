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
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>