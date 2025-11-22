<?php
$active_page = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>➕</span> Create Follow-up</h1>
        <p>Create a new follow-up for contact communication</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/followups" class="btn btn--secondary">
            <span>←</span> Back to Follow-ups
        </a>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Follow-up Details</h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/followups/create" id="followupForm">
            <div class="form-group">
                <label class="form-label" for="followup_type">Follow-up Type *</label>
                <select name="followup_type" id="followup_type" class="form-control" required onchange="toggleTaskSelection()">
                    <option value="standalone">Standalone Follow-up</option>
                    <option value="task">Task-linked Follow-up</option>
                </select>
                <small class="form-help">Choose whether this is a standalone follow-up or linked to a task</small>
            </div>
            
            <div id="taskSelection" class="form-group" style="display: none;">
                <label class="form-label" for="task_id">Link to Task</label>
                <select name="task_id" id="task_id" class="form-control">
                    <option value="">Select a task</option>
                    <?php if (isset($tasks) && !empty($tasks)): ?>
                        <?php foreach ($tasks as $task): ?>
                            <option value="<?= $task['id'] ?>">
                                <?= htmlspecialchars($task['title']) ?>
                                <?php if ($task['due_date']): ?>
                                    (Due: <?= date('M j, Y', strtotime($task['due_date'])) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small class="form-help">Select the task this follow-up is related to</small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="contact_id">Contact</label>
                    <select name="contact_id" id="contact_id" class="form-control">
                        <option value="">Select a contact (optional)</option>
                        <?php if (isset($contacts) && !empty($contacts)): ?>
                            <?php foreach ($contacts as $contact): ?>
                                <option value="<?= $contact['id'] ?>">
                                    <?= htmlspecialchars($contact['name']) ?>
                                    <?php if ($contact['company']): ?>
                                        - <?= htmlspecialchars($contact['company']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <small class="form-help">Select the contact this follow-up is for (optional)</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="follow_up_date">Follow-up Date *</label>
                    <input type="date" name="follow_up_date" id="follow_up_date" class="form-control" 
                           value="<?= date('Y-m-d') ?>" required>
                    <small class="form-help">When should this follow-up be done?</small>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="title">Title *</label>
                <input type="text" name="title" id="title" class="form-control" 
                       placeholder="e.g., Follow up on proposal discussion" required>
                <small class="form-help">Brief description of what this follow-up is about</small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4" 
                          placeholder="Additional details about this follow-up..."></textarea>
                <small class="form-help">Optional: Add more context or notes about this follow-up</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <span>💾</span> Create Follow-up
                </button>
                <a href="/ergon/followups" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.8rem;
    color: #6b7280;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 6px;
    border: 1px solid;
}

.alert-danger {
    background-color: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}

.alert-success {
    background-color: #f0fdf4;
    border-color: #bbf7d0;
    color: #16a34a;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
function toggleTaskSelection() {
    const followupType = document.getElementById('followup_type').value;
    const taskSelection = document.getElementById('taskSelection');
    const taskSelect = document.getElementById('task_id');
    
    if (followupType === 'task') {
        taskSelection.style.display = 'block';
        taskSelect.required = false; // Optional for now
    } else {
        taskSelection.style.display = 'none';
        taskSelect.required = false;
        taskSelect.value = '';
    }
}

// Auto-focus on title field
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('title').focus();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>