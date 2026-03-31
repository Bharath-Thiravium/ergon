// unified-daily-planner.js
// Provides ONLY the helper functions not covered by working-timer.js or sla-dashboard-fix.js.
// Does NOT redefine: startTask, pauseTask, resumeTask, postponeTask, openProgressModal,
//                    updateTaskUI, showNotification, forceSLARefresh, cancelPostpone, submitPostpone

// ── Date navigation ───────────────────────────────────────────────────────────
function changeDate(date) {
    if (!date || !/^\d{4}-\d{2}-\d{2}$/.test(date)) return;
    const today = new Date();
    const max = new Date(); max.setDate(today.getDate() + 30);
    const min = new Date(); min.setDate(today.getDate() - 90);
    const d = new Date(date);
    if (d > max || d < min) {
        document.getElementById('dateSelector').value =
            document.querySelector('.planner-grid')?.dataset.selectedDate || date;
        return;
    }
    window.location.href = '/ergon/workflow/daily-planner/' + date;
}

// ── Postponed task activation ─────────────────────────────────────────────────
function activatePostponedTask(taskId, event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('/ergon/api/daily_planner_workflow.php?action=start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId), csrf_token: csrf })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (typeof showNotification === 'function') showNotification('Task activated', 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            if (typeof showNotification === 'function') showNotification(data.message || 'Failed to activate', 'error');
        }
    })
    .catch(() => { if (typeof showNotification === 'function') showNotification('Network error', 'error'); });
}

// ── Historical view stubs ─────────────────────────────────────────────────────
function showHistoryInfo() {
    if (typeof showNotification === 'function')
        showNotification('Historical view: task execution is read-only for past dates.', 'info');
}

function showTaskHistory(taskId, title) {
    if (typeof showNotification === 'function')
        showNotification('History for: ' + title, 'info');
}

function showReadOnlyProgress(taskId, progress) {
    if (typeof showNotification === 'function')
        showNotification('Task completed at ' + progress + '%', 'info');
}

// ── Postpone modal helpers ────────────────────────────────────────────────────
// Guard: working-timer.js already defines cancelPostpone/submitPostpone.
// Only define here if they are missing (e.g. script load order changes).
if (typeof window.cancelPostpone !== 'function') {
    window.cancelPostpone = function() {
        const f = document.getElementById('postponeForm');
        const o = document.getElementById('postponeOverlay');
        if (f) f.style.display = 'none';
        if (o) o.style.display = 'none';
        const d = document.getElementById('newDate');
        const r = document.getElementById('postponeReason');
        if (d) d.value = '';
        if (r) r.value = '';
    };
}

if (typeof window.submitPostpone !== 'function') {
    window.submitPostpone = function() {
        const taskId  = document.getElementById('postponeTaskId')?.value;
        const newDate = document.getElementById('newDate')?.value;
        const reason  = document.getElementById('postponeReason')?.value || '';
        const csrf    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        if (!newDate) {
            if (typeof showNotification === 'function') showNotification('Please select a date', 'error');
            return;
        }
        fetch('/ergon/api/daily_planner_workflow.php?action=postpone', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task_id: parseInt(taskId), new_date: newDate, reason: reason, csrf_token: csrf })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (typeof showNotification === 'function') showNotification('Task postponed', 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                if (typeof showNotification === 'function') showNotification(data.message || 'Failed to postpone', 'error');
            }
        })
        .catch(() => { if (typeof showNotification === 'function') showNotification('Network error', 'error'); });
    };
}
