// Real-Time State Persistence
function persistTimerState(taskId, state) {
    const key = `timer_state_${taskId}`;
    localStorage.setItem(key, JSON.stringify({
        ...state,
        timestamp: Date.now()
    }));
}

function restoreTimerState(taskId) {
    const key = `timer_state_${taskId}`;
    const stored = localStorage.getItem(key);
    if (stored) {
        const state = JSON.parse(stored);
        if (Date.now() - state.timestamp < 300000) {
            return state;
        }
    }
    return null;
}

// Unified timestamp parsing function
function parseTimestamp(timestamp) {
    if (!timestamp) return 0;
    const date = new Date(timestamp);
    return Math.floor(date.getTime() / 1000);
}

// SLA Timer - Fixed Implementation
setInterval(() => {
    document.querySelectorAll('.task-card').forEach(card => {
        const taskId = card.dataset.taskId;
        const status = card.dataset.status;
        // Fixed DOM selector to correctly target countdown display element
        const display = document.querySelector(`#countdown-${taskId} .countdown-display`);
        
        if (display && taskId) {
            // Solution 1: Use Dynamic SLA from Data Attribute
            const slaSeconds = parseInt(card.dataset.slaDuration) || 900;
            
            // Solution 3: Add SLA Validation
            if (!card.dataset.slaDuration) {
                console.warn('Missing SLA duration for task:', taskId);
                return;
            }
            
            // Solution 4: Debug SLA Loading
            console.log(`Task ${taskId} SLA: ${slaSeconds}s (${Math.floor(slaSeconds/60)}min)`);
            
            let activeSeconds = parseInt(card.dataset.activeSeconds) || 0;
            let pauseSeconds = parseInt(card.dataset.pauseDuration) || 0;
            
            // Live calculation for in_progress status with unified timestamp parsing
            if (status === 'in_progress') {
                const startTime = card.dataset.resumeTime || card.dataset.startTime;
                if (!isValidTimestamp(startTime)) {
                    console.warn('Invalid reference time for in_progress task:', taskId);
                } else {
                    const startTimestamp = parseTimestamp(startTime);
                    const elapsed = Math.floor(Date.now() / 1000) - startTimestamp;
                    if (elapsed > 0 && elapsed < 7200) activeSeconds += elapsed;
                }
            }
            
            // Session-aware break time calculation - prevent double accumulation
            if (status === 'on_break') {
                const pauseStart = parseTimestamp(card.dataset.pauseStartTime);
                const basePause = parseInt(card.dataset.pauseDuration) || 0;
                
                if (pauseStart > 0) {
                    // Calculate session time only
                    const sessionPause = Math.floor(Date.now() / 1000) - pauseStart;
                    pauseSeconds = basePause + sessionPause;
                } else {
                    // Use static value if no valid pause start
                    pauseSeconds = basePause;
                }
            } else {
                // For non-break status, use static value only
                pauseSeconds = parseInt(card.dataset.pauseDuration) || 0;
            }
            
            let displaySeconds, color;
            if (status === 'not_started' || status === 'assigned') {
                displaySeconds = slaSeconds;
                color = '#6b7280';
            } else if (activeSeconds >= slaSeconds) {
                displaySeconds = activeSeconds - slaSeconds;
                color = '#dc2626';
            } else {
                displaySeconds = slaSeconds - activeSeconds;
                color = status === 'in_progress' ? '#059669' : '#f59e0b';
            }
            
            const h = Math.floor(displaySeconds / 3600);
            const m = Math.floor((displaySeconds % 3600) / 60);
            const s = displaySeconds % 60;
            const timeStr = `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
            
            display.textContent = timeStr;
            display.style.color = color;
            display.style.fontWeight = status === 'in_progress' ? 'bold' : 'normal';
            
            // Update break time display using live calculated pauseSeconds
            const pauseEl = document.getElementById(`pause-timer-${taskId}`);
            if (pauseEl) {
                const ph = Math.floor(pauseSeconds / 3600);
                const pm = Math.floor((pauseSeconds % 3600) / 60);
                const ps = pauseSeconds % 60;
                const pauseTimeStr = `${ph.toString().padStart(2,'0')}:${pm.toString().padStart(2,'0')}:${ps.toString().padStart(2,'0')}`;
                pauseEl.textContent = pauseTimeStr;
                pauseEl.style.color = status === 'on_break' ? '#f59e0b' : '#6b7280';
            }
            
            // Solution 1: Add Time Used calculation to JavaScript Timer
            const timeUsedEl = document.getElementById(`time-used-${taskId}`);
            if (timeUsedEl) {
                const timeUsedSeconds = activeSeconds + pauseSeconds;
                const th = Math.floor(timeUsedSeconds / 3600);
                const tm = Math.floor((timeUsedSeconds % 3600) / 60);
                const ts = timeUsedSeconds % 60;
                const timeUsedStr = `${th.toString().padStart(2,'0')}:${tm.toString().padStart(2,'0')}:${ts.toString().padStart(2,'0')}`;
                timeUsedEl.textContent = timeUsedStr;
                
                // Solution 5: Add Time Used debug logging
                console.log(`Task ${taskId} Time Used: ${timeUsedSeconds}s (Active: ${activeSeconds}s + Break: ${pauseSeconds}s)`);
            }
        } else {
            console.warn('Timer elements not found for task:', taskId);
        }
    });
}, 1000);

// Enhanced timestamp validation
function isValidTimestamp(timestamp) {
    if (!timestamp || timestamp.includes('1970') || timestamp === '0000-00-00 00:00:00' || timestamp === 'null') return false;
    const date = new Date(timestamp);
    const year = date.getFullYear();
    return !isNaN(date.getTime()) && year >= 2020 && year <= 2030 && timestamp.length > 10;
}

// Enhanced syncTimestamps with persistence
function syncTimestamps(taskId, apiData) {
    const card = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!card) return;
    
    // Update card data
    if (apiData.resume_time) card.dataset.resumeTime = apiData.resume_time;
    if (apiData.pause_start_time) card.dataset.pauseStartTime = apiData.pause_start_time;
    if (apiData.start_time && !card.dataset.startTime) card.dataset.startTime = apiData.start_time;
    if (apiData.active_seconds !== undefined) card.dataset.activeSeconds = apiData.active_seconds;
    if (apiData.pause_duration !== undefined) card.dataset.pauseDuration = apiData.pause_duration;
    
    // Solution 5: Persist SLA in localStorage
    persistTimerState(taskId, {
        status: apiData.status,
        slaDuration: card.dataset.slaDuration,
        resumeTime: apiData.resume_time,
        pauseStartTime: apiData.pause_start_time,
        activeSeconds: apiData.active_seconds,
        pauseDuration: apiData.pause_duration
    });
    
    console.log('syncTimestamps updated and persisted:', taskId, apiData);
}

// UI update functions - Fixed to include complete button sets
function updateTaskUI(taskId, status) {
    const statusBadge = document.querySelector(`#status-${taskId}`);
    const actionsDiv = document.querySelector(`#actions-${taskId}`);
    const card = document.querySelector(`[data-task-id="${taskId}"]`);
    
    if (statusBadge) {
        statusBadge.textContent = status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        statusBadge.className = `badge badge--${status}`;
    }
    
    if (actionsDiv && card) {
        // Solution 3: Get current progress from task card data
        const currentProgress = parseInt(card.dataset.completedPercentage) || 0;
        
        if (status === 'in_progress') {
            // Solution 1: Complete Button Set Generation for in_progress
            actionsDiv.innerHTML = `
                <button type="button" onclick="pauseTask(${taskId}, event)" class="btn btn--sm btn--warning"><i class="bi bi-pause"></i> Break</button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, ${currentProgress}, 'in_progress')" title="Update task completion progress"><i class="bi bi-percent"></i> Update Progress</button>
                <button type="button" class="btn btn--sm btn--secondary" onclick="postponeTask(${taskId}, event)" title="Postpone task to another date"><i class="bi bi-calendar-plus"></i> Postpone</button>
            `;
        } else if (status === 'on_break') {
            // Solution 2: Complete Button Set Generation for on_break
            actionsDiv.innerHTML = `
                <button type="button" onclick="resumeTask(${taskId}, event)" class="btn btn--sm btn--success"><i class="bi bi-play"></i> Resume</button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, ${currentProgress}, 'on_break')" title="Update task completion progress"><i class="bi bi-percent"></i> Update Progress</button>
                <button type="button" class="btn btn--sm btn--secondary" onclick="postponeTask(${taskId}, event)" title="Postpone task to another date"><i class="bi bi-calendar-plus"></i> Postpone</button>
            `;
        }
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification--${type}`;
    notification.style.cssText = `position: fixed; top: 20px; right: 20px; padding: 12px 16px; border-radius: 4px; color: white; z-index: 10000; background: ${type === 'success' ? '#10b981' : '#ef4444'};`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

// Fixed button functions
window.startTask = function(taskId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const button = event?.target;
    if (button) button.disabled = true;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('/ergon/api/daily_planner_workflow.php?action=start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId), csrf_token: csrfToken })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Start task response:', data);
        if (data.success) {
            syncTimestamps(taskId, data);
            const card = document.querySelector(`[data-task-id="${taskId}"]`);
            if (card) {
                card.dataset.status = 'in_progress';
                card.dataset.startTime = data.start_time;
                console.log('Task started - updated card data:', card.dataset);
            }
            updateTaskUI(taskId, 'in_progress');
            showNotification('Task started successfully', 'success');
        } else {
            showNotification('Failed to start task', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Action failed: ' + error.message, 'error');
    })
    .finally(() => {
        if (button) button.disabled = false;
    });
    
    return false;
};

window.pauseTask = function(taskId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Add request validation
    if (!taskId || isNaN(parseInt(taskId))) {
        showNotification('Invalid task ID', 'error');
        return false;
    }
    
    const card = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!card || card.dataset.status !== 'in_progress') {
        showNotification('Task must be in progress to pause', 'error');
        return false;
    }
    
    const button = event?.target;
    if (button) button.disabled = true;
    
    // Fix CSRF token handling
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        console.error('CSRF token not found');
        showNotification('Security token missing. Please refresh the page.', 'error');
        if (button) button.disabled = false;
        return false;
    }
    
    fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId), csrf_token: csrfToken })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Pause task response:', data);
        if (data.success) {
            syncTimestamps(taskId, data);
            const card = document.querySelector(`[data-task-id="${taskId}"]`);
            if (card) {
                card.dataset.status = 'on_break';
                card.dataset.pauseStartTime = data.pause_start_time;
                console.log('Task paused - updated card data:', card.dataset);
            }
            updateTaskUI(taskId, 'on_break');
            showNotification('Task paused - break started', 'success');
        } else {
            showNotification('Failed to pause task: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Pause Error Details:', error);
        if (error.message.includes('400')) {
            showNotification('Task cannot be paused. Check task status.', 'error');
        } else {
            showNotification('Action failed: ' + error.message, 'error');
        }
    })
    .finally(() => {
        if (button) button.disabled = false;
    });
    
    return false;
};

window.resumeTask = function(taskId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const button = event?.target;
    if (button) button.disabled = true;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId), csrf_token: csrfToken })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Resume task response:', data);
        if (data.success) {
            syncTimestamps(taskId, data);
            const card = document.querySelector(`[data-task-id="${taskId}"]`);
            if (card) {
                card.dataset.status = 'in_progress';
                card.dataset.resumeTime = data.resume_time;
                console.log('Task resumed - updated card data:', card.dataset);
            }
            updateTaskUI(taskId, 'in_progress');
            showNotification('Task resumed successfully', 'success');
        } else {
            showNotification('Failed to resume task', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Action failed: ' + error.message, 'error');
    })
    .finally(() => {
        if (button) button.disabled = false;
    });
    
    return false;
};

window.postponeTask = function(taskId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const newDate = prompt('Enter new date (YYYY-MM-DD):');
    if (!newDate) return false;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('/ergon/api/daily_planner_workflow.php?action=postpone', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId), new_date: newDate, csrf_token: csrfToken })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Task postponed successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Failed to postpone task', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Action failed: ' + error.message, 'error');
    });
    
    return false;
};

// Missing global functions that PHP expects
window.forceSLARefresh = function() {
    console.log('Manual SLA refresh triggered');
    location.reload();
};

window.cancelPostpone = function() {
    document.getElementById('postponeForm').style.display = 'none';
    document.getElementById('postponeOverlay').style.display = 'none';
};

window.submitPostpone = function() {
    const taskId = document.getElementById('postponeTaskId').value;
    const newDate = document.getElementById('newDate').value;
    
    if (!newDate) {
        alert('Please select a date');
        return;
    }
    
    window.postponeTask(taskId);
    window.cancelPostpone();
};

// State recovery for page refresh
function recoverTimerState() {
    document.querySelectorAll('.task-card').forEach(card => {
        const status = card.dataset.status;
        const taskId = card.dataset.taskId;
        
        if (status === 'in_progress') {
            if (!isValidTimestamp(card.dataset.resumeTime) && !isValidTimestamp(card.dataset.startTime)) {
                console.warn('No valid reference time for in_progress task:', taskId);
            }
        } else if (status === 'on_break') {
            if (!isValidTimestamp(card.dataset.pauseStartTime)) {
                console.warn('No valid pause time for on_break task:', taskId);
            }
        }
    });
}

// Unified State Management - Override PHP with live session data
document.addEventListener('DOMContentLoaded', function() {
    console.log('Timer system initialized with state recovery');
    
    document.querySelectorAll('.task-card').forEach(card => {
        const taskId = card.dataset.taskId;
        const display = document.querySelector(`#countdown-${taskId} .countdown-display`);
        
        // Restore live state from localStorage
        const liveState = restoreTimerState(taskId);
        if (liveState) {
            console.log(`Restoring live state for task ${taskId}:`, liveState);
            // Override PHP data with live session data
            if (liveState.resumeTime) card.dataset.resumeTime = liveState.resumeTime;
            if (liveState.pauseStartTime) card.dataset.pauseStartTime = liveState.pauseStartTime;
            if (liveState.activeSeconds !== undefined) card.dataset.activeSeconds = liveState.activeSeconds;
            if (liveState.pauseDuration !== undefined) card.dataset.pauseDuration = liveState.pauseDuration;
            if (liveState.status) card.dataset.status = liveState.status;
        }
        
        if (!display) {
            console.warn('Timer elements not found for task:', taskId);
        }
        
        console.log(`Task ${taskId} final state:`, {
            status: card.dataset.status,
            startTime: card.dataset.startTime,
            resumeTime: card.dataset.resumeTime,
            pauseStartTime: card.dataset.pauseStartTime,
            activeSeconds: card.dataset.activeSeconds,
            pauseDuration: card.dataset.pauseDuration,
            displayElement: !!display,
            liveStateRestored: !!liveState
        });
        
        const numericAttrs = ['activeSeconds', 'pauseDuration', 'slaDuration'];
        numericAttrs.forEach(attr => {
            const value = parseInt(card.dataset[attr]) || 0;
            card.dataset[attr] = value.toString();
        });
    });
    
    recoverTimerState();
});

console.log('SLA Timer loaded with all fixes');
