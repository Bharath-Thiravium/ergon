<?php
$title = 'Evening Update';
$active_page = 'evening_update';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🌅</span> Evening Update</h1>
        <p>Report your progress - <?= date('M d, Y', strtotime($current_date)) ?></p>
    </div>
    <div class="page-actions">
        <input type="date" id="updateDate" value="<?= $current_date ?>" onchange="changeUpdateDate(this.value)" class="form-control" style="width: auto;">
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📊</span> Today's Progress Report
        </h2>
    </div>
    <div class="card__body">
        <?php if (empty($planned_tasks)): ?>
            <div class="empty-state">
                <div class="empty-icon">📅</div>
                <h3>No tasks planned for today</h3>
                <p>Visit <a href="/ergon/planner">Daily Planner</a> to plan your tasks.</p>
            </div>
        <?php else: ?>
            <form id="eveningUpdateForm">
                <input type="hidden" name="date" value="<?= $current_date ?>">
                
                <?php foreach ($planned_tasks as $index => $task): ?>
                    <div class="update-item">
                        <div class="task-header">
                            <h4><?= htmlspecialchars($task['title']) ?></h4>
                            <span class="task-type badge badge--<?= $task['task_type'] === 'assigned' ? 'info' : 'secondary' ?>">
                                <?= $task['task_type'] === 'assigned' ? '📋 Assigned' : '📝 Personal' ?>
                            </span>
                        </div>
                        
                        <div class="update-form">
                            <input type="hidden" name="updates[<?= $index ?>][planner_id]" value="<?= $task['id'] ?>">
                            <input type="hidden" name="updates[<?= $index ?>][task_id]" value="<?= $task['task_id'] ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Progress (%)</label>
                                    <div class="progress-input">
                                        <input type="range" 
                                               name="updates[<?= $index ?>][progress_percentage]" 
                                               min="0" max="100" 
                                               value="<?= $task['progress_percentage'] ?? 0 ?>"
                                               oninput="updateProgressDisplay(this, <?= $index ?>)"
                                               class="progress-slider">
                                        <span id="progress-display-<?= $index ?>" class="progress-display">
                                            <?= $task['progress_percentage'] ?? 0 ?>%
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Time Spent (hours)</label>
                                    <input type="number" 
                                           name="updates[<?= $index ?>][actual_hours_spent]" 
                                           step="0.25" min="0" max="12"
                                           value="<?= $task['actual_hours_spent'] ?? 0 ?>"
                                           class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="updates[<?= $index ?>][completion_status]" class="form-control">
                                        <option value="not_started" <?= ($task['completion_status'] ?? '') === 'not_started' ? 'selected' : '' ?>>Not Started</option>
                                        <option value="in_progress" <?= ($task['completion_status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="completed" <?= ($task['completion_status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="blocked" <?= ($task['completion_status'] ?? '') === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="updates[<?= $index ?>][notes]" 
                                          class="form-control" 
                                          rows="2" 
                                          placeholder="Progress notes and achievements..."><?= htmlspecialchars($task['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">
                        <span>💾</span> Submit Update
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function changeUpdateDate(date) {
    window.location.href = '/ergon/evening-update?date=' + date;
}

function updateProgressDisplay(slider, index) {
    document.getElementById('progress-display-' + index).textContent = slider.value + '%';
}

document.getElementById('eveningUpdateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const updates = [];
    const updatesMap = {};
    
    for (let [key, value] of formData.entries()) {
        const match = key.match(/updates\[(\d+)\]\[(\w+)\]/);
        if (match) {
            const index = match[1];
            const field = match[2];
            
            if (!updatesMap[index]) updatesMap[index] = {};
            updatesMap[index][field] = value;
        }
    }
    
    for (let index in updatesMap) {
        updates.push(updatesMap[index]);
    }
    
    const submitData = new FormData();
    submitData.append('updates', JSON.stringify(updates));
    submitData.append('date', formData.get('date'));
    
    fetch('/ergon/evening-update/submit', {
        method: 'POST',
        body: submitData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Update submitted successfully!');
            window.location.href = '/ergon/planner';
        } else {
            alert('Error: ' + data.error);
        }
    });
});
</script>

<style>
.update-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    background: #f9fafb;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.progress-input {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.progress-slider {
    flex: 1;
}

.progress-display {
    font-weight: 600;
    color: #059669;
    min-width: 50px;
}

.form-actions {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>