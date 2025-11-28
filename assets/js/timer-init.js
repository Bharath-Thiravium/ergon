// Initialize timers on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.task-card').forEach(taskCard => {
        const taskId = taskCard.dataset.taskId;
        const status = taskCard.dataset.status;
        
        // Ensure mutual exclusivity - only one timer per task
        window.taskTimer.stop(taskId);
        window.taskTimer.stopPause(taskId);
        
        if (status === 'in_progress') {
            const slaDuration = parseInt(taskCard.dataset.slaDuration) || 900;
            const startTime = parseInt(taskCard.dataset.startTime) || Math.floor(Date.now() / 1000);
            window.taskTimer.start(taskId, slaDuration, startTime);
        } else if (status === 'on_break') {
            const pauseStartTime = taskCard.dataset.pauseStartTime ? 
                Math.floor(new Date(taskCard.dataset.pauseStartTime).getTime() / 1000) : 
                Math.floor(Date.now() / 1000);
            window.taskTimer.startPause(taskId, pauseStartTime);
        }
    });
    
    // Initialize SLA Dashboard with API data
    if (typeof window.forceSLARefresh === 'function') {
        window.forceSLARefresh();
    } else {
        // Fallback to manual calculation
        updateSLADashboard();
    }
    
    // Update SLA Dashboard every 30 seconds (reduced frequency to prevent rate limiting)
    setInterval(() => {
        if (typeof window.forceSLARefresh === 'function') {
            window.forceSLARefresh();
        } else {
            updateSLADashboard();
        }
    }, 30000);
});

// Update SLA Dashboard totals
function updateSLADashboard() {
    let totalSLATime = 0;
    let totalTimeUsed = 0;
    let totalRemainingTime = 0;
    let totalPauseTime = 0;
    
    const taskCards = document.querySelectorAll('.task-card');
    
    if (taskCards.length === 0) {
        // No tasks found, set all values to 0
        updateDashboardDisplay(0, 0, 0, 0);
        return;
    }
    
    taskCards.forEach(taskCard => {
        const taskId = taskCard.dataset.taskId;
        const status = taskCard.dataset.status || 'not_started';
        const slaDuration = parseInt(taskCard.dataset.slaDuration) || 900; // Default 15 minutes
        const activeSeconds = parseInt(taskCard.dataset.activeSeconds) || 0;
        const pauseDuration = parseInt(taskCard.dataset.pauseDuration) || 0;
        
        // Parse start time safely
        let startTime = 0;
        const startTimeStr = taskCard.dataset.startTime;
        if (startTimeStr && startTimeStr !== '0' && startTimeStr !== '') {
            startTime = parseInt(startTimeStr);
            // Validate timestamp (should be after year 2000)
            if (isNaN(startTime) || startTime < 946684800) {
                startTime = 0;
            }
        }
        
        totalSLATime += slaDuration;
        
        let currentActiveTime = activeSeconds;
        let currentPauseTime = pauseDuration;
        
        // Calculate current session time for active tasks
        if (status === 'in_progress' && startTime > 0) {
            const now = Math.floor(Date.now() / 1000);
            const sessionTime = now - startTime;
            if (sessionTime > 0 && sessionTime < 86400) { // Sanity check: less than 24 hours
                currentActiveTime += sessionTime;
            }
        }
        
        // Calculate current pause time for paused tasks
        if (status === 'on_break') {
            const pauseStartTimeStr = taskCard.dataset.pauseStartTime;
            if (pauseStartTimeStr && pauseStartTimeStr !== '') {
                try {
                    const pauseStartTime = Math.floor(new Date(pauseStartTimeStr).getTime() / 1000);
                    if (pauseStartTime > 946684800) { // Valid timestamp after year 2000
                        const now = Math.floor(Date.now() / 1000);
                        const currentPauseSession = now - pauseStartTime;
                        if (currentPauseSession > 0 && currentPauseSession < 86400) { // Sanity check
                            currentPauseTime += currentPauseSession;
                        }
                    }
                } catch (e) {
                    console.warn('Invalid pause start time for task', taskId, ':', pauseStartTimeStr);
                }
            }
        }
        
        totalTimeUsed += Math.max(0, currentActiveTime);
        totalPauseTime += Math.max(0, currentPauseTime);
        totalRemainingTime += Math.max(0, slaDuration - currentActiveTime);
    });
    
    updateDashboardDisplay(totalSLATime, totalTimeUsed, totalRemainingTime, totalPauseTime);
}

// Helper function to update dashboard display elements
function updateDashboardDisplay(slaTime, usedTime, remainingTime, pauseTime) {
    const slaTimeElement = document.querySelector('.sla-total-time');
    if (slaTimeElement) {
        slaTimeElement.textContent = formatTime(slaTime);
    }
    
    const usedTimeElement = document.querySelector('.sla-used-time');
    if (usedTimeElement) {
        usedTimeElement.textContent = formatTime(usedTime);
    }
    
    const remainingTimeElement = document.querySelector('.sla-remaining-time');
    if (remainingTimeElement) {
        remainingTimeElement.textContent = formatTime(remainingTime);
    }
    
    const pauseTimeElement = document.querySelector('.sla-pause-time');
    if (pauseTimeElement) {
        pauseTimeElement.textContent = formatTime(pauseTime);
    }
}

// Format time helper function
function formatTime(seconds) {
    // Handle invalid, null, undefined, or negative values
    if (seconds === null || seconds === undefined || isNaN(seconds) || seconds < 0) {
        return '00:00:00';
    }
    
    const totalSeconds = Math.floor(Math.abs(seconds));
    const h = Math.floor(totalSeconds / 3600);
    const m = Math.floor((totalSeconds % 3600) / 60);
    const s = totalSeconds % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

// Manual refresh function - will be overridden by unified-daily-planner.js if available
if (typeof window.forceSLARefresh !== 'function') {
    window.forceSLARefresh = function() {
        updateSLADashboard();
    };
}