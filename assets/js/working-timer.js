// ── Helpers ───────────────────────────────────────────────────────────────────

function fmt(ms) {
    const sec = Math.max(0, Math.floor(ms / 1000));
    return String(Math.floor(sec / 3600)).padStart(2, '0') + ':' +
           String(Math.floor((sec % 3600) / 60)).padStart(2, '0') + ':' +
           String(sec % 60).padStart(2, '0');
}

function isRunningStatus(s) {
    return s === 'in_progress' || s === 'overdue';
}

function showNotification(msg, type) {
    const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
    const n = document.createElement('div');
    n.style.cssText = `position:fixed;top:20px;right:20px;padding:12px 16px;border-radius:4px;
                       color:#fff;z-index:10000;background:${colors[type] || colors.error};
                       box-shadow:0 2px 8px rgba(0,0,0,.2);font-size:14px;`;
    n.textContent = msg;
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 3000);
}

// ── Core SLA calculation (spec-exact) ─────────────────────────────────────────
//
//  working_time_ms:
//    in_progress/overdue → paused_accum_ms + (now - start_ts_ms)
//    on_break            → paused_accum_ms   (frozen)
//    otherwise           → 0
//
//  remaining_ms = sla_duration_ms - working_time_ms

function getWorkingTimeMs(card) {
    const status      = card.dataset.status || '';
    const accumMs     = parseInt(card.dataset.pausedAccumMs) || 0;
    const startTsMs   = parseInt(card.dataset.startTsMs)     || 0;

    if (isRunningStatus(status) && startTsMs > 0) {
        return accumMs + Math.max(0, Date.now() - startTsMs);
    }
    return accumMs;   // paused or not started — frozen
}

function getSlaDurationMs(card) {
    const sec = parseInt(card.dataset.slaDuration) || 0;
    if (sec > 0) return sec * 1000;
    // sla_duration_seconds not yet written (task not started) — use sla_hours from task creation
    const hours = parseFloat(card.dataset.slaHours) || 0;
    return hours > 0 ? Math.max(60, Math.round(hours * 3600)) * 1000 : 900000; // 900000ms = 15 min only as last resort
}

// ── 1-second UI tick ──────────────────────────────────────────────────────────

setInterval(() => {
    document.querySelectorAll('.task-card').forEach(card => {
        const taskId  = card.dataset.taskId;
        const status  = card.dataset.status || '';
        const display = document.querySelector('#countdown-' + taskId + ' .countdown-display');
        if (!display || !taskId) return;

        const slaDurMs    = getSlaDurationMs(card);
        const workingMs   = getWorkingTimeMs(card);
        const remainingMs = slaDurMs - workingMs;

        // ── Elapsed display ──
        let color, label;
        if (status === 'not_started' || status === 'assigned') {
            display.textContent = '00:00:00';
            display.style.color = '#6b7280';
            display.style.fontWeight = 'normal';
            display.style.opacity   = '1';
        } else if (status === 'on_break') {
            // Timer is frozen — show accumulated work time, dim it to signal paused
            display.textContent     = fmt(workingMs);
            display.style.color     = '#f59e0b';
            display.style.fontWeight = 'normal';
            display.style.opacity   = '0.6';
            label = 'Paused';
        } else {
            display.textContent  = fmt(workingMs);
            display.style.fontWeight = isRunningStatus(status) ? 'bold' : 'normal';
            display.style.opacity   = '1';

            if (status === 'completed') {
                display.style.color = '#10b981';
                label = 'Total Time';
            } else if (remainingMs <= 0) {
                display.style.color = '#dc2626';
                label = 'Overdue';
                if (status === 'in_progress') markTaskOverdue(taskId);
            } else {
                display.style.color = '#059669';
                label = 'Elapsed';
            }
        }

        const timingLabel = card.querySelector('.timing-card--primary .timing-label');
        if (timingLabel && label) timingLabel.textContent = label;

        // ── Remaining SLA display — frozen during break ──
        const remainEl = document.getElementById('remaining-sla-' + taskId);
        if (remainEl) {
            if (status === 'not_started' || status === 'assigned') {
                remainEl.textContent   = fmt(slaDurMs);
                remainEl.style.color   = '';
                remainEl.style.opacity = '1';
            } else if (status === 'on_break') {
                // Frozen — show remaining at the moment of pause, dimmed
                remainEl.textContent   = remainingMs > 0 ? fmt(remainingMs) : '00:00:00';
                remainEl.style.color   = remainingMs <= 0 ? '#dc2626' : '#f59e0b';
                remainEl.style.opacity = '0.6';
            } else {
                remainEl.textContent   = remainingMs > 0 ? fmt(remainingMs) : '00:00:00';
                remainEl.style.color   = remainingMs <= 0 ? '#dc2626' : '';
                remainEl.style.opacity = '1';
            }
        }
    });
}, 1000);

// ── Overdue sync ──────────────────────────────────────────────────────────────

