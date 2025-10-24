<?php
$title = 'Project Progress Overview';
$active_page = 'project-overview';
ob_start();
?>

<div class="project-overview-header">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">🎯 Project Progress Overview</h1>
        <a href="/ergon/daily-planner/dashboard" class="btn btn--secondary">← Back to Dashboard</a>
    </div>
    
    <!-- Advanced Filters and Search -->
    <div class="filters-container">
        <div class="search-section">
            <input type="text" id="projectSearch" placeholder="Search projects..." class="form-control search-input">
        </div>
        <div class="filter-section">
            <select id="departmentFilter" class="form-control filter-select">
                <option value="">All Departments</option>
                <option value="IT">IT</option>
                <option value="Civil">Civil</option>
                <option value="Accounts">Accounts</option>
                <option value="Sales">Sales</option>
                <option value="Marketing">Marketing</option>
                <option value="HR">HR</option>
                <option value="Admin">Admin</option>
            </select>
            <select id="progressFilter" class="form-control filter-select">
                <option value="">All Progress</option>
                <option value="0-25">0-25%</option>
                <option value="26-50">26-50%</option>
                <option value="51-75">51-75%</option>
                <option value="76-100">76-100%</option>
            </select>
            <select id="sortBy" class="form-control filter-select">
                <option value="name">Sort by Name</option>
                <option value="progress">Sort by Progress</option>
                <option value="tasks">Sort by Tasks</option>
                <option value="department">Sort by Department</option>
            </select>
            <button id="sortOrder" class="btn btn--secondary sort-btn" data-order="asc">
                ↑ ASC
            </button>
        </div>
        <div class="view-options">
            <button id="gridView" class="btn btn--primary view-btn active" data-view="grid">
                📊 Grid
            </button>
            <button id="listView" class="btn btn--secondary view-btn" data-view="list">
                📋 List
            </button>
        </div>
    </div>
    
    <div class="overview-stats">
        <div class="stat-card">
            <div class="stat-number"><?= count($data['projectProgress']) ?></div>
            <div class="stat-label">Active Projects</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= array_sum(array_column($data['projectProgress'], 'total_tasks')) ?></div>
            <div class="stat-label">Total Tasks</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= array_sum(array_column($data['projectProgress'], 'completed_tasks')) ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= round(array_sum(array_column($data['projectProgress'], 'completion_percentage')) / max(count($data['projectProgress']), 1)) ?>%</div>
            <div class="stat-label">Avg Progress</div>
        </div>
    </div>
</div>

<div class="projects-container">
    <div class="projects-grid" id="projectsGrid">
        <?php foreach ($data['projectProgress'] as $project): ?>
        <div class="project-card" 
             data-name="<?= htmlspecialchars($project['name']) ?>"
             data-department="<?= htmlspecialchars($project['department']) ?>"
             data-progress="<?= $project['completion_percentage'] ?>"
             data-tasks="<?= $project['total_tasks'] ?>">
            <div class="project-card__header">
                <h4 class="project-title"><?= htmlspecialchars($project['name']) ?></h4>
                <span class="progress-badge <?= $project['completion_percentage'] >= 100 ? 'complete' : ($project['completion_percentage'] >= 50 ? 'good' : 'poor') ?>">
                    <?= $project['completion_percentage'] ?>%
                </span>
            </div>
        
        <div class="project-card__body">
            <div class="progress-bar-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $project['completion_percentage'] ?>%"></div>
                </div>
                <span class="progress-text"><?= $project['completed_tasks'] ?>/<?= $project['total_tasks'] ?> tasks</span>
            </div>
            
            <div class="project-metrics">
                <div class="metric">
                    <span class="metric-value"><?= $project['total_tasks'] ?></span>
                    <span class="metric-label">Total</span>
                </div>
                <div class="metric">
                    <span class="metric-value"><?= $project['completed_tasks'] ?></span>
                    <span class="metric-label">Done</span>
                </div>
                <div class="metric">
                    <span class="metric-value"><?= $project['total_tasks'] - $project['completed_tasks'] ?></span>
                    <span class="metric-label">Pending</span>
                </div>
            </div>
        </div>
        
        <div class="project-card__footer">
            <span class="department-tag"><?= htmlspecialchars($project['department']) ?></span>
            <button class="btn btn--sm btn--primary" onclick="viewDetails(<?= $project['id'] ?? 0 ?>)">
                View Details
            </button>
        </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- List View (Hidden by default) -->
    <div class="projects-list" id="projectsList" style="display: none;">
        <div class="list-header">
            <div class="list-col">Project Name</div>
            <div class="list-col">Department</div>
            <div class="list-col">Progress</div>
            <div class="list-col">Tasks</div>
            <div class="list-col">Actions</div>
        </div>
        <?php foreach ($data['projectProgress'] as $project): ?>
        <div class="list-item" 
             data-name="<?= htmlspecialchars($project['name']) ?>"
             data-department="<?= htmlspecialchars($project['department']) ?>"
             data-progress="<?= $project['completion_percentage'] ?>"
             data-tasks="<?= $project['total_tasks'] ?>">
            <div class="list-col">
                <strong><?= htmlspecialchars($project['name']) ?></strong>
            </div>
            <div class="list-col">
                <span class="department-tag"><?= htmlspecialchars($project['department']) ?></span>
            </div>
            <div class="list-col">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $project['completion_percentage'] ?>%"></div>
                </div>
                <span class="progress-text"><?= $project['completion_percentage'] ?>%</span>
            </div>
            <div class="list-col">
                <span class="task-count"><?= $project['completed_tasks'] ?>/<?= $project['total_tasks'] ?></span>
            </div>
            <div class="list-col">
                <button class="btn btn--sm btn--primary" onclick="viewDetails(<?= $project['id'] ?? 0 ?>)">
                    View Details
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($data['projectProgress'])): ?>
<div class="empty-state">
    <div class="empty-icon">📊</div>
    <h3>No Projects Found</h3>
    <p>No active projects to display at the moment.</p>
