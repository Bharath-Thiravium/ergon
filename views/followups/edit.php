<?php
$active_page = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>✏️</span> Edit Follow-up</h1>
        <p>Update follow-up details</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/followups" class="btn btn--secondary">
            <span>←</span> Back to Follow-ups
        </a>
        <?php if (!empty($followup)): ?>
            <a href="/ergon/followups/view/<?= $followup['id'] ?>" class="btn btn--outline">
                <span>👁️</span> View
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($followup)): ?>
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Edit Follow-up</h2>
        </div>
        <div class="card__body">
            <form method="POST" class="form">
                <div class="form__group">
                    <label for="title" class="form__label">Title *</label>
                    <input type="text" id="title" name="title" class="form__input" 
                           value="<?= htmlspecialchars($followup['title'] ?? '') ?>" required>
                </div>

                <div class="form__group">
                    <label for="description" class="form__label">Description</label>
                    <textarea id="description" name="description" class="form__textarea" rows="4"><?= htmlspecialchars($followup['description'] ?? '') ?></textarea>
                </div>

                <div class="form__group">
                    <label for="followup_type" class="form__label">Type</label>
                    <select id="followup_type" name="followup_type" class="form__select" onchange="toggleTaskSelection()">
                        <option value="standalone" <?= ($followup['followup_type'] ?? '') === 'standalone' ? 'selected' : '' ?>>📞 Standalone Follow-up</option>
                        <option value="task" <?= ($followup['followup_type'] ?? '') === 'task' ? 'selected' : '' ?>>🔗 Task-linked Follow-up</option>
                    </select>
                </div>

                <div class="form__group" id="task_selection" style="<?= ($followup['followup_type'] ?? '') === 'task' ? '' : 'display: none;' ?>">
                    <label for="task_id" class="form__label">Select Task</label>
                    <select id="task_id" name="task_id" class="form__select">
                        <option value="">-- Select a Task --</option>
                        <?php foreach ($tasks as $task): ?>
                            <option value="<?= $task['id'] ?>" <?= ($followup['task_id'] ?? '') == $task['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($task['title']) ?>
                                <?php if ($task['due_date']): ?>
                                    (Due: <?= date('M d, Y', strtotime($task['due_date'])) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form__group">
                    <label for="contact_id" class="form__label">Contact (Optional)</label>
                    <select id="contact_id" name="contact_id" class="form__select">
                        <option value="">-- Select a Contact --</option>
                        <?php foreach ($contacts as $contact): ?>
                            <option value="<?= $contact['id'] ?>" <?= ($followup['contact_id'] ?? '') == $contact['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($contact['name']) ?>
                                <?php if ($contact['company']): ?>
                                    - <?= htmlspecialchars($contact['company']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form__group">
                    <label for="follow_up_date" class="form__label">Follow-up Date *</label>
                    <input type="date" id="follow_up_date" name="follow_up_date" class="form__input" 
                           value="<?= $followup['follow_up_date'] ?? date('Y-m-d') ?>" required>
                </div>

                <div class="form__actions">
                    <button type="submit" class="btn btn--primary">
                        <span>💾</span> Update Follow-up
                    </button>
                    <a href="/ergon/followups" class="btn btn--secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card__body">
            <div class="empty-state">
                <div class="empty-icon">❌</div>
                <h3>Follow-up Not Found</h3>
                <p>The requested follow-up could not be found.</p>
                <a href="/ergon/followups" class="btn btn--primary">
                    Back to Follow-ups
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.form {
    max-width: 600px;
}

.form__group {
    margin-bottom: 1.5rem;
}

.form__label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.form__input,
.form__select,
.form__textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.2s ease;
}

.form__input:focus,
.form__select:focus,
.form__textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form__textarea {
    resize: vertical;
    min-height: 100px;
}

.form__actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    color: #374151;
}

.empty-state p {
    margin: 0 0 1.5rem 0;
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

@media (max-width: 768px) {
    .form__actions {
        flex-direction: column;
    }
}
</style>

<script>
function toggleTaskSelection() {
    const followupType = document.getElementById('followup_type').value;
    const taskSelection = document.getElementById('task_selection');
    
    if (followupType === 'task') {
        taskSelection.style.display = 'block';
    } else {
        taskSelection.style.display = 'none';
        document.getElementById('task_id').value = '';
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
