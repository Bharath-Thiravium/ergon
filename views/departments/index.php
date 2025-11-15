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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['departments'] as $dept): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($dept['name']) ?></strong>
                            <br><small class="text-muted">Dept ID: <?= $dept['id'] ?></small>
                        </td>
                        <td><?= htmlspecialchars(substr($dept['description'], 0, 60)) ?>...</td>
                        <td>
                            <strong><?= $dept['head_name'] ? htmlspecialchars($dept['head_name']) : 'Not Assigned' ?></strong>
                            <br><small class="text-muted"><?= $dept['head_name'] ? 'Department Head' : 'Position Vacant' ?></small>
                        </td>
                        <td>
                            <strong><?= $dept['employee_count'] ?></strong> employees
                        </td>
                        <td><span class="badge badge--<?= $dept['status'] === 'active' ? 'success' : 'warning' ?>"><?= ucfirst($dept['status']) ?></span></td>
                        <td>
                            <div class="ab-container">
                                <a class="ab-btn ab-btn--view" data-action="view" data-module="departments" data-id="<?= $dept['id'] ?>" title="View Details">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                <a class="ab-btn ab-btn--edit" data-action="edit" data-module="departments" data-id="<?= $dept['id'] ?>" title="Edit Department">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </a>
                                <button class="ab-btn ab-btn--delete" data-action="delete" data-module="departments" data-id="<?= $dept['id'] ?>" data-name="<?= htmlspecialchars($dept['name']) ?>" title="Delete Department">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <polyline points="3,6 5,6 21,6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
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

<script src="/ergon/assets/js/table-utils.js"></script>



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
