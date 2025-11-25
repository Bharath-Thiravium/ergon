// Initialize timer objects
const timers = {};
const slaTimers = {};

// Define global functions
window.pauseTask = function(taskId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            task_id: parseInt(taskId),
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'on_break');
            showPauseTimer(taskId);
            showNotification('Task paused', 'success');
        } else {
            alert('Error: ' + (data.error || data.message));
        }
    })
    .catch(error => {
        alert('Error pausing task: ' + error.message);
    });
};

window.resumeTask = function(taskId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            task_id: parseInt(taskId),
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'in_progress');
            startCountdownTimer(taskId);
            showNotification('Task resumed', 'success');
        } else {
            alert('Error: ' + (data.error || data.message));
        }
    })
    .catch(error => {
        alert('Error resuming task: ' + error.message);
    });
};

window.startTask = function(taskId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('/ergon/api/daily_planner_workflow.php?action=start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            task_id: parseInt(taskId),
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'in_progress');
            startCountdownTimer(taskId);
            showNotification('Task started', 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error starting task: ' + error.message);
    });
};

window.openProgressModal = function(taskId, progress, status) {
    window.currentTaskId = taskId;
    const progressDialog = document.getElementById('progressDialog');
    const progressSlider = document.getElementById('progressSlider');
    const progressValue = document.getElementById('progressValue');
    
    if (progressDialog && progressSlider && progressValue) {
        progressSlider.value = progress || 0;
        progressValue.textContent = progress || 0;
        progressDialog.style.display = 'flex';
    }
};

window.postponeTask = function(taskId) {
    const newDate = prompt('Enter new date (YYYY-MM-DD):');
    if (!newDate) return;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('/ergon/api/daily_planner_workflow.php?action=postpone', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            task_id: parseInt(taskId), 
            new_date: newDate,
            reason: 'Postponed via daily planner',
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Task postponed to ' + newDate);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error postponing task: ' + error.message);
    });
};

function updateTaskUI(taskId, action, data = {}) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    const statusBadge = document.querySelector(`#status-${taskId}`);
    const actionsDiv = document.querySelector(`#actions-${taskId}`);
    
    if (!taskCard || !statusBadge || !actionsDiv) return;
    
    let newStatus, newActions;
    
    switch(action) {
        case 'in_progress':
            newStatus = 'in_progress';
            statusBadge.textContent = 'In Progress';
            statusBadge.className = 'badge badge--success';
            taskCard.className = 'task-card task-card--in-progress';
            newActions = `
                <button class="btn btn--sm btn--warning" onclick="pauseTask(${taskId})" title="Pause this task">
                    <i class="bi bi-pause"></i> Pause
                </button>
                <button class="btn btn--sm btn--info" onclick="showPostponeModal(${taskId})" title="Postpone this task">
                    <i class="bi bi-calendar-plus"></i> Postpone
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, '${newStatus}')" title="Update task completion progress">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
            `;
            break;
            
        case 'on_break':
            newStatus = 'on_break';
            statusBadge.textContent = 'On Break';
            statusBadge.className = 'badge badge--warning';
            taskCard.className = 'task-card task-card--paused';
            newActions = `
                <button class="btn btn--sm btn--success" onclick="resumeTask(${taskId})" title="Resume working on this task">
                    <i class="bi bi-play"></i> Resume
                </button>
                <button class="btn btn--sm btn--info" onclick="showPostponeModal(${taskId})" title="Postpone this task">
                    <i class="bi bi-calendar-plus"></i> Postpone
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, '${newStatus}')" title="Update task completion progress">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
            `;
            break;
            
        case 'pending':
            newStatus = 'pending';
            statusBadge.textContent = 'Pending';
            statusBadge.className = 'badge badge--secondary';
            taskCard.className = 'task-card';
            newActions = `
                <button class="btn btn--sm btn--success" onclick="startTask(${taskId})" title="Start working on this task">
                    <i class="bi bi-play"></i> Start
                </button>
                <button class="btn btn--sm btn--info" onclick="showPostponeModal(${taskId})" title="Postpone this task">
                    <i class="bi bi-calendar-plus"></i> Postpone
                </button>
            `;
            break;
            
        default:
            return;
    }
    
    if (newActions) {
        actionsDiv.innerHTML = newActions;
    }
    
    // Update countdown label
    const countdownLabel = taskCard.querySelector(`#countdown-${taskId} .countdown-label`);
    if (countdownLabel) {
        countdownLabel.textContent = newStatus === 'in_progress' ? 'Remaining' : (newStatus === 'on_break' ? 'Paused' : 'SLA Time');
    }
}

