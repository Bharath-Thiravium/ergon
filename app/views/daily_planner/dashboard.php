<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">📊 Daily Task Manager Dashboard</h4>
                </div>
                <div class="card-body">
                    
                    <!-- Project Progress Overview -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5>🎯 Project Progress Overview</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($projectProgress as $project): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong><?= htmlspecialchars($project['name']) ?></strong>
                                            <span class="badge bg-<?= $project['completion_percentage'] >= 100 ? 'success' : ($project['completion_percentage'] >= 50 ? 'warning' : 'danger') ?>">
                                                <?= $project['completion_percentage'] ?>%
                                            </span>
                                        </div>
                                        <div class="progress mb-1" style="height: 25px;">
                                            <div class="progress-bar bg-<?= $project['completion_percentage'] >= 100 ? 'success' : ($project['completion_percentage'] >= 50 ? 'warning' : 'primary') ?>" 
                                                 style="width: <?= $project['completion_percentage'] ?>%">
                                                <?= $project['completion_percentage'] ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?= $project['completed_tasks'] ?>/<?= $project['total_tasks'] ?> tasks completed
                                            • <?= $project['department'] ?> Department
                                        </small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="col-md-4">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body text-center">
                                            <h3><?= count($projectProgress) ?></h3>
                                            <p class="mb-0">Active Projects</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body text-center">
                                            <h3><?= count($delayedTasks) ?></h3>
                                            <p class="mb-0">Delayed Tasks</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h3><?= count($teamActivity) ?></h3>
                                            <p class="mb-0">Active Users</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Team Activity Today -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5>👥 Team Activity - <?= date('d M Y', strtotime($today)) ?></h5>
                                    <div>
                                        <select class="form-select form-select-sm" onchange="window.location.href='?department='+this.value">
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
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
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
                                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($activity['department']) ?></span></td>
                                                    <td>
                                                        <span class="badge bg-<?= $activity['tasks_updated'] > 0 ? 'success' : 'danger' ?>">
                                                            <?= $activity['tasks_updated'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($activity['avg_progress']): ?>
                                                            <div class="progress" style="height: 20px; width: 80px;">
                                                                <div class="progress-bar" style="width: <?= $activity['avg_progress'] ?>%">
                                                                    <?= round($activity['avg_progress']) ?>%
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">No updates</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $activity['total_hours'] ?? 0 ?>h</td>
                                                    <td>
                                                        <?php 
                                                        $performance = 'Poor';
                                                        $badgeClass = 'danger';
                                                        if ($activity['tasks_updated'] > 0 && $activity['avg_progress'] > 50) {
                                                            $performance = 'Excellent';
                                                            $badgeClass = 'success';
                                                        } elseif ($activity['tasks_updated'] > 0) {
                                                            $performance = 'Good';
                                                            $badgeClass = 'warning';
                                                        }
                                                        ?>
                                                        <span class="badge bg-<?= $badgeClass ?>"><?= $performance ?></span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delayed Tasks Alert -->
                    <?php if (!empty($delayedTasks)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h5>⚠️ Delayed Tasks Alert</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Project</th>
                                                    <th>Task</th>
                                                    <th>Category</th>
                                                    <th>Progress</th>
                                                    <th>Days Since Update</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($delayedTasks as $task): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($task['project_name']) ?></td>
                                                    <td><?= htmlspecialchars($task['task_name']) ?></td>
                                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($task['category_name']) ?></span></td>
                                                    <td>
                                                        <div class="progress" style="height: 15px; width: 60px;">
                                                            <div class="progress-bar bg-danger" style="width: <?= $task['completion_percentage'] ?>%"></div>
                                                        </div>
                                                        <?= $task['completion_percentage'] ?>%
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-danger">
                                                            <?= $task['days_since_update'] ?? 'Never' ?> days
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="followUpTask(<?= $task['id'] ?>)">
                                                            Follow Up
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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
function followUpTask(taskId) {
    // Simple follow-up action - could be enhanced with notifications
    if (confirm('Send follow-up reminder for this task?')) {
        alert('Follow-up reminder sent! (Feature to be implemented)');
    }
}

// Auto-refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>