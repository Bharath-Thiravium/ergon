// Define missing global functions for onclick handlers
window.pauseTask = function(taskId) {
    updateTaskUI(taskId, 'on_break');
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    if (taskCard) taskCard.dataset.pauseStart = Date.now();
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    slaTimers[taskId] = setInterval(() => updateLocalCountdown(taskId), 1000);
    fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    }).catch(() => {});
};

// Add CSS for spin animation
const style = document.createElement('style');
style.textContent = `
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
`;
document.head.appendChild(style);

// SLA Dashboard and Timer Management (using external objects)
let slaDebugMode = false;
let slaUpdateCount = 0;
let lastValidSLAData = null;
let currentTaskId = null;
let slaTimers = {}; // Unified timer object

function updateLocalCountdown(taskId) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!taskCard) return;
    
    const status = taskCard.dataset.status;
    const slaDuration = parseInt(taskCard.dataset.slaDuration) || 900; // 15 minutes default
    const startTime = parseInt(taskCard.dataset.startTime) || 0;
    const activeSeconds = parseInt(taskCard.dataset.activeSeconds) || 0;
    
    const countdownDisplay = taskCard.querySelector(`#countdown-${taskId} .countdown-display`);
    const pauseTimer = taskCard.querySelector(`#pause-timer-${taskId}`);
    
    if (status === 'in_progress' && startTime > 0) {
        const elapsed = Math.floor((Date.now() - (startTime * 1000)) / 1000);
        const totalUsed = activeSeconds + elapsed;
        const remaining = Math.max(0, slaDuration - totalUsed);
        
        if (countdownDisplay) {
            const hours = Math.floor(remaining / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;
            countdownDisplay.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Add warning classes
            if (remaining <= 300) { // 5 minutes
                countdownDisplay.classList.add('countdown-display--warning');
            }
            if (remaining <= 0) {
                countdownDisplay.classList.add('countdown-display--expired');
            }
        }
    } else if (status === 'on_break' && pauseTimer) {
        const pauseStart = parseInt(taskCard.dataset.pauseStart) || Date.now();
        const pauseElapsed = Math.floor((Date.now() - pauseStart) / 1000);
        
        const hours = Math.floor(pauseElapsed / 3600);
        const minutes = Math.floor((pauseElapsed % 3600) / 60);
        const seconds = pauseElapsed % 60;
        pauseTimer.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
}

function stopSLATimer(taskId) {
    if (slaTimers[taskId]) {
        clearInterval(slaTimers[taskId]);
        delete slaTimers[taskId];
    }
}

function updateSLADashboard(data) {
    if (!data || !data.user_specific) return;
    
    const totalTime = document.querySelector('.sla-total-time');
    const usedTime = document.querySelector('.sla-used-time');
    const remainingTime = document.querySelector('.sla-remaining-time');
    const pauseTime = document.querySelector('.sla-pause-time');
    
    if (totalTime) totalTime.textContent = data.total_sla_time || '0h 0m';
    if (usedTime) usedTime.textContent = data.total_active_time || '0h 0m';
    if (remainingTime) remainingTime.textContent = data.total_remaining_time || '0h 0m';
    if (pauseTime) pauseTime.textContent = data.total_pause_time || '0h 0m';
}

function updateSLADashboardStats(stats) {
    const totalTasks = document.querySelector('.stat-item:nth-child(4) .stat-value');
    const completedTasks = document.querySelector('.stat-item:nth-child(1) .stat-value');
    const inProgressTasks = document.querySelector('.stat-item:nth-child(2) .stat-value');
    const postponedTasks = document.querySelector('.stat-item:nth-child(3) .stat-value');
    
    if (totalTasks) totalTasks.textContent = stats.total_tasks || 0;
    if (completedTasks) completedTasks.textContent = stats.completed_tasks || 0;
    if (inProgressTasks) inProgressTasks.textContent = stats.in_progress_tasks || 0;
    if (postponedTasks) postponedTasks.textContent = stats.postponed_tasks || 0;
}

function setButtonLoadingState(button, isLoading) {
    if (!button) return;
    
    if (isLoading) {
        button.disabled = true;
        const originalText = button.innerHTML;
        button.dataset.originalText = originalText;
        button.innerHTML = '<i class="bi bi-arrow-clockwise spinner"></i> Loading...';
    } else {
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    }
}

function debugLog(message, data = '') {
    if (slaDebugMode) {
        console.log(`[SLA DEBUG] ${message}`, data || '');
    }
}
// Log available debug commands
console.log('SLA Dashboard Debug Commands Available:');
console.log('- enableSLADebug() - Enable detailed logging');
console.log('- disableSLADebug() - Disable detailed logging');
console.log('- checkSLAStatus() - Show current SLA status');
console.log('- forceSLARefresh() - Force refresh SLA data');

window.openProgressModal = function(taskId, progress, status) {
    currentTaskId = taskId;
    document.getElementById('progressSlider').value = progress;
    document.getElementById('progressValue').textContent = progress;
    document.getElementById('progressDialog').style.display = 'flex';
};

window.postponeTask = function(taskId) {
    document.getElementById('postponeTaskId').value = taskId;
    document.getElementById('postponeForm').style.display = 'block';
    document.getElementById('postponeOverlay').style.display = 'block';
    document.getElementById('newDate').focus();
};

function closeDialog() {
    document.getElementById('progressDialog').style.display = 'none';
}

function saveProgress() {
    var progress = document.getElementById('progressSlider').value;
    var status = progress >= 100 ? 'completed' : progress > 0 ? 'in_progress' : 'assigned';
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    const requestData = { 
        task_id: parseInt(currentTaskId), 
        progress: parseInt(progress),
        status: status,
        reason: 'Progress updated via daily planner',
        csrf_token: csrfToken
    };
    
    fetch('/ergon/api/daily_planner_workflow.php?action=update-progress', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const actualProgress = data.progress || progress;
            const taskStatus = actualProgress >= 100 ? 'completed' : 'in_progress';
            
            updateTaskUI(currentTaskId, taskStatus, { percentage: actualProgress });
            updateProgressBar(currentTaskId, actualProgress);
            closeDialog();
            
            if (actualProgress < 100) {
                showNotification('Progress updated to ' + actualProgress + '% - Task will continue in progress', 'success');
            } else {
                showNotification('Task completed successfully!', 'success');
                stopSLATimer(currentTaskId);
            }
        } else {
            console.log('API Error:', data.message || 'Failed to update progress');
        }
    })
    .catch(error => {
        console.log('Progress update error:', error.message);
    });
}

