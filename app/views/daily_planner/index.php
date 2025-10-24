<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">📝 Daily Task Planner - <?= date('d M Y', strtotime($today)) ?></h4>
                    <span class="badge bg-primary"><?= $_SESSION['user']['department'] ?> Department</span>
                </div>
                <div class="card-body">
                    
                    <!-- Task Entry Form -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <form method="POST" action="/daily-planner/submit" enctype="multipart/form-data" id="taskForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Select Project</label>
                                        <select class="form-select" name="project_id" id="projectSelect" required>
                                            <option value="">Choose Project...</option>
                                            <?php foreach ($projects as $project): ?>
                                                <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Task Category</label>
                                        <select class="form-select" name="category_id" id="categorySelect">
                                            <option value="">Choose Category...</option>
                                            <?php foreach ($taskCategories as $category): ?>
                                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Select Task</label>
                                        <select class="form-select" name="task_id" id="taskSelect" required>
                                            <option value="">Choose Task...</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Progress %</label>
                                        <input type="number" class="form-control" name="progress_percentage" min="0" max="100" required>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Hours Spent</label>
                                        <input type="number" class="form-control" name="hours_spent" step="0.5" min="0" max="24">
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Work Notes</label>
                                        <textarea class="form-control" name="work_notes" rows="3" placeholder="What did you work on today?"></textarea>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Attachment (Optional)</label>
                                        <input type="file" class="form-control" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Location (Optional)</label>
                                        <button type="button" class="btn btn-outline-secondary" id="getLocationBtn">📍 Get GPS Location</button>
                                        <input type="hidden" name="gps_latitude" id="gpsLat">
                                        <input type="hidden" name="gps_longitude" id="gpsLng">
                                        <small class="text-muted d-block" id="locationStatus">Click to capture location</small>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">✅ Submit Task Update</button>
                            </form>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>📊 Today's Summary</h6>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h4 class="text-primary"><?= count($todayTasks) ?></h4>
                                            <small>Tasks Updated</small>
                                        </div>
                                        <div class="col-6">
                                            <h4 class="text-success"><?= array_sum(array_column($todayTasks, 'hours_spent')) ?>h</h4>
                                            <small>Hours Logged</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Today's Tasks -->
                    <?php if (!empty($todayTasks)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>📋 Today's Task Updates</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
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
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($task['category_name']) ?></span></td>
                                            <td><?= htmlspecialchars($task['task_name']) ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" style="width: <?= $task['progress_percentage'] ?>%">
                                                        <?= $task['progress_percentage'] ?>%
                                                    </div>
                                                </div>
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
                </div>
            </div>
        </div>
    </div>
</div>

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
                btn.className = 'btn btn-success';
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

<?php include __DIR__ . '/../layouts/footer.php'; ?>