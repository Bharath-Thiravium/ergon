<?php
$title = 'Daily Task Manager Dashboard';
$active_page = 'daily-planner-dashboard';
ob_start();
?>

<div class="header-actions" style="margin-bottom: var(--space-6);">
    <select class="form-control" onchange="window.location.href='?department='+this.value">
        <option value="">All Departments</option>
        <option value="IT" <?= $selectedDepartment === 'IT' ? 'selected' : '' ?>>IT</option>
        <option value="Civil" <?= $selectedDepartment === 'Civil' ? 'selected' : '' ?>>Civil</option>
        <option value="Accounts" <?= $selectedDepartment === 'Accounts' ? 'selected' : '' ?>>Accounts</option>
        <option value="Sales" <?= $selectedDepartment === 'Sales' ? 'selected' : '' ?>>Sales</option>
        <option value="Marketing" <?= $selectedDepartment === 'Marketing' ? 'selected' : '' ?>>Marketing</option>
        <option value="HR" <?= $selectedDepartment === 'HR' ? 'selected' : '' ?>>HR</option>
        <option value="Admin" <?= $selectedDepartment === 'Admin' ? 'selected' : '' ?>>Admin</option>
    </select>
</div>

<!-- KPI Cards Row -->
<div class="kpi-row">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🎯</div>
            <div class="kpi-card__trend kpi-card__trend--up">Projects</div>
        </div>
        <div class="kpi-card__value"><?= count($projectProgress) ?></div>
        <div class="kpi-card__label">Active Projects</div>
        <div class="kpi-card__status kpi-card__status--active">Running</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✓</div>
            <div class="kpi-card__trend kpi-card__trend--up">Done</div>
        </div>
        <div class="kpi-card__value"><?= array_sum(array_column($projectProgress, 'completed_tasks')) ?></div>
        <div class="kpi-card__label">Completed Tasks</div>
        <div class="kpi-card__status kpi-card__status--review">Finished</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend kpi-card__trend--down">Alert</div>
        </div>
        <div class="kpi-card__value"><?= count($delayedTasks) ?></div>
        <div class="kpi-card__label">Delayed Tasks</div>
        <div class="kpi-card__status kpi-card__status--pending">Needs Action</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend kpi-card__trend--up">Team</div>
        </div>
        <div class="kpi-card__value"><?= count($teamActivity) ?></div>
        <div class="kpi-card__label">Active Users</div>
        <div class="kpi-card__status kpi-card__status--active">Online</div>
    </div>
    
</div>

<!-- Overview Cards Row -->
<div class="overview-row">
    <div class="card">
        <div class="card__header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="card__title" style="margin: 0; flex: 1;">🎯 Project Progress Overview</h2>
            <button class="btn btn--sm btn--primary" onclick="openProjectOverview()" style="flex-shrink: 0; margin-left: var(--space-4);">
                View Details
            </button>
        </div>
        <div class="card__body">
            <div class="project-summary">
                <div class="summary-stat">
                    <span class="summary-number"><?= count($projectProgress) ?></span>
                    <span class="summary-label">Active Projects</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number"><?= array_sum(array_column($projectProgress, 'completed_tasks')) ?></span>
                    <span class="summary-label">Completed Tasks</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number"><?= round(array_sum(array_column($projectProgress, 'completion_percentage')) / max(count($projectProgress), 1)) ?>%</span>
                    <span class="summary-label">Avg Progress</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="card__title" style="margin: 0; flex: 1;">⚠️ Delayed Tasks Overview</h2>
            <button class="btn btn--sm btn--primary" onclick="openDelayedTasksOverview()" style="flex-shrink: 0; margin-left: var(--space-4);">
                View Details
            </button>
        </div>
        <div class="card__body">
            <div class="project-summary">
                <div class="summary-stat">
                    <span class="summary-number"><?= count($delayedTasks) ?></span>
                    <span class="summary-label">Delayed Tasks</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number"><?= array_sum(array_map(function($task) { return $task['days_since_update'] ?? 0; }, $delayedTasks)) ?></span>
                    <span class="summary-label">Total Days Overdue</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number"><?= !empty($delayedTasks) ? round(array_sum(array_column($delayedTasks, 'completion_percentage')) / count($delayedTasks)) : 0 ?>%</span>
                    <span class="summary-label">Avg Progress</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Team Activity Today -->