function updateTaskUI(taskId, action, data = {}) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    const statusBadge = taskCard?.querySelector('.badge');
    const actionsDiv = taskCard?.querySelector('.task-card__actions');
    const countdownLabel = taskCard?.querySelector('.countdown-label');
    
    if (!taskCard || !statusBadge || !actionsDiv) return;
    
    // Update task card dataset
    taskCard.dataset.status = action === 'start' || action === 'resume' ? 'in_progress' : 
                              action === 'pause' ? 'on_break' : action;
    
    switch(action) {
        case 'start':
        case 'resume':
            statusBadge.textContent = 'In Progress';
            statusBadge.className = 'badge badge--in_progress';
            taskCard.className = taskCard.className.replace(/task-card--\w+/g, '') + ' task-card--active';
            
            if (countdownLabel) countdownLabel.textContent = 'Remaining';
            
            const pauseTimer = taskCard.querySelector('.pause-timer');
            const pauseLabel = taskCard.querySelector('.pause-timer-label');
            if (pauseTimer) pauseTimer.remove();
            if (pauseLabel) pauseLabel.remove();
            
            actionsDiv.innerHTML = `
                <button class="btn btn--sm btn--warning" onclick="pauseTask(${taskId})">
                    <i class="bi bi-pause"></i> Break
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, 'in_progress')">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
                <button class="btn btn--sm btn--secondary" onclick="postponeTask(${taskId})">
                    <i class="bi bi-calendar-plus"></i> Postpone
                </button>
            `;
            break;
            
        case 'pause':
            statusBadge.textContent = 'On Break';
            statusBadge.className = 'badge badge--on_break';
            taskCard.className = taskCard.className.replace(/task-card--\w+/g, '') + ' task-card--break';
            
            if (countdownLabel) countdownLabel.textContent = 'Paused';
            
            const countdownDiv = taskCard.querySelector('.countdown-timer');
            if (countdownDiv && !countdownDiv.querySelector('.pause-timer')) {
                countdownDiv.innerHTML += `
                    <div class="pause-timer" id="pause-timer-${taskId}">00:00:00</div>
                    <div class="pause-timer-label">Break Time</div>
                `;
            }
            
            actionsDiv.innerHTML = `
                <button class="btn btn--sm btn--success" onclick="resumeTask(${taskId})">
                    <i class="bi bi-play"></i> Resume
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, 'on_break')">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
                <button class="btn btn--sm btn--secondary" onclick="postponeTask(${taskId})">
                    <i class="bi bi-calendar-plus"></i> Postpone
                </button>
            `;
            break;
            
        case 'completed':
            statusBadge.textContent = 'Completed';
            statusBadge.className = 'badge badge--success';
            taskCard.className = taskCard.className.replace(/task-card--\w+/g, '') + ' task-card--completed';
            actionsDiv.innerHTML = `<span class="badge badge--success"><i class="bi bi-check-circle"></i> Done</span>`;
            break;
    }
}

