<?php
$title = 'Daily Task Planner';
$active_page = 'daily-planner';
ob_start();
?>

<div class="header-actions" style="margin-bottom: var(--space-6);">
    <span class="badge badge--info"><?= $userDepartment ?> Department</span>
</div>

<!-- Stats Overview -->
<div class="dashboard-grid">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📊</div>
            <div class="kpi-card__trend kpi-card__trend--up">Today</div>
        </div>
        <div class="kpi-card__value"><?= count($todayTasks) ?></div>
        <div class="kpi-card__label">Tasks Updated</div>
        <div class="kpi-card__status kpi-card__status--active">Active</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏱️</div>
            <div class="kpi-card__trend kpi-card__trend--up">Hours</div>
        </div>
        <div class="kpi-card__value"><?= array_sum(array_column($todayTasks, 'hours_spent')) ?>h</div>
        <div class="kpi-card__label">Hours Logged</div>
        <div class="kpi-card__status kpi-card__status--info">Tracked</div>
    </div>
</div>

<!-- Task Entry Form -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Task Entry Form</h2>
    </div>
    <div class="card__body">
                    <form method="POST" action="/daily-planner/submit" enctype="multipart/form-data" id="taskForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Select Project</label>
                                <select class="form-control" name="project_id" id="projectSelect" required>
                                            <option value="">Choose Project...</option>
                                            <?php if (empty($projects)): ?>
                                                <option value="" disabled>No projects available for <?= $userDepartment ?> department</option>
                                            <?php else: ?>
                                                <?php foreach ($projects as $project): ?>
                                                    <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Task Category</label>
                                    <select class="form-control" name="category_id" id="categorySelect">
                                            <option value="">Choose Category...</option>
                                            <?php if (empty($taskCategories)): ?>
                                                <option value="" disabled>No categories available for <?= $userDepartment ?> department</option>
                                            <?php else: ?>
                                                <?php foreach ($taskCategories as $category): ?>
                                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Select Task</label>
                                <select class="form-control" name="task_id" id="taskSelect" required>
                                    <option value="">Choose Task...</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Progress %</label>
                                <input type="number" class="form-control" name="progress_percentage" min="0" max="100" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Hours Spent</label>
                                <input type="number" class="form-control" name="hours_spent" step="0.5" min="0" max="24">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Attachment (Optional)</label>
                                <input type="file" class="form-control" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Work Notes</label>
                            <textarea class="form-control" name="work_notes" rows="3" placeholder="What did you work on today?"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Location (Optional)</label>
                            <button type="button" class="btn btn--secondary" id="getLocationBtn">📍 Get GPS Location</button>
                            <input type="hidden" name="gps_latitude" id="gpsLat">
                            <input type="hidden" name="gps_longitude" id="gpsLng">
                            <small class="form-help" id="locationStatus">Click to capture location</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">✅ Submit Task Update</button>
                        </div>
                    </form>
    </div>
</div>
        
        <!-- Today's Tasks -->
        <?php if (!empty($todayTasks)): ?>
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">📋 Today's Task Updates</h2>
            </div>
            <div class="card__body">
                <div class="table-responsive">
                    <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Category</th>
                                            <th>Task</th>
                                            <th>Progress</th>
                                            <th>Hours</th>
                                            <th>Notes</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            <?php foreach ($todayTasks as $task): ?>
                            <tr>
                                <td><?= htmlspecialchars($task['project_name']) ?></td>
                                <td><span class="badge badge--info"><?= htmlspecialchars($task['category_name']) ?></span></td>
                                <td><?= htmlspecialchars($task['task_name']) ?></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress__bar" style="width: <?= $task['progress_percentage'] ?>%"></div>
                                    </div>
                                    <small><?= $task['progress_percentage'] ?>%</small>
                                </td>
                                <td><?= $task['hours_spent'] ?>h</td>
                                <td><?= htmlspecialchars(substr($task['work_notes'], 0, 50)) ?><?= strlen($task['work_notes']) > 50 ? '...' : '' ?></td>
                                <td><?= date('H:i', strtotime($task['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

<script>
// Load tasks when project/category changes
document.getElementById('projectSelect').addEventListener('change', loadTasks);
document.getElementById('categorySelect').addEventListener('change', loadTasks);

function loadTasks() {
    const projectId = document.getElementById('projectSelect').value;
    const categoryId = document.getElementById('categorySelect').value;
    const taskSelect = document.getElementById('taskSelect');
    
    if (!projectId) {
        taskSelect.innerHTML = '<option value="">Choose Task...</option>';
        return;
    }
    
    let url = `/daily-planner/get-tasks?project_id=${projectId}`;
    if (categoryId) url += `&category_id=${categoryId}`;
    
    fetch(url)
        .then(response => response.json())
        .then(tasks => {
            taskSelect.innerHTML = '<option value="">Choose Task...</option>';
            tasks.forEach(task => {
                taskSelect.innerHTML += `<option value="${task.id}">${task.task_name} (${task.completion_percentage}% complete)</option>`;
            });
        })
        .catch(error => console.error('Error loading tasks:', error));
}

// GPS Location
document.getElementById('getLocationBtn').addEventListener('click', function() {
    const btn = this;
    const status = document.getElementById('locationStatus');
    
    btn.disabled = true;
    btn.innerHTML = '📍 Getting Location...';
    status.textContent = 'Fetching GPS coordinates...';
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('gpsLat').value = position.coords.latitude;
                document.getElementById('gpsLng').value = position.coords.longitude;
                btn.innerHTML = '✅ Location Captured';
                btn.className = 'btn btn--success';
                status.textContent = `Location: ${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}`;
            },
            function(error) {
                btn.disabled = false;
                btn.innerHTML = '📍 Get GPS Location';
                status.textContent = 'Location access denied or unavailable';
                console.error('GPS Error:', error);
            }
        );
    } else {
        btn.disabled = false;
        btn.innerHTML = '📍 Get GPS Location';
        status.textContent = 'GPS not supported by browser';
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>