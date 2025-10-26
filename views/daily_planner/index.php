<?php
$title = 'Daily Progress Report';
$active_page = 'daily-planner';
ob_start();
?>

<div class="header-actions">
    <span class="badge badge--info"><?= $userDepartment ?> Department</span>
</div>

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

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Progress Report Form</h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/public/daily-planner/submit" enctype="multipart/form-data" id="taskForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Task Source</label>
                    <select class="form-control" id="taskSource" onchange="toggleTaskSource()">
                        <option value="planned">From Daily Planner</option>
                        <option value="adhoc">Ad-hoc Task (Not Planned)</option>
                    </select>
                </div>
            </div>
            
            <div id="plannedTaskSection">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Select Planned Activity</label>
                        <select class="form-control" name="plan_id" id="planSelect">
                            <option value="">Choose from Daily Planner...</option>
                            <?php if (!empty($todayPlans)): ?>
                                <?php foreach ($todayPlans as $plan): ?>
                                    <option value="<?= $plan['id'] ?>"><?= htmlspecialchars($plan['title']) ?> (<?= $plan['priority'] ?>)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div id="adhocTaskSection" style="display:none;">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Task Title</label>
                        <input type="text" class="form-control" name="adhoc_title" placeholder="Enter task title">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Task Category</label>
                        <select class="form-control" name="adhoc_category">
                            <option value="Development">Development</option>
                            <option value="Meeting">Meeting</option>
                            <option value="Documentation">Documentation</option>
                            <option value="Support">Support</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Completion Status</label>
                    <select class="form-control" name="completion_status" required>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Progress %</label>
                    <input type="number" class="form-control" name="progress_percentage" min="0" max="100" value="0" required>
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
                <button type="submit" class="btn btn--primary">✅ Submit Progress Report</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($todayTasks)): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">📋 Today's Progress Reports</h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Task/Activity</th>
                        <th>Status</th>
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
document.addEventListener('DOMContentLoaded', function() {
    const getLocationBtn = document.getElementById('getLocationBtn');
    if (getLocationBtn) {
        getLocationBtn.addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('gpsLat').value = position.coords.latitude;
                    document.getElementById('gpsLng').value = position.coords.longitude;
                    document.getElementById('locationStatus').textContent = 'Location captured successfully';
                });
            }
        });
    }
});

function toggleTaskSource() {
    const taskSource = document.getElementById('taskSource').value;
    const plannedSection = document.getElementById('plannedTaskSection');
    const adhocSection = document.getElementById('adhocTaskSection');
    
    if (taskSource === 'planned') {
        plannedSection.style.display = 'block';
        adhocSection.style.display = 'none';
    } else {
        plannedSection.style.display = 'none';
        adhocSection.style.display = 'block';
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