// Console commands for debugging (use in browser console)
window.enableSLADebug = function() {
    slaDebugMode = true;
    console.log('SLA Debug mode enabled. Use disableSLADebug() to turn off.');
};

window.disableSLADebug = function() {
    slaDebugMode = false;
    console.log('SLA Debug mode disabled.');
};

window.checkSLAStatus = function() {
    console.log('SLA Dashboard Status:', {
        debugMode: slaDebugMode,
        updateCount: slaUpdateCount,
        lastValidData: lastValidSLAData,
        currentValues: {
            total: document.querySelector('.sla-total-time')?.textContent,
            used: document.querySelector('.sla-used-time')?.textContent,
            remaining: document.querySelector('.sla-remaining-time')?.textContent,
            pause: document.querySelector('.sla-pause-time')?.textContent
        }
    });
};

window.forceSLARefresh = function() {
    console.log('Manual SLA Dashboard refresh...');
    const refreshBtn = document.querySelector('.card__header button[onclick="forceSLARefresh()"]');
    setButtonLoadingState(refreshBtn, true);
    refreshSLADashboard().finally(() => {
        setButtonLoadingState(refreshBtn, false);
        showNotification('SLA Dashboard refreshed (manual)', 'success');
    });
};

function refreshTaskStatuses() {
    const plannerGrid = document.querySelector('.planner-grid');
    if (!plannerGrid) return;
    const currentDate = plannerGrid.dataset.selectedDate;
    const currentUserId = plannerGrid.dataset.userId;
    
    return fetch(`/ergon/api/daily_planner_workflow.php?action=task-statuses&date=${currentDate}&user_id=${currentUserId}&t=${Date.now()}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.tasks) {
            data.tasks.forEach(task => {
                const taskCard = document.querySelector(`[data-task-id="${task.id}"]`);
                if (taskCard && task.status === 'postponed' && !taskCard.dataset.postponed) {
                    taskCard.dataset.status = 'postponed';
                    taskCard.dataset.postponed = 'true';
                    taskCard.style.opacity = '0.6';
                    taskCard.style.pointerEvents = 'none';
                    
                    const statusBadge = taskCard.querySelector('.badge');
                    if (statusBadge) {
                        statusBadge.textContent = 'Postponed';
                        statusBadge.className = 'badge badge--warning';
                    }
                    
                    const actionsDiv = taskCard.querySelector('.task-card__actions');
                    if (actionsDiv) {
                        actionsDiv.innerHTML = `<span class="badge badge--warning"><i class="bi bi-calendar-plus"></i> Postponed</span>`;
                    }
                }
            });
        }
    })
    .catch(error => {
        console.log('Task status refresh failed:', error.message);
    });
}

function refreshSLADashboard() {
    const plannerGrid = document.querySelector('.planner-grid');
    if (!plannerGrid) return Promise.reject("Planner grid not found");
    const currentDate = plannerGrid.dataset.selectedDate;
    const currentUserId = plannerGrid.dataset.userId;
    
    return fetch(`/ergon/api/daily_planner_workflow.php?action=sla-dashboard&date=${currentDate}&user_id=${currentUserId}&t=${Date.now()}`, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.user_specific) {
            lastValidSLAData = data;
            updateSLADashboard(data);
            updateSLADashboardStats({
                total_tasks: data.total_tasks || 0,
                completed_tasks: data.completed_tasks || 0,
                in_progress_tasks: data.in_progress_tasks || 0,
                postponed_tasks: data.postponed_tasks || 0
            });
        }
    })
    .catch(error => {
        console.log('SLA Dashboard error:', error);
    });
}

function enforcePastDateRestrictions() {
    const plannerGrid = document.querySelector('.planner-grid');
    if (!plannerGrid) return;
    const selectedDate = plannerGrid.dataset.selectedDate;
    const today = new Date().toISOString().split('T')[0];
    const isPastDate = selectedDate < today;
    
    if (isPastDate) {
        document.querySelectorAll('.task-card').forEach(taskCard => {
            const buttons = taskCard.querySelectorAll('button[onclick*="startTask"], button[onclick*="pauseTask"], button[onclick*="resumeTask"], button[onclick*="postponeTask"]');
            buttons.forEach(btn => {
                if (!btn.disabled) {
                    btn.disabled = true;
                    btn.title = '🔒 Action disabled for past dates';
                }
            });
        });
    }
}

function showNotification(message, type = 'info') {
    console.log(`[NOTIFICATION ${type.toUpperCase()}]:`, message);
    try {
        const notification = document.createElement('div');
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        const bgColor = colors[type] || colors.info;
        notification.style.cssText = `position:fixed;top:20px;right:20px;background:${bgColor};color:white;padding:10px 20px;border-radius:5px;z-index:9999;box-shadow: 0 2px 10px rgba(0,0,0,0.2);`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    } catch (e) {}
}

function changeDate(date) {
    const dateSelector = document.getElementById('dateSelector');
    const originalDate = dateSelector.defaultValue;
    if (!date || !/^\d{4}-\d{2}-\d{2}$/.test(date)) {
        console.log('Invalid date format');
        dateSelector.value = originalDate;
        return;
    }
    
    const maxFutureDate = new Date();
    maxFutureDate.setDate(maxFutureDate.getDate() + 30);
    const maxDateStr = maxFutureDate.toISOString().split('T')[0];
    
    if (date > maxDateStr) {
        console.log('Cannot view dates more than 30 days in the future');
        dateSelector.value = originalDate;
        return;
    }
    
    const minDate = new Date();
    minDate.setDate(minDate.getDate() - 90);
    const minDateStr = minDate.toISOString().split('T')[0];
    
    if (date < minDateStr) {
        console.log('Cannot view dates more than 90 days in the past');
        dateSelector.value = originalDate;
        return;
    }
    
    window.location.href = '/ergon/workflow/daily-planner/' + date;
}

function activatePostponedTask(taskId) {
    fetch('/ergon/api/daily_planner_workflow.php?action=activate-postponed', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ task_id: parseInt(taskId) })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Simplest way to reflect the change
        }
    })
    .catch(error => {
        console.log('Activate postponed task error:', error.message);
    });
}

window.startTask = function(taskId) {
    if (!taskId) return;
    updateTaskUI(taskId, 'start');
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    slaTimers[taskId] = setInterval(() => updateLocalCountdown(taskId), 1000);
    showNotification('Task started', 'success');
    
    fetch('/ergon/api/daily_planner_workflow.php?action=start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    }).catch(() => {});
}

window.resumeTask = function(taskId) {
    updateTaskUI(taskId, 'resume');
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    if (taskCard) delete taskCard.dataset.pauseStart;
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    slaTimers[taskId] = setInterval(() => updateLocalCountdown(taskId), 1000);
    showNotification('Task resumed', 'success');
    
    fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    }).catch(() => {});
}

function cancelPostpone() {
    document.getElementById('postponeForm').style.display = 'none';
    document.getElementById('postponeOverlay').style.display = 'none';
    document.getElementById('newDate').value = '';
    document.getElementById('postponeReason').value = '';
}

function submitPostpone() {
    const taskId = document.getElementById('postponeTaskId').value;
    const newDate = document.getElementById('newDate').value;
    const reason = document.getElementById('postponeReason').value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    if (!newDate) {
        alert('Please select a date');
        return;
    }
    
    fetch('/ergon/api/daily_planner_workflow.php?action=postpone', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            task_id: parseInt(taskId), 
            new_date: newDate,
            reason: reason || 'No reason provided',
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Simplest way to reflect the change
        } else {
            alert('Failed to postpone task: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('An error occurred while postponing the task.');
    });
}

function updateProgressBar(taskId, percentage) {
    const progressBar = document.querySelector(`[data-task-id="${taskId}"] .progress-fill`);
    const progressValue = document.querySelector(`[data-task-id="${taskId}"] .progress-value`);
    
    if (progressBar) progressBar.style.width = percentage + '%';
    if (progressValue) progressValue.textContent = percentage + '%';
}

document.addEventListener('DOMContentLoaded', function() {
    const plannerGrid = document.querySelector('.planner-grid');
    if (plannerGrid) {
        plannerGrid.dataset.selectedDate = document.getElementById('dateSelector').value;
        // This assumes user_id is available in the global scope or a data attribute.
        // For this refactor, I'll add it to the planner-grid element in the PHP.
    }

    enforcePastDateRestrictions();    
    
    document.querySelectorAll('.task-card').forEach(item => {
        const taskId = item.dataset.taskId;
        const status = item.dataset.status;
        
        if (status === 'in_progress' || status === 'on_break') {
            if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
            slaTimers[taskId] = setInterval(() => updateLocalCountdown(taskId), 1000);
        }
        
        if (status === 'on_break') {
            const pauseStartTime = item.dataset.pauseStartTime;
            if (pauseStartTime && pauseStartTime !== '') {
                item.dataset.pauseStart = new Date(pauseStartTime).getTime();
            } else {
                item.dataset.pauseStart = Date.now();
            }
        }
    });
    
    refreshSLADashboard();
    
    const slider = document.getElementById('progressSlider');
    if (slider) {
        slider.oninput = function() {
            document.getElementById('progressValue').textContent = this.value;
        }
    }
});

(function() {
    const originalAlert = window.alert;
    window.alert = function(message) {
        console.log('[ALERT BLOCKED]:', message);
        if (typeof showNotification === 'function') {
            showNotification(message, 'info');
        }
    };
})();