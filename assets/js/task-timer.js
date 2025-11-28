// Dedicated Task Timer System
class TaskTimer {
    constructor() {
        this.timers = new Map();
        this.pauseTimers = new Map();
    }

    start(taskId, slaDuration, actualStartTime) {
        this.stop(taskId);
        
        const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
        const serverStartTime = parseInt(taskCard?.dataset.startTime) || actualStartTime;
        
        const timer = setInterval(() => {
            this.updateRemainingTime(taskId, slaDuration, serverStartTime);
        }, 1000);
        
        this.timers.set(taskId, timer);
    }

    startPause(taskId, actualPauseStartTime) {
        this.stop(taskId); // Stop the main timer when pausing
        this.stopPause(taskId);
        
        const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
        const serverPauseStartTime = parseInt(taskCard?.dataset.pauseStartTime) ? 
            Math.floor(new Date(taskCard.dataset.pauseStartTime).getTime() / 1000) : 
            actualPauseStartTime;
        
        const timer = setInterval(() => {
            this.updatePauseTime(taskId, serverPauseStartTime);
        }, 1000);
        
        this.pauseTimers.set(taskId, timer);
    }

    stop(taskId) {
        if (this.timers.has(taskId)) {
            clearInterval(this.timers.get(taskId));
            this.timers.delete(taskId);
        }
    }

    stopPause(taskId) {
        if (this.pauseTimers.has(taskId)) {
            clearInterval(this.pauseTimers.get(taskId));
            this.pauseTimers.delete(taskId);
        }
    }

    updateRemainingTime(taskId, slaDuration, startTime) {
        const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
        if (!taskCard) return;
        
        // Only update if task is in_progress (not on break)
        if (taskCard.dataset.status !== 'in_progress') return;
        
        const now = Math.floor(Date.now() / 1000);
        const activeSeconds = parseInt(taskCard.dataset.activeSeconds) || 0;
        const currentSessionTime = now - startTime;
        const totalUsed = activeSeconds + currentSessionTime;
        
        // Apply formula: Overdue = Duration Exceeding the SLA Time
        const overdue = Math.max(0, totalUsed - slaDuration);
        // Apply formula: Time Used = Overdue + SLA Time
        const timeUsed = overdue > 0 ? overdue + slaDuration : totalUsed;
        const remaining = slaDuration - totalUsed;
        
        // Update Time Used display
        const timeUsedDisplay = document.querySelector(`#time-used-${taskId}`);
        if (timeUsedDisplay) {
            timeUsedDisplay.textContent = this.formatTime(timeUsed);
        }
        
        const display = document.querySelector(`#countdown-${taskId} .countdown-display`);
        if (display) {
            if (remaining <= 0) {
                display.textContent = 'OVERDUE: ' + this.formatTime(overdue);
                display.className = 'countdown-display countdown-display--expired';
            } else {
                display.textContent = this.formatTime(remaining);
                display.className = remaining < 300 ? 'countdown-display countdown-display--warning' : 'countdown-display';
            }
        }
    }

    updatePauseTime(taskId, pauseStartTime) {
        const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
        if (!taskCard) return;
        
        // Only update if task is on_break
        if (taskCard.dataset.status !== 'on_break') return;
        
        const now = Math.floor(Date.now() / 1000);
        const pauseDuration = parseInt(taskCard.dataset.pauseDuration) || 0;
        const currentPauseTime = pauseStartTime > 0 ? now - pauseStartTime : 0;
        const totalPauseTime = pauseDuration + currentPauseTime;
        
        const display = document.querySelector(`#pause-timer-${taskId}`);
        if (display) {
            display.textContent = this.formatTime(Math.max(0, totalPauseTime));
        }
    }

    formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
}

// Global timer instance
window.taskTimer = new TaskTimer();