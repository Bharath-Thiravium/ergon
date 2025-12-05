<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Create Follow-up</title>
    <link rel="stylesheet" href="/ergon/assets/css/ergon.css">
    <link rel="stylesheet" href="/ergon/assets/css/contact-followup-responsive.css">
</head>
<body>
<?php
include __DIR__ . '/../shared/modal_component.php';

$contactId = isset($_GET['contact_id']) ? str_replace('C_', '', $_GET['contact_id']) : '';
$createContent = '<form method="POST" id="createForm" action="/ergon/contacts/followups/store">
    <div class="form-group">
        <label class="form-label">Follow-up Type *</label>
        <select name="followup_type" id="followup_type" class="form-control" required onchange="toggleTaskSelection()">
            <option value="standalone">Standalone Follow-up</option>
            <option value="task">Task-linked Follow-up</option>
        </select>
        <small class="form-help">Choose whether this is a standalone follow-up or linked to a task</small>
    </div>
    
    <div id="taskSelection" class="form-group" style="display: none;">
        <label class="form-label">Link to Task *</label>
        <select name="task_id" id="task_id" class="form-control">
            <option value="">Select a task</option>';
            if (isset($tasks)) {
                foreach ($tasks as $task) {
                    $createContent .= '<option value="' . $task['id'] . '">' . htmlspecialchars($task['title']) . '</option>';
                }
            }
    $createContent .= '</select>
        <small class="form-help">Select the task this follow-up is related to</small>
    </div>
    
    <div class="form-group">
        <label class="form-label">Contact *</label>
        <select name="contact_id" id="contact_id" class="form-control" required>';
        $createContent .= '<option value="">Select a contact</option>';
        foreach ($contacts as $contact) {
            $selected = ($contact['id'] == $contactId) ? 'selected' : '';
            $createContent .= '<option value="' . $contact['id'] . '" ' . $selected . '>' . htmlspecialchars($contact['name']) . '</option>';
        }
    $createContent .= '</select>
        <small class="form-help">Select the contact this follow-up is for</small>
    </div>
    
    <div class="form-group">
        <label class="form-label">Title *</label>
        <input type="text" name="title" class="form-control" placeholder="e.g., Follow up on proposal discussion" required>
        <small class="form-help">Brief description of what this follow-up is about</small>
    </div>
    
    <div class="form-group">
        <label class="form-label">Follow-up Date *</label>
        <input type="date" name="follow_up_date" class="form-control" value="' . date('Y-m-d') . '" required>
        <small class="form-help">When should this follow-up be done?</small>
    </div>
    
    <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="4" placeholder="Additional details about this follow-up..."></textarea>
        <small class="form-help">Optional: Add more context or notes about this follow-up</small>
    </div>
</form>';

$createFooter = '<button type="button" onclick="window.history.back()" class="btn btn--secondary">Cancel</button><button type="submit" form="createForm" class="btn btn--primary">💾 Create Follow-up</button>';

renderModal('createModal', 'Create Follow-up', $createContent, $createFooter, ['icon' => '➕']);
?>

<?php renderModalCSS(); ?>
<?php renderModalJS(); ?>

<style>
.form-help { display: block; margin-top: 0.25rem; font-size: 0.8rem; color: #6b7280; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    showModal('createModal');
    document.querySelector('.dialog-close').onclick = function() {
        window.history.back();
    };
});

function toggleTaskSelection() {
    const followupType = document.getElementById('followup_type').value;
    const taskSelection = document.getElementById('taskSelection');
    const taskSelect = document.getElementById('task_id');
    
    if (followupType === 'task') {
        taskSelection.style.display = 'block';
        taskSelect.required = true;
    } else {
        taskSelection.style.display = 'none';
        taskSelect.required = false;
    }
}

document.getElementById('createForm').onsubmit = function(e) {
    e.preventDefault();
    const contactId = document.getElementById('contact_id').value;
    const formData = new FormData(this);
    fetch(this.action, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(response => response.json())
    .then(data => { 
        if (data.success) { 
            window.location.replace('/ergon/contacts/followups/view/C_' + contactId);
        } else { 
            alert('Error: ' + (data.error || 'Failed to create follow-up')); 
        } 
    })
    .catch(error => { console.error('Error:', error); alert('Network error occurred'); });
};
</script>
</body>
</html>
