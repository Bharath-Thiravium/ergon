<?php
$content = ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Edit Task</h1>
        <p>Update task details</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/tasks" class="btn btn--secondary">← Back to Tasks</a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Task Details</h2>
    </div>
    <div class="card__body">
        <form method="POST">
            <div class="form-group">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($task['title'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($task['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="assigned_to" class="form-label">Assign To</label>
                <select class="form-control" id="assigned_to" name="assigned_to" required>
                    <option value="">Select User</option>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($task['assigned_to'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="deadline" class="form-label">Due Date</label>
                <input type="date" class="form-control" id="deadline" name="deadline" value="<?= $task['deadline'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="priority" class="form-label">Priority</label>
                <select class="form-control" id="priority" name="priority">
                    <option value="low" <?= ($task['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                    <option value="medium" <?= ($task['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="high" <?= ($task['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select class="form-control" id="status" name="status">
                    <option value="assigned" <?= ($task['status'] ?? '') === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                    <option value="in_progress" <?= ($task['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= ($task['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="on_hold" <?= ($task['status'] ?? '') === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                </select>
            </div>
            <button type="submit" class="btn btn--primary">Update Task</button>
            <a href="/ergon/tasks" class="btn btn--secondary">Cancel</a>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Edit Task';
$active_page = 'tasks';
include __DIR__ . '/../layouts/dashboard.php';
?>