<div class="card">
    <div class="card__header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card__title">👥 Team Activity - <?= date('d M Y', strtotime($today)) ?></h2>
        <div class="search-filter-container">
            <input type="text" id="teamSearch" placeholder="Search employees..." class="form-control" style="width: 200px; margin-right: var(--space-2);">
            <select id="departmentFilter" class="form-control" style="width: 150px;">
                <option value="">All Departments</option>
                <option value="IT">IT</option>
                <option value="Civil">Civil</option>
                <option value="Accounts">Accounts</option>
                <option value="Sales">Sales</option>
                <option value="Marketing">Marketing</option>
                <option value="HR">HR</option>
                <option value="Admin">Admin</option>
            </select>
        </div>
    </div>
    <div class="card__body">
        <div class="table-container">
            <table class="table" id="teamActivityTable">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Department</th>
                                                    <th>Tasks Updated</th>
                                                    <th>Avg Progress</th>
                                                    <th>Hours Logged</th>
                                                    <th>Performance</th>
                                                </tr>
                                            </thead>
                    <tbody>
                        <?php foreach ($teamActivity as $activity): ?>
                        <tr>
                            <td><?= htmlspecialchars($activity['name']) ?></td>
                            <td><span class="badge badge--info"><?= htmlspecialchars($activity['department']) ?></span></td>
                            <td>
                                <span class="badge <?= $activity['tasks_updated'] > 0 ? 'badge--success' : 'badge--error' ?>">
                                    <?= $activity['tasks_updated'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($activity['avg_progress']): ?>
                                    <div class="progress" style="width: 80px;">
                                        <div class="progress__bar" style="width: <?= $activity['avg_progress'] ?>%"></div>
                                    </div>
                                    <small><?= round($activity['avg_progress']) ?>%</small>
                                <?php else: ?>
                                    <span class="form-help">No updates</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $activity['total_hours'] ?? 0 ?>h</td>
                            <td>
                                <?php 
                                $performance = 'Poor';
                                $badgeClass = 'badge--error';
                                if ($activity['tasks_updated'] > 0 && $activity['avg_progress'] > 50) {
                                    $performance = 'Excellent';
                                    $badgeClass = 'badge--success';
                                } elseif ($activity['tasks_updated'] > 0) {
                                    $performance = 'Good';
                                    $badgeClass = 'badge--warning';
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $performance ?></span>
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
const projectProgress = <?= json_encode($projectProgress) ?>;

function openProjectOverview() {
    window.location.href = '/ergon/daily-planner/project-overview';
}

function openDelayedTasksOverview() {
    window.location.href = '/ergon/daily-planner/delayed-tasks-overview';
}



function followUpTask(taskId) {
    if (confirm('Send follow-up reminder for this task?')) {
        alert('Follow-up reminder sent! (Feature to be implemented)');
    }
}

// Search and filter functionality
document.addEventListener('DOMContentLoaded', function() {
    // Team activity search
    const teamSearch = document.getElementById('teamSearch');
    const departmentFilter = document.getElementById('departmentFilter');
    const teamTable = document.getElementById('teamActivityTable');
    
    if (teamSearch && teamTable) {
        teamSearch.addEventListener('input', filterTeamActivity);
        departmentFilter.addEventListener('change', filterTeamActivity);
    }
    
    function filterTeamActivity() {
        const searchTerm = teamSearch.value.toLowerCase();
        const selectedDept = departmentFilter.value.toLowerCase();
        const rows = teamTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const name = row.cells[0].textContent.toLowerCase();
            const dept = row.cells[1].textContent.toLowerCase();
            const matchesSearch = name.includes(searchTerm);
            const matchesDept = !selectedDept || dept.includes(selectedDept);
            
            row.style.display = matchesSearch && matchesDept ? '' : 'none';
        });
    }
    

});

// Auto-refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<style>
.project-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-4);
    text-align: center;
}

.summary-stat {
    padding: var(--space-3);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.summary-number {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary);
    margin-bottom: var(--space-1);
}

.summary-label {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--space-4);
    margin-bottom: var(--space-6);
}

.overview-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-6);
    margin-bottom: var(--space-8);
}

.search-filter-container {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.table-container {
    width: 100%;
    max-width: calc(100vw - var(--sidebar-width) - 4rem);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.delayed-filters {
    flex-wrap: wrap;
}

.table-container {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

@media (max-width: 768px) {
    .kpi-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .overview-row {
        grid-template-columns: 1fr;
    }
    
    .table-container {
        width: 100%;
    }
    
    .search-filter-container {
        flex-direction: column;
        align-items: stretch;
        gap: var(--space-2);
    }
    
    .search-filter-container .form-control {
        width: 100% !important;
    }
    
    .delayed-filters {
        flex-direction: column;
    }
    
    .delayed-filters .form-control {
        width: 100% !important;
    }
    
    .card__header {
        flex-direction: column;
        align-items: stretch;
        gap: var(--space-3);
    }
}
</style>



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>