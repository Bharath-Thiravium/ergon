<?php
$contactId = isset($_GET['contact_id']) ? str_replace('C_', '', $_GET['contact_id']) : '';
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

if (!$isAjax) {
    header('Location: /ergon/contacts/followups');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<style>
#followupModal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
}

#followupModal .dialog-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

#followupModal .dialog-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

#followupModal .dialog-header h4 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
}

#followupModal .dialog-close {
    background: transparent;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    width: 32px;
    height: 32px;
    padding: 0;
}

#followupModal .dialog-body {
    padding: 1.5rem;
}

#followupModal .dialog-footer {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

#followupModal .form-group {
    margin-bottom: 1rem;
}

#followupModal .form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

#followupModal .form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 0.875rem;
    box-sizing: border-box;
}

#followupModal .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    min-height: 44px;
}

#followupModal .btn--primary {
    background: #3b82f6;
    color: white;
}

#followupModal .btn--secondary {
    background: #f3f4f6;
    color: #1f2937;
}

.hidden {
    display: none;
}
</style>
</head>
<body>

<div id="followupModal">
    <div class="dialog-content">
        <div class="dialog-header">
            <h4>➕ Create Follow-up</h4>
            <button type="button" class="dialog-close" onclick="closeModal()">&times;</button>
        </div>
        
        <form id="followupForm" method="POST" action="/ergon/contacts/followups/store">
            <div class="dialog-body">
                <div class="form-group">
                    <label class="form-label">Follow-up Type *</label>
                    <select name="followup_type" id="followup_type" class="form-control" required>
                        <option value="standalone">Standalone Follow-up</option>
                        <option value="task">Task-linked Follow-up</option>
                    </select>
                </div>
                
                <div id="taskField" class="form-group hidden">
                    <label class="form-label">Link to Task</label>
                    <select name="task_id" id="task_id" class="form-control">
                        <option value="">Select a task</option>
                        <?php if (isset($tasks) && !empty($tasks)): ?>
                            <?php foreach ($tasks as $task): ?>
                                <option value="<?= $task['id'] ?>"><?= htmlspecialchars($task['title']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contact *</label>
                    <select name="contact_id" id="contact_id" class="form-control" required>
                        <option value="">Select a contact</option>
                        <?php if (isset($contacts) && !empty($contacts)): ?>
                            <?php foreach ($contacts as $contact): ?>
                                <option value="<?= $contact['id'] ?>" <?= ($contact['id'] == $contactId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($contact['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., Follow up on proposal" required autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Follow-up Date *</label>
                    <input type="date" name="follow_up_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Additional details..."></textarea>
                </div>
            </div>
            
            <div class="dialog-footer">
                <button type="button" class="btn btn--secondary" id="cancelBtn">Cancel</button>
                <button type="submit" class="btn btn--primary">💾 Create Follow-up</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal() {
    var modal = document.getElementById('followupModal');
    if (modal && modal.parentElement) modal.parentElement.remove();
}
document.getElementById('cancelBtn').onclick = closeModal;
document.getElementById('followup_type').onchange = function() {
    document.getElementById('taskField').classList.toggle('hidden', this.value !== 'task');
};
document.getElementById('followupForm').onsubmit = function(e) {
    e.preventDefault();
    var contactId = document.getElementById('contact_id').value;
    fetch(this.action, {
        method: 'POST',
        body: new FormData(this),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeModal();
            window.location.replace(contactId && contactId !== '0' ? '/ergon/contacts/followups/view/C_' + contactId : '/ergon/contacts/followups');
        } else {
            alert('Error: ' + (data.error || 'Failed to create follow-up'));
        }
    })
    .catch(error => { console.error('Error:', error); alert('Network error occurred'); });
};
</script>

</body>
</html>
