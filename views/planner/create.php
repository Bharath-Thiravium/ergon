<?php
$title = 'Add Task to Planner';
$active_page = 'planner';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>➕</span> Add Task to Planner</h1>
        <p>Plan your task for <?= date('M d, Y', strtotime($current_date)) ?></p>
    </div>
    <div class="page-actions">
        <a href="/ergon/planner?date=<?= $current_date ?>" class="btn btn--secondary">
            <span>←</span> Back to Planner
        </a>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert--danger">
        <?php if ($_GET['error'] === 'title_required'): ?>
            <strong>Error:</strong> Task title is required.
        <?php elseif ($_GET['error'] === 'save_failed'): ?>
            <strong>Error:</strong> Failed to save task. Please try again.
        <?php elseif ($_GET['error'] === 'database_error'): ?>
            <strong>Error:</strong> Database error occurred. Please try again.
        <?php else: ?>
            <strong>Error:</strong> An error occurred. Please try again.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📝</span> Task Details
        </h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/planner/create" id="plannerForm">
            <input type="hidden" name="date" value="<?= htmlspecialchars($current_date) ?>">
            
            <div class="form-row">
                <div class="form-group form-group--flex-2">
                    <label class="form-label">Task Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="Enter task title">
                </div>
                <div class="form-group">
                    <label class="form-label">Task Type</label>
                    <select name="task_type" class="form-control">
                        <option value="personal">📝 Personal Task</option>
                        <option value="assigned">📋 Assigned Task</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Enter task description"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="planned_start_time" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" name="planned_duration" class="form-control" value="60" min="15" max="480" step="15">
                </div>
                <div class="form-group">
                    <label class="form-label">Priority Order</label>
                    <input type="number" name="priority_order" class="form-control" value="1" min="1" max="10">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <span>💾</span> Save Task
                </button>
                <a href="/ergon/planner?date=<?= $current_date ?>" class="btn btn--secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('plannerForm').addEventListener('submit', function(e) {
    const title = this.querySelector('input[name="title"]').value.trim();
    if (!title) {
        e.preventDefault();
        alert('Task title is required');
        return false;
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>