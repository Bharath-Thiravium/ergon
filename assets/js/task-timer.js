// Dedicated Task Timer System
class TaskTimer {
    constructor() {
        this.timers = new Map();
        this.pauseTimers = new Map();
        this.sessionStartTimes = new Map();
    }

    start(taskId, slaDuration, actualStartTime) {
        this.stop(taskId);
        
        const now = Math.floor(Date.now() / 1000);
        this.sessionStartTimes.set(taskId, now);
        
        const timer = setInterval(() => {
            this.updateRemainingTime(taskId, slaDuration);
        }, 1000);
        
        this.timers.set(taskId, timer);
    }

    startPause(taskId, actualPauseStartTime) {
        this.stop(taskId);
        this.stopPause(taskId);
        
        const now = Math.floor(Date.now() / 1000);
        
        const timer = setInterval(() => {
            this.updatePauseTime(taskId, now);
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

    updateRemainingTime(taskId, slaDuration) {
        const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
        if (!taskCard) return;
        
        if (taskCard.dataset.status !== 'in_progress') return;
        
        const now = Math.floor(Date.now() / 1000);
        const sessionStart = this.sessionStartTimes.get(taskId) || now;
        const elapsedInSession = now - sessionStart;
        const totalUsed = elapsedInSession;
        const remaining = Math.max(0, slaDuration - totalUsed);
        const overdue = Math.max(0, totalUsed - slaDuration);
        
        const timeUsedDisplay = document.querySelector(`#time-used-${taskId}`);
        if (timeUsedDisplay) {
            timeUsedDisplay.textContent = this.formatTime(totalUsed);
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
        
        if (taskCard.dataset.status !== 'on_break') return;
        
        const now = Math.floor(Date.now() / 1000);
        const pauseElapsed = now - pauseStartTime;
        
        const display = document.querySelector(`#pause-timer-${taskId}`);
        if (display) {
            display.textContent = this.formatTime(Math.max(0, pauseElapsed));
        }
    }

    formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
}

window.taskTimer = new TaskTimer();
