<?php
$title = 'Project Progress Overview';
$active_page = 'daily-planner';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Project Progress Overview</h1>
        <p>Monitor and track progress across all active projects</p>
    </div>
    <div class="page-actions">
        <button id="gridViewBtn" class="btn btn--primary">Grid View</button>
        <button id="listViewBtn" class="btn btn--secondary">List View</button>
        <a href="/ergon_clean/public/owner/dashboard" class="btn btn--secondary">Back</a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📈</div>
            <div class="kpi-card__trend">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= count($data['projectProgress']) ?></div>
        <div class="kpi-card__label">Active Projects</div>
        <div class="kpi-card__status">Running</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ +12%</div>
        </div>
        <div class="kpi-card__value"><?= array_sum(array_column($data['projectProgress'], 'completed_tasks')) ?></div>
        <div class="kpi-card__label">Completed Tasks</div>
        <div class="kpi-card__status">Done</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= array_sum(array_column($data['projectProgress'], 'total_tasks')) ?></div>
        <div class="kpi-card__label">Total Tasks</div>
        <div class="kpi-card__status">Assigned</div>
    </div>
</div>

<!-- Grid View -->
<div id="gridView" class="projects-grid">
    <?php foreach ($data['projectProgress'] as $project): ?>
    <div class="project-card">
        <div class="project-header">
            <h3><?= htmlspecialchars($project['name']) ?></h3>
            <span class="progress-badge progress-<?= $project['completion_percentage'] >= 75 ? 'high' : ($project['completion_percentage'] >= 50 ? 'medium' : 'low') ?>">
                <?= $project['completion_percentage'] ?>%
            </span>
        </div>
        <div class="project-body">
            <div class="project-stats">
                <div class="project-stat">
                    <div class="project-stat__header">
                        <div class="project-stat__icon">✅</div>
                    </div>
                    <div class="project-stat__value"><?= $project['completed_tasks'] ?></div>
                    <div class="project-stat__label">Completed</div>
                </div>
                <div class="project-stat">
                    <div class="project-stat__header">
                        <div class="project-stat__icon">📅</div>
                    </div>
                    <div class="project-stat__value"><?= $project['total_tasks'] ?></div>
                    <div class="project-stat__label">Total</div>
                </div>
            </div>
            <div class="project-footer">
                <span class="department-tag"><?= htmlspecialchars($project['department']) ?></span>
                <button class="btn btn--sm btn--primary" onclick="viewProjectDetails(<?= $project['id'] ?>)">Details</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- List View -->
<div id="listView" class="projects-list projects-list--hidden">
    <div class="card">
        <div class="card__body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Department</th>
                            <th>Progress</th>
                            <th>Tasks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['projectProgress'] as $project): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($project['name']) ?></strong></td>
                            <td><span class="badge badge--secondary"><?= htmlspecialchars($project['department']) ?></span></td>
                            <td>
                                <span class="progress-text progress-<?= $project['completion_percentage'] >= 75 ? 'high' : ($project['completion_percentage'] >= 50 ? 'medium' : 'low') ?>">
                                    <?= $project['completion_percentage'] ?>%
                                </span>
                            </td>
                            <td><?= $project['completed_tasks'] ?>/<?= $project['total_tasks'] ?></td>
                            <td>
                                <button class="btn btn--sm btn--primary" onclick="viewProjectDetails(<?= $project['id'] ?>)">View Details</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function viewProjectDetails(projectId) {
    alert(`Project details for ID: ${projectId} (Feature to be implemented)`);
}

document.addEventListener('DOMContentLoaded', function() {
    const gridViewBtn = document.getElementById('gridViewBtn');
    const listViewBtn = document.getElementById('listViewBtn');
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    
    gridViewBtn.addEventListener('click', function() {
        gridView.classList.remove('projects-grid--hidden');
        listView.classList.add('projects-list--hidden');
        gridViewBtn.classList.remove('btn--secondary');
        gridViewBtn.classList.add('btn--primary');
        listViewBtn.classList.remove('btn--primary');
        listViewBtn.classList.add('btn--secondary');
    });
    
    listViewBtn.addEventListener('click', function() {
        gridView.classList.add('projects-grid--hidden');
        listView.classList.remove('projects-list--hidden');
        listViewBtn.classList.remove('btn--secondary');
        listViewBtn.classList.add('btn--primary');
        gridViewBtn.classList.remove('btn--primary');
        gridViewBtn.classList.add('btn--secondary');
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>