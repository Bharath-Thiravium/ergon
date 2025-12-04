<?php
$editContent = '
<form method="POST" id="editForm" action="">
    <input type="hidden" name="followup_id" id="editFollowupId">
    <div class="form-group">
        <label class="form-label">Follow-up Type *</label>
        <select name="followup_type" id="editFollowupType" class="form-control" disabled>
            <option value="standalone">Standalone Follow-up</option>
            <option value="task">Task-linked Follow-up</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Contact *</label>
        <select name="contact_id" id="editContactId" class="form-control" disabled>
            <option value="">Select a contact</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Title *</label>
        <input type="text" name="title" class="form-control" placeholder="e.g., Follow up on proposal discussion" required>
    </div>
    <div class="form-group">
        <label class="form-label">Follow-up Date *</label>
        <input type="date" name="follow_up_date" class="form-control" required>
    </div>
    <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="4" placeholder="Additional details about this follow-up..."></textarea>
    </div>
</form>';

$editFooter = '
<button type="button" onclick="closeModal(\'editModal\')" class="btn btn--secondary">
    Cancel
</button>
<button type="submit" form="editForm" class="btn btn--primary">
    ✏️ Update
</button>';

renderModal('editModal', 'Edit Follow-up', $editContent, $editFooter, ['icon' => '✏️']);
?>
