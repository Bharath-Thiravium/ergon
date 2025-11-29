/**
 * SLA Dashboard Fix
 * Ensures SLA timing fields are properly populated and updated
 */

// Global function to change date and refresh SLA data
window.changeDate = function(newDate) {
    if (newDate) {
        // Update the URL to reflect the new date
        const baseUrl = window.location.pathname.replace(/\/\d{4}-\d{2}-\d{2}$/, '');
        const newUrl = baseUrl + '/' + newDate;
        window.location.href = newUrl;
    }
};

// Function to show history info modal
window.showHistoryInfo = function() {
    alert('Historical View: This shows tasks that were assigned to or completed on the selected past date. Task execution controls are disabled for historical data.');
};

// Function to show read-only progress
window.showReadOnlyProgress = function(taskId, progress) {
    alert(`Task Progress: ${progress}%\n\nThis is a historical record and cannot be modified.`);
};

// Function to show task history
window.showTaskHistory = function(taskId, taskTitle) {
    alert(`Task History for: ${taskTitle}\n\nThis feature shows the complete timeline of task actions and status changes. Historical data is read-only.`);
};

// Function to activate postponed task
window.activatePostponedTask = function(taskId) {
    if (confirm('Start this postponed task now?')) {
        window.startTask(taskId);
    }
};

// Enhanced SLA refresh with error handling
window.enhancedSLARefresh = function() {
    const slaElements = {
        totalTime: document.querySelector('.sla-total-time'),
        usedTime: document.querySelector('.sla-used-time'),
        remainingTime: document.querySelector('.sla-remaining-time'),
        pauseTime: document.querySelector('.sla-pause-time')
    };
    
    // Check if elements exist
    const elementsExist = Object.values(slaElements).some(el => el !== null);
    if (!elementsExist) {
        console.warn('SLA dashboard elements not found');
        return;
    }
    
    // Try API refresh first
    if (typeof window.forceSLARefresh === 'function') {
        try {
            window.forceSLARefresh();
        } catch (error) {
            console.error('API SLA refresh failed, falling back to manual calculation:', error);
            fallbackSLACalculation();
        }
    } else {
        fallbackSLACalculation();
    }
};

// Fallback SLA calculation using DOM data
function fallbackSLACalculation() {
    let totalSlaTime = 0;
    let totalTimeUsed = 0;
    let totalRemainingTime = 0;
    let totalPauseTime = 0;
    
    document.querySelectorAll('.task-card').forEach(taskCard => {
        const slaDuration = parseInt(taskCard.dataset.slaDuration) || 900;
        const activeSeconds = parseInt(taskCard.dataset.activeSeconds) || 0;
        const pauseDuration = parseInt(taskCard.dataset.pauseDuration) || 0;
        const status = taskCard.dataset.status || 'not_started';
        
        totalSlaTime += slaDuration;
        totalTimeUsed += activeSeconds;
        totalPauseTime += pauseDuration;
        
        // Calculate remaining time
        const remaining = Math.max(0, slaDuration - activeSeconds);
        totalRemainingTime += remaining;
    });
    
    // Update display elements
    const slaElements = {
        totalTime: document.querySelector('.sla-total-time'),
        usedTime: document.querySelector('.sla-used-time'),
        remainingTime: document.querySelector('.sla-remaining-time'),
        pauseTime: document.querySelector('.sla-pause-time')
    };
    
    function formatTime(seconds) {
        if (seconds < 0) seconds = 0;
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
    
    if (slaElements.totalTime) slaElements.totalTime.textContent = formatTime(totalSlaTime);
    if (slaElements.usedTime) slaElements.usedTime.textContent = formatTime(totalTimeUsed);
    if (slaElements.remainingTime) slaElements.remainingTime.textContent = formatTime(totalRemainingTime);
    if (slaElements.pauseTime) slaElements.pauseTime.textContent = formatTime(totalPauseTime);
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for other scripts to load
    setTimeout(() => {
        window.enhancedSLARefresh();
    }, 1000);
    
    // Set up periodic refresh
    setInterval(() => {
        window.enhancedSLARefresh();
    }, 15000); // Every 15 seconds
});

// Export functions for global access
window.fallbackSLACalculation = fallbackSLACalculation;