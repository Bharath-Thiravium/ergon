// Global timer storage
const slaTimers = {};

// Define pauseTask function globally
window.pauseTask = function(taskId) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    const currentStatus = taskCard?.dataset.status;
    
    if (currentStatus !== 'in_progress') {
        showNotification(`Cannot pause task. Status: ${currentStatus}. Must be 'in_progress'.`, 'error');
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ task_id: parseInt(taskId, 10), csrf_token: csrfToken })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            taskCard.dataset.status = 'on_break';
            updateTaskUI(taskId, 'on_break');
            window.taskTimer.startPause(taskId, Math.floor(Date.now() / 1000));
            showNotification('Task paused', 'info');
        } else {
            showNotification('Failed to pause: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Network error: ' + error.message, 'error');
    });
};

window.resumeTask = function(taskId) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    const currentStatus = taskCard?.dataset.status;
    
    if (currentStatus !== 'on_break') {
        showNotification(`Cannot resume task. Status: ${currentStatus}. Must be 'on_break'.`, 'error');
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ task_id: parseInt(taskId, 10), csrf_token: csrfToken })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            taskCard.dataset.status = 'in_progress';
            updateTaskUI(taskId, 'in_progress');
            window.taskTimer.stopPause(taskId);
            window.taskTimer.start(taskId, parseInt(taskCard.dataset.slaDuration) || 900, Math.floor(Date.now() / 1000));
            showNotification('Task resumed', 'success');
        } else {
            showNotification('Failed to resume: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Network error: ' + error.message, 'error');
    });
};

window.startTask = function(taskId) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('/ergon/api/daily_planner_workflow.php?action=start', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ task_id: parseInt(taskId, 10), csrf_token: csrfToken })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            taskCard.dataset.status = 'in_progress';
            updateTaskUI(taskId, 'in_progress');
            window.taskTimer.start(taskId, parseInt(taskCard.dataset.slaDuration) || 900, Math.floor(Date.now() / 1000));
            showNotification('Task started', 'success');
        } else {
            showNotification('Failed to start: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Network error: ' + error.message, 'error');
    });
};

// UI update function
function updateTaskUI(taskId, newStatus) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!taskCard) return;
    
    const statusBadge = taskCard.querySelector(`#status-${taskId}`);
    if (statusBadge) {
        statusBadge.textContent = newStatus.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        statusBadge.className = `badge badge--${newStatus}`;
    }
    
    const actionsDiv = taskCard.querySelector(`#actions-${taskId}`);
    if (actionsDiv) {
        if (newStatus === 'in_progress') {
            actionsDiv.innerHTML = `
                <button class="btn btn--sm btn--warning" onclick="pauseTask(${taskId})" title="Take a break from this task">
                    <i class="bi bi-pause"></i> Break
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, '${newStatus}')" title="Update task completion progress">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
                <button class="btn btn--sm btn--secondary" onclick="postponeTask(${taskId})" title="Postpone task to another date">
                    <i class="bi bi-calendar-plus"></i> Postpone
                </button>
            `;
        } else if (newStatus === 'on_break') {
            actionsDiv.innerHTML = `
                <button class="btn btn--sm btn--success" onclick="resumeTask(${taskId})" title="Resume working on this task">
                    <i class="bi bi-play"></i> Resume
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, '${newStatus}')" title="Update task completion progress">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
                <button class="btn btn--sm btn--secondary" onclick="postponeTask(${taskId})" title="Postpone task to another date">
                    <i class="bi bi-calendar-plus"></i> Postpone
                </button>
            `;
        } else if (newStatus === 'postponed') {
            actionsDiv.innerHTML = `
                <span class="badge badge--warning"><i class="bi bi-calendar-plus"></i> Postponed</span>
            `;
        }
    }
    
    const countdownLabel = taskCard.querySelector(`#countdown-${taskId} .countdown-label`);
    if (countdownLabel) {
        countdownLabel.textContent = newStatus === 'in_progress' ? 'Remaining' : (newStatus === 'on_break' ? 'Paused' : 'SLA Time');
    }
}

window.openProgressModal = function(taskId, progress, status) {
    alert('Progress modal for task ' + taskId + ' (progress: ' + progress + '%, status: ' + status + ')');
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
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
                taskCard.dataset.status = 'postponed';
                updateTaskUI(taskId, 'postponed');
                window.taskTimer.stop(taskId);
                window.taskTimer.stopPause(taskId);
            }
            showNotification('Task postponed to ' + newDate, 'success');
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error postponing task: ' + error.message, 'error');
    });
};

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification--${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
    `;
    
    // Add styles if not exists
    if (!document.getElementById('notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 16px;
                border-radius: 4px;
                color: white;
                z-index: 10000;
                max-width: 300px;
                animation: slideIn 0.3s ease;
            }
            .notification--success { background: #10b981; }
            .notification--error { background: #ef4444; }
            .notification--info { background: #3b82f6; }
            .notification-content { display: flex; justify-content: space-between; align-items: center; }
            .notification-close { background: none; border: none; color: white; font-size: 18px; cursor: pointer; }
            @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 3000);
}

function refreshSLADashboard() {
    // Refresh dashboard if needed
}

// Compatibility functions
function pauseTask(taskId) { return window.pauseTask(taskId); }
function resumeTask(taskId) { return window.resumeTask(taskId); }
function startTask(taskId) { return window.startTask(taskId); }
function openProgressModal(taskId, progress, status) { return window.openProgressModal(taskId, progress, status); }
function postponeTask(taskId) { return window.postponeTask(taskId); }