function markTaskOverdue(taskId) {
    const card = document.querySelector('[data-task-id="' + taskId + '"]');
    if (!card || card.dataset.status === 'overdue' || card.dataset.overdueSyncing === '1') return;
    card.dataset.overdueSyncing = '1';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('/ergon/api/daily_planner_workflow.php?action=mark-overdue', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId), csrf_token: csrf })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            card.dataset.status = 'overdue';
            applyCardState(card, 'overdue');
            updateTaskUI(taskId, 'overdue');
            showNotification('Task is now overdue', 'error');
            if (window.forceSLARefresh) window.forceSLARefresh();
        }
    })
    .catch(() => {})
    .finally(() => { delete card.dataset.overdueSyncing; });
}

// ── Apply visual state to a card immediately (no tick delay) ─────────────────

function applyCardState(card, status) {
    // Swap the activity CSS class
    card.classList.remove('task-card--active', 'task-card--break', 'task-card--completed');
    if (status === 'in_progress' || status === 'overdue') card.classList.add('task-card--active');
    else if (status === 'on_break')                       card.classList.add('task-card--break');
    else if (status === 'completed')                      card.classList.add('task-card--completed');

    // Update the timing label immediately
    const timingLabel = card.querySelector('.timing-card--primary .timing-label');
    if (timingLabel) {
        if (status === 'on_break')                              timingLabel.textContent = 'Paused';
        else if (status === 'in_progress')                      timingLabel.textContent = 'Elapsed';
        else if (status === 'overdue')                          timingLabel.textContent = 'Overdue';
        else if (status === 'completed')                        timingLabel.textContent = 'Total Time';
    }

    // Freeze / unfreeze the two timer displays immediately
    const taskId     = card.dataset.taskId;
    const frozen     = status === 'on_break';
    const opacity    = frozen ? '0.6' : '1';
    const display    = card.querySelector('#countdown-' + taskId + ' .countdown-display');
    const remainEl   = document.getElementById('remaining-sla-' + taskId);

    if (display)  { display.style.opacity  = opacity; if (frozen) display.style.color  = '#f59e0b'; }
    if (remainEl) { remainEl.style.opacity = opacity; if (frozen) remainEl.style.color = '#f59e0b'; }
}

// ── UI update after action ────────────────────────────────────────────────────

function updateTaskUI(taskId, status) {
    const badge      = document.querySelector('#status-' + taskId);
    const actionsDiv = document.querySelector('#actions-' + taskId);
    const card       = document.querySelector('[data-task-id="' + taskId + '"]');

    if (badge) {
        badge.textContent = status === 'overdue' ? 'Overdue'
            : status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        badge.className = status === 'overdue' ? 'badge badge--danger' : 'badge badge--' + status;
    }

    if (actionsDiv && card) {
        const prog = parseInt(card.dataset.completedPercentage) || 0;
        if (isRunningStatus(status)) {
            actionsDiv.innerHTML =
                '<button type="button" onclick="pauseTask(' + taskId + ',event)" class="btn btn--sm btn--warning"><i class="bi bi-pause"></i> Break</button>' +
                '<button class="btn btn--sm btn--primary" onclick="openProgressModal(' + taskId + ',' + prog + ',\'' + status + '\')"><i class="bi bi-percent"></i> Update Progress</button>' +
                '<button type="button" class="btn btn--sm btn--secondary" onclick="postponeTask(' + taskId + ',event)"><i class="bi bi-calendar-plus"></i> Postpone</button>';
        } else if (status === 'on_break') {
            actionsDiv.innerHTML =
                '<button type="button" onclick="resumeTask(' + taskId + ',event)" class="btn btn--sm btn--success"><i class="bi bi-play"></i> Resume</button>' +
                '<button class="btn btn--sm btn--primary" onclick="openProgressModal(' + taskId + ',' + prog + ',\'on_break\')"><i class="bi bi-percent"></i> Update Progress</button>' +
                '<button type="button" class="btn btn--sm btn--secondary" onclick="postponeTask(' + taskId + ',event)"><i class="bi bi-calendar-plus"></i> Postpone</button>';
        }
    }
}

// ── Actions ───────────────────────────────────────────────────────────────────

window.startTask = function(taskId, event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    const btn = event ? (event.target.closest('button') || event.target) : null;
    if (btn && btn.disabled) return false;
    if (btn) btn.disabled = true;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch('/ergon/api/daily_planner_workflow.php?action=start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId), csrf_token: csrf })
    })
    .then(r => r.text())
    .then(text => {
        let data;
        try { data = JSON.parse(text); }
        catch (_) {
            console.error('start API non-JSON response:', text.slice(0, 500));
            throw new Error('Server returned non-JSON response');
        }
        if (data.success) {
            const card = document.querySelector('[data-task-id="' + taskId + '"]');
            if (card) {
                card.dataset.status         = 'in_progress';
                card.dataset.startTsMs      = String(data.start_ts_ms);
                card.dataset.pausedAccumMs  = '0';
                card.dataset.pauseStartTsMs = '0';
                card.dataset.slaDuration    = String(data.sla_duration_seconds);
                applyCardState(card, 'in_progress');
            }
            updateTaskUI(taskId, 'in_progress');
            showNotification('Task started', 'success');
            if (window.forceSLARefresh) window.forceSLARefresh();
        } else {
            showNotification('Failed to start: ' + (data.message || ''), 'error');
        }
    })
    .catch(e => showNotification('Error: ' + e.message, 'error'))
    .finally(() => { if (btn) btn.disabled = false; });
    return false;
};

