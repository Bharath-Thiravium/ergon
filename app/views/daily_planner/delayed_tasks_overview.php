<?php
$title = 'Delayed Tasks Overview';
$active_page = 'delayed-tasks-overview';
ob_start();
?>

<div class="project-overview-header">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">⚠️ Delayed Tasks Overview</h1>
        <a href="/ergon/daily-planner/dashboard" class="btn btn--secondary">← Back to Dashboard</a>
    </div>
    
    <div class="overview-stats">
        <div class="stat-card">
            <div class="stat-number"><?= count($data['delayedTasks']) ?></div>
            <div class="stat-label">Total Delayed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= array_sum(array_map(function($task) { return $task['days_since_update'] ?? 0; }, $data['delayedTasks'])) ?></div>
            <div class="stat-label">Total Days Overdue</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= !empty($data['delayedTasks']) ? round(array_sum(array_column($data['delayedTasks'], 'completion_percentage')) / count($data['delayedTasks'])) : 0 ?>%</div>
            <div class="stat-label">Avg Progress</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count(array_unique(array_column($data['delayedTasks'], 'project_name'))) ?></div>
            <div class="stat-label">Affected Projects</div>
        </div>
    </div>
</div>

<div class="filters-container">
    <div class="search-section">
        <input type="text" id="taskSearch" placeholder="Search tasks or projects..." class="form-control search-input">
    </div>
    <div class="filter-section">
        <select id="projectFilter" class="form-control filter-select">
            <option value="">All Projects</option>
        </select>
        <select id="categoryFilter" class="form-control filter-select">
            <option value="">All Categories</option>
        </select>
        <select id="progressFilter" class="form-control filter-select">
            <option value="">All Progress</option>
            <option value="0-25">0-25%</option>
            <option value="26-50">26-50%</option>
            <option value="51-75">51-75%</option>
            <option value="76-100">76-100%</option>
        </select>
        <select id="sortBy" class="form-control filter-select">
            <option value="days">Sort by Days Overdue</option>
            <option value="project">Sort by Project</option>
            <option value="task">Sort by Task</option>
            <option value="progress">Sort by Progress</option>
        </select>
        <button id="sortOrder" class="btn btn--secondary sort-btn" data-order="desc">
            ↓ DESC
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

<div class="tasks-container">
    <div class="tasks-grid" id="tasksGrid">
        <?php foreach ($data['delayedTasks'] as $task): ?>
        <div class="kpi-card kpi-card--warning" 
             data-project="<?= htmlspecialchars($task['project_name']) ?>"
             data-task="<?= htmlspecialchars($task['task_name']) ?>"
             data-category="<?= htmlspecialchars($task['category_name']) ?>"
             data-progress="<?= $task['completion_percentage'] ?>"
             data-days="<?= $task['days_since_update'] ?? 0 ?>">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">⚠️</div>
                <div class="kpi-card__trend kpi-card__trend--down"><?= $task['days_since_update'] ?? 0 ?> days</div>
            </div>
            <div class="kpi-card__value"><?= $task['completion_percentage'] ?>%</div>
            <div class="kpi-card__label"><?= htmlspecialchars($task['task_name']) ?></div>
            <div class="kpi-card__status kpi-card__status--pending"><?= htmlspecialchars($task['project_name']) ?></div>
            <div class="task-actions">
                <button class="btn btn--sm btn--primary" onclick="followUpTask(<?= $task['id'] ?>)">
                    Follow Up
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="tasks-list" id="tasksList" style="display: none;">
        <div class="list-header">
            <div class="list-col">Task</div>
            <div class="list-col">Project</div>
            <div class="list-col">Category</div>
            <div class="list-col">Progress</div>
            <div class="list-col">Days Overdue</div>
            <div class="list-col">Action</div>
        </div>
        <?php foreach ($data['delayedTasks'] as $task): ?>
        <div class="list-item" 
             data-project="<?= htmlspecialchars($task['project_name']) ?>"
             data-task="<?= htmlspecialchars($task['task_name']) ?>"
             data-category="<?= htmlspecialchars($task['category_name']) ?>"
             data-progress="<?= $task['completion_percentage'] ?>"
             data-days="<?= $task['days_since_update'] ?? 0 ?>">
            <div class="list-col"><?= htmlspecialchars($task['task_name']) ?></div>
            <div class="list-col"><?= htmlspecialchars($task['project_name']) ?></div>
            <div class="list-col"><span class="badge badge--info"><?= htmlspecialchars($task['category_name']) ?></span></div>
            <div class="list-col">
                <div class="progress" style="width: 60px;">
                    <div class="progress__bar" style="width: <?= $task['completion_percentage'] ?>%"></div>
                </div>
                <?= $task['completion_percentage'] ?>%
            </div>
            <div class="list-col"><span class="badge badge--error"><?= $task['days_since_update'] ?? 0 ?> days</span></div>
            <div class="list-col">
                <button class="btn btn--sm btn--primary" onclick="followUpTask(<?= $task['id'] ?>)">
                    Follow Up
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($data['delayedTasks'])): ?>
<div class="empty-state">
    <div class="empty-icon">✅</div>
    <h3>No Delayed Tasks</h3>
    <p>Great! All tasks are on track.</p>
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
    color: var(--error);
    line-height: 1;
    margin-bottom: var(--space-1);
}