</div>
<?php endif; ?>

<style>
.project-overview-header {
    margin-bottom: var(--space-6);
}

.overview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-4);
    margin-top: var(--space-4);
}

.stat-card {
    background: var(--bg-primary);
    padding: var(--space-4);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    text-align: center;
    border: 1px solid var(--border-color);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: var(--primary);
    line-height: 1;
    margin-bottom: var(--space-1);
}

.stat-label {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.projects-container {
    position: relative;
}

.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: var(--space-4);
}

.project-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    overflow: hidden;
    transition: var(--transition);
}

.project-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.project-card__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-4);
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.project-title {
    margin: 0;
    font-size: var(--font-size-lg);
    color: var(--text-primary);
    font-weight: 600;
}

.progress-badge {
    padding: var(--space-1) var(--space-2);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
    font-weight: 600;
}

.progress-badge.complete {
    background: rgba(34, 197, 94, 0.1);
    color: var(--success);
}

.progress-badge.good {
    background: rgba(251, 191, 36, 0.1);
    color: var(--warning);
}

.progress-badge.poor {
    background: rgba(239, 68, 68, 0.1);
    color: var(--error);
}

.project-card__body {
    padding: var(--space-4);
}

.progress-bar-container {
    margin-bottom: var(--space-4);
}

.progress-bar {
    height: 8px;
    background: var(--bg-secondary);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: var(--space-2);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    transition: width 0.3s ease;
}

.progress-text {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
}

.project-metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-2);
}

.metric {
    text-align: center;
    padding: var(--space-2);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.metric-value {
    display: block;
    font-size: var(--font-size-lg);
    font-weight: bold;
    color: var(--text-primary);
}

.metric-label {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.project-card__footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-4);
    border-top: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.department-tag {
    background: var(--primary);
    color: var(--text-inverse);
    padding: var(--space-1) var(--space-2);
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.empty-state {
    text-align: center;
    padding: var(--space-12);
    color: var(--text-secondary);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: var(--space-4);
}

.empty-state h3 {
    margin-bottom: var(--space-2);
    color: var(--text-primary);
}

/* Filters and Search Styling */
.filters-container {
    background: var(--bg-primary);
    padding: var(--space-4);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    margin-bottom: var(--space-6);
    border: 1px solid var(--border-color);
}

.search-section {
    margin-bottom: var(--space-4);
}

.search-input {
    max-width: 400px;
    font-size: var(--font-size-base);
}

.filter-section {
    display: flex;
    gap: var(--space-3);
    align-items: center;
    margin-bottom: var(--space-4);
    flex-wrap: wrap;
}

.filter-select {
    min-width: 150px;
    flex: 1;
    max-width: 200px;
}

.sort-btn {
    min-width: 80px;
    font-weight: 600;
}

.view-options {
    display: flex;
    gap: var(--space-2);
}

.view-btn {
    min-width: 100px;
}

.view-btn.active {
    font-weight: 600;
}

/* List View Styling */
.projects-list {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.list-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1.5fr 1fr 1fr;
    gap: var(--space-3);
    padding: var(--space-4);
    background: var(--bg-secondary);
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid var(--border-color);
}

.list-item {
    display: grid;
    grid-template-columns: 2fr 1fr 1.5fr 1fr 1fr;
    gap: var(--space-3);
    padding: var(--space-4);
    border-bottom: 1px solid var(--border-color);
    transition: var(--transition);
    align-items: center;
}

.list-item:hover {
    background: var(--bg-secondary);
}

.list-item:last-child {
    border-bottom: none;
}

.list-col {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.task-count {
    font-weight: 600;
    color: var(--text-primary);
}

.progress-text {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    margin-left: var(--space-2);
}

@media (max-width: 768px) {
    .overview-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .projects-grid {
        grid-template-columns: 1fr;
    }
    
    .project-metrics {
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-1);
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .filter-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-select {
        max-width: none;
    }
    
    .list-header,
    .list-item {
        grid-template-columns: 1fr;
        gap: var(--space-2);
    }
    
    .list-col {
        justify-content: space-between;
        padding: var(--space-1) 0;
    }
    
    .list-col:before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--text-secondary);
        font-size: var(--font-size-sm);
    }
    
    .view-options {
        justify-content: stretch;
    }
    
    .view-btn {
        flex: 1;
    }
}
</style>

<script>
let currentView = 'grid';
let currentSort = { by: 'name', order: 'asc' };

function viewDetails(projectId) {
    if (projectId) {
        alert(`Project details for ID: ${projectId} (Feature to be implemented)`);
    } else {
        alert('Project details not available');
    }
}

// Initialize filters and search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('projectSearch');
    const departmentFilter = document.getElementById('departmentFilter');
    const progressFilter = document.getElementById('progressFilter');
    const sortBy = document.getElementById('sortBy');
    const sortOrder = document.getElementById('sortOrder');
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    
    // Search functionality
    searchInput.addEventListener('input', filterProjects);
    departmentFilter.addEventListener('change', filterProjects);
    progressFilter.addEventListener('change', filterProjects);
    sortBy.addEventListener('change', sortProjects);
    sortOrder.addEventListener('click', toggleSortOrder);
    
    // View toggle
    gridView.addEventListener('click', () => switchView('grid'));
    listView.addEventListener('click', () => switchView('list'));
    
    function filterProjects() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedDept = departmentFilter.value;
        const selectedProgress = progressFilter.value;
        
        const items = currentView === 'grid' 
            ? document.querySelectorAll('.project-card')
            : document.querySelectorAll('.list-item');
        
        items.forEach(item => {
            const name = item.dataset.name.toLowerCase();
            const dept = item.dataset.department;
            const progress = parseInt(item.dataset.progress);
            
            let show = true;
            
            // Search filter
            if (searchTerm && !name.includes(searchTerm)) {
                show = false;
            }
            
            // Department filter
            if (selectedDept && dept !== selectedDept) {
                show = false;
            }
            
            // Progress filter
            if (selectedProgress) {
                const [min, max] = selectedProgress.split('-').map(Number);
                if (progress < min || progress > max) {
                    show = false;
                }
            }
            
            item.style.display = show ? '' : 'none';
        });
    }
    
    function sortProjects() {
        currentSort.by = sortBy.value;
        applySorting();
    }
    
    function toggleSortOrder() {
        currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
        sortOrder.textContent = currentSort.order === 'asc' ? '↑ ASC' : '↓ DESC';
        sortOrder.dataset.order = currentSort.order;
        applySorting();
    }
    
    function applySorting() {
        const container = currentView === 'grid' 
            ? document.getElementById('projectsGrid')
            : document.getElementById('projectsList');
        
        const items = Array.from(currentView === 'grid' 
            ? container.querySelectorAll('.project-card')
            : container.querySelectorAll('.list-item:not(.list-header)'));
        
        items.sort((a, b) => {
            let aVal, bVal;
            
            switch (currentSort.by) {
                case 'name':
                    aVal = a.dataset.name.toLowerCase();
                    bVal = b.dataset.name.toLowerCase();
                    break;
                case 'progress':
                    aVal = parseInt(a.dataset.progress);
                    bVal = parseInt(b.dataset.progress);
                    break;
                case 'tasks':
                    aVal = parseInt(a.dataset.tasks);
                    bVal = parseInt(b.dataset.tasks);
                    break;
                case 'department':
                    aVal = a.dataset.department.toLowerCase();
                    bVal = b.dataset.department.toLowerCase();
                    break;
                default:
                    return 0;
            }
            
            if (typeof aVal === 'string') {
                return currentSort.order === 'asc' 
                    ? aVal.localeCompare(bVal)
                    : bVal.localeCompare(aVal);
            } else {
                return currentSort.order === 'asc' 
                    ? aVal - bVal
                    : bVal - aVal;
            }
        });
        
        // Re-append sorted items
        items.forEach(item => container.appendChild(item));
    }
    
    function switchView(view) {
        currentView = view;
        
        if (view === 'grid') {
            document.getElementById('projectsGrid').style.display = 'grid';
            document.getElementById('projectsList').style.display = 'none';
            gridView.classList.add('active');
            gridView.classList.remove('btn--secondary');
            gridView.classList.add('btn--primary');
            listView.classList.remove('active');
            listView.classList.remove('btn--primary');
            listView.classList.add('btn--secondary');
        } else {
            document.getElementById('projectsGrid').style.display = 'none';
            document.getElementById('projectsList').style.display = 'block';
            listView.classList.add('active');
            listView.classList.remove('btn--secondary');
            listView.classList.add('btn--primary');
            gridView.classList.remove('active');
            gridView.classList.remove('btn--primary');
            gridView.classList.add('btn--secondary');
        }
        
        // Re-apply filters after view switch
        filterProjects();
    }
});

// Auto-refresh every 2 minutes
setInterval(function() {
    location.reload();
}, 120000);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>