window.pauseTask = function(taskId, event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    const btn = event ? (event.target.closest('button') || event.target) : null;
    if (btn && btn.disabled) return false;
    const card = document.querySelector('[data-task-id="' + taskId + '"]');
    if (!card || !isRunningStatus(card.dataset.status)) {
        showNotification('Task must be running to pause', 'error'); return false;
    }
    if (btn) btn.disabled = true;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId), csrf_token: csrf })
    })
    .then(r => r.text())
    .then(text => {
        let data;
        try { data = JSON.parse(text); }
        catch (_) {
            console.error('pause API non-JSON response:', text.slice(0, 500));
            throw new Error('Server returned non-JSON response');
        }
        // Also handle the case where DB is already on_break (DOM/DB desync)
        const alreadyOnBreak = !data.success && data.message && data.message.includes('on_break');
        if (data.success || alreadyOnBreak) {
            const c = document.querySelector('[data-task-id="' + taskId + '"]');
            if (c) {
                c.dataset.status         = 'on_break';
                c.dataset.startTsMs      = '0';
                if (data.pause_start_ts_ms) c.dataset.pauseStartTsMs = String(data.pause_start_ts_ms);
                if (data.paused_accum_ms)   c.dataset.pausedAccumMs  = String(data.paused_accum_ms);
                applyCardState(c, 'on_break');
            }
            updateTaskUI(taskId, 'on_break');
            if (data.success) showNotification('Break started', 'success');
            if (window.forceSLARefresh) window.forceSLARefresh();
        } else {
            showNotification('Failed to pause: ' + (data.message || ''), 'error');
        }
    })
    .catch(e => showNotification('Error: ' + e.message, 'error'))
    .finally(() => { if (btn) btn.disabled = false; });
    return false;
};

window.resumeTask = function(taskId, event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    const btn = event ? (event.target.closest('button') || event.target) : null;
    if (btn && btn.disabled) return false;
    if (btn) btn.disabled = true;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId), csrf_token: csrf })
    })
    .then(r => r.text())
    .then(text => {
        let data;
        try { data = JSON.parse(text); }
        catch (_) {
            console.error('resume API non-JSON response:', text.slice(0, 500));
            throw new Error('Server returned non-JSON response');
        }
        if (data.success) {
            const card = document.querySelector('[data-task-id="' + taskId + '"]');
            if (card) {
                card.dataset.status         = data.status;
                card.dataset.startTsMs      = String(data.start_ts_ms);
                card.dataset.pausedAccumMs  = String(data.paused_accum_ms);
                card.dataset.pauseStartTsMs = '0';
                if (data.sla_duration_seconds) card.dataset.slaDuration = String(data.sla_duration_seconds);
                applyCardState(card, data.status);
            }
            updateTaskUI(taskId, data.status);
            showNotification('Task resumed', 'success');
            if (window.forceSLARefresh) window.forceSLARefresh();
        } else {
            showNotification('Failed to resume: ' + (data.message || ''), 'error');
        }
    })
    .catch(e => showNotification('Error: ' + e.message, 'error'))
    .finally(() => { if (btn) btn.disabled = false; });
    return false;
};

window.postponeTask = function(taskId, event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    const form    = document.getElementById('postponeForm');
    const overlay = document.getElementById('postponeOverlay');
    const idInput = document.getElementById('postponeTaskId');
    const dateInput = document.getElementById('newDate');
    if (!form || !overlay || !idInput) return false;
    idInput.value = taskId;
    if (dateInput) dateInput.value = '';
    form.style.display    = 'block';
    overlay.style.display = 'block';
    if (dateInput) dateInput.focus();
    return false;
};

window.cancelPostpone = function() {
    const f = document.getElementById('postponeForm');
    const o = document.getElementById('postponeOverlay');
    if (f) f.style.display = 'none';
    if (o) o.style.display = 'none';
};

window.submitPostpone = function() {
    const taskId  = document.getElementById('postponeTaskId')?.value;
    const newDate = document.getElementById('newDate')?.value;
    const reason  = document.getElementById('postponeReason')?.value || '';
    const csrf    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (!newDate) { showNotification('Please select a date', 'error'); return; }
    fetch('/ergon/api/daily_planner_workflow.php?action=postpone', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId), new_date: newDate, reason: reason, csrf_token: csrf })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { showNotification('Task postponed', 'success'); setTimeout(() => location.reload(), 600); }
        else showNotification(data.message || 'Failed to postpone', 'error');
    })
    .catch(e => showNotification('Error: ' + e.message, 'error'));
};

// ── Init + event delegation ──────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function() {
    // Normalise all numeric ms/sec data attributes
    document.querySelectorAll('.task-card').forEach(card => {
        ['startTsMs', 'pauseStartTsMs', 'pausedAccumMs', 'slaDuration'].forEach(attr => {
            card.dataset[attr] = String(parseInt(card.dataset[attr]) || 0);
        });
    });


});
