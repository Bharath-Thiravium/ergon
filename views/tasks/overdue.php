<?php
$title = 'Overdue Tasks';
$active_page = 'tasks';
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-danger">⚠️ Overdue Tasks</h3>
                    <span class="badge badge-danger"><?= count($tasks) ?> Tasks</span>
                </div>
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> No overdue tasks! Great job!
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Assignee</th>
                                        <th>Deadline</th>
                                        <th>Hours Overdue</th>
                                        <th>Progress</th>
                                        <th>Priority</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                        <tr class="<?= $task['hours_overdue'] > 48 ? 'table-danger' : 'table-warning' ?>">
                                            <td>
                                                <strong><?= htmlspecialchars($task['title']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 50)) ?>...</small>
                                            </td>
                                            <td><?= htmlspecialchars($task['assigned_to_name']) ?></td>
                                            <td><?= date('M j, Y', strtotime($task['deadline'])) ?></td>
                                            <td>
                                                <span class="badge badge-danger"><?= $task['hours_overdue'] ?>h</span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-warning" style="width: <?= $task['progress'] ?>%">
                                                        <?= $task['progress'] ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $task['priority'] === 'high' ? 'danger' : ($task['priority'] === 'medium' ? 'warning' : 'info') ?>">
                                                    <?= ucfirst($task['priority']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="escalateTask(<?= $task['id'] ?>)">
                                                    Escalate
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function escalateTask(taskId) {
    if (confirm('Escalate this overdue task to management?')) {
        fetch(`/ergon_clean/public/api/tasks/escalate/${taskId}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'}
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert('Task escalated successfully');
                  location.reload();
              }
          });
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>