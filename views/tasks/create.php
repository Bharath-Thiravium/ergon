<?php
$content = ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Create Task</h1>
        <p>Add a new task to the system</p>
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
        <form id="createTaskForm">
            <div class="form-group">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="assigned_to" class="form-label">Assign To</label>
                <select class="form-control" id="assigned_to" name="assigned_to">
                    <option value="">Select User</option>
                </select>
            </div>
            <div class="form-group">
                <label for="due_date" class="form-label">Due Date</label>
                <input type="date" class="form-control" id="due_date" name="due_date">
            </div>
            <div class="form-group">
                <label for="priority" class="form-label">Priority</label>
                <select class="form-control" id="priority" name="priority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <button type="submit" class="btn btn--primary">Create Task</button>
            <a href="/ergon/tasks" class="btn btn--secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
document.getElementById('createTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/ergon/tasks/create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Task created successfully!');
            window.location.href = '/ergon/tasks';
        } else {
            alert(data.error || 'Failed to create task');
        }
    })
    .catch(error => {
        alert('Error creating task');
    });
});
</script>

<?php
$content = ob_get_clean();
$title = 'Create Task';
$active_page = 'tasks';
include __DIR__ . '/../layouts/dashboard.php';
?>
