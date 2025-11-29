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

// Debounce mechanism to prevent multiple modal triggers
let modalDebounce = {};

window.openProgressModal = function(taskId, progress, status) {
    // Prevent multiple rapid clicks
    if (modalDebounce[taskId]) {
        return;
    }
    
    modalDebounce[taskId] = true;
    
    // Clear debounce after 1 second
    setTimeout(() => {
        modalDebounce[taskId] = false;
    }, 1000);
    
    console.log('Progress modal for task ' + taskId + ' (progress: ' + progress + '%, status: ' + status + ')');
    
    // Create and show modal
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Progress - Task ${taskId}</h3>
                <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <label>Progress Percentage: <span id="progress-display-${taskId}">${progress}%</span></label>
                <input type="range" id="progress-slider-${taskId}" min="0" max="100" value="${progress}" class="form-slider" oninput="document.getElementById('progress-display-${taskId}').textContent = this.value + '%'; document.getElementById('progress-${taskId}').value = this.value;">
                <input type="number" id="progress-${taskId}" min="0" max="100" value="${progress}" class="form-input" oninput="document.getElementById('progress-slider-${taskId}').value = this.value; document.getElementById('progress-display-${taskId}').textContent = this.value + '%';">
                
                <div class="progress-presets">
                    <button type="button" class="preset-btn" onclick="setProgress(${taskId}, 25)">25%</button>
                    <button type="button" class="preset-btn" onclick="setProgress(${taskId}, 50)">50%</button>
                    <button type="button" class="preset-btn" onclick="setProgress(${taskId}, 75)">75%</button>
                    <button type="button" class="preset-btn" onclick="setProgress(${taskId}, 100)">100%</button>
                </div>
                
                <label>Status:</label>
                <select id="status-${taskId}" class="form-input">
                    <option value="not_started" ${status === 'not_started' ? 'selected' : ''}>Not Started</option>
                    <option value="in_progress" ${status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                    <option value="on_break" ${status === 'on_break' ? 'selected' : ''}>On Break</option>
                    <option value="completed" ${status === 'completed' ? 'selected' : ''}>Completed</option>
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn--secondary" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
                <button class="btn btn--primary" onclick="updateTaskProgress(${taskId})">Update</button>
            </div>
        </div>
    `;
    
    // Add modal styles if not exists
    if (!document.getElementById('modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'modal-styles';
        // Use individual CSS rules to avoid parsing errors
        styles.textContent = '.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10001; }' +
        '.modal-content { background: white; border-radius: 8px; width: 400px; max-width: 90vw; }' +
        '.modal-header { padding: 16px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }' +
        '.modal-body { padding: 16px; }' +
        '.modal-body label { display: block; margin-bottom: 4px; font-weight: 500; }' +
        '.modal-body .form-input { width: 100%; margin-bottom: 12px; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; }' +
        '.modal-footer { padding: 16px; border-top: 1px solid #e5e7eb; display: flex; gap: 8px; justify-content: flex-end; }' +
        '.modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; }' +
        '.form-slider { width: 100%; margin-bottom: 12px; -webkit-appearance: none; height: 6px; border-radius: 3px; background: #e5e7eb; outline: none; }' +
        '.form-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 20px; height: 20px; border-radius: 50%; background: #3b82f6; cursor: pointer; }' +
        '.form-slider::-moz-range-thumb { width: 20px; height: 20px; border-radius: 50%; background: #3b82f6; cursor: pointer; border: none; }' +
        '.progress-presets { display: flex; gap: 8px; margin-bottom: 12px; }' +
        '.preset-btn { padding: 6px 12px; border: 1px solid #d1d5db; background: #f9fafb; border-radius: 4px; cursor: pointer; font-size: 12px; }' +
        '.preset-btn:hover { background: #e5e7eb; }';
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(modal);
};

// Helper function to set progress from preset buttons
window.setProgress = function(taskId, value) {
    const progressSlider = document.getElementById(`progress-slider-${taskId}`);
    const progressInput = document.getElementById(`progress-${taskId}`);
    const progressDisplay = document.getElementById(`progress-display-${taskId}`);
    
    if (progressSlider) progressSlider.value = value;
    if (progressInput) progressInput.value = value;
    if (progressDisplay) progressDisplay.textContent = value + '%';
    
    // Auto-update status based on progress
    const statusSelect = document.getElementById(`status-${taskId}`);
    if (statusSelect) {
        if (value === 0) {
            statusSelect.value = 'not_started';
        } else if (value === 100) {
            statusSelect.value = 'completed';
        } else if (value > 0) {
            statusSelect.value = 'in_progress';
        }
    }
};

window.updateTaskProgress = function(taskId) {
    const progressInput = document.getElementById(`progress-${taskId}`);
    const statusSelect = document.getElementById(`status-${taskId}`);
    
    if (!progressInput || !statusSelect) return;
    
    const progress = parseInt(progressInput.value);
    const status = statusSelect.value;
    
    // Validate progress
    if (isNaN(progress) || progress < 0 || progress > 100) {
        showNotification('Progress must be between 0 and 100', 'error');
        return;
    }
    
    // Get the original task ID from the task card
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    const originalTaskId = taskCard?.dataset.originalTaskId || taskId;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    // Close modal
    document.querySelector('.modal-overlay')?.remove();
    
    // Send update to server using the same endpoint as Task module with original task ID
    fetch('/ergon/tasks/update-status', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ 
            task_id: parseInt(originalTaskId), 
            progress: progress,
            status: status,
            reason: 'Progress updated via daily planner'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
                taskCard.dataset.status = status;
                
                // Update progress bar
                const progressBar = taskCard.querySelector('.progress-fill');
                if (progressBar) {
                    progressBar.style.width = progress + '%';
                }
                
                // Update progress value display
                const progressValue = taskCard.querySelector('.progress-value');
                if (progressValue) {
                    progressValue.textContent = progress + '%';
                }
                
                // Update status badge
                const statusBadge = taskCard.querySelector(`#status-${taskId}`);
                if (statusBadge) {
                    statusBadge.textContent = status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    statusBadge.className = `badge badge--${status}`;
                }
                
                // If completed, update actions
                if (progress >= 100 || status === 'completed') {
                    updateTaskUI(taskId, 'completed', progress);
                    window.taskTimer.stop(taskId);
                    window.taskTimer.stopPause(taskId);
                } else {
                    updateTaskUI(taskId, status, progress);
                }
            }
            
            showNotification(`Task updated: ${progress}% - ${status}`, 'success');
        } else {
            showNotification('Failed to update progress: ' + (data.error || data.message), 'error');
        }
    })
    .catch(error => {
        showNotification('Network error: ' + error.message, 'error');
    });
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
        // Use concatenated strings to avoid CSS parsing errors
        styles.textContent = '.notification { position: fixed; top: 20px; right: 20px; padding: 12px 16px; border-radius: 4px; color: white; z-index: 10000; max-width: 300px; animation: slideIn 0.3s ease; }' +
        '.notification--success { background: #10b981; }' +
        '.notification--error { background: #ef4444; }' +
        '.notification--info { background: #3b82f6; }' +
        '.notification-content { display: flex; justify-content: space-between; align-items: center; }' +
        '.notification-close { background: none; border: none; color: white; font-size: 18px; cursor: pointer; }' +
        '@keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }';
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
    forceSLARefresh();
}