// Simple countdown timer without server fetching
function startCountdownTimer(taskId) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!taskCard) return;
    
    // Clear existing timer
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    
    // Get SLA duration from task card data or default to 15 minutes
    const slaDuration = parseInt(taskCard.dataset.slaDuration) || 900;
    let remainingTime = slaDuration;
    
    // Start simple countdown
    slaTimers[taskId] = setInterval(() => {
        remainingTime = Math.max(0, remainingTime - 1);
        
        const countdownEl = taskCard.querySelector(`#countdown-${taskId} .countdown-display`);
        if (countdownEl) {
            countdownEl.textContent = formatTime(remainingTime);
            countdownEl.className = remainingTime < 300 ? 'countdown-display countdown-display--warning' : 'countdown-display';
            
            if (remainingTime === 0) {
                countdownEl.textContent = 'OVERDUE';
                countdownEl.className = 'countdown-display countdown-display--overdue';
                clearInterval(slaTimers[taskId]);
            }
        }
    }, 1000);
}

// Update timer display for pause state
function showPauseTimer(taskId) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!taskCard) return;
    
    const countdownEl = taskCard.querySelector(`#countdown-${taskId} .countdown-display`);
    let pauseStartTime = Date.now();
    
    // Clear SLA timer
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    
    // Start pause timer
    slaTimers[taskId] = setInterval(() => {
        const pauseDuration = Math.floor((Date.now() - pauseStartTime) / 1000);
        
        if (countdownEl) {
            countdownEl.textContent = `Paused (Break: ${formatTime(pauseDuration)})`;
            countdownEl.className = 'countdown-display countdown-display--paused';
        }
    }, 1000);
}

// Format seconds to HH:MM:SS with validation
function formatTime(seconds) {
    // Handle invalid or null values
    if (!seconds || seconds <= 0 || isNaN(seconds)) {
        return '00:00:00';
    }
    
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

function showPostponeModal(taskId) {
    document.getElementById('postponeTaskId').value = taskId;
    document.getElementById('postponeForm').style.display = 'block';
    document.getElementById('postponeOverlay').style.display = 'block';
    document.getElementById('newDate').focus();
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
            cancelPostpone();
            
            // Update SLA Dashboard with actual database values
            if (data.updated_stats) {
                updateSLADashboardStats(data.updated_stats);
            }
            
            // Mark task as postponed in UI permanently
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
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
                    actionsDiv.innerHTML = `<span class="badge badge--warning"><i class="bi bi-calendar-plus"></i> Postponed to ${newDate}</span>`;
                }
            }
            
            showNotification(`Task postponed to ${newDate}`, 'success');
            
            // Immediately update SLA Dashboard postponed count
            const postponedStat = document.querySelector('.stat-item:nth-child(3) .stat-value');
            if (postponedStat) {
                const currentCount = parseInt(postponedStat.textContent) || 0;
                postponedStat.textContent = currentCount + 1;
            }
            
            // Also refresh SLA Dashboard
            refreshSLADashboard();
            
            // Prevent any auto-refresh by marking as processed
            window.postponedTasks = window.postponedTasks || new Set();
            window.postponedTasks.add(taskId);
            
        } else {
            alert(data.message || 'Failed to postpone task');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error postponing task');
    });
}

function stopTimer(taskId) {
    if (timers[taskId]) {
        clearInterval(timers[taskId]);
        delete timers[taskId];
    }
    if (slaTimers[taskId]) {
        clearInterval(slaTimers[taskId]);
        delete slaTimers[taskId];
    }
}

// Missing utility functions
function showNotification(message, type) {
    console.log(`${type.toUpperCase()}: ${message}`);
}

function refreshSLADashboard() {
    // Refresh dashboard if needed
}

function updateSLADashboardStats(stats) {
    // Update dashboard stats if needed
}

function setButtonLoadingState(button, isLoading) {
    if (!button) return;
    
    if (isLoading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> Loading...';
    } else {
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    }
}

// Progress dialog functionality
document.addEventListener('DOMContentLoaded', function() {
    const progressSlider = document.getElementById('progressSlider');
    const progressValue = document.getElementById('progressValue');
    
    if (progressSlider && progressValue) {
        progressSlider.addEventListener('input', function() {
            progressValue.textContent = this.value;
        });
    }
});

window.closeDialog = function() {
    const progressDialog = document.getElementById('progressDialog');
    if (progressDialog) {
        progressDialog.style.display = 'none';
    }
};

window.saveProgress = function() {
    const taskId = window.currentTaskId;
    const progress = document.getElementById('progressSlider').value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    if (!taskId) {
        alert('No task selected');
        return;
    }
    
    fetch('/ergon/api/daily_planner_workflow.php?action=update-progress', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            task_id: parseInt(taskId),
            progress: parseInt(progress),
            status: progress >= 100 ? 'completed' : 'in_progress',
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeDialog();
            showNotification(`Progress updated to ${progress}%`, 'success');
            // Optionally reload or update UI
        } else {
            alert('Error: ' + (data.error || data.message));
        }
    })
    .catch(error => {
        alert('Error updating progress: ' + error.message);
    });
};

// Compatibility functions
function pauseTask(taskId) { return window.pauseTask(taskId); }
function resumeTask(taskId) { return window.resumeTask(taskId); }
function startTask(taskId) { return window.startTask(taskId); }
function openProgressModal(taskId, progress, status) { return window.openProgressModal(taskId, progress, status); }
function postponeTask(taskId) { return window.postponeTask(taskId); }