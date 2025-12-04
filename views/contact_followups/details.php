<?php
$active_page = 'contact_followups';
include __DIR__ . '/../shared/modal_component.php';
ob_start();
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    showModal('followupDetailsModal');
});
</script>

<?php renderModalCSS(); ?>

<?php 
$statusClass = match($followup['status']) { 'completed' => 'success', 'in_progress' => 'info', 'postponed' => 'warning', 'cancelled' => 'danger', default => 'secondary' };
$statusIcon = match($followup['status']) { 'completed' => '✅', 'in_progress' => '⚡', 'postponed' => '🔄', 'cancelled' => '❌', default => '⏳' };

$content = '<div style="display:grid;gap:0.5rem;font-size:0.85rem">';
$content .= '<div><strong>Title:</strong> ' . htmlspecialchars($followup['title']) . '</div>';
$content .= '<div><strong>Status:</strong> <span class="badge badge--' . $statusClass . '">' . $statusIcon . ' ' . ucfirst(str_replace('_', ' ', $followup['status'])) . '</span></div>';
$content .= '<div><strong>Due:</strong> 📅 ' . date('M d, Y', strtotime($followup['follow_up_date'])) . '</div>';
if ($followup['contact_name']) {
    $content .= '<div><strong>Contact:</strong> 👤 ' . htmlspecialchars($followup['contact_name']);
    if ($followup['contact_phone']) $content .= ' | 📞 ' . htmlspecialchars($followup['contact_phone']);
    if ($followup['contact_company']) $content .= ' | 🏢 ' . htmlspecialchars($followup['contact_company']);
    $content .= '</div>';
}
if ($followup['description']) {
    $content .= '<div><strong>Description:</strong><div style="background:#f8fafc;padding:0.5rem;border-radius:4px;margin-top:0.25rem;display:flex;gap:0.5rem;align-items:flex-start">' . nl2br(htmlspecialchars($followup['description'])) . '<div style="display:flex;gap:0.25rem;flex-shrink:0"><button class="ab-btn ab-btn--edit" onclick="editFollowup(\'F_' . $followup['id'] . '\')" title="Edit" style="width:24px;height:24px;padding:0;display:flex;align-items:center;justify-content:center"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/><path d="M20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button><button class="ab-btn ab-btn--delete" onclick="deleteFollowup(\'F_' . $followup['id'] . '\')" title="Delete" style="width:24px;height:24px;padding:0;display:flex;align-items:center;justify-content:center"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-9l-1 1H5v2h14V4z"/></svg></button></div></div></div>';
}
if (!empty($history)) {
    $content .= '<div><strong>Activity (' . count($history) . '):</strong><div style="display:flex;flex-direction:column;gap:0.5rem;margin-top:0.25rem">';
    foreach ($history as $entry) {
        $actionIcon = match($entry['action']) { 'created' => '✨', 'rescheduled' => '📅', 'completed' => '✅', 'cancelled' => '❌', 'postponed' => '🔄', 'edited' => '✏️', default => '📝' };
        $content .= '<div style="font-size:0.75rem;color:#6b7280;border-left:2px solid #e5e7eb;padding-left:0.5rem">' . $actionIcon . ' ' . ucfirst($entry['action']) . ' - ' . date('M d', strtotime($entry['created_at']));
        if (!empty($entry['notes'])) {
            $content .= '<div style="font-size:0.7rem;color:#9ca3af;margin-top:0.25rem;font-style:italic">' . htmlspecialchars($entry['notes']) . '</div>';
        }
        $content .= '</div>';
    }
    $content .= '</div></div>';
}
$content .= '</div>';

$footer = '<button type="button" class="btn btn--secondary" onclick="closeModal(\'followupDetailsModal\'); window.history.back()">← Back</button>';

renderModal('followupDetailsModal', '👁️ Follow-up', $content, $footer, ['size' => 'small']);
echo '<style>#followupDetailsModal .dialog-content { max-width: 380px !important; min-width: 300px !important; } #followupDetailsModal .dialog-header, #followupDetailsModal .dialog-body, #followupDetailsModal .dialog-footer { padding: 0.75rem !important; }</style>';
?>

<?php renderModalJS(); $editContent = '<form method="POST" id="editForm"><input type="hidden" name="followup_id" id="editFollowupId"><div class="form-group"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div><div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div><div class="form-group"><label class="form-label">Date *</label><input type="date" name="follow_up_date" class="form-control" required></div></form>'; $editFooter = '<button type="button" onclick="closeModal(\'editModal\')" class="btn btn--secondary">Cancel</button><button type="submit" form="editForm" class="btn btn--primary">Update</button>'; renderModal('editModal', 'Edit Follow-up', $editContent, $editFooter, ['size' => 'small']);
echo '<style>#editModal .dialog-content { max-width: 380px !important; min-width: 300px !important; } #editModal .dialog-header, #editModal .dialog-body, #editModal .dialog-footer { padding: 0.75rem !important; }</style>'; ?>

<script>
function editFollowup(id) {
    id = id.replace('F_', '');
    showModal('editModal');
    document.getElementById('editFollowupId').value = id;
    const form = document.getElementById('editForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch(`/ergon/contacts/followups/edit/${id}`, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.json())
        .then(data => { if (data.success) { closeModal('editModal'); location.reload(); } else { alert('Error: ' + (data.error || 'Failed to update follow-up')); } })
        .catch(error => { console.error('Edit error:', error); alert('Network error occurred'); });
    };
}

function deleteFollowup(id) {
    id = id.replace('F_', '');
    if (confirm('Are you sure you want to delete this follow-up? This action cannot be undone.')) {
        fetch(`/ergon/contacts/followups/delete/${id}`, { method: 'POST', body: new FormData(), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.json())
        .then(data => { if (data.success) { alert('Follow-up deleted successfully!'); location.reload(); } else { alert('Error: ' + (data.error || 'Failed to delete follow-up')); } })
        .catch(error => { console.error('Delete error:', error); alert('Network error occurred'); });
    }
}
</script>

<style>
.badge { display: inline-block; padding: 0.2rem 0.4rem; border-radius: 3px; font-size: 0.75rem; font-weight: 500; }
.badge--success { background: #d1fae5; color: #059669; }
.badge--info { background: #dbeafe; color: #1d4ed8; }
.badge--warning { background: #fef3c7; color: #d97706; }
.badge--danger { background: #fee2e2; color: #dc2626; }
.badge--secondary { background: #f3f4f6; color: #6b7280; }
#followupDetailsModal .dialog-content { max-width: 380px !important; min-width: 300px !important; }
#editModal .dialog-content { max-width: 380px !important; min-width: 300px !important; }
.ab-btn { background: none; border: none; cursor: pointer; padding: 0.25rem; border-radius: 3px; transition: background 0.2s; }
.ab-btn:hover { background: #e5e7eb; }
.ab-btn--edit { color: #3b82f6; }
.ab-btn--delete { color: #ef4444; }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
