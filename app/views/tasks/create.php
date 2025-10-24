<?php
$title = 'Create Task';
$active_page = 'tasks';
ob_start();
?>

<div class="header-actions" style="margin-bottom: var(--space-6);">
    <a href="/ergon/tasks" class="btn btn--secondary">Back to Tasks</a>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Task Details</h2>
    </div>
    <div class="card__body">
        <form method="POST" class="auth-form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Task Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-control" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4" required></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Assign To</label>
                    <select name="assigned_to" class="form-control" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($data['users'] as $user): ?>
                            <?php if ($user['role'] === 'user'): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Task Type</label>
                    <select name="task_type" class="form-control" required>
                        <option value="ad-hoc" selected>Ad-hoc</option>
                        <option value="checklist">Checklist</option>
                        <option value="milestone">Milestone</option>
                        <option value="timed">Timed</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Deadline</label>
                <input type="datetime-local" name="deadline" class="form-control" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Create Task</button>
                <a href="/ergon/tasks" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>