<?php
$title = 'Department Management';
$active_page = 'departments';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🏢</span> Department Management</h1>
        <p>Manage organizational departments and structure</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/departments/create" class="btn btn--primary">
            <span>➕</span> Create Department
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏢</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ Active</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['total_departments'] ?></div>
        <div class="kpi-card__label">Total Departments</div>
        <div class="kpi-card__status kpi-card__status--info">All</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ Running</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['active_departments'] ?></div>
        <div class="kpi-card__label">Active Departments</div>
        <div class="kpi-card__status kpi-card__status--active">Operational</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— Total</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['total_employees'] ?></div>
        <div class="kpi-card__label">Total Employees</div>
        <div class="kpi-card__status kpi-card__status--info">Assigned</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">All Departments</h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Department Name</th>
                        <th>Description</th>
                        <th>Department Head</th>
                        <th>Employees</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['departments'] as $dept): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                        <td><?= htmlspecialchars(substr($dept['description'], 0, 50)) ?>...</td>
                        <td><?= $dept['head_name'] ? htmlspecialchars($dept['head_name']) : 'Not Assigned' ?></td>
                        <td><?= $dept['employee_count'] ?></td>
                        <td>
                            <span class="badge badge--<?= $dept['status'] === 'active' ? 'success' : 'warning' ?>">
                                <?= ucfirst($dept['status']) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($dept['created_at'])) ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="/ergon/departments/view/<?= $dept['id'] ?>" class="btn btn--sm btn--primary" title="View Details">
                                    <span>👁️</span> View
                                </a>
                                <a href="/ergon/departments/edit/<?= $dept['id'] ?>" class="btn btn--sm btn--secondary" title="Edit Department">
                                    <span>✏️</span> Edit
                                </a>
                                <button onclick="deleteRecord('departments', <?= $dept['id'] ?>, '<?= htmlspecialchars($dept['name']) ?>')" class="btn btn--sm btn--danger" title="Delete Department">
                                    <span>🗑️</span> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
