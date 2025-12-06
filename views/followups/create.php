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

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Follow-up Details</h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/followups/store" id="followupForm">
            <div class="form-group">
                <label class="form-label">Follow-up Type *</label>
                <select name="followup_type" id="followup_type" class="form-control" required onchange="toggleTaskField()">
                    <option value="standalone">Standalone Follow-up</option>
                    <option value="task">Task-linked Follow-up</option>
                </select>
            </div>
            
            <div id="taskField" class="form-group" style="display: none;">
                <label class="form-label">Link to Task</label>
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
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Contact</label>
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
                </div>
                
                <div class="form-group">
                    <label class="form-label">Follow-up Date *</label>
                    <input type="date" name="follow_up_date" id="follow_up_date" class="form-control" 
                           value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" name="title" id="title" class="form-control" 
                       placeholder="e.g., Follow up on proposal discussion" required autofocus>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4" 
                          placeholder="Additional details about this follow-up..."></textarea>
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
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    background: white;
    color: #1f2937;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    text-decoration: none;
}

.btn--primary {
    background: #3b82f6;
    color: white;
}

.btn--primary:hover {
    background: #2563eb;
}

.btn--secondary {
    background: #f8fafc;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn--secondary:hover {
    background: #f3f4f6;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
function toggleTaskField() {
    const type = document.getElementById('followup_type').value;
    const taskField = document.getElementById('taskField');
    const taskSelect = document.getElementById('task_id');
    
    if (type === 'task') {
        taskField.style.display = 'block';
    } else {
        taskField.style.display = 'none';
        taskSelect.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('followupForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/ergon/followups';
            } else {
                alert('Error: ' + (data.error || 'Failed to create follow-up'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    });
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/dashboard.php';
?>