.stat-label {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

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
}

.filter-section {
    display: flex;
    gap: var(--space-3);
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: var(--space-4);
}

.filter-select {
    min-width: 150px;
    flex: 1;
    max-width: 200px;
}

.sort-btn {
    min-width: 80px;
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

.tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--space-4);
}

.task-actions {
    margin-top: var(--space-3);
    text-align: center;
}

.tasks-list {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.list-header {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr;
    gap: var(--space-3);
    padding: var(--space-4);
    background: var(--bg-secondary);
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid var(--border-color);
}

.list-item {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr;
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

.empty-state {
    text-align: center;
    padding: var(--space-12);
    color: var(--text-secondary);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: var(--space-4);
}

@media (max-width: 768px) {
    .overview-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .tasks-grid {
        grid-template-columns: 1fr;
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
let currentSort = { by: 'days', order: 'desc' };

function followUpTask(taskId) {
    if (confirm('Send follow-up reminder for this task?')) {
        alert('Follow-up reminder sent! (Feature to be implemented)');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('taskSearch');
    const projectFilter = document.getElementById('projectFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const progressFilter = document.getElementById('progressFilter');
    const sortBy = document.getElementById('sortBy');
    const sortOrder = document.getElementById('sortOrder');
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    
    // Populate filters
    const projects = new Set();
    const categories = new Set();
    
    document.querySelectorAll('.kpi-card').forEach(card => {
        projects.add(card.dataset.project);
        categories.add(card.dataset.category);
    });
    
    projects.forEach(project => {
        const option = document.createElement('option');
        option.value = project;
        option.textContent = project;
        projectFilter.appendChild(option);
    });
    
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category;
        option.textContent = category;
        categoryFilter.appendChild(option);
    });
    
    // Event listeners
    searchInput.addEventListener('input', filterTasks);
    projectFilter.addEventListener('change', filterTasks);
    categoryFilter.addEventListener('change', filterTasks);
    progressFilter.addEventListener('change', filterTasks);
    sortBy.addEventListener('change', sortTasks);
    sortOrder.addEventListener('click', toggleSortOrder);
    gridView.addEventListener('click', () => switchView('grid'));
    listView.addEventListener('click', () => switchView('list'));
    
    function filterTasks() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedProject = projectFilter.value;
        const selectedCategory = categoryFilter.value;
        const selectedProgress = progressFilter.value;
        
        const items = currentView === 'grid' 
            ? document.querySelectorAll('.kpi-card')
            : document.querySelectorAll('.list-item');
        
        items.forEach(item => {
            const project = item.dataset.project;
            const task = item.dataset.task.toLowerCase();
            const category = item.dataset.category;
            const progress = parseInt(item.dataset.progress);
            
            let show = true;
            
            if (searchTerm && !task.includes(searchTerm) && !project.toLowerCase().includes(searchTerm)) {
                show = false;
            }
            
            if (selectedProject && project !== selectedProject) {
                show = false;
            }
            
            if (selectedCategory && category !== selectedCategory) {
                show = false;
            }
            
            if (selectedProgress) {
                const [min, max] = selectedProgress.split('-').map(Number);
                if (progress < min || progress > max) {
                    show = false;
                }
            }
            
            item.style.display = show ? '' : 'none';
        });
    }
    
    function sortTasks() {
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
            ? document.getElementById('tasksGrid')
            : document.getElementById('tasksList');
        
        const items = Array.from(currentView === 'grid' 
            ? container.querySelectorAll('.kpi-card')
            : container.querySelectorAll('.list-item:not(.list-header)'));
        
        items.sort((a, b) => {
            let aVal, bVal;
            
            switch (currentSort.by) {
                case 'days':
                    aVal = parseInt(a.dataset.days);
                    bVal = parseInt(b.dataset.days);
                    break;
                case 'project':
                    aVal = a.dataset.project.toLowerCase();
                    bVal = b.dataset.project.toLowerCase();
                    break;
                case 'task':
                    aVal = a.dataset.task.toLowerCase();
                    bVal = b.dataset.task.toLowerCase();
                    break;
                case 'progress':
                    aVal = parseInt(a.dataset.progress);
                    bVal = parseInt(b.dataset.progress);
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
        
        items.forEach(item => container.appendChild(item));
    }
    
    function switchView(view) {
        currentView = view;
        
        if (view === 'grid') {
            document.getElementById('tasksGrid').style.display = 'grid';
            document.getElementById('tasksList').style.display = 'none';
            gridView.classList.add('active');
            gridView.classList.remove('btn--secondary');
            gridView.classList.add('btn--primary');
            listView.classList.remove('active');
            listView.classList.remove('btn--primary');
            listView.classList.add('btn--secondary');
        } else {
            document.getElementById('tasksGrid').style.display = 'none';
            document.getElementById('tasksList').style.display = 'block';
            listView.classList.add('active');
            listView.classList.remove('btn--secondary');
            listView.classList.add('btn--primary');
            gridView.classList.remove('active');
            gridView.classList.remove('btn--primary');
            gridView.classList.add('btn--secondary');
        }
        
        filterTasks();
    }
    
    // Initial sort
    applySorting();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>