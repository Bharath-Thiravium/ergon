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
    
    // Initialize SLA Dashboard
    updateSLADashboard();
    
    // Update SLA Dashboard every 5 seconds
    setInterval(updateSLADashboard, 5000);
});

// Update SLA Dashboard totals
function updateSLADashboard() {
    let totalSLATime = 0;
    let totalTimeUsed = 0;
    let totalRemainingTime = 0;
    let totalPauseTime = 0;
    
    document.querySelectorAll('.task-card').forEach(taskCard => {
        const taskId = taskCard.dataset.taskId;
        const status = taskCard.dataset.status;
        const slaDuration = parseInt(taskCard.dataset.slaDuration) || 900;
        const activeSeconds = parseInt(taskCard.dataset.activeSeconds) || 0;
        const pauseDuration = parseInt(taskCard.dataset.pauseDuration) || 0;
        const startTime = parseInt(taskCard.dataset.startTime) || 0;
        
        totalSLATime += slaDuration;
        
        let currentActiveTime = activeSeconds;
        let currentPauseTime = pauseDuration;
        
        // Calculate current session time for active tasks
        if (status === 'in_progress' && startTime > 0) {
            const now = Math.floor(Date.now() / 1000);
            currentActiveTime += (now - startTime);
        }
        
        // Calculate current pause time for paused tasks
        if (status === 'on_break') {
            const pauseStartTime = taskCard.dataset.pauseStartTime ? 
                Math.floor(new Date(taskCard.dataset.pauseStartTime).getTime() / 1000) : 0;
            if (pauseStartTime > 0) {
                const now = Math.floor(Date.now() / 1000);
                currentPauseTime += (now - pauseStartTime);
            }
        }
        
        totalTimeUsed += currentActiveTime;
        totalPauseTime += currentPauseTime;
        totalRemainingTime += Math.max(0, slaDuration - currentActiveTime);
    });
    
    // Update dashboard displays
    const slaTimeElement = document.querySelector('.sla-total-time');
    if (slaTimeElement) {
        slaTimeElement.textContent = formatTime(totalSLATime);
    }
    
    const usedTimeElement = document.querySelector('.sla-used-time');
    if (usedTimeElement) {
        usedTimeElement.textContent = formatTime(totalTimeUsed);
    }
    
    const remainingTimeElement = document.querySelector('.sla-remaining-time');
    if (remainingTimeElement) {
        remainingTimeElement.textContent = formatTime(totalRemainingTime);
    }
    
    const pauseTimeElement = document.querySelector('.sla-pause-time');
    if (pauseTimeElement) {
        pauseTimeElement.textContent = formatTime(totalPauseTime);
    }
}

// Format time helper function
function formatTime(seconds) {
    // Handle invalid or zero values
    if (!seconds || isNaN(seconds) || seconds < 0) {
        return '00:00:00';
    }
    
    const totalSeconds = Math.floor(seconds);
    const h = Math.floor(totalSeconds / 3600);
    const m = Math.floor((totalSeconds % 3600) / 60);
    const s = totalSeconds % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

// Manual refresh function
window.forceSLARefresh = function() {
    updateSLADashboard();
};