function forceSLARefresh() {
    return window.forceSLARefresh();
}

window.forceSLARefresh = function() {
    // Show loading state for SLA metrics
    const slaElements = {
        totalTime: document.querySelector('.sla-total-time'),
        usedTime: document.querySelector('.sla-used-time'),
        remainingTime: document.querySelector('.sla-remaining-time'),
        pauseTime: document.querySelector('.sla-pause-time')
    };
    
    // Set loading state
    Object.values(slaElements).forEach(element => {
        if (element) {
            element.textContent = 'Loading...';
        }
    });
    
    // Get current date from page context
    const plannerGrid = document.querySelector('.planner-grid');
    const selectedDate = plannerGrid ? plannerGrid.dataset.selectedDate : new Date().toISOString().split('T')[0];
    
    // Fetch updated SLA data
    fetch(`/ergon/api/sla_dashboard.php?date=${selectedDate}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.sla_data) {
                updateSLADashboardFromAPI(data.sla_data);
            } else {
                console.error('Failed to refresh SLA dashboard:', data.message || 'Unknown error');
                resetSLAToDefaults();
            }
        })
        .catch(error => {
            console.error('SLA refresh error:', error);
            resetSLAToDefaults();
        });
};

// Helper function to update SLA dashboard from API data
function updateSLADashboardFromAPI(slaData) {
    const slaElements = {
        totalTime: document.querySelector('.sla-total-time'),
        usedTime: document.querySelector('.sla-used-time'),
        remainingTime: document.querySelector('.sla-remaining-time'),
        pauseTime: document.querySelector('.sla-pause-time')
    };
    
    if (slaElements.totalTime) slaElements.totalTime.textContent = slaData.total_sla_time || '00:00:00';
    if (slaElements.usedTime) slaElements.usedTime.textContent = slaData.total_time_used || '00:00:00';
    if (slaElements.remainingTime) slaElements.remainingTime.textContent = slaData.total_remaining_time || '00:00:00';
    if (slaElements.pauseTime) slaElements.pauseTime.textContent = slaData.total_pause_time || '00:00:00';
}

// Helper function to reset SLA values to defaults on error
function resetSLAToDefaults() {
    const slaElements = {
        totalTime: document.querySelector('.sla-total-time'),
        usedTime: document.querySelector('.sla-used-time'),
        remainingTime: document.querySelector('.sla-remaining-time'),
        pauseTime: document.querySelector('.sla-pause-time')
    };
    
    Object.values(slaElements).forEach(element => {
        if (element && element.textContent === 'Loading...') {
            element.textContent = '00:00:00';
        }
    });
}

function updateSLADashboard(slaData) {
    // Legacy function - redirect to new API-based update
    if (slaData && typeof slaData === 'object') {
        updateSLADashboardFromAPI(slaData);
    } else {
        // Fallback to manual calculation
        updateSLADashboard();
    }
}

// Compatibility functions
function pauseTask(taskId) { return window.pauseTask(taskId); }
function resumeTask(taskId) { return window.resumeTask(taskId); }
function startTask(taskId) { return window.startTask(taskId); }
function openProgressModal(taskId, progress, status) { return window.openProgressModal(taskId, progress, status); }
function updateTaskProgress(taskId) { return window.updateTaskProgress(taskId); }
function postponeTask(taskId) { return window.postponeTask(taskId); }
function setProgress(taskId, value) { return window.setProgress(taskId